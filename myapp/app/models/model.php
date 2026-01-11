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
        $this->db = db();
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

    public function __get(string $key): mixed { return $this->_rs[$key] ?? null; }
    public function __set(string $key, mixed $val): void { $this->_rs[$key] = $val; }
    public function getData(): array { return $this->_rs; }

    // --- Query Builder (Where/Having/Joins) ---
    public function select(string $fields = '*'): self { $this->selectedFields = $fields; return $this; }
    public function selectRaw(string $expression): self { $this->selectedFields = $expression; return $this; }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null && $operator !== null) [$value, $operator] = [$operator, '='];
        $prefix = empty($this->_where) ? "" : "AND ";
        $this->_where[] = "{$prefix}{$column} {$operator} ?";
        $this->_bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) [$value, $operator] = [$operator, '='];
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
        if (empty($values)) return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->_where[] = "{$prefix}{$column} IN ({$placeholders})";
        $this->_bindings = array_merge($this->_bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, array &$values, string $boolean = 'AND', bool $not = false): self
    {
        if (count($values) !== 2) return $this;
        $prefix = empty($this->_where) ? "" : "{$boolean} ";
        $type = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $this->_where[] = "{$prefix}{$column} {$type} ? AND ?";
        $this->_bindings[] = $values[0];
        $this->_bindings[] = $values[1];
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

    public function search(array &$columns, string $term): self
    {
        if (empty($term)) return $this;
        return $this->whereGroup(function ($q) use (&$columns, $term) {
            foreach ($columns as &$column) $q->orWhere($column, 'LIKE', "%{$term}%");
        });
    }

    public function whereGroup(callable $callback): self
    {
        $nested = new self($this->table, $this->pk);
        $callback($nested);
        if (!empty($nested->_where)) {
            $prefix = empty($this->_where) ? "" : "AND ";
            $this->_where[] = "{$prefix}(" . implode(' ', $nested->_where) . ")";
            $this->_bindings = array_merge($this->_bindings, $nested->_bindings);
        }
        return $this;
    }

    public function withCount(string $table, string $foreignKey, string $alias = null): self
    {
        $alias = $alias ?? "{$table}_count";
        $subQuery = "(SELECT COUNT(*) FROM {$table} WHERE {$table}.{$foreignKey} = p.{$this->pk})";
        $this->selectedFields = ($this->selectedFields === '*') ? "p.*, {$subQuery} AS {$alias}" : "{$this->selectedFields}, {$subQuery} AS {$alias}";
        return $this;
    }

    public function groupBy(string ...$columns): self { $this->_groupBy = " GROUP BY " . implode(', ', $columns); return $this; }
    public function orderBy(string $column, string $direction = 'ASC'): self { $this->_order = " ORDER BY {$column} " . (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'); return $this; }
    public function limit(int $limit, int $offset = 0): self { $this->_limit = " LIMIT {$offset}, {$limit}"; return $this; }

    // --- Execution ---
    public function find(): array|static|null
    {
        $sql = $this->buildSelectSql($this->selectedFields) . $this->_order . $this->_limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$results) return null;
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
            'items' => is_array($data) ? $data : ($data ? [$data] : []),
            'meta' => (object) ['total_records' => $total, 'total_pages' => (int) ceil($total / $perPage), 'current_page' => $page, 'per_page' => $perPage]
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
        $jsonExpr = $this->parseGraphSchema($schema, $alias);
        $stmt = $this->db->prepare($this->buildSelectSql($jsonExpr . " AS graph_data", $alias));
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
        $result = $stmt->fetchColumn();
        return $result ? json_decode($result) : null;
    }

    public function paginateGraph(array &$schema, int $page = 1, int $perPage = 15): object
    {
        $total = $this->count();
        $this->limit($perPage, (max(1, $page) - 1) * $perPage);
        $jsonExpr = $this->parseGraphSchema($schema, 'p');
        $stmt = $this->db->prepare($this->buildSelectSql($jsonExpr . " AS graph_data", 'p') . $this->_order . $this->_limit);
        $stmt->execute(array_merge($this->_bindings, $this->_havingBindings));
        $this->resetQuery();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return (object) [
            'items' => array_map(fn ($json) => json_decode($json), $rows),
            'meta' => (object) ['total_records' => $total, 'total_pages' => (int) ceil($total / $perPage), 'current_page' => $page, 'per_page' => $perPage]
        ];
    }

    private function parseGraphSchema(array &$schema, string $alias): string
    {
        $parts = [];
        foreach ($schema as $key => &$val) {
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
    public function save(): bool|string { return isset($this->_rs[$this->pk]) ? $this->update() : $this->insert(); }

    public function insert(): string|false
    {
        if ($this->timestamps) $this->_rs[$this->createdAtColumn] = $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $cols = array_keys($this->_rs);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        return $this->db->prepare($sql)->execute($this->mapValues($this->_rs)) ? $this->db->lastInsertId() : false;
    }

    public function update(): bool
    {
        if (!isset($this->_rs[$this->pk])) return false;
        if ($this->timestamps) $this->_rs[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        $data = $this->_rs; $id = $data[$this->pk]; unset($data[$this->pk]);
        $fields = array_map(fn($k) => "{$k}=?", array_keys($data));
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->pk}=?";
        $values = $this->mapValues($data); $values[] = $id;
        return $this->db->prepare($sql)->execute($values);
    }

    private function mapValues(array &$data): array
    {
        $mapped = [];
        foreach ($data as $key => &$val) {
            $mapped[] = (is_array($val) || (isset($this->casts[$key]) && $this->casts[$key] === 'json')) ? json_encode($val) : $val;
        }
        return $mapped;
    }

    private function buildSelectSql(string $fields, string $alias = 'p'): string
    {
        $where = $this->_where;
        if ($this->softDelete && !$this->_ignoreSoftDelete) {
            array_unshift($where, (empty($where) ? "" : "AND ") . "{$alias}.{$this->deletedAtColumn} IS NULL");
        }
        $sql = "SELECT {$fields} FROM {$this->table} {$alias} " . implode('', $this->_joins);
        if (!empty($where)) $sql .= " WHERE " . implode(' ', $where);
        if ($this->_groupBy) $sql .= $this->_groupBy;
        if ($this->_having) $sql .= " HAVING " . implode(' ', $this->_having);
        return $sql;
    }

    private function resetQuery(): void
    {
        $this->_where = $this->_bindings = $this->_joins = $this->_having = $this->_havingBindings = [];
        $this->_order = $this->_limit = $this->_groupBy = '';
        $this->selectedFields = '*';
    }
}
