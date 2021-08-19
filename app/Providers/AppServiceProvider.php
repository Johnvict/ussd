<?php

namespace App\Providers;

use App\Services\APICaller;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    use APICaller;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function boot() {
        APICaller::init();
    }
    public function register()
    {
        //
    }
}
