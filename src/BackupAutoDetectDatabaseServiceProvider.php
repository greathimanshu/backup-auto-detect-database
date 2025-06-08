<?php

namespace GreatHimansh\BackupAutoDetectDatabase;

use Illuminate\Support\ServiceProvider;

class BackupAutoDetectDatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {

        $this->publishes([
            __DIR__ . '/../config/backup-detect.php' => config_path('backup-detect.php'),
        ], 'backup-auto-detect-config');
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupAutoDetectDatabase::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backup-detect.php',
            'backup-detect'
        );
    }
}
