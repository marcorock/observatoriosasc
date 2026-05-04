<?php

namespace App\Database;

use App\Database\DataConect;

abstract class QueryBuilder
{
    protected $pdo;
    protected string $select = '*';
    protected string $from = '';
    protected array $joins = [];
    protected array $where = [];
    protected string $groupBy = '';
    protected array $having = [];
    protected string $orderBy = '';
    protected string $orderDirection = 'ASC';
    protected string $limit = '';
    protected array $insertData = [];
    protected array $updateData = [];

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
    }

    public function select(string $columns = '*'): self {
        $this->select = $columns;
        return $this;
    }

    public function from(string $table): self {
        $this->from = $table;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'LEFT'): self {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function where(string $condition): self {
        $this->where[] = $condition;
        return $this;
    }

    public function aplicarFiltroPeriodo($query = null, ?string $dataInicio = null, ?string $dataFim = null, string $column = 'data_referencia'): self|string
    {
        $conditions = $this->buildPeriodConditions($dataInicio, $dataFim, $column);

        if (is_string($query)) {
            if (empty($conditions)) {
                return $query;
            }

            return $query . (stripos($query, ' where ') === false ? ' WHERE ' : ' AND ') . implode(' AND ', $conditions);
        }

        foreach ($conditions as $condition) {
            $this->where($condition);
        }

        return $this;
    }

    protected function buildPeriodConditions(?string $dataInicio = null, ?string $dataFim = null, string $column = 'data_referencia'): array
    {
        $dataInicio = $this->normalizeDate($dataInicio);
        $dataFim = $this->normalizeDate($dataFim);

        if ($dataInicio === null && $dataFim === null) {
            return [];
        }

        if ($dataInicio !== null && $dataFim !== null) {
            return ["{$column} BETWEEN '{$dataInicio}' AND '{$dataFim}'"];
        }

        if ($dataInicio !== null) {
            return ["{$column} >= '{$dataInicio}'"];
        }

        return ["{$column} <= '{$dataFim}'"];
    }

    protected function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }

    public function groupBy(string $column): self {
        $this->groupBy = $column;
        return $this;
    }

    public function having(string $condition): self {
        $this->having[] = $condition;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy = $column;
        $this->orderDirection = $direction;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self {
        $this->limit = "$offset, $limit";
        return $this;
    }

    public function insert(string $table, array $data): self {
        $this->from = $table;
        $this->insertData = $data;
        return $this;
    }

    public function update(string $table, array $data): self {
        $this->from = $table;
        $this->updateData = $data;
        return $this;
    }

    public function delete(string $table): self {
        $this->from = $table;
        return $this;
    }

    public function getSelect(): string {
        $query = "SELECT $this->select FROM $this->from";

        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $query .= " GROUP BY $this->groupBy";
        }

        if (!empty($this->having)) {
            $query .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $query .= " ORDER BY $this->orderBy $this->orderDirection";
        }

        if (!empty($this->limit)) {
            $query .= " LIMIT $this->limit";
        }

        return $query;
    }

    public function getInsert(): string {
        $columns = implode(', ', array_keys($this->insertData));
        $values = implode(', ', array_map(fn($value) => "'$value'", array_values($this->insertData)));

        return "INSERT INTO $this->from ($columns) VALUES ($values)";
    }

    public function getUpdate(): string {
        $set = implode(', ', array_map(fn($key, $value) => "$key='$value'", array_keys($this->updateData), array_values($this->updateData)));
        
        $query = "UPDATE $this->from SET $set";

        if (!empty($this->where)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);
        }

        return $query;
    }

    public function getDelete(): string {
        $query = "DELETE FROM $this->from";

        if (!empty($this->where)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);
        }

        return $query;
    }

    /**
     * Método responsável por limpar a query para a próxima chamada
     * @return self
     */
    public function reset(): self {
        $this->select = '*';
        $this->from = '';
        $this->joins = [];
        $this->where = [];
        $this->groupBy = '';
        $this->having = [];
        $this->orderBy = '';
        $this->orderDirection = 'ASC';
        $this->limit = '';
        $this->insertData = [];
        $this->updateData = [];
        return $this;
    }
}
