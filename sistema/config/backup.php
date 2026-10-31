<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Carpeta de respaldos
    |--------------------------------------------------------------------------
    |
    | Cada respaldo es una copia consistente de la base SQLite creada con
    | VACUUM INTO. La carpeta debe estar fuera del directorio público.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Retención
    |--------------------------------------------------------------------------
    |
    | Cantidad de días que se conservan los respaldos antes de eliminarlos.
    |
    */

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
];
