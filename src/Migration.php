<?php
namespace Salad\Core;

use PDO;

class Migration
{
    protected $pdo;
    protected $ROOT_DIR;

    public function __construct(PDO $pdo, $rootDir)
    {
        $this->pdo = $pdo;
        $this->ROOT_DIR = $rootDir . "/src/Migrations/";
        $this->createMigrationsTable();
    }

    protected function createMigrationsTable()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function getAppliedMigrations()
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function saveMigration($migration)
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->execute(['migration' => $migration]);
    }

    public function deleteMigration($migration)
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
    }
    
    public function runMigrations()
    {
        $appliedMigrations = $this->getAppliedMigrations();

        $files = scandir($this->ROOT_DIR);
        $toApplyMigrations = array_diff($files, $appliedMigrations);

        foreach ($toApplyMigrations as $migration) {
            if ($migration === '.' || $migration === '..') {
                continue;
            }

            require_once $this->ROOT_DIR . $migration;
            $migrationClass = pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $migrationClass();
            $instance->up($this->pdo);
            $this->saveMigration($migration);

            echo "Applied migration: $migration\n";
        }

        if (empty($toApplyMigrations)) {
            echo "All migrations are applied.\n";
        }
    }

    public function rollbackMigration()
    {
        $appliedMigrations = $this->getAppliedMigrations();
        if (empty($appliedMigrations)) {
            echo "No migrations to rollback.\n";
            return;
        }
        $lastAppliedMigration = end($appliedMigrations);

        require_once $this->ROOT_DIR . $lastAppliedMigration;
        $migrationClass = pathinfo($lastAppliedMigration, PATHINFO_FILENAME);
        $instance = new $migrationClass();
        $instance->down($this->pdo);

        $this->deleteMigration($lastAppliedMigration);

        echo "Rolled back migration: $lastAppliedMigration\n";
    }

    public function runSpecificMigration($migration)
    {
        $appliedMigrations = $this->getAppliedMigrations();
        if (in_array($migration, $appliedMigrations)) {
            echo "Migration $migration has already been applied.\n";
            return;
        }

        require_once $this->ROOT_DIR . $migration;
        $migrationClass = pathinfo($migration, PATHINFO_FILENAME);
        $instance = new $migrationClass();
        $instance->up($this->pdo);

        $this->saveMigration($migration);
        echo "Applied migration: $migration\n";
    }

    public function rollbackSpecificMigration($migration)
    {
        $appliedMigrations = $this->getAppliedMigrations();
        if (!in_array($migration, $appliedMigrations)) {
            echo "Migration $migration has not been applied.\n";
            return;
        }

        require_once $this->ROOT_DIR . $migration;
        $migrationClass = pathinfo($migration, PATHINFO_FILENAME);
        $instance = new $migrationClass();
        $instance->down($this->pdo);

        $this->deleteMigration($migration);
        echo "Rolled back migration: $migration\n";
    }

}
