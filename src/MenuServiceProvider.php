<?php

namespace Crm\Menu;

use Crm\Menu\Console\MenuCommand;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    protected $commands = [
        MenuCommand::class
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/menu.php' => config_path('menu.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
