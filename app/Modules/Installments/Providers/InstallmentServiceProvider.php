<?php

namespace App\Modules\Installments\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Installments\Contracts\InstallmentServiceInterface;
use App\Modules\Installments\Contracts\InstallmentRepositoryInterface;
use App\Modules\Installments\Contracts\InstallmentPlanRepositoryInterface;
use App\Modules\Installments\Contracts\InstallmentCalculatorInterface;
use App\Modules\Installments\Services\InstallmentManager;
use App\Modules\Installments\Services\NullInstallmentManager;
use App\Modules\Installments\Services\InstallmentCalculator;
use App\Modules\Installments\Repositories\InstallmentRepository;
use App\Modules\Installments\Repositories\InstallmentPlanRepository;

class InstallmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar archivo de configuración
        $this->mergeConfigFrom(
            __DIR__.'/../Config/installments.php',
            'installments'
        );

        // Registrar repositorios
        $this->app->singleton(InstallmentRepositoryInterface::class, InstallmentRepository::class);
        $this->app->singleton(InstallmentPlanRepositoryInterface::class, InstallmentPlanRepository::class);

        // Registrar calculator
        $this->app->singleton(InstallmentCalculatorInterface::class, InstallmentCalculator::class);

        // Registrar servicio principal con binding condicional
        $this->app->singleton(InstallmentServiceInterface::class, function ($app) {
            // Verificar si el sistema está habilitado
            if (config('installments.enabled', true)) {
                return new InstallmentManager(
                    $app->make(InstallmentRepositoryInterface::class),
                    $app->make(InstallmentPlanRepositoryInterface::class),
                    $app->make(InstallmentCalculatorInterface::class)
                );
            }

            // Si está deshabilitado, usar implementación nula
            return new NullInstallmentManager();
        });

        // Alias para fácil acceso
        $this->app->alias(InstallmentServiceInterface::class, 'installments');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/installments.php' => config_path('installments.php'),
        ], 'installments-config');

        // Registrar comandos si están habilitados
        if ($this->app->runningInConsole()) {
            // Los comandos se registrarán aquí si es necesario
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            InstallmentServiceInterface::class,
            InstallmentRepositoryInterface::class,
            InstallmentPlanRepositoryInterface::class,
            InstallmentCalculatorInterface::class,
            'installments',
        ];
    }
}
