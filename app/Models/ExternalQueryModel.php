<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;

class ExternalQueryModel
{
    private PDO $pdo;
    private string $table = 'external_data_queries';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
        new ExternalDataSourceModel();
        $this->ensureTable();
    }

    public function readAll(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT q.id, q.source_id, q.nome, q.descricao, q.sql_query, q.ativo, q.created_at, q.updated_at,
                        s.nome AS source_nome
                 FROM {$this->table} q
                 INNER JOIN external_data_sources s ON s.id = q.source_id
                 ORDER BY q.nome ASC, q.id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar as consultas externas.';
        }
    }

    public function readById(int $id): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT q.id, q.source_id, q.nome, q.descricao, q.sql_query, q.ativo, q.created_at, q.updated_at,
                        s.nome AS source_nome
                 FROM {$this->table} q
                 INNER JOIN external_data_sources s ON s.id = q.source_id
                 WHERE q.id = :id
                 LIMIT 1"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Consulta externa nao encontrada.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar a consulta externa.';
        }
    }

    public function readFirstActive(): object|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT q.id, q.source_id, q.nome, q.descricao, q.sql_query, q.ativo, q.created_at, q.updated_at,
                        s.nome AS source_nome
                 FROM {$this->table} q
                 INNER JOIN external_data_sources s ON s.id = q.source_id
                 WHERE q.ativo = 1 AND s.ativo = 1
                 ORDER BY q.id ASC
                 LIMIT 1"
            );

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Nenhuma consulta externa ativa foi encontrada para alimentar o PPA.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar a consulta inicial do PPA.';
        }
    }

    public function readActiveOptions(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT q.id, q.nome, s.nome AS source_nome
                 FROM {$this->table} q
                 INNER JOIN external_data_sources s ON s.id = q.source_id
                 WHERE q.ativo = 1 AND s.ativo = 1
                 ORDER BY q.nome ASC, q.id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return [];
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
                "INSERT INTO {$this->table} (source_id, nome, descricao, sql_query, ativo)
                 VALUES (:source_id, :nome, :descricao, :sql_query, :ativo)"
            );

            $this->bindPayload($stmt, $payload);

            return $stmt->execute();
        } catch (PDOException $e) {
            return 'Nao foi possivel cadastrar a consulta externa.';
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
                 SET source_id = :source_id,
                     nome = :nome,
                     descricao = :descricao,
                     sql_query = :sql_query,
                     ativo = :ativo
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindPayload($stmt, $payload);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel atualizar a consulta externa.';
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
            return 'Nao foi possivel excluir a consulta externa.';
        }
    }

    private function preparePayload(array $data): array|string
    {
        $sourceId = (int) ($data['source_id'] ?? 0);
        $nome = trim((string) ($data['nome'] ?? ''));
        $descricao = trim((string) ($data['descricao'] ?? ''));
        $sqlQuery = trim((string) ($data['sql_query'] ?? ''));
        $ativo = (int) ((($data['ativo'] ?? '1') === '0') ? 0 : 1);

        if ($sourceId <= 0) {
            return 'Selecione uma conexao externa valida.';
        }

        if ($nome === '' || mb_strlen($nome) > 150) {
            return 'Informe um nome valido para a consulta.';
        }

        $validation = ExternalDatabaseRuntime::validateSelectQuery($sqlQuery);

        if ($validation !== true) {
            return $validation;
        }

        return [
            'source_id' => $sourceId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'sql_query' => ExternalDatabaseRuntime::normalizeSql($sqlQuery),
            'ativo' => $ativo,
        ];
    }

    private function bindPayload(\PDOStatement $stmt, array $payload): void
    {
        $stmt->bindValue(':source_id', $payload['source_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome', $payload['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $payload['descricao'], $payload['descricao'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':sql_query', $payload['sql_query'], PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $payload['ativo'], PDO::PARAM_INT);
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            source_id BIGINT UNSIGNED NOT NULL,
            nome VARCHAR(150) NOT NULL,
            descricao TEXT NULL,
            sql_query LONGTEXT NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_external_data_queries_source FOREIGN KEY (source_id) REFERENCES external_data_sources(id) ON DELETE CASCADE,
            KEY idx_external_data_queries_source_id (source_id),
            KEY idx_external_data_queries_ativo (ativo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            return;
        }
    }
}
