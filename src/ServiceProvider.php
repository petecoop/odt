<?php

namespace Petecoop\ODT;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->bind(ODT::class, function ($app) {
            return new ODT($app[BladeCompiler::class], $app[Factory::class]->getShared());
        });
    }
}
