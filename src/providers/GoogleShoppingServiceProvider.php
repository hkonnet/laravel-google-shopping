<?php

namespace Hkonnet\LaravelGoogleShopping\Providers;

use Illuminate\Support\ServiceProvider;

class GoogleShoppingServiceProvider extends ServiceProvider {
    public function boot(){
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/google_shopping.php' => config_path('google_shopping.php'),
        ],'google-shopping');
    }

    public function register(){

    }
}