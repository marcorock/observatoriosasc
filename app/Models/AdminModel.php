<?php

namespace App\Models;

use App\Database\DataConect;
use PDO;
use PDOException;

class AdminModel
{
    private PDO $pdo;
    private string $table = 'admin_users';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
    }

    public function findActiveByCpf(string $cpf): ?object
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nome, cpf, senha_hash, ativo
                 FROM {$this->table}
                 WHERE cpf = :cpf AND ativo = 1
                 LIMIT 1"
            );

            $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function readAll(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, nome, cpf, ativo, created_at, updated_at
                 FROM {$this->table}
                 ORDER BY nome ASC, id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Não foi possível carregar os usuários administrativos.';
        }
    }

    public function readById(int $id): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nome, cpf, ativo, created_at, updated_at
                 FROM {$this->table}
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Usuário administrativo não encontrado.';
        } catch (PDOException $e) {
            return 'Não foi possível carregar o usuário administrativo.';
        }
    }

    public function countAll(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function create(array $data): bool|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (nome, cpf, senha_hash, ativo)
                 VALUES (:nome, :cpf, :senha_hash, :ativo)"
            );

            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':cpf', $data['cpf'], PDO::PARAM_STR);
            $stmt->bindValue(':senha_hash', $data['senha_hash'], PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'], PDO::PARAM_INT);

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Não foi possível cadastrar o usuário administrativo.';
        }
    }

    public function updateById(int $id, array $data): bool|string
    {
        try {
            $fields = [
                'nome = :nome',
                'cpf = :cpf',
                'ativo = :ativo',
            ];

            if (!empty($data['senha_hash'])) {
                $fields[] = 'senha_hash = :senha_hash';
            }

            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET " . implode(', ', $fields) . "
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':cpf', $data['cpf'], PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'], PDO::PARAM_INT);

            if (!empty($data['senha_hash'])) {
                $stmt->bindValue(':senha_hash', $data['senha_hash'], PDO::PARAM_STR);
            }

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Não foi possível atualizar o usuário administrativo.';
        }
    }

    public function deleteById(int $id): bool|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table}
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Não foi possível excluir o usuário administrativo.';
        }
    }

    public function cpfExists(string $cpf, ?int $ignoreId = null): bool
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE cpf = :cpf";

            if ($ignoreId !== null) {
                $sql .= " AND id <> :ignore_id";
            }

            $sql .= " LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);

            if ($ignoreId !== null) {
                $stmt->bindValue(':ignore_id', $ignoreId, PDO::PARAM_INT);
            }

            $stmt->execute();

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }
}
