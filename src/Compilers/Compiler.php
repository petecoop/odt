<?php

declare(strict_types=1);

namespace Petecoop\ODT\Compilers;

interface Compiler
{
    public function compile(string $value): string;
}
