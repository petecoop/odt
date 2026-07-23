<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Orchestra\Testbench\TestCase as Orchestra;
use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\ODT;

class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [\Petecoop\ODT\ServiceProvider::class];
    }

    function odt(): ODT
    {
        $blade = new BladeCompiler(new Filesystem, 'tests/cache');

        return new ODT($blade);
    }

    function template(string $xml): OdtFile
    {
        $template = new OdtFile('');
        $template->setContent($xml);
        $template->setStyles('');

        return $template;
    }
}
