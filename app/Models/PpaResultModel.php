<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;

class PpaResultModel
{
    private PDO $pdo;
    private string $table = 'ppa_resultados';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
    }

    public function readLatestPublishedByIndicatorIds(array $indicatorIds): array
    {
        $indicatorIds = array_values(array_unique(array_filter(
            array_map('intval', $indicatorIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($indicatorIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($indicatorIds), '?'));

        try {
            $stmt = $this->pdo->prepare(
                "SELECT result.indicador_id, result.valor_meta_quantitativa,
                        result.valor_resultado, result.indice_recente, result.indice_futuro,
                        result.unidade_medida, result.status, result.validated_at, result.created_at
                 FROM {$this->table} result
                 INNER JOIN (
                    SELECT indicador_id, MAX(id) AS result_id
                    FROM {$this->table}
                    WHERE status IN ('validado', 'publicado')
                      AND indicador_id IN ({$placeholders})
                    GROUP BY indicador_id
                 ) latest ON latest.result_id = result.id"
            );

            foreach ($indicatorIds as $index => $indicatorId) {
                $stmt->bindValue($index + 1, $indicatorId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            $results = [];

            foreach ($rows as $row) {
                $results[(int) $row->indicador_id] = $row;
            }

            return $results;
        } catch (PDOException $e) {
            // The catalog must remain available if the optional history table
            // has not been installed yet or is temporarily unavailable.
            return [];
        }
    }
}
