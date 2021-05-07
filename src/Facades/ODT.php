<?php

namespace Petecoop\ODT\Facades;

use Illuminate\Support\Facades\Facade;
use Petecoop\ODT\ODT as ActualODT;

class ODT extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActualODT::class;
    }
}
