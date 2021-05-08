<?php

namespace Petecoop\ODT\Compilers;

interface Compiler
{
    public function compile(string $value): string;
}
