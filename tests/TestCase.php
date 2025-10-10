<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\ODT;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    function odt(): ODT
    {
        $blade = new BladeCompiler(new Filesystem(), 'tests/cache');
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
