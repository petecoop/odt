<?php

namespace Petecoop\ODT;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(ODT::class, function ($app) {
            return new ODT($app->make(BladeCompiler::class), $app->make(Factory::class)->getShared());
        });
    }

    public function provides()
    {
        return [ODT::class];
    }
}
