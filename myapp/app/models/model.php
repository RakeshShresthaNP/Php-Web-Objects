<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

class model
{

    protected ?PDO $db = null;

    private array $_rs = [];

    private array $_where = [];

    private array $_bindings = [];

    private array $_joins = [];

    private string $_order = '';

    private string $_limit = '';

    private string $_groupBy = '';

    private array $_having = [];

    private array $_havingBindings = [];

    private string $selectedFields = '*';

    private bool $_ignoreSoftDelete = false;

    protected bool $timestamps = true;

    protected bool $softDelete = false;

    protected string $createdAtColumn = 'd_created';

    protected string $updatedAtColumn = 'd_updated';

    protected string $deletedAtColumn = 'd_deleted';

    protected array $casts = [];

    public function __construct(protected string $table, protected string $pk = 'id')
    {
        // Assumes a global or accessible db() function returning a PDO instance
        $this->db = db();
    }

    public function assign(array &$arr): void
    {
        foreach ($arr as $key => &$val) {
            if (isset($this->casts[$key]) && $this->casts[$key] === 'json' && is_string($val)) {
                $decoded = json_decode($val, true);

                // Only assign the decoded array if it's actually valid JSON
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->_rs[$key] = $decoded;
                } else {
                    // If invalid, keep the original string so data isn't lost
                    $this->_rs[$key] = $val;
                }
            } else {
                $this->_rs[$key] = $val;
            }
        }
    }

    public function __get(string $key): mixed
    {
        return $this->_rs[$key] ?? null;
    }

    public function __set(string $key, mixed $val): void
    {
        $this->_rs[$key] = $val;
    }

    public function getData(): object
    {
        return (object) $this->_rs;
    }

    // --- Query Builder ---
    public function select(string $fields = '*'): self
    {
        $this->selectedFields = $fields;
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->selectedFields = $expression;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null && $operator !== null)
            [
                $value,
                $operator
            ] = [
                $operator,
                '='
            ];
        $prefix = empty($this->_where) ? "" : "AND ";
        $this->_where[] = "{$prefix}{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $this->_where[] = "{$prefix}({$sql})";
        $this->_bindings = array_merge($this->_bindings, $bindings);
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null)
            [
                $value,
                $operator
            ] = [
                $operator,
                '='
            ];
        $prefix = empty($this->_where) ? "" : " OR ";
        $this->_where[] = "{$prefix}{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    /**
     * Perform a MySQL Full-Text search.
     * Note: Requires a FULLTEXT index on the specified columns.
     */
    public function whereMatch(array|string $columns, string $term, string $boolean = 'AND'): self
    {
        $cols = is_array($columns) ? implode(',', $columns) : $columns;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";

        $this->_where[] = "{$prefix}MATCH({$cols}) AGAINST(? IN NATURAL LANGUAGE MODE)";
        $this->_bindings[] = $term;

        return $this;
    }

    /**
     * Perform a Boolean Mode Full-Text search for more complex operators (+, -, *).
     */
    public function whereMatchBoolean(array|string $columns, string $term, string $boolean = 'AND'): self
    {
        $cols = is_array($columns) ? implode(',', $columns) : $columns;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";

        $this->_where[] = "{$prefix}MATCH({$cols}) AGAINST(? IN BOOLEAN MODE)";
        $this->_bindings[] = $term;

        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $this->_where[] = "{$prefix}{$column} IS NULL";
        return $this;
    }

    public function whereIn(string $column, array &$values, string $boolean = ' AND'): self
    {
        if (empty($values))
            return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->_where[] = "{$prefix}{$column} IN ({$placeholders})";
        $this->_bindings = array_merge($this->_bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, array &$values, string $boolean = ' AND', bool $not = false): self
    {
        if (count($values) !== 2)
            return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $type = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $this->_where[] = "{$prefix}{$column} {$type} ? AND ?";
        $this->_bindings[] = $values[0];
        $this->_bindings[] = $values[1];
        return $this;
    }

    public function whereExists(string $table, callable $callback, string $boolean = 'AND', bool $not = false): self
    {
        // 1. Create a fresh instance for the subquery targeting the specific table
        $sub = new self($table);
        $callback($sub);

        // 2. Build the "SELECT 1" part of the EXISTS clause
        // We use "sub" alias inside to avoid collision with parent aliases
        $subSql = $sub->buildSelectSql("1", "sub");
        $type = $not ? 'NOT EXISTS' : 'EXISTS';

        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $this->_where[] = "{$prefix}{$type} ({$subSql})";
        $this->_bindings = array_merge($this->_bindings, $sub->_bindings);

        return $this;
    }

    public function whereGroup(callable $callback): self
    {
        $nested = new self($this->table, $this->pk);
        $callback($nested);
        if (! empty($nested->_where)) {
            $prefix = empty($this->_where) ? "" : "AND ";
            $this->_where[] = "{$prefix}(" . implode(' ', $nested->_where) . ")";
            $this->_bindings = array_merge($this->_bindings, $nested->_bindings);
        }
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $prefix = empty($this->_having) ? "" : "AND ";
        $this->_having[] = "{$prefix}{$column} {$operator} ?";
        $this->_havingBindings[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->_joins[] = " {$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->_groupBy = " GROUP BY " . implode(', ', $columns);
        return $this;
    }

    public function orderBy(string|array $columns, string $direction = 'ASC'): self
    {
        $orders = [];

        // 1. Handle comma-separated strings (REST style: "-price,name")
        if (is_string($columns) && str_contains($columns, ',')) {
            $columns = explode(',', $columns);
        }

        // 2. Normalize to array
        $columns = (array) $columns;

        foreach ($columns as $col) {
            $col = trim($col);
            $currentDir = strtoupper($direction);

            // 3. Handle minus prefix for DESC (e.g., "-price")
            if (str_starts_with($col, '-')) {
                $currentDir = 'DESC';
                $col = ltrim($col, '-');
            }

            // 4. Basic Security: Only allow alphanumeric and underscores (prevents SQL injection)
            // Note: If using JSON sorting, you'd allow '->' or '$'
            if (preg_match('/^[a-zA-Z0-9_\.]+$/', $col)) {
                $orders[] = "{$col} {$currentDir}";
            }
        }

        if (! empty($orders)) {
            // Append to existing orders or set new
            $prefix = empty($this->_order) ? " ORDER BY " : "{$this->_order}, ";
            $this->_order = $prefix . implode(', ', $orders);
        }

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->_limit = " LIMIT {$offset}, {$limit}";
        return $this;
    }

    // --- Execution ---
    public function find(): array
    {
        $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();

        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        if (! $results)
            return []; // Always return empty array instead of null

        return $results;
    }

    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->find();

        if ($results) {
            $arr = (array) $results[0] ?? [];
            $this->assign($arr);
        }

        return $results[0] ?? null; // Returns one object or null
    }

    public function paginate(int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $this->limit($perPage, (max(1, $page) - 1) * $perPage);
        $data = $this->find();
        return (object) [
            'items' => $data,
            'meta' => (object) [
                'total_records' => $total,
                'total_pages' => (int) ceil($total / $perPage),
                'current_page' => $page,
                'per_page' => $perPage
            ]
        ];
    }

    public function count(): int
    {
        $stmt = $this->db->prepare($this->buildSelectSql("COUNT(*)"));
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        return (int) $stmt->fetchColumn();
    }

    // chunk methods
    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;
        while (true) {
            $clone = clone $this;
            $results = $clone->limit($count, ($page - 1) * $count)->find();

            if (empty($results))
                break;

            // $results is now guaranteed to be an array
            if ($callback($results, $page) === false)
                return false;

            $page ++;
            unset($results, $clone);
        }
        return true;
    }

    public function chunkById(int $count, callable $callback, ?string $alias = 'p'): bool
    {
        $lastId = null;
        while (true) {
            $clone = clone $this;
            if ($lastId !== null) {
                $clone->where("{$alias}.{$this->pk}", '>', $lastId);
            }

            // find() now always returns an array
            $results = $clone->orderBy("{$alias}.{$this->pk}", 'ASC')
                ->limit($count)
                ->find();

            if (empty($results)) {
                break;
            }

            if ($callback($results) === false) {
                return false;
            }

            // Get the PK from the last object in the array
            $lastEntry = end($results);
            $lastId = $lastEntry->{$this->pk};

            unset($results, $clone);
        }
        return true;
    }

    protected function displayProgress(int $current, int $total, int $startTime): void
    {
        if (PHP_SAPI !== 'cli')
            return;

        $percent = min(100, ($current / $total) * 100);
        $barWidth = 40;
        $done = (int) ($percent / (100 / $barWidth));
        $left = $barWidth - $done;

        $elapsed = time() - $startTime;
        $memory = round(memory_get_usage() / 1024 / 1024, 2);

        printf("\r[%s%s] %3d%% | %d/%d | %ds | Memory: %sMB", str_repeat("=", $done), str_repeat(" ", $left), $percent, $current, $total, $elapsed, $memory);

        if ($current === $total)
            echo PHP_EOL;
    }

    // --- Graph Engine ---
    public function findGraph(array &$schema, string $alias = 'p'): mixed
    {
        // Use the same alias for the JSON structure AND the SELECT query
        $jsonExpr = $this->parseGraphSchema($schema, $alias);

        // Pass $alias to buildSelectSql
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", $alias) . " " . $this->_order . $this->_limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();

        $result = $stmt->fetchColumn();
        return $result ? json_decode($result, false) : null;
    }

    public function paginateGraph(array &$schema, int $page = 1, int $perPage = 15): object
    {
        // 1. Get the total count for metadata
        $total = $this->count();

        // 2. Set the limits for the current page
        $this->limit($perPage, (max(1, $page) - 1) * $perPage);

        // 3. Build the complex JSON object query
        $jsonExpr = $this->parseGraphSchema($schema, 'p');
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", 'p') . $this->_order . $this->_limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();

        // 4. Fetch results (each row is a JSON string from MySQL)
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return (object) [
            'items' => array_map(fn ($json) => json_decode($json), $rows),
            'meta' => (object) [
                'total_records' => $total,
                'total_pages' => (int) ceil($total / $perPage),
                'current_page' => $page,
                'per_page' => $perPage
            ]
        ];
    }

    public function withAnalytics(string $alias, string $rawSql, string $foreignKey, ?string $localKey = null, string $primaryAlias = 'p'): self
    {
        $localKey = $localKey ?? $this->pk;
        // We use the $primaryAlias parameter to match whatever you pass to findGraph
        $this->join("({$rawSql}) {$alias}", "{$alias}.{$foreignKey}", "=", "{$primaryAlias}.{$localKey}", "LEFT");
        return $this;
    }

    /**
     * Generates the complex JSON_OBJECT SQL string for hierarchical data.
     * Handles Deep Nesting, Aggregates with filters, and Parent-Link lookups.
     */
    private function parseGraphSchema(array &$schema, string $alias): string
    {
        $parts = [];
        $metaKeys = [
            'table',
            'alias',
            'joins',
            'group',
            'order',
            'limit',
            'where',
            'foreign_key',
            'link_to_parent',
            'type',
            'column',
            'fields',
            'select',
            'partition_by',
            'order_by',
            'offset',
            'default',
            'rows'
        ];

        foreach ($schema as $key => $val) {
            if (in_array($key, $metaKeys, true))
                continue;

            $currentKey = "'{$key}'";
            $currentVal = "NULL";

            if (is_array($val)) {
                $subAlias = $alias . "_" . $key;
                $type = $val['type'] ?? null;

                if (in_array($type, [
                    'count',
                    'sum',
                    'avg',
                    'min',
                    'max'
                ])) {
                    $func = strtoupper($type);
                    $targetCol = ($func === 'COUNT') ? '*' : (str_contains($val['column'] ?? '', '.') ? $val['column'] : "{$subAlias}." . ($val['column'] ?? $this->pk));
                    $extraFilter = isset($val['where']) ? " AND (" . (is_array($val['where']) ? implode(' AND ', $val['where']) : $val['where']) . ")" : "";
                    $currentVal = "(SELECT COALESCE({$func}({$targetCol}),0) FROM {$val['table']} {$subAlias} WHERE {$subAlias}.{$val['foreign_key']} = {$alias}.{$this->pk}{$extraFilter})";
                } elseif (isset($val['fields'])) {
                    $subFields = $this->parseGraphSchema($val['fields'], $subAlias);
                    if (isset($val['link_to_parent']) && $val['link_to_parent'] === true) {
                        $currentVal = "(SELECT {$subFields} FROM {$val['table']} {$subAlias} WHERE {$subAlias}.{$this->pk} = {$alias}.{$val['foreign_key']} LIMIT 1)";
                    } else {
                        $softFilter = ($this->softDelete) ? " AND {$subAlias}.{$this->deletedAtColumn} IS NULL" : "";
                        $currentVal = "COALESCE((SELECT JSON_ARRAYAGG({$subFields}) FROM {$val['table']} {$subAlias} WHERE {$subAlias}.{$val['foreign_key']} = {$alias}.{$this->pk}{$softFilter}), JSON_ARRAY())";
                    }
                }
            } else {
                $currentVal = (str_contains((string) $val, '(') || str_contains((string) $val, '.')) ? $val : "{$alias}.{$val}";
            }

            $parts[] = $currentKey;
            $parts[] = (trim((string) $currentVal) === "") ? "NULL" : $currentVal;
        }
        return "JSON_OBJECT(" . implode(',', $parts) . ")";
    }

    // --- Persistence & Transactions ---
    public function save(): bool|string
    {
        return isset($this->_rs[$this->pk]) ? (string) $this->update() : $this->insert();
    }

    public function insert(): string|false
    {
        if ($this->timestamps) {
            $this->_rs[$this->createdAtColumn] = $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        $cols = array_keys($this->_rs);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";

        // Process the internal data through mapValues here
        $values = $this->mapValues($this->_rs);

        return $this->db->prepare($sql)->execute($values) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (! isset($this->_rs[$this->pk]))
            return false;

        if ($this->timestamps) {
            $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);

        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";

        // Process data for database storage
        $values = $this->mapValues($data);
        $values[] = $id;

        return $this->db->prepare($sql)->execute($values);
    }

    public function updateWhere(array $data): bool
    {
        if (empty($this->_where))
            return false;
        if ($this->timestamps)
            $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE " . implode(' ', $this->_where);
        $values = array_merge($this->mapValues($data), $this->_bindings);
        $result = $this->db->prepare($sql)->execute($values);
        $this->resetQuery();
        return $result;
    }

    public function delete(): bool
    {
        if (! isset($this->_rs[$this->pk]))
            return false;
        if ($this->softDelete) {
            $this->_rs[$this->deletedAtColumn] = date('Y-m-d H:i:s');
            $sql = "UPDATE {$this->table} SET {$this->deletedAtColumn} = ? WHERE {$this->pk} = ?";
            return $this->db->prepare($sql)->execute([
                $this->_rs[$this->deletedAtColumn],
                $this->_rs[$this->pk]
            ]);
        }
        $sql = "DELETE FROM {$this->table} WHERE {$this->pk} = ?";
        return $this->db->prepare($sql)->execute([
            $this->_rs[$this->pk]
        ]);
    }

    public function deleteWhere(): bool
    {
        if (empty($this->_where))
            return false;
        if ($this->softDelete) {
            $sql = "UPDATE {$this->table} SET {$this->deletedAtColumn} = ? WHERE " . implode(' ', $this->_where);
            $values = array_merge([
                date('Y-m-d H:i:s')
            ], $this->_bindings);
        } else {
            $sql = "DELETE FROM {$this->table} WHERE " . implode(' ', $this->_where);
            $values = $this->_bindings;
        }
        $result = $this->db->prepare($sql)->execute($values);
        $this->resetQuery();
        return $result;
    }

    public function deleteById(mixed $id): bool
    {
        return $this->where($this->pk, $id)->deleteWhere();
    }

    public function restore(): bool
    {
        if (! $this->softDelete || ! isset($this->_rs[$this->pk]))
            return false;
        $sql = "UPDATE {$this->table} SET {$this->deletedAtColumn} = NULL WHERE {$this->pk} = ?";
        return $this->db->prepare($sql)->execute([
            $this->_rs[$this->pk]
        ]);
    }

    public function transaction(callable $callback): mixed
    {
        try {
            $this->db->beginTransaction();
            $result = $callback($this);
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Helpers & Debugging ---
    public function toSql(string $type = 'select', array &$data = []): string
    {
        $sql = '';
        $bindings = $this->_bindings;

        if ($type === 'select') {
            $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
            $bindings = array_merge($this->_bindings, $this->_havingBindings);
        } elseif ($type === 'update') {
            if ($this->timestamps)
                $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
            $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields);
            if (! empty($this->_where))
                $sql .= " WHERE " . implode(' ', $this->_where);
            $bindings = array_merge($this->mapValues($data), $this->_bindings);
        } elseif ($type === 'delete') {
            if ($this->softDelete) {
                $sql = "UPDATE {$this->table} SET {$this->deletedAtColumn} = ? WHERE " . implode(' ', $this->_where);
                $bindings = array_merge([
                    date('Y-m-d H:i:s')
                ], $this->_bindings);
            } else {
                $sql = "DELETE FROM {$this->table} WHERE " . implode(' ', $this->_where);
            }
        }

        foreach ($bindings as $val) {
            $escaped = is_null($val) ? 'NULL' : (is_numeric($val) ? $val : "'" . addslashes((string) $val) . "'");
            $sql = preg_replace('/\?/', (string) $escaped, $sql, 1);
        }
        return $sql . ";";
    }

    public function toGraphSql(array &$schema, string $alias = 'p'): string
    {
        $jsonExpr = $this->parseGraphSchema($schema, $alias);

        // This builds the full query including WHERE, JOINs, and Analytics
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", $alias) . $this->_order . $this->_limit;

        $bindings = array_merge($this->_bindings, $this->_havingBindings);

        // Replace placeholders with actual values for easy copy-pasting into Sequel Ace/TablePlus
        foreach ($bindings as $val) {
            $escaped = is_null($val) ? 'NULL' : (is_numeric($val) ? $val : "'" . addslashes((string) $val) . "'");
            $sql = preg_replace('/\?/', (string) $escaped, $sql, 1);
        }

        return $sql . ";";
    }

    private function mapValues(array &$data): array
    {
        $mapped = [];
        foreach ($data as $key => &$val) {
            $isJsonCast = isset($this->casts[$key]) && $this->casts[$key] === 'json';

            // Only encode if it's an array OR if it's a json-cast field that isn't already a string
            if (is_array($val) || ($isJsonCast && ! is_string($val))) {
                $mapped[] = json_encode($val);
            } else {
                $mapped[] = $val;
            }
        }
        return $mapped;
    }

    private function buildSelectSql(string $fields, string $alias = 'p'): string
    {
        $where = $this->_where;
        if ($this->softDelete && ! $this->_ignoreSoftDelete) {
            // Use the dynamic $alias instead of a hardcoded one
            $softDeleteStr = "{$alias}.{$this->deletedAtColumn} IS NULL";
            if (empty($where)) {
                $where[] = $softDeleteStr;
            } else {
                // Prepend the soft delete condition properly
                array_unshift($where, $softDeleteStr . " AND ");
            }
        }

        $sql = "SELECT {$fields} FROM {$this->table} {$alias} " . implode('', $this->_joins);
        if (! empty($where)) {
            $sql .= " WHERE " . implode('', $where);
        }
        if ($this->_groupBy)
            $sql .= $this->_groupBy;
        if ($this->_having)
            $sql .= " HAVING " . implode(' ', $this->_having);

        return $sql;
    }

    private function resetQuery(): void
    {
        $this->_where = $this->_bindings = $this->_joins = $this->_having = $this->_havingBindings = [];
        $this->_order = $this->_limit = $this->_groupBy = '';
        $this->selectedFields = '*';
    }

    public function explain(): array
    {
        $sql = "EXPLAIN " . $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
