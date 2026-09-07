<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:backup {--path= : Optional backup directory}', function () {
    $connection = config('database.default');
    $database = config("database.connections.{$connection}.database");
    $backupPath = $this->option('path') ?: storage_path('app/backups');
    $timestamp = now()->format('Ymd_His');

    File::ensureDirectoryExists($backupPath);

    if ($connection === 'sqlite') {
        if (! $database || ! File::exists($database)) {
            $this->error('SQLite database file was not found.');

            return self::FAILURE;
        }

        $target = $backupPath . DIRECTORY_SEPARATOR . "database_{$timestamp}.sqlite";
        File::copy($database, $target);
        $this->info("Database backup created: {$target}");

        return self::SUCCESS;
    }

    if (! in_array($connection, ['mysql', 'mariadb'], true)) {
        $this->error("Unsupported backup connection [{$connection}]. Configure server-native backups for this driver.");

        return self::FAILURE;
    }

    $target = $backupPath . DIRECTORY_SEPARATOR . "{$database}_{$timestamp}.sql";
    $command = [
        env('MYSQLDUMP_PATH', 'mysqldump'),
        '--host=' . config("database.connections.{$connection}.host"),
        '--port=' . config("database.connections.{$connection}.port"),
        '--user=' . config("database.connections.{$connection}.username"),
        '--single-transaction',
        '--routines',
        '--triggers',
    ];

    $password = config("database.connections.{$connection}.password");
    if ($password !== null && $password !== '') {
        $command[] = '--password=' . $password;
    }

    $command[] = $database;

    $process = new Process($command);
    $process->setTimeout(300);
    $process->run();

    if (! $process->isSuccessful()) {
        $this->error(trim($process->getErrorOutput()) ?: 'Database backup failed.');

        return self::FAILURE;
    }

    File::put($target, $process->getOutput());
    $this->info("Database backup created: {$target}");

    return self::SUCCESS;
})->purpose('Create a timestamped database backup for deployment operations');

Artisan::command('crimes:sync-barangays-from-pins {--write : Apply the detected barangay corrections}', function () {
    $write = (bool) $this->option('write');
    $locator = app(\App\Http\Controllers\CrimeMapController::class);
    $query = \App\Models\Crime::with('barangay')
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->orderBy('case_number');

    $changes = 0;

    $query->each(function (\App\Models\Crime $crime) use ($locator, $write, &$changes) {
        $match = $locator->detectBarangayByCoordinates((float) $crime->latitude, (float) $crime->longitude);

        if (empty($match['id']) || (int) $match['id'] === (int) $crime->barangay_id) {
            return;
        }

        $changes++;
        $from = $crime->barangay?->name ?? "Barangay #{$crime->barangay_id}";
        $to = $match['name'] ?? "Barangay #{$match['id']}";
        $this->line("{$crime->case_number}: {$from} -> {$to}");

        if ($write) {
            $crime->forceFill(['barangay_id' => $match['id']])->save();
        }
    });

    if ($changes === 0) {
        $this->info('No mismatched pinned incidents were found.');

        return self::SUCCESS;
    }

    $message = $write
        ? "Updated {$changes} pinned incident(s)."
        : "Found {$changes} pinned incident(s) to update. Re-run with --write to apply.";

    $this->info($message);

    return self::SUCCESS;
})->purpose('Sync incident barangays from their pinned latitude and longitude');
