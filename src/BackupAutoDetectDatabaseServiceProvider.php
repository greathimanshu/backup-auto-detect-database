<?php
namespace GreatHimansh\BackupAutoDetectDatabase;

use Illuminate\Support\ServiceProvider;

class BackupAutoDetectDatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupAutoDetectDatabase::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
