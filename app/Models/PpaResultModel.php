<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;
use RuntimeException;

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

    public function readLatestCompletedSynchronizationByIndicatorIds(array $indicatorIds): array
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
                "SELECT sync.indicador_id, sync.status, sync.finished_at, sync.mensagem
                 FROM ppa_sincronizacoes sync
                 INNER JOIN (
                    SELECT indicador_id, MAX(id) AS sync_id
                    FROM ppa_sincronizacoes
                    WHERE status = 'concluido'
                      AND indicador_id IN ({$placeholders})
                    GROUP BY indicador_id
                 ) latest ON latest.sync_id = sync.id"
            );

            foreach ($indicatorIds as $index => $indicatorId) {
                $stmt->bindValue($index + 1, $indicatorId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            $synchronizations = [];

            foreach ($rows as $row) {
                $synchronizations[(int) $row->indicador_id] = $row;
            }

            return $synchronizations;
        } catch (PDOException) {
            return [];
        }
    }

    public function storeValidatedPreview(array $preview): array
    {
        $payload = self::normalizeSyncPreview($preview);

        if (is_string($payload)) {
            throw new RuntimeException($payload);
        }

        try {
            $this->pdo->beginTransaction();
            $syncId = $this->createSynchronization($payload);
            $existingId = $this->findEquivalentResultId($payload);

            if ($existingId !== null) {
                $this->finishSynchronization(
                    $syncId,
                    0,
                    'Resultado identico ao ultimo registro validado; nenhuma duplicacao foi criada.'
                );
                $this->pdo->commit();

                return [
                    'synchronization_id' => $syncId,
                    'result_id' => $existingId,
                    'inserted' => false,
                    'message' => 'O resultado local ja estava atualizado.',
                ];
            }

            $resultId = $this->insertValidatedResult($payload);
            $this->finishSynchronization($syncId, 1, 'Resultado consolidado e validado com sucesso.');
            $this->pdo->commit();

            return [
                'synchronization_id' => $syncId,
                'result_id' => $resultId,
                'inserted' => true,
                'message' => 'Resultado local gravado com sucesso.',
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException('Nao foi possivel persistir a sincronizacao do PPA.', 0, $e);
        }
    }

    public static function normalizeSyncPreview(array $preview): array|string
    {
        if (($preview['success'] ?? false) !== true) {
            return 'A previa precisa ser concluida com sucesso antes da gravacao.';
        }

        $indicatorId = (int) ($preview['indicator_id'] ?? 0);
        $metrics = is_array($preview['metrics'] ?? null) ? $preview['metrics'] : [];
        $year = (int) ($metrics['ano_referencia'] ?? 0);
        $competence = $metrics['competencia'] ?? null;
        $target = $metrics['valor_meta_quantitativa'] ?? null;
        $result = $metrics['valor_resultado'] ?? null;

        if ($indicatorId <= 0) {
            return 'A previa nao possui um indicador valido.';
        }

        if ($year < 2000 || $year > 2100) {
            return 'O ano de referencia da previa e invalido.';
        }

        if ($competence !== null && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $competence)) {
            return 'A competencia da previa deve usar o formato YYYY-MM.';
        }

        if (!is_numeric($target) || (float) $target <= 0) {
            return 'A meta quantitativa da previa e invalida.';
        }

        if (!is_numeric($result) || (float) $result < 0) {
            return 'O valor realizado da previa e invalido.';
        }

        return [
            'indicator_id' => $indicatorId,
            'indicator_code' => trim((string) ($preview['indicator_code'] ?? '')),
            'year' => $year,
            'competence' => $competence !== null ? (string) $competence : null,
            'target' => round((float) $target, 4),
            'result' => round((float) $result, 4),
            'unit' => trim((string) ($metrics['unidade_medida'] ?? '')),
            'reference_date' => $metrics['data_referencia'] ?? null,
        ];
    }

    private function createSynchronization(array $payload): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ppa_sincronizacoes
                (indicador_id, ano_referencia, competencia, tipo_execucao, status,
                 total_lidos, total_processados, total_inseridos, total_atualizados, mensagem)
             VALUES
                (:indicador_id, :ano_referencia, :competencia, 'manual', 'processando',
                 1, 0, 0, 0, :mensagem)"
        );
        $stmt->execute([
            ':indicador_id' => $payload['indicator_id'],
            ':ano_referencia' => $payload['year'],
            ':competencia' => $payload['competence'],
            ':mensagem' => 'Sincronizacao CLI iniciada apos previa validada.',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function findEquivalentResultId(array $payload): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, ano_referencia, competencia, valor_meta_quantitativa, valor_resultado
             FROM {$this->table}
             WHERE indicador_id = :indicador_id
               AND status IN ('validado', 'publicado')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':indicador_id' => $payload['indicator_id'],
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return null;
        }

        $sameCompetence = ($existing['competencia'] ?? null) === $payload['competence'];
        $sameTarget = abs((float) $existing['valor_meta_quantitativa'] - $payload['target']) < 0.00005;
        $sameResult = abs((float) $existing['valor_resultado'] - $payload['result']) < 0.00005;

        if ((int) $existing['ano_referencia'] !== $payload['year']
            || !$sameCompetence
            || !$sameTarget
            || !$sameResult) {
            return null;
        }

        return (int) $existing['id'];
    }

    private function insertValidatedResult(array $payload): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (indicador_id, ano_referencia, competencia, valor_meta_quantitativa,
                 valor_resultado, unidade_medida, tipo_lancamento, fonte_resumo,
                 observacao, status, validated_at)
             VALUES
                (:indicador_id, :ano_referencia, :competencia, :valor_meta_quantitativa,
                 :valor_resultado, :unidade_medida, 'automatico', :fonte_resumo,
                 :observacao, 'validado', CURRENT_TIMESTAMP)"
        );
        $stmt->execute([
            ':indicador_id' => $payload['indicator_id'],
            ':ano_referencia' => $payload['year'],
            ':competencia' => $payload['competence'],
            ':valor_meta_quantitativa' => $payload['target'],
            ':valor_resultado' => $payload['result'],
            ':unidade_medida' => $payload['unit'] !== '' ? $payload['unit'] : null,
            ':fonte_resumo' => 'Consultas vinculadas ao dashboard PPA.',
            ':observacao' => $payload['reference_date'] !== null
                ? 'Referencia dos dados: ' . (string) $payload['reference_date']
                : 'Sincronizacao CLI controlada.',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function finishSynchronization(int $syncId, int $inserted, string $message): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ppa_sincronizacoes
             SET status = 'concluido',
                 total_processados = 1,
                 total_inseridos = :total_inseridos,
                 mensagem = :mensagem,
                 finished_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->execute([
            ':total_inseridos' => $inserted,
            ':mensagem' => $message,
            ':id' => $syncId,
        ]);
    }
}
