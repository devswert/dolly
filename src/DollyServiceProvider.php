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
        $this->mergeConfigFrom(__DIR__.'/../config/dolly.php', 'dolly');

        if (! class_exists('CreateWebpayLogsTable')) {
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__.'/../migrations/create_webpay_logs_table.php.stub' => database_path("/migrations/{$timestamp}_create_webpay_logs_table.php"),
            ], 'migrations');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(){
    }
}