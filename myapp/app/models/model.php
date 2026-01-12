<?php
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

    public function __sleep(): array
    {
        // We explicitly list what to save.
        // We leave 'db' out so it doesn't crash.
        return [
            'table',
            'pk',
            '_rs',
            'casts',
            'timestamps',
            'softDelete',
            'createdAtColumn',
            'updatedAtColumn',
            'deletedAtColumn'
        ];
    }

    // --- Memory-Efficient Assignment ---
    public function assign(array &$arr): void
    {
        foreach ($arr as $key => &$val) {
            if (isset($this->casts[$key]) && $this->casts[$key] === 'json' && is_string($val)) {
                $this->_rs[$key] = json_decode($val, true);
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

    public function getData(): array
    {
        return $this->_rs;
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
        $prefix = empty($this->_where) ? "" : "OR ";
        $this->_where[] = "{$prefix}{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $this->_where[] = "{$prefix}{$column} IS NULL";
        return $this;
    }

    public function whereIn(string $column, array &$values, string $boolean = 'AND'): self
    {
        if (empty($values))
            return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->_where[] = "{$prefix}{$column} IN ({$placeholders})";
        $this->_bindings = array_merge($this->_bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, array &$values, string $boolean = 'AND', bool $not = false): self
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

    public function search(array $columns, string $term): self
    {
        if (empty($term))
            return $this;
        return $this->whereGroup(function ($q) use (&$columns, $term) {
            foreach ($columns as &$column)
                $q->orWhere($column, 'LIKE', "%{$term}%");
        });
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

    public function withCount(string $table, string $foreignKey, ?string $alias = null): self
    {
        $alias = $alias ?? "{$table}_count";
        $subQuery = "(SELECT COUNT(*) FROM {$table} WHERE {$table}.{$foreignKey} = p.{$this->pk})";
        $this->selectedFields = ($this->selectedFields === '*') ? "p.*, {$subQuery} AS {$alias}" : "{$this->selectedFields}, {$subQuery} AS {$alias}";
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->_groupBy = " GROUP BY " . implode(', ', $columns);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->_order = " ORDER BY {$column} " . (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->_limit = " LIMIT {$offset}, {$limit}";
        return $this;
    }

    // --- Execution ---
    public function find(): array|static|null
    {
        $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (! $results)
            return null;
        $instances = [];
        foreach ($results as &$row) {
            $instance = new static($this->table, $this->pk);
            $instance->assign($row);
            $instances[] = $instance;
        }
        return (str_contains($this->_limit, 'LIMIT 1') || count($instances) === 1) ? $instances[0] : $instances;
    }

    public function paginate(int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $this->limit($perPage, (max(1, $page) - 1) * $perPage);
        $data = $this->find();
        return (object) [
            'items' => is_array($data) ? $data : ($data ? [
                $data
            ] : []),
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

    // --- Graph Engine ---
    public function findGraph(array &$schema, string $alias = 'p'): mixed
    {
        // Use the same alias for the JSON structure AND the SELECT query
        $jsonExpr = $this->parseGraphSchema($schema, $alias);

        // Pass $alias to buildSelectSql
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", $alias) . $this->_order . $this->_limit;

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

    /**
     * Generates the complex JSON_OBJECT SQL string for hierarchical data.
     * Handles Deep Nesting, Aggregates with filters, and Parent-Link lookups.
     */
    private function parseGraphSchema(array $schema, string $alias): string
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
                    'rownumber',
                    'lead',
                    'lag',
                    'percent_change',
                    'running_total',
                    'moving_average'
                ])) {
                    $partition = isset($val['partition_by']) ? "PARTITION BY " . trim($val['partition_by']) . " " : "";

                    // Ensure ORDER BY keyword isn't duplicated
                    $rawOrder = $val['order_by'] ?? $this->_order;
                    $cleanOrder = trim(str_ireplace('ORDER BY', '', (string) $rawOrder));
                    if (empty($cleanOrder))
                        $cleanOrder = "{$alias}.{$this->pk}";

                    $colName = $val['column'] ?? $this->pk;
                    $col = str_contains($colName, '.') ? $colName : "{$alias}.{$colName}";

                    if ($type === 'rownumber') {
                        $currentVal = "ROW_NUMBER() OVER ({$partition}ORDER BY {$cleanOrder})";
                    } elseif ($type === 'running_total') {
                        $currentVal = "SUM({$col}) OVER ({$partition}ORDER BY {$cleanOrder} ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)";
                    } elseif ($type === 'percent_change') {
                        // MariaDB 10.x requires explicit spaces after commas in LAG/LEAD
                        $lagExpr = "LAG({$col}, 1, 0) OVER ({$partition}ORDER BY {$cleanOrder})";
                        $currentVal = "ROUND((({$col} - {$lagExpr}) / NULLIF({$lagExpr}, 0)) * 100, 2)";
                    } elseif ($type === 'moving_average') {
                        $rows = (int) ($val['rows'] ?? 3);
                        $currentVal = "AVG({$col}) OVER ({$partition}ORDER BY {$cleanOrder} ROWS BETWEEN {$rows} PRECEDING AND CURRENT ROW)";
                    } elseif (in_array($type, [
                        'lead',
                        'lag'
                    ])) {
                        $func = strtoupper($type);
                        $offset = (int) ($val['offset'] ?? 1);
                        $defaultPart = "";
                        if (isset($val['default'])) {
                            $rawDefault = is_numeric($val['default']) ? $val['default'] : "'{$val['default']}'";
                            // CRITICAL: The space after the comma is required for some MariaDB versions
                            $defaultPart = ", " . $rawDefault;
                        }
                        $currentVal = "{$func}({$col}, {$offset}{$defaultPart}) OVER ({$partition}ORDER BY {$cleanOrder})";
                    }
                } elseif (in_array($type, [
                    'count',
                    'sum',
                    'avg',
                    'min',
                    'max'
                ])) {
                    $func = strtoupper($type);
                    $targetCol = ($func === 'COUNT') ? '*' : (str_contains($val['column'] ?? '', '.') ? $val['column'] : "{$subAlias}." . ($val['column'] ?? $this->pk));
                    $extraFilter = isset($val['where']) ? " AND (" . (is_array($val['where']) ? implode(' AND ', $val['where']) : $val['where']) . ")" : "";
                    $currentVal = "(SELECT COALESCE({$func}({$targetCol}), 0) FROM {$val['table']} {$subAlias} WHERE {$subAlias}.{$val['foreign_key']} = {$alias}.{$this->pk}{$extraFilter})";
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
        if ($this->timestamps)
            $this->_rs[$this->createdAtColumn] = $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $cols = array_keys($this->_rs);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        return $this->db->prepare($sql)->execute($this->mapValues($this->_rs)) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (! isset($this->_rs[$this->pk]))
            return false;
        if ($this->timestamps)
            $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);
        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";
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
