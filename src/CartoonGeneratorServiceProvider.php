<?php
namespace AcmeCorp\CartoonGenerator;

use Illuminate\Support\ServiceProvider;

class CartoonGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cartoon_generator.php', 'cartoon_generator');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cartoon_generator.php' => config_path('cartoon_generator.php'),
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'cartoon-generator');
        }
    }
}
