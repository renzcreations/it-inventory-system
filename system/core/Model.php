<?php

namespace System\Core;

use PDO;
use PDOStatement;
use Throwable;

abstract class Model
{
    public function __construct(protected ?Database $database = null)
    {
        $this->database ??= new Database();
    }

    protected function statement(string $sql, array $parameters = []): PDOStatement
    {
        return $this->database->query($sql, $parameters);
    }

    protected function all(string $sql, array $parameters = []): array
    {
        return $this->statement($sql, $parameters)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function first(string $sql, array $parameters = []): ?array
    {
        $row = $this->statement($sql, $parameters)->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    protected function scalar(string $sql, array $parameters = []): mixed
    {
        return $this->statement($sql, $parameters)->fetchColumn();
    }

    protected function execute(string $sql, array $parameters = []): int
    {
        return $this->statement($sql, $parameters)->rowCount();
    }

    public function transaction(callable $operation): mixed
    {
        $this->database->beginTransaction();

        try {
            $result = $operation();
            $this->database->commit();
            return $result;
        } catch (Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    protected function lastInsertId(): int
    {
        return (int) $this->database->lastInsertId();
    }
}
