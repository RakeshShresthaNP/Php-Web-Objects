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

    protected array $_rs = [];

    private array $_where = [];

    private array $_bindings = [];

    private array $_joins = [];

    private string $_order = '';

    private string $_limit = '';

    // Configurable defaults
    private string $selectedFields = '*';

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
        return $this->_rs[$key] ?? null;
    }

    // --- Fluent Query Builder ---
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

    // --- GraphQL-style JSON Generation ---

    /**
     * Fetches nested relational data as a single JSON object (GraphQL style)
     */
    public function findGraph(array $schema, string $alias = 'p'): mixed
    {
        $jsonExpr = $this->parseGraphSchema($schema, $alias);

        // We use a specific alias 'graph_data' to identify the result
        $sql = "SELECT {$jsonExpr} AS graph_data FROM {$this->table} {$alias} " . implode('', $this->_joins);

        if ($this->_where) {
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);
        $this->resetQuery();

        // Fetch the specific column containing the JSON string
        $result = $stmt->fetchColumn();

        if (! $result)
            return null;

        // Decode into a PHP object
        return json_decode($result);
    }

    /**
     * Paginated version of the GraphQL nested query
     */
    public function paginateGraph(array $schema, int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $jsonExpr = $this->parseGraphSchema($schema, 'p');
        $sql = "SELECT {$jsonExpr} as row_data FROM {$this->table} p " . implode('', $this->_joins);

        if ($this->_where)
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        $sql .= $this->_order . " LIMIT {$offset}, {$perPage}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);
        $this->resetQuery();

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return (object) [
            'items' => array_map(fn ($j) => json_decode($j), $rows),
            'meta' => (object) [
                'total_records' => $total,
                'total_pages' => (int) ceil($total / $perPage),
                'current_page' => $page
            ]
        ];
    }

    protected function parseGraphSchema(array $schema, string $alias): string
    {
        $parts = [];
        foreach ($schema as $key => $val) {
            $parts[] = "'{$key}'";
            if (is_array($val)) {
                $subFields = $this->parseGraphSchema($val['fields'], 'sub');
                $parts[] = "COALESCE((SELECT JSON_ARRAYAGG({$subFields}) FROM {$val['table']} sub WHERE sub.{$val['foreign_key']} = {$alias}.{$this->pk}), JSON_ARRAY())";
            } else {
                $parts[] = "{$alias}.{$val}";
            }
        }
        return "JSON_OBJECT(" . implode(', ', $parts) . ")";
    }

    // --- Standard Execution ---
    public function find(?string $columns = null): array|object|null
    {
        $fields = $columns ?? $this->selectedFields;
        $sql = "SELECT {$fields} FROM {$this->table} p " . implode('', $this->_joins);
        if ($this->_where)
            $sql .= " WHERE " . implode(' AND ', $this->_where);
        $sql .= $this->_order . $this->_limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->_bindings);
        $this->resetQuery();

        if ($stmt->rowCount() == 1) {
            $this->_rs = $stmt->fetch(PDO::FETCH_ASSOC);
            return (object) $this->_rs;
        }

        $res = $stmt->fetchAll();

        if (! $res)
            return null;

        return $res;
    }

    public function paginate(int $page = 1, int $perPage = 15, ?string $columns = null): object
    {
        $total = $this->count();
        $page = max(1, $page);
        $this->limit($perPage, ($page - 1) * $perPage);
        $data = $this->find($columns);
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
        $sql = "SELECT COUNT(*) FROM {$this->table} p " . implode('', $this->_joins);
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
        return true;
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
