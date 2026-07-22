<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * DataConect - Gerenciador de Conexão com Banco de Dados
 * 
 * Implementa o padrão Singleton para garantir que apenas uma conexão
 * PDO seja aberta durante toda a execução da aplicação.
 */
class DataConect
{
    private static ?PDO $instance = null;

    private function __construct() {}

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Não é permitido desserializar o Singleton");
    }

    /**
     * Obter instância única da conexão com banco de dados
     * Carrega as configurações do arquivo .env
     * 
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Criar nova conexão PDO com base nas variáveis de ambiente
     * 
     * @return PDO
     * @throws PDOException
     */
    private static function createConnection(): PDO
    {
        try {

            // Obter configurações do banco de dados
            $host = DB_HOST ?? 'localhost';
            $port = DB_PORT ?? '3306';
            $database = DB_NAME ?? 'observatoriosasc';
            $username = DB_USER ?? 'root';
            $password = DB_PASS ?? '';
            $charset = DB_CHARSET ?? 'utf8mb4';

            // Remover protocolo caso o valor do host tenha sido configurado com http:// ou https://
            $host = preg_replace('#^https?://#i', '', trim($host));

            // Montar string de conexão DSN
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

            // Criar instância PDO com configurações de erro
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;

        } catch (PDOException $e) {
            throw new PDOException("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
}