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
        $this->commands('Bugotech\Phar\Commands\CompilerCommand');
    }
}
