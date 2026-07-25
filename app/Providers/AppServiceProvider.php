<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerSpreadsheetFallback();
    }

    /**
     * Mantiene las importaciones Excel disponibles en esta instalación local.
     * En despliegues con `composer install`, el autoloader oficial tiene
     * prioridad y este bloque no interviene.
     */
    private function registerSpreadsheetFallback(): void
    {
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return;
        }

        $loader = new ClassLoader();
        $loader->addPsr4('PhpOffice\\PhpSpreadsheet\\', base_path('vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet'));
        $loader->addPsr4('Matrix\\', base_path('vendor/markbaker/matrix/classes/src'));
        $loader->addPsr4('Complex\\', base_path('vendor/markbaker/complex/classes/src'));
        $loader->addPsr4('ZipStream\\', base_path('vendor/maennchen/zipstream-php/src'));
        $loader->addPsr4('Composer\\Pcre\\', base_path('vendor/composer-pcre/src'));
        $loader->register(prepend: true);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
