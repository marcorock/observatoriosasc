<?php

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;

class ExternalDatabaseRuntime
{
    public static function testConnection(object $source): array
    {
        try {
            self::connect($source);

            return [
                'success' => true,
                'message' => 'Conexao com a base externa realizada com sucesso.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Falha ao conectar na base externa: ' . $e->getMessage(),
            ];
        }
    }

    public static function runRegisteredQuery(object $source, object $query, int $limit = 100): array
    {
        $validation = self::validateSelectQuery((string) $query->sql_query);

        if ($validation !== true) {
            return [
                'success' => false,
                'message' => $validation,
                'columns' => [],
                'rows' => [],
                'row_count' => 0,
            ];
        }

        try {
            $pdo = self::connect($source);
            $sql = self::normalizeSql((string) $query->sql_query);
            $sql = self::applyLimit($sql, $limit);
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            return [
                'success' => true,
                'message' => 'Consulta executada com sucesso.',
                'columns' => !empty($rows) ? array_keys($rows[0]) : [],
                'rows' => $rows,
                'row_count' => count($rows),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Falha ao executar a consulta externa: ' . $e->getMessage(),
                'columns' => [],
                'rows' => [],
                'row_count' => 0,
            ];
        }
    }

    public static function validateSelectQuery(string $sql): bool|string
    {
        $normalized = self::normalizeSql($sql);

        if ($normalized === '') {
            return 'Informe a consulta SQL.';
        }

        if (!preg_match('/^select\b/i', $normalized)) {
            return 'Somente consultas iniciadas com SELECT sao permitidas neste modulo.';
        }

        if (substr_count($normalized, ';') > 0) {
            return 'Nao use multiplas instrucoes SQL nesta consulta.';
        }

        if (preg_match('/\b(insert|update|delete|drop|alter|truncate|create|grant|revoke|call)\b/i', $normalized)) {
            return 'A consulta contem comandos nao permitidos. Use apenas SELECT.';
        }

        return true;
    }

    public static function normalizeSql(string $sql): string
    {
        return trim(preg_replace('/;+\s*$/', '', $sql) ?? '');
    }

    private static function applyLimit(string $sql, int $limit): string
    {
        $safeLimit = max(1, $limit);

        if (preg_match('/\s+LIMIT\s+(?:(\d+)\s*,\s*(\d+)|(\d+)(?:\s+OFFSET\s+(\d+))?)\s*$/i', $sql, $matches)) {
            $offset = $matches[1] !== '' ? (int) $matches[1] : (int) ($matches[4] ?? 0);
            $currentLimit = $matches[2] !== '' ? (int) $matches[2] : (int) $matches[3];
            $effectiveLimit = min($safeLimit, $currentLimit);
            $sqlWithoutLimit = substr($sql, 0, -strlen($matches[0]));

            return $offset > 0
                ? "{$sqlWithoutLimit}\nLIMIT {$effectiveLimit} OFFSET {$offset}"
                : "{$sqlWithoutLimit}\nLIMIT {$effectiveLimit}";
        }

        return "{$sql}\nLIMIT {$safeLimit}";
    }

    private static function connect(object $source): PDO
    {
        $host = preg_replace('#^https?://#i', '', trim((string) $source->host));
        $port = (int) ($source->porta ?? 3306);
        $dbName = trim((string) $source->database_name);
        $user = trim((string) $source->username);
        $password = externalDbDecrypt((string) $source->password_encrypted);
        $charset = trim((string) ($source->charset ?? 'utf8mb4'));

        if ($host === '' || $dbName === '' || $user === '') {
            throw new RuntimeException('A conexao externa esta com dados incompletos.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }
}
