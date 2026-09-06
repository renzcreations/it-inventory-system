<?php
namespace System\Core;

class Database {
    protected $connection;

    public function __construct() {
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dsn = "mysql:host={$_ENV['DB_HOST']};port={$port};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            $this->connection = new \PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                $options
            );
        } catch (\PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            exit('The service is temporarily unavailable.');
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->inTransaction()
            ? $this->connection->rollBack()
            : false;
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
