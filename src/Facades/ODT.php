<?php

declare(strict_types=1);

namespace Petecoop\ODT\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Petecoop\ODT\Files\OdtFile open(string $path)
 * @method static \Petecoop\ODT\ODT officeBinary(string $binaryPath)
 * @method static \Petecoop\ODT\ODT gotenberg(string $url)
 * @method static \Petecoop\ODT\Compiler compiler()
 * @method static \Petecoop\ODT\Converters\Converter converter()
 */
class ODT extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Petecoop\ODT\ODT::class;
    }
}
