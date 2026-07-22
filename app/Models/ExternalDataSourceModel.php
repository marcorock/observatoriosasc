<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;
use RuntimeException;

class ExternalDataSourceModel
{
    private PDO $pdo;
    private string $table = 'external_data_sources';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
        $this->ensureTable();
    }

    public function readAll(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, nome, host, porta, database_name, username, charset, descricao, ativo, created_at, updated_at
                 FROM {$this->table}
                 ORDER BY nome ASC, id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar as conexoes externas.';
        }
    }

    public function readActiveOptions(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, nome
                 FROM {$this->table}
                 WHERE ativo = 1
                 ORDER BY nome ASC, id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function readById(int $id): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nome, host, porta, database_name, username, password_encrypted, charset, descricao, ativo, created_at, updated_at
                 FROM {$this->table}
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Conexao externa nao encontrada.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar a conexao externa.';
        }
    }

    public function create(array $data): bool|string
    {
        try {
            $payload = $this->preparePayload($data, true);

            if (is_string($payload)) {
                return $payload;
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (nome, host, porta, database_name, username, password_encrypted, charset, descricao, ativo)
                 VALUES (:nome, :host, :porta, :database_name, :username, :password_encrypted, :charset, :descricao, :ativo)"
            );

            $this->bindPayload($stmt, $payload);

            return $stmt->execute();
        } catch (PDOException $e) {
            return 'Nao foi possivel cadastrar a conexao externa.';
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
    }

    public function updateById(int $id, array $data): bool|string
    {
        $existing = $this->readById($id);

        if (is_string($existing)) {
            return $existing;
        }

        try {
            $payload = $this->preparePayload($data, false, (string) $existing->password_encrypted);

            if (is_string($payload)) {
                return $payload;
            }

            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET nome = :nome,
                     host = :host,
                     porta = :porta,
                     database_name = :database_name,
                     username = :username,
                     password_encrypted = :password_encrypted,
                     charset = :charset,
                     descricao = :descricao,
                     ativo = :ativo
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindPayload($stmt, $payload);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel atualizar a conexao externa.';
        } catch (RuntimeException $e) {
            return $e->getMessage();
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
            return 'Nao foi possivel excluir a conexao externa.';
        }
    }

    private function preparePayload(array $data, bool $requirePassword, string $existingEncryptedPassword = ''): array|string
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $host = trim((string) ($data['host'] ?? ''));
        $porta = (int) ($data['porta'] ?? 3306);
        $databaseName = trim((string) ($data['database_name'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $charset = trim((string) ($data['charset'] ?? 'utf8mb4'));
        $descricao = trim((string) ($data['descricao'] ?? ''));
        $ativo = (int) ((($data['ativo'] ?? '1') === '0') ? 0 : 1);

        if ($nome === '' || mb_strlen($nome) > 150) {
            return 'Informe um nome valido para a conexao externa.';
        }

        if ($host === '' || mb_strlen($host) > 190) {
            return 'Informe um host valido para a conexao externa.';
        }

        if ($databaseName === '' || mb_strlen($databaseName) > 120) {
            return 'Informe o nome do banco externo.';
        }

        if ($username === '' || mb_strlen($username) > 120) {
            return 'Informe o usuario de acesso ao banco externo.';
        }

        if ($porta < 1 || $porta > 65535) {
            return 'Informe uma porta valida para a conexao externa.';
        }

        if ($charset === '' || mb_strlen($charset) > 40) {
            return 'Informe um charset valido para a conexao externa.';
        }

        if ($requirePassword && trim($password) === '') {
            return 'Informe a senha da conexao externa.';
        }

        $encryptedPassword = $existingEncryptedPassword;

        if (trim($password) !== '') {
            $encryptedPassword = externalDbEncrypt($password);
        }

        if ($encryptedPassword === '') {
            return 'A senha da conexao externa nao foi informada.';
        }

        return [
            'nome' => $nome,
            'host' => $host,
            'porta' => $porta,
            'database_name' => $databaseName,
            'username' => $username,
            'password_encrypted' => $encryptedPassword,
            'charset' => $charset,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ativo' => $ativo,
        ];
    }

    private function bindPayload(\PDOStatement $stmt, array $payload): void
    {
        $stmt->bindValue(':nome', $payload['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':host', $payload['host'], PDO::PARAM_STR);
        $stmt->bindValue(':porta', $payload['porta'], PDO::PARAM_INT);
        $stmt->bindValue(':database_name', $payload['database_name'], PDO::PARAM_STR);
        $stmt->bindValue(':username', $payload['username'], PDO::PARAM_STR);
        $stmt->bindValue(':password_encrypted', $payload['password_encrypted'], PDO::PARAM_STR);
        $stmt->bindValue(':charset', $payload['charset'], PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $payload['descricao'], $payload['descricao'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $payload['ativo'], PDO::PARAM_INT);
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            host VARCHAR(190) NOT NULL,
            porta INT NOT NULL DEFAULT 3306,
            database_name VARCHAR(120) NOT NULL,
            username VARCHAR(120) NOT NULL,
            password_encrypted TEXT NOT NULL,
            charset VARCHAR(40) NOT NULL DEFAULT 'utf8mb4',
            descricao TEXT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            return;
        }
    }
}
