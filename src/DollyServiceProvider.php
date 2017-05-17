<?php

namespace Devswert\Dolly;

use Illuminate\Support\ServiceProvider;

class DollyServiceProvider extends ServiceProvider{
    
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(){
        $this->publishes([
            __DIR__.'/../config/dolly.php' => config_path('config.php'),
        ], 'config');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(){
    }
}