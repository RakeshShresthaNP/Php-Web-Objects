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

class Model
{

    protected ?PDO $db = null;

    protected array $_rs = [];

    protected array $_where = [];

    protected array $_bindings = [];

    protected array $_joins = [];

    protected string $_order = '';

    protected string $_limit = '';

    // Defaulting to string for comma-separated fields
    protected string $selectedFields = '*';

    public function __construct(protected string $table, protected string $pk = 'id')
    {
        $this->db = db();
    }

    public function __set(string $key, mixed $val): void
    {
        $this->_rs[$key] = $val;
    }

    public function __get(string $key): mixed
    {
        $this->_rs[$key] ?? null;
    }

    /**
     * Fluent method to set fields as a comma-separated string
     */
    public function select(string $fields): self
    {
        $this->selectedFields = $fields;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            [
                $value,
                $operator
            ] = [
                $operator,
                '='
            ];
        }
        $this->_where[] = "{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->_joins[] = " {$type} JOIN {$table} ON {$first} {$operator} {$second}";
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

    /**
     * Executes the query.
     * Uses the provided string, or falls back to $this->selectedFields
     */
    public function find(?string $columns = null): array|object|null
    {
        if ($this->useSoftDeletes) {
            $this->where("{$this->table}.{$this->deletedAtColumn}", "IS", null);
        }

        $fieldString = $columns ?? $this->selectedFields;

        $sql = "SELECT {$fieldString} FROM {$this->table}" . implode('', $this->_joins);

        if ($this->_where) {
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        }

        $sql .= $this->_order . $this->_limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);

        $this->resetQuery();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (! $results)
            return null;

        if (count($results) === 1) {
            $this->_rs = $results[0];
            return (object) $this->_rs;
        }

        return $results;
    }

    public function paginate(int $page = 1, int $perPage = 15, ?string $columns = null): object
    {
        $total = $this->count();
        $page = max(1, $page);

        $this->limit($perPage, ($page - 1) * $perPage);
        $data = $this->find($columns);

        $items = is_array($data) ? $data : ($data ? [
            $data
        ] : []);

        return (object) [
            'items' => $items,
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
        $sql = "SELECT COUNT(*) FROM {$this->table}" . implode('', $this->_joins);
        if ($this->_where) {
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);

        return (int) $stmt->fetchColumn();
    }

    // --- Persistence Methods ---
    public function save(): bool|string
    {
        return isset($this->_rs[$this->pk]) ? $this->update() : $this->insert();
    }

    public function insert(): string|false
    {
        $columns = array_keys($this->_rs);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ")
                VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $values = array_map(fn ($v) => is_scalar($v) ? $v : serialize($v), array_values($this->_rs));

        return $stmt->execute($values) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (! isset($this->_rs[$this->pk]))
            return false;

        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);

        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";

        $values = array_map(fn ($v) => is_scalar($v) ? $v : serialize($v), array_values($data));
        $values[] = $id;

        return $this->db->prepare($sql)->execute($values);
    }

    public function delete(): bool
    {
        if (! isset($this->_rs[$this->pk]))
            return false;

        $sql = "DELETE FROM {$this->table} WHERE {$this->pk}=?";
        $values[] = $this->_rs[$this->pk];

        return $this->db->prepare($sql)->execute($values);
    }

    private function resetQuery(): void
    {
        $this->_where = [];
        $this->_bindings = [];
        $this->_joins = [];
        $this->_order = '';
        $this->_limit = '';
        $this->selectedFields = '*';
    }

    public function assign(array $arr): void
    {
        foreach ($arr as $key => $val) {
            $this->$key = $val;
        }
    }
}
