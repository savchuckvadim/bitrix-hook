<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema as FacadesSchema;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        FacadesSchema::defaultStringLength(191);

        // Страховочный таймаут для всех исходящих Http-запросов:
        // без него зависший внешний сервис держит воркер php-fpm бесконечно.
        // Точечный ->timeout() у конкретного вызова имеет приоритет.
        Http::globalOptions([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }
}
