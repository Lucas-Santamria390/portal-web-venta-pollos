<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una copia de la base SQLite y elimina los respaldos vencidos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = (string) config('backup.path');

        if ($directory === '') {
            $this->error('No se configuró la carpeta de respaldos.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($directory);

        $filename = 'portal-ventas-'.now()->format('Y-m-d_His_u').'.sqlite';
        $destination = $directory.DIRECTORY_SEPARATOR.$filename;

        $connection = DB::connection();
        $quotedDestination = $connection->getPdo()->quote($destination);

        $connection->statement("VACUUM INTO {$quotedDestination}");

        $this->deleteExpiredBackups($directory);

        $this->info("Respaldo creado: {$filename}");

        return self::SUCCESS;
    }

    /**
     * Delete backups older than the configured retention period.
     */
    private function deleteExpiredBackups(string $directory): void
    {
        $retentionDays = (int) config('backup.retention_days');
        $expiration = now()->subDays($retentionDays)->getTimestamp();

        foreach (File::glob($directory.DIRECTORY_SEPARATOR.'portal-ventas-*.sqlite') as $file) {
            if (File::lastModified($file) < $expiration) {
                File::delete($file);
            }
        }
    }
}
