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

    // --- Query Builder ---
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
        $this->_order = " ORDER BY {$column} " . (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC');
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->_limit = " LIMIT {$offset}, {$limit}";
        return $this;
    }

    // --- Execution ---
    public function find(string $select = '*'): array|object|null
    {
        $sql = "SELECT {$select} FROM {$this->table}" . implode('', $this->_joins);
        if ($this->_where)
            $sql .= " WHERE " . implode(' AND ', $this->_where);
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

    public function paginate(int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $page = max(1, $page);

        $this->limit($perPage, ($page - 1) * $perPage);
        $data = $this->find();
        $items = is_array($data) ? $data : ($data ? [
            $data
        ] : []);

        return (object) [
            'items' => $items,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'current_page' => $page
        ];
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}" . implode('', $this->_joins);
        if ($this->_where)
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);
        return (int) $stmt->fetchColumn();
    }

    // --- Persistence ---
    public function save(): bool|string
    {
        isset($this->_rs[$this->pk]) ? $this->update() : $this->insert();
    }

    public function insert(): string|false
    {
        $cols = array_keys($this->_rs);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        $values = array_map(fn ($v) => is_scalar($v) ? $v : serialize($v), array_values($this->_rs));
        return $this->db->prepare($sql)->execute($values) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        $data = $this->_rs;
        $id = $data[$this->pk];
        unset($data[$this->pk]);

        $fields = array_map(fn ($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";
        $values = [
            ...array_map(fn ($v) => is_scalar($v) ? $v : serialize($v), array_values($data)),
            $id
        ];
        return $this->db->prepare($sql)->execute($values);
    }

    public function trash(): bool
    {
        return $this->update();
    }

    private function resetQuery(): void
    {
        $this->_where = [];
        $this->_bindings = [];
        $this->_joins = [];
        $this->_order = '';
        $this->_limit = '';
    }
}
