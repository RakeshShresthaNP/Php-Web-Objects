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

    // Feature Flags & Custom Column Naming
    protected bool $timestamps = true;
    protected bool $softDelete = false;
    protected string $createdAtColumn = 'd_created';
    protected string $updatedAtColumn = 'd_updated';
    protected string $deletedAtColumn = 'd_deleted';

    public function __construct(protected string $table, protected string $pk = 'id')
    {
        $this->db = db();
    }

    // --- Magic Accessors & Serialization Protection ---
    public function __get(string $key): mixed
    {
        if (method_exists($this, $key))
            return $this->$key();
        return $this->_rs[$key] ?? null;
    }

    public function __set(string $key, mixed $val): void
    {
        $this->_rs[$key] = $val;
    }

    public function __sleep(): array
    {
        return [
            '_rs', 'table', 'pk', 'timestamps', 'softDelete',
            'createdAtColumn', 'updatedAtColumn', 'deletedAtColumn'
        ];
    }

    public function __debugInfo(): array
    {
        $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $bindings = array_merge($this->_bindings, $this->_havingBindings);

        // Map bindings into the SQL string for a "copy-paste" ready version
        $rawSql = $sql;
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $rawSql = preg_replace('/\?/', (string)$value, $rawSql, 1);
        }

        return [
            'table'   => $this->table,
            'data'    => $this->_rs,
            'sql'     => $sql,
            'raw_sql' => $rawSql,
            'params'  => $bindings
        ];
    }

    public function getData(): array
    {
        return $this->_rs;
    }

    // --- Fluent Query Builder ---
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
            [$value, $operator] = [$operator, '='];
        $prefix = empty($this->_where) ? "" : "AND ";
        $this->_where[] = "{$prefix}{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null)
            [$value, $operator] = [$operator, '='];
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

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->_where[] = "{$prefix}{$column} IN ({$placeholders})";
        $this->_bindings = array_merge($this->_bindings, $values);
        return $this;
    }

    public function whereDate(string $column, mixed $operator, mixed $value = null, string $boolean = 'AND'): self
    {
        if ($value === null && $operator !== null) {
            [$value, $operator] = [$operator, '='];
        }

        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        // DATE() strips the time (H:i:s) from the database column for the comparison
        $this->_where[] = "{$prefix}DATE({$column}) {$operator} ?";
        $this->_bindings[] = $value;

        return $this;
    }
 
    public function whereColumn(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        // Notice: We do not use a '?' placeholder here because $second is a column name, not a value.
        $this->_where[] = "{$prefix}{$first} {$operator} {$second}";

        return $this;
    }

    public function orWhereColumn(string $first, string $operator, string $second): self
    {
        return $this->whereColumn($first, $operator, $second, 'OR');
    }


    // --- NEW: Between Methods ---
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        if (count($values) !== 2) return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $type = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $this->_where[] = "{$prefix}{$column} {$type} ? AND ?";
        $this->_bindings[] = $values[0];
        $this->_bindings[] = $values[1];
        return $this;
    }

    public function orWhereBetween(string $column, array $values): self
    {
        return $this->whereBetween($column, $values, 'OR');
    }

    public function whereNotBetween(string $column, array $values): self
    {
        return $this->whereBetween($column, $values, 'AND', true);
    }

    // --- NEW: Subqueries & Count ---
    public function whereExists(callable $callback, string $boolean = 'AND', bool $not = false): self
    {
        $type = $not ? 'NOT EXISTS' : 'EXISTS';
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $query = new self($this->table, $this->pk);
        $callback($query);
        $subSql = $query->buildSelectSql($query->selectedFields);
        $this->_where[] = "{$prefix}{$type} ({$subSql})";
        $this->_bindings = array_merge($this->_bindings, $query->_bindings);
        return $this;
    }

    public function withCount(string $table, string $foreignKey, string $alias = null): self
    {
        $alias = $alias ?? "{$table}_count";
        $subQuery = "(SELECT COUNT(*) FROM {$table} WHERE {$table}.{$foreignKey} = p.{$this->pk})";
        if ($this->selectedFields === '*') {
            $this->selectedFields = "p.*, {$subQuery} AS {$alias}";
        } else {
            $this->selectedFields .= ", {$subQuery} AS {$alias}";
        }
        return $this;
    }

    public function whereGroup(callable $callback): self
    {
        $nestedQuery = new self($this->table, $this->pk);
        $callback($nestedQuery);
        if (! empty($nestedQuery->_where)) {
            $prefix = empty($this->_where) ? "" : "AND ";
            $this->_where[] = "{$prefix}(" . implode(' ', $nestedQuery->_where) . ")";
            $this->_bindings = array_merge($this->_bindings, $nestedQuery->_bindings);
        }
        return $this;
    }

    public function search(array $columns, string $term): self
    {
        if (empty($term)) return $this;
        return $this->whereGroup(function ($q) use ($columns, $term) {
            foreach ($columns as $column)
                $q->orWhere($column, 'LIKE', "%{$term}%");
        });
    }

    public function withTrashed(): self
    {
        $this->_ignoreSoftDelete = true;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->_joins[] = " {$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function groupBy(string ...$columns): self
    {
        $this->_groupBy = " GROUP BY " . implode(', ', $columns);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->_order = " ORDER BY {$column} {$dir}";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->_limit = " LIMIT {$offset}, {$limit}";
        return $this;
    }

    // --- Core Execution ---
    private function buildSelectSql(string $fields, string $alias = 'p'): string
    {
        if ($this->softDelete && ! $this->_ignoreSoftDelete) {
            $this->whereNull("{$alias}.{$this->deletedAtColumn}");
        }
        $sql = "SELECT {$fields} FROM {$this->table} {$alias} " . implode('', $this->_joins);
        if ($this->_where)
            $sql .= " WHERE " . implode(' ', $this->_where);
        if ($this->_groupBy)
            $sql .= $this->_groupBy;
        if ($this->_having)
            $sql .= " HAVING " . implode(' ', $this->_having);
        return $sql;
    }

    private array $_eagerLoads = [];

    public function with(string $relation, string $relatedClass, string $foreignKey): self
    {
        $this->_eagerLoads[$relation] = [
            'class' => $relatedClass,
            'foreignKey' => $foreignKey
        ];
        return $this;
    }
 
    public function find(): array|static|null
    {
        $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();

        $results = $stmt->fetchAll();
        if (!$results) return null;

        $instances = array_map(function ($obj) {
            $instance = new static($this->table, $this->pk);
            $instance->assign((array)$obj);
            return $instance;
        }, $results);

        // --- Eager Loading Logic ---
        if (!empty($instances) && !empty($this->_eagerLoads)) {
            $ids = array_map(fn($inst) => $inst->{$this->pk}, $instances);
            
            foreach ($this->_eagerLoads as $relation => $config) {
                $relatedModel = new $config['class']();
                // Fetch ALL related records for ALL found IDs in one query
                $relatedRecords = $relatedModel->whereIn($config['foreignKey'], $ids)->find();
                
                if (!is_array($relatedRecords)) $relatedRecords = $relatedRecords ? [$relatedRecords] : [];

                // Map them back to the parents
                foreach ($instances as $instance) {
                    $instance->{$relation} = array_filter($relatedRecords, function($rel) use ($instance, $config) {
                        return $rel->{$config['foreignKey']} == $instance->{$this->pk};
                    });
                }
            }
        }

        if (str_contains($this->_limit, 'LIMIT 1') || count($instances) === 1)
            return $instances[0];
            
        return $instances;
    }

    public function paginate(int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $page = max(1, $page);
        $this->limit($perPage, ($page - 1) * $perPage);
        $data = $this->find();
        return (object) [
            'items' => is_array($data) ? $data : ($data ? [$data] : []),
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
        $sql = $this->buildSelectSql("COUNT(*)");
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        return (int) $stmt->fetchColumn();
    }

    // --- Graph Engine ---
    public function findGraph(array &$schema, string $alias = 'p'): mixed
    {
        $jsonExpr = $this->parseGraphSchema($schema, $alias);
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", $alias);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
        $result = $stmt->fetchColumn();
        return $result ? json_decode($result) : null;
    }

    public function paginateGraph(array &$schema, int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $page = max(1, $page);
        $this->limit($perPage, ($page - 1) * $perPage);
        $jsonExpr = $this->parseGraphSchema($schema, 'p');
        $sql = $this->buildSelectSql($jsonExpr . " AS graph_data", 'p') . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
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

    private function parseGraphSchema(array &$schema, string $alias): string
    {
        $parts = [];
        foreach ($schema as $key => $val) {
            $parts[] = "'{$key}'";
            if (is_array($val)) {
                if (isset($val['type']) && $val['type'] === 'count') {
                    $parts[] = "(SELECT COUNT(*) FROM {$val['table']} sub WHERE sub.{$val['foreign_key']} = {$alias}.{$this->pk})";
                } else {
                    $subFields = $this->parseGraphSchema($val['fields'], 'sub');
                    $softFilter = $this->softDelete ? " AND sub.{$this->deletedAtColumn} IS NULL" : "";
                    $parts[] = "COALESCE((SELECT JSON_ARRAYAGG({$subFields}) FROM {$val['table']} sub WHERE sub.{$val['foreign_key']} = {$alias}.{$this->pk}{$softFilter}), JSON_ARRAY())";
                }
            } else {
                $parts[] = (str_contains($val, '(')) ? $val : "{$alias}.{$val}";
            }
        }
        return "JSON_OBJECT(" . implode(', ', $parts) . ")";
    }

    // --- Persistence ---
    public function save(): bool|string
    {
        return isset($this->_rs[$this->pk]) ? $this->update() : $this->insert();
    }

    public function insert(): string|false
    {
        if ($this->timestamps) {
            $this->_rs[$this->createdAtColumn] = $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        $cols = array_keys($this->_rs);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        $values = array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), array_values($this->_rs));
        return $this->db->prepare($sql)->execute($values) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (! isset($this->_rs[$this->pk])) return false;
        if ($this->timestamps) $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);
        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";
        $values = array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), array_values($data));
        $values[] = $id;
        return $this->db->prepare($sql)->execute($values);
    }

    public function delete(): bool
    {
        if (! isset($this->_rs[$this->pk])) return false;
        if ($this->softDelete) {
            $this->{$this->deletedAtColumn} = date('Y-m-d H:i:s');
            return $this->update();
        }
        $sql = "DELETE FROM {$this->table} WHERE {$this->pk}=?";
        return $this->db->prepare($sql)->execute([$this->_rs[$this->pk]]);
    }

    public function updateWhere(array $data): bool
    {
        if (empty($this->_where)) return false; 
        if ($this->timestamps) $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE " . implode(' ', $this->_where);
        $values = array_values($data);
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(array_merge($values, $this->_bindings));
        $this->resetQuery();
        return $result;
    }

    public function deleteWhere(): bool
    {
        if (empty($this->_where)) return false; 
        if ($this->softDelete) return $this->updateWhere([$this->deletedAtColumn => date('Y-m-d H:i:s')]);
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' ', $this->_where);
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($this->_bindings);
        $this->resetQuery();
        return $result;
    }

    public function deleteById(int|string $id): bool
    {
        return $this->where($this->pk, $id)->deleteWhere();
    }
 
    /**
     * Execute a callback within a database transaction.
     */
    public function transaction(callable $callback): mixed
    {
        try {
            $this->db->beginTransaction();
            
            // Execute the logic passed into the function
            $result = $callback($this);
            
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            // Roll back changes if any exception or error occurs
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // --- Relationship Helpers ---
    public function hasMany(string $relatedClass, string $foreignKey): array|null
    {
        if (! isset($this->_rs[$this->pk])) return null;
        $related = new $relatedClass();
        return $related->where($foreignKey, $this->_rs[$this->pk])->find();
    }

    public function belongsTo(string $relatedClass, string $localKey): object|null
    {
        if (! isset($this->_rs[$localKey])) return null;
        $related = new $relatedClass();
        return $related->where($related->pk, $this->_rs[$localKey])->limit(1)->find();
    }

    private function resetQuery(): void
    {
        $this->_where = [];
     $this->_eagerLoads = [];
        $this->_bindings = [];
        $this->_joins = [];
        $this->_order = '';
        $this->_limit = '';
        $this->_groupBy = '';
        $this->_having = [];
        $this->_havingBindings = [];
        $this->selectedFields = '*';
        $this->_ignoreSoftDelete = false;
    }
 
    protected array $casts = []; // Define which columns should be JSON (e.g. ['meta' => 'json'])

    public function assign(array &$arr): void
    {
        foreach ($arr as $key => $val) {
            // Automatically decode if column is cast to JSON
            if (isset($this->casts[$key]) && $this->casts[$key] === 'json' && is_string($val)) {
                $this->_rs[$key] = json_decode($val, true);
            } else {
                $this->_rs[$key] = $val;
            }
        }
    }

    public function insert(): string|false
    {
        if ($this->timestamps) {
            $this->_rs[$this->createdAtColumn] = $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        
        $cols = array_keys($this->_rs);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES ({$placeholders})";
        
        $values = $this->mapValues($this->_rs);
        return $this->db->prepare($sql)->execute($values) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (!isset($this->_rs[$this->pk])) return false;
        
        if ($this->timestamps) {
            $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }
        
        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);
        
        $fields = array_map(fn($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";
        
        $values = $this->mapValues($data);
        $values[] = $id; // Append ID for the WHERE clause
        
        return $this->db->prepare($sql)->execute($values);
    }

        /**
     * Maps the internal record data to SQL-ready values.
     * Automatically JSON encodes arrays or columns defined in $casts.
     */
    private function mapValues(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || (isset($this->casts[$key]) && $this->casts[$key] === 'json')) {
                $mapped[] = json_encode($value);
            } else {
                $mapped[] = is_scalar($value) ? $value : json_encode($value);
            }
        }
        return $mapped;
    }



}




