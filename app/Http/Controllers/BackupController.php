<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('backups.index');
    }

    public function download(): Response
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        $timestamp = now()->format('Ymd_His');
        $filename = "cms_backup_{$timestamp}.sql";
        $sql = "-- Crime Mapping System backup\n-- Database: {$database}\n-- Created: " . now()->toDateTimeString() . "\n\n";

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            abort(422, 'Backup download currently supports MySQL, MariaDB, and SQLite connections.');
        }

        $tables = $driver === 'sqlite'
            ? collect($connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name')
            : collect($connection->select('SHOW TABLES'))->map(fn ($row) => array_values((array) $row)[0]);

        foreach ($tables as $table) {
            $rows = $connection->table($table)->get();
            $sql .= "\n-- Table: {$table}\n";

            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = collect(array_keys($data))->map(fn ($column) => "`{$column}`")->implode(', ');
                $values = collect(array_values($data))
                    ->map(fn ($value) => $value === null ? 'NULL' : $connection->getPdo()->quote((string) $value))
                    ->implode(', ');
                $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES ({$values});\n";
            }
        }

        Audit::log('backup.downloaded');

        return response($sql, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
