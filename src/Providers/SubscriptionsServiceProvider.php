<?php
namespace Slashforward\Subscriptions\Providers;

use Illuminate\Support\ServiceProvider;
// use Slashforward\Support\Traits\ConsoleTools;
// use Slashforward\Subscriptions\Models\Plan;
// use Slashforward\Subscriptions\Models\PlanFeature;
// use Slashforward\Subscriptions\Models\PlanSubscription;
// use Slashforward\Subscriptions\Models\PlanSubscriptionUsage;
use Slashforward\Subscriptions\Console\Commands\MigrateCommand;
use Slashforward\Subscriptions\Console\Commands\PublishCommand;
use Slashforward\Subscriptions\Console\Commands\RollbackCommand;

class SubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/config.php', 'subscriptions'
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('subscriptions.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateCommand::class,
                PublishCommand::class,
                RollbackCommand::class,
            ]);
        }
    }
}
