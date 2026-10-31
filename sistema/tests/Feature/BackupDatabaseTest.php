<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/backups');
        File::deleteDirectory($this->directory);

        config([
            'backup.path' => $this->directory,
            'backup.retention_days' => 14,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_command_creates_a_valid_sqlite_backup(): void
    {
        $this->artisan('backup:database')->assertSuccessful();

        $files = File::glob($this->directory.DIRECTORY_SEPARATOR.'portal-ventas-*.sqlite');

        $this->assertCount(1, $files);
        $this->assertGreaterThan(0, File::size($files[0]));

        $connection = new PDO('sqlite:'.$files[0]);

        $this->assertSame('ok', $connection->query('PRAGMA integrity_check')->fetchColumn());
    }

    public function test_command_deletes_expired_backups_and_keeps_recent_ones(): void
    {
        File::ensureDirectoryExists($this->directory);

        $expired = $this->directory.DIRECTORY_SEPARATOR.'portal-ventas-2000-01-01_000000_000000.sqlite';
        $recent = $this->directory.DIRECTORY_SEPARATOR.'portal-ventas-2026-10-20_000000_000000.sqlite';

        File::put($expired, 'respaldo viejo');
        File::put($recent, 'respaldo reciente');

        touch($expired, now()->subDays(20)->getTimestamp());
        touch($recent, now()->subDays(2)->getTimestamp());

        $this->artisan('backup:database')->assertSuccessful();

        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($recent);
    }
}
