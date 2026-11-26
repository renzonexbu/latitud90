<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy as ZiggyZiggy;
use Tightenco\Ziggy\Ziggy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Optimización de rutas para entornos de producción
        if ($this->app->environment('production')) {
            $this->app->singleton(\Illuminate\Routing\UrlGenerator::class, function ($app) {
                $routes = $app['router']->getRoutes();
                $app = $app->rebinding(
                    'request',
                    $this->requestRebinder()
                );

                $url = new \Illuminate\Routing\UrlGenerator(
                    $routes, $app->make('request')
                );

                // Forzar HTTPS en producción
                $url->forceScheme('https');

                return $url;
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar timezone global
        date_default_timezone_set('America/Santiago');

        $this->registerDocumentSearch();
        
        // Optimización de memoria
        if ($this->app->isProduction()) {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading(!$this->app->isProduction());
            \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes(!$this->app->isProduction());
            // El método preventsModelEvents no existe, lo comentamos
            // \Illuminate\Database\Eloquent\Model::preventsModelEvents($this->app->isProduction());
        }

        // Optimización de Inertia
        \Inertia\Inertia::share([
            'appName' => config('app.name'),
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                    'info' => session('info'),
                ];
            },
            'auth' => function () {
                return [
                    'user' => auth()->user() ? [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                    ] : null,
                ];
            },
        ]);

        // Compartimos la configuración de Ziggy
        \Inertia\Inertia::share('ziggy', function () {
            return array_merge((new ZiggyZiggy())->toArray(), [
                'location' => request()->url(),
            ]);
        });
    }

    /**
     * Búsqueda por documento (RUT, pasaporte, DNI) sin importar el formato.
     *
     *     $q->whereDocumentLike('p.document_number', $busqueda)
     *     $q->orWhereDocumentLike('document_number', $busqueda)
     *
     * En la base los documentos están guardados en formatos mezclados
     * (12.345.678-9, 123456789) y muchos buscadores comparaban el texto tal cual,
     * así que un participante aparecía o no según cómo se tipeara el RUT
     * (Liliam 2026-11-26). Se quitan puntos, guiones y espacios de los dos lados;
     * las letras se conservan para no romper la búsqueda de pasaportes.
     */
    private function registerDocumentSearch(): void
    {
        \Illuminate\Database\Query\Builder::macro('whereDocumentLike', function (string $column, ?string $term, string $boolean = 'and') {
            // La columna va dentro del SQL: solo se aceptan nombres simples.
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
                throw new \InvalidArgumentException("Columna de documento inválida: {$column}");
            }

            $limpio = preg_replace('/[.\-\s]/', '', (string) $term);
            if ($limpio === '') {
                return $this;
            }

            return $this->whereRaw(
                "REPLACE(REPLACE(REPLACE({$column}, '.', ''), '-', ''), ' ', '') LIKE ?",
                ['%' . $limpio . '%'],
                $boolean
            );
        });

        \Illuminate\Database\Query\Builder::macro('orWhereDocumentLike', function (string $column, ?string $term) {
            return $this->whereDocumentLike($column, $term, 'or');
        });
    }
}
