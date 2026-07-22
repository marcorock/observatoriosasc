<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;

class PpaIndicatorQueryModel
{
    private PDO $pdo;
    private string $table = 'ppa_indicador_queries';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
        new PpaIndicatorModel();
        new ExternalQueryModel();
        $this->ensureTable();
    }

    public function readAll(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT pq.id, pq.indicador_id, pq.external_query_id, pq.papel, pq.campo_resultado,
                        pq.observacao, pq.ativo, pq.created_at, pq.updated_at,
                        pi.codigo AS codigo_indicador, pi.nome AS indicador_nome,
                        eq.nome AS query_nome
                 FROM {$this->table} pq
                 INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
                 INNER JOIN external_data_queries eq ON eq.id = pq.external_query_id
                 ORDER BY pi.codigo ASC, pq.papel ASC, pq.id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar os vinculos do PPA.';
        }
    }

    public function readById(int $id): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pq.id, pq.indicador_id, pq.external_query_id, pq.papel, pq.campo_resultado,
                        pq.observacao, pq.ativo, pq.created_at, pq.updated_at,
                        pi.codigo AS codigo_indicador, pi.nome AS indicador_nome,
                        eq.nome AS query_nome
                 FROM {$this->table} pq
                 INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
                 INNER JOIN external_data_queries eq ON eq.id = pq.external_query_id
                 WHERE pq.id = :id
                 LIMIT 1"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Vinculo do PPA nao encontrado.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar o vinculo do PPA.';
        }
    }

    public function readPrimaryActiveLink(?string $indicatorCode = null): object|string
    {
        try {
            $sql = "SELECT pq.id, pq.indicador_id, pq.external_query_id, pq.papel, pq.campo_resultado,
                           pq.observacao, pq.ativo, pq.created_at, pq.updated_at,
                           pi.codigo AS codigo_indicador, pi.nome AS indicador_nome, pi.objetivo,
                           pi.unidade_medida, pi.memoria_calculo, pi.tipo_alimentacao, pi.periodicidade,
                           pi.status AS indicador_ativo,
                           eq.source_id, eq.nome AS query_nome, eq.descricao AS query_descricao, eq.ativo AS query_ativo
                    FROM {$this->table} pq
                    INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
                    INNER JOIN external_data_queries eq ON eq.id = pq.external_query_id
                    WHERE pq.ativo = 1
                      AND pi.status = 1
                      AND eq.ativo = 1";

            if ($indicatorCode !== null && trim($indicatorCode) !== '') {
                $sql .= " AND pi.codigo = :codigo_indicador";
            }

            $sql .= " ORDER BY
                        CASE pq.papel
                            WHEN 'principal' THEN 0
                            WHEN 'base' THEN 1
                            ELSE 2
                        END,
                        pi.codigo ASC,
                        pq.id ASC
                      LIMIT 1";

            $stmt = $this->pdo->prepare($sql);

            if ($indicatorCode !== null && trim($indicatorCode) !== '') {
                $stmt->bindValue(':codigo_indicador', trim($indicatorCode), PDO::PARAM_STR);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Nenhum vinculo ativo do PPA foi encontrado.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar o vinculo principal do PPA.';
        }
    }

    public function readPrimaryActiveLinkByIndicatorId(int $indicatorId): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pq.id, pq.indicador_id, pq.external_query_id, pq.papel, pq.campo_resultado,
                        pq.observacao, pq.ativo, pq.created_at, pq.updated_at,
                        pi.codigo AS codigo_indicador, pi.nome AS indicador_nome, pi.objetivo,
                        pi.unidade_medida, pi.memoria_calculo, pi.tipo_alimentacao, pi.periodicidade,
                        pi.status AS indicador_ativo,
                        eq.source_id, eq.nome AS query_nome, eq.descricao AS query_descricao, eq.ativo AS query_ativo
                 FROM {$this->table} pq
                 INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
                 INNER JOIN external_data_queries eq ON eq.id = pq.external_query_id
                 WHERE pq.indicador_id = :indicador_id
                   AND pq.ativo = 1
                   AND pi.status = 1
                   AND eq.ativo = 1
                 ORDER BY
                    CASE pq.papel
                        WHEN 'principal' THEN 0
                        WHEN 'base' THEN 1
                        ELSE 2
                    END,
                    pq.id ASC
                 LIMIT 1"
            );
            $stmt->bindValue(':indicador_id', $indicatorId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar o vinculo do indicador do PPA.';
        }
    }

    public function readActiveLinksByIndicatorId(int $indicatorId): array|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT pq.id, pq.indicador_id, pq.external_query_id, pq.papel, pq.campo_resultado,
                        pq.observacao, pq.ativo, pq.created_at, pq.updated_at,
                        pi.codigo AS codigo_indicador, pi.nome AS indicador_nome, pi.objetivo,
                        pi.unidade_medida, pi.memoria_calculo, pi.tipo_alimentacao, pi.periodicidade,
                        pi.status AS indicador_ativo,
                        eq.source_id, eq.nome AS query_nome, eq.descricao AS query_descricao, eq.sql_query, eq.ativo AS query_ativo,
                        src.nome AS source_nome
                 FROM {$this->table} pq
                 INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
                 INNER JOIN external_data_queries eq ON eq.id = pq.external_query_id
                 INNER JOIN external_data_sources src ON src.id = eq.source_id
                 WHERE pq.indicador_id = :indicador_id
                   AND pq.ativo = 1
                   AND pi.status = 1
                   AND eq.ativo = 1
                   AND src.ativo = 1
                 ORDER BY
                    CASE pq.papel
                        WHEN 'principal' THEN 0
                        WHEN 'base' THEN 1
                        ELSE 2
                    END,
                    pq.id ASC"
            );
            $stmt->bindValue(':indicador_id', $indicatorId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar os vinculos ativos do indicador do PPA.';
        }
    }

    public function create(array $data): bool|string
    {
        $payload = $this->preparePayload($data);

        if (is_string($payload)) {
            return $payload;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (indicador_id, external_query_id, papel, campo_resultado, observacao, ativo)
                 VALUES
                    (:indicador_id, :external_query_id, :papel, :campo_resultado, :observacao, :ativo)"
            );

            $this->bindPayload($stmt, $payload);

            return $stmt->execute();
        } catch (PDOException $e) {
            return 'Nao foi possivel cadastrar o vinculo do PPA.';
        }
    }

    public function updateById(int $id, array $data): bool|string
    {
        $payload = $this->preparePayload($data);

        if (is_string($payload)) {
            return $payload;
        }

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET indicador_id = :indicador_id,
                     external_query_id = :external_query_id,
                     papel = :papel,
                     campo_resultado = :campo_resultado,
                     observacao = :observacao,
                     ativo = :ativo
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindPayload($stmt, $payload);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel atualizar o vinculo do PPA.';
        }
    }

    public function deleteById(int $id): bool|string
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel excluir o vinculo do PPA.';
        }
    }

    private function preparePayload(array $data): array|string
    {
        $indicadorId = (int) ($data['indicador_id'] ?? 0);
        $externalQueryId = (int) ($data['external_query_id'] ?? 0);
        $papel = trim((string) ($data['papel'] ?? 'principal'));
        $campoResultado = trim((string) ($data['campo_resultado'] ?? ''));
        $observacao = trim((string) ($data['observacao'] ?? ''));
        $ativo = (int) ((($data['ativo'] ?? '1') === '0') ? 0 : 1);

        if ($indicadorId <= 0) {
            return 'Selecione um indicador valido.';
        }

        if ($externalQueryId <= 0) {
            return 'Selecione uma consulta externa valida.';
        }

        $allowedPapeis = ['principal', 'base', 'apoio'];

        if (!in_array($papel, $allowedPapeis, true)) {
            return 'Papel do vinculo invalido.';
        }

        if ($campoResultado !== '' && mb_strlen($campoResultado) > 120) {
            return 'Informe um campo de resultado com ate 120 caracteres.';
        }

        return [
            'indicador_id' => $indicadorId,
            'external_query_id' => $externalQueryId,
            'papel' => $papel,
            'campo_resultado' => $campoResultado !== '' ? $campoResultado : null,
            'observacao' => $observacao !== '' ? $observacao : null,
            'ativo' => $ativo,
        ];
    }

    private function bindPayload(\PDOStatement $stmt, array $payload): void
    {
        $stmt->bindValue(':indicador_id', $payload['indicador_id'], PDO::PARAM_INT);
        $stmt->bindValue(':external_query_id', $payload['external_query_id'], PDO::PARAM_INT);
        $stmt->bindValue(':papel', $payload['papel'], PDO::PARAM_STR);
        $stmt->bindValue(':campo_resultado', $payload['campo_resultado'], $payload['campo_resultado'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':observacao', $payload['observacao'], $payload['observacao'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $payload['ativo'], PDO::PARAM_INT);
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            indicador_id BIGINT UNSIGNED NOT NULL,
            external_query_id BIGINT UNSIGNED NOT NULL,
            papel ENUM('principal','base','apoio') NOT NULL DEFAULT 'principal',
            campo_resultado VARCHAR(120) NULL,
            observacao TEXT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ppa_indicador_queries_indicador_id (indicador_id),
            KEY idx_ppa_indicador_queries_external_query_id (external_query_id),
            CONSTRAINT fk_ppa_indicador_queries_indicador
                FOREIGN KEY (indicador_id) REFERENCES ppa_indicadores(id) ON DELETE CASCADE,
            CONSTRAINT fk_ppa_indicador_queries_external_query
                FOREIGN KEY (external_query_id) REFERENCES external_data_queries(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            return;
        }
    }
}
