<?php

namespace App\Providers;

use App\Services\Admin\Courses\CourseDataService;
use App\Services\Admin\Courses\CoursePaymentService;
use App\Services\Admin\Courses\CourseService;
use Illuminate\Support\ServiceProvider;

class CourseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(CourseService::class, function ($app) {
            return new CourseService();
        });

        $this->app->bind(CourseDataService::class, function ($app) {
            return new CourseDataService();
        });

        $this->app->bind(CoursePaymentService::class, function ($app) {
            return new CoursePaymentService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
