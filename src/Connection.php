<?php

namespace Salad\Core;

use PDO;
use PDOException;

class Connection
{
    private PDO $connection;
    private string $host;
    private string $dbname;
    private string $dbport;
    private string $username;
    private string $password;
    private string $charset;

    public function __construct()
    {
        $this->loadEnv();
        $this->initializeDbConfig();
        $this->connect();
    }

    private function loadEnv(): void
    {
        $envFilePath = Application::$ROOT_DIR . '/.env';
        if (file_exists($envFilePath)) {
            $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '=') !== false) {
                    [$name, $value] = explode('=', $line, 2);
                    putenv(trim($name) . '=' . trim($value));
                }
            }
        }
    }

    private function initializeDbConfig(): void
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'default_db';
        $this->dbport = getenv('DB_PORT') ?: '3306';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';
    }

    private function connect(): void
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->dbport};dbname={$this->dbname};charset={$this->charset}";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function migrateSpecific(string $migrationName): bool
    {
        return $this->runMigration('runSpecificMigration', $migrationName);
    }

    public function migrateAll(): bool
    {
        return $this->runMigration('runMigrations');
    }

    public function rollbackSpecific(string $migrationName): bool
    {
        return $this->runMigration('rollbackSpecificMigration', $migrationName);
    }

    public function rollbackAll(): bool
    {
        return $this->runMigration('rollbackMigration');
    }

    private function runMigration(string $method, ?string $migrationName = null): bool
    {
        try {
            $migration = new Migration($this->connection, dirname(Application::$ROOT_DIR));
            if ($migrationName !== null) {
                $migration->{$method}($migrationName);
            } else {
                $migration->{$method}();
            }
            return true;
        } catch (PDOException $e) {
            throw new \RuntimeException('Migration failed: ' . $e->getMessage());
        }
    }
}
