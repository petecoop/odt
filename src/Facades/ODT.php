<?php

namespace Petecoop\ODT\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Petecoop\ODT\Files\OdtFile open(string $path)
 * @method static \Petecoop\ODT\ODT officeBinary(string $binaryPath)
 * @method static \Petecoop\ODT\ODT gotenberg(string $url)
 * @method static \Petecoop\ODT\Compiler compiler()
 * @method static \Petecoop\ODT\Converters`Coverter converter()
 */
class ODT extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Petecoop\ODT\ODT::class;
    }
}
