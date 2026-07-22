<?php

namespace App\Models;

use App\Database\QueryBuilder;
use PDO;
use PDOException;

class DashboardConfigModel extends QueryBuilder
{
    protected string $table = 'dashboard_config';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function getGlobalPeriod(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data_inicio, data_fim FROM {$this->table} WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'data_inicio' => $result['data_inicio'] ?? null,
                'data_fim' => $result['data_fim'] ?? null,
            ];
        } catch (PDOException $e) {
            return [
                'data_inicio' => null,
                'data_fim' => null,
            ];
        }
    }

    public function updateGlobalPeriod(?string $dataInicio = null, ?string $dataFim = null): bool|string
    {
        $dataInicio = $this->normalizeDate($dataInicio);
        $dataFim = $this->normalizeDate($dataFim);

        try {
            $sql = "INSERT INTO {$this->table} (id, data_inicio, data_fim, updated_at)
                    VALUES (1, :data_inicio, :data_fim, NOW())
                    ON DUPLICATE KEY UPDATE
                        data_inicio = VALUES(data_inicio),
                        data_fim = VALUES(data_fim),
                        updated_at = NOW()";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':data_inicio', $dataInicio, $dataInicio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':data_fim', $dataFim, $dataFim === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT NOT NULL PRIMARY KEY,
            data_inicio DATE NULL,
            data_fim DATE NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            return;
        }
    }
}
