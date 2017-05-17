<?php namespace Bugotech\Phar;

use Illuminate\Support\ServiceProvider;

class PharServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Registrar so se nao estiver dentro no phar
        if (! $this->app->runningInPhar()) {
            $this->commands('Bugotech\Phar\Commands\CompilerCommand');
        }
    }
}
