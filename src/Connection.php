<?php

namespace Salad\Core;

use PDO;
use PDOException;

class Connection
{
    private $connection;
    private $host;
    private $dbname;
    private $dbport;
    private $username;
    private $password;
    private $charset;

    public function __construct()
    {
        // Load environment variables
        $this->loadEnv();

        // Set connection parameters
        $this->host = getenv('DB_HOST');
        $this->dbname = getenv('DB_NAME');
        $this->dbport = getenv('DB_PORT');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
        $this->charset = getenv('DB_CHARSET') ?? 'utf8mb4';

        // Establish the connection
        $this->connect();
    }

    private function loadEnv()
    {
        if (file_exists(Application::$ROOT_DIR . '/.env')) {
          $lines = file(Application::$ROOT_DIR . '/.env');
          foreach ($lines as $line) {
            if (strpos(trim($line), '=') !== false) {
              list($name, $value) = explode('=', $line, 2);
              putenv(trim($name) . '=' . trim($value));
            }
          }
        }
    }

    private function connect()
    {
      try {
        $dsn = "mysql:host={$this->host};port={$this->dbport};dbname={$this->dbname}";
        $this->connection = new PDO($dsn, $this->username, $this->password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return true;
      } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
      }
    }

    public function getConnection()
    {
      return $this->connection;
    }

    public function migrateSpecific($migration_name) {
      try {
        $migration = new Migration($this->connection, dirname(Application::$ROOT_DIR));
        $migration->runSpecificMigration($migration_name);
        return true;
      } catch (PDOException $e) {
        die('Migration failed: ' . $e->getMessage());
      }
    }

    public function migrateAll() {
      try {
        $migration = new Migration($this->connection, dirname(Application::$ROOT_DIR));
        $migration->runMigrations();
        return true;
      } catch (PDOException $e) {
        die('Migration failed: ' . $e->getMessage());
      }
    }

    public function rollbackSpecific($migration_name) {
      try {
        $migration = new Migration($this->connection, dirname(Application::$ROOT_DIR));
        $migration->rollbackSpecificMigration($migration_name);
        return true;
      } catch (PDOException $e) {
        die('Migration failed: ' . $e->getMessage());
      }
    }


    public function rollbackAll() {
      try {
        $migration = new Migration($this->connection, dirname(Application::$ROOT_DIR));
        $migration->rollbackMigration();
        return true;
      } catch (PDOException $e) {
        die('Migration failed: ' . $e->getMessage());
      }
    }

}
