<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('backups.index');
    }

    public function download(): StreamedResponse
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        $timestamp = now()->format('Ymd_His');
        $filename = "cms_backup_{$timestamp}.sql";
        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            abort(422, 'Backup download currently supports MySQL, MariaDB, and SQLite connections.');
        }

        $tables = $driver === 'sqlite'
            ? collect($connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name')
            : collect($connection->select('SHOW TABLES'))->map(fn ($row) => array_values((array) $row)[0]);

        Audit::log('backup.downloaded');

        return response()->streamDownload(function () use ($connection, $driver, $database, $tables): void {
            echo "-- Crime Mapping System backup\n-- Database: {$database}\n-- Created: " . now()->toDateTimeString() . "\n\n";
            echo $driver === 'sqlite' ? "PRAGMA foreign_keys = OFF;\n" : "SET FOREIGN_KEY_CHECKS=0;\n";

            foreach ($tables as $table) {
                $quotedTable = $this->quoteIdentifier($table, $driver);
                $schema = $this->tableSchema($connection, $table, $driver);

                echo "\n-- Table: {$table}\nDROP TABLE IF EXISTS {$quotedTable};\n";
                echo rtrim($schema, ';') . ";\n";

                foreach ($connection->table($table)->cursor() as $row) {
                    $data = (array) $row;
                    $columns = collect(array_keys($data))
                        ->map(fn ($column) => $this->quoteIdentifier($column, $driver))
                        ->implode(', ');
                    $values = collect(array_values($data))
                        ->map(fn ($value) => $value === null ? 'NULL' : $connection->getPdo()->quote((string) $value))
                        ->implode(', ');

                    echo "INSERT INTO {$quotedTable} ({$columns}) VALUES ({$values});\n";
                }
            }

            if ($driver === 'sqlite') {
                $indexes = $connection->select("SELECT sql FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL");

                foreach ($indexes as $index) {
                    echo rtrim($index->sql, ';') . ";\n";
                }
            }

            echo $driver === 'sqlite' ? "PRAGMA foreign_keys = ON;\n" : "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function tableSchema($connection, string $table, string $driver): string
    {
        if ($driver === 'sqlite') {
            $schema = $connection->selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            );

            return $schema->sql;
        }

        $definition = (array) $connection->selectOne('SHOW CREATE TABLE ' . $this->quoteIdentifier($table, $driver));

        return array_values($definition)[1];
    }

    private function quoteIdentifier(string $identifier, string $driver): string
    {
        if ($driver === 'sqlite') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
