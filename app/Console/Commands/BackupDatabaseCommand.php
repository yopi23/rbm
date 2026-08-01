<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = 'Backup database to compressed SQL file in storage/app/backups';

    public function handle()
    {
        $this->info('Starting automated database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        if (empty($database)) {
            $this->error('Database configuration is missing.');
            return 1;
        }

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . '/' . $filename;
        $gzFilePath = $filePath . '.gz';

        // Construct mysqldump command
        $passParam = !empty($password) ? '-p' . escapeshellarg($password) : '';
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $passParam,
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        $returnVar = null;
        $output = [];
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
            // Compress with gzip if available
            if (function_exists('gzopen')) {
                $fpOut = gzopen($gzFilePath, 'wb9');
                $fpIn = fopen($filePath, 'rb');

                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 512));
                }

                fclose($fpIn);
                gzclose($fpOut);
                @unlink($filePath);
                $finalPath = $gzFilePath;
            } else {
                $finalPath = $filePath;
            }

            $sizeMb = round(filesize($finalPath) / 1024 / 1024, 2);
            $msg = "Database backup created successfully: " . basename($finalPath) . " ({$sizeMb} MB)";
            $this->info($msg);
            Log::info($msg);

            // Clean up backups older than 14 days
            $files = glob($backupDir . '/backup-*');
            $now = time();
            $deletedCount = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($now - filemtime($file) >= 14 * 86400) {
                        unlink($file);
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->info("Cleaned up {$deletedCount} old backup files older than 14 days.");
            }

            return 0;
        } else {
            $msg = "Database backup failed. Command exit code: {$returnVar}";
            $this->error($msg);
            Log::error($msg);
            return 1;
        }
    }
}
