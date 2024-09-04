<?php

namespace Salad\Core;

use PDO;
use PDOException;
use Salad\Core\Connection;

class Database
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();  // Use the existing Database class to get the PDO connection
        $this->connection = $db->getConnection();
    }

    /**
     * Execute a query and return the result set.
     *
     * @param string $sql The SQL query.
     * @param array $params Optional parameters for prepared statements.
     * @return bool|\PDOStatement
     */
    public function query(string $sql, array $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Handle query error
            echo 'Query failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Fetch a single row from the database.
     *
     * @param string $sql The SQL query.
     * @param array $params Optional parameters for prepared statements.
     * @return array|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    }

    /**
     * Fetch all rows from the database.
     *
     * @param string $sql The SQL query.
     * @param array $params Optional parameters for prepared statements.
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE statement.
     *
     * @param string $sql The SQL query.
     * @param array $params Parameters for prepared statements.
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params) !== false;
    }

    /**
     * Start a database transaction.
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Rollback the current transaction.
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * @return string The last insert ID.
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
