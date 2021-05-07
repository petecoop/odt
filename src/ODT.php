<?php

namespace Petecoop\ODT;

class ODT
{
    private Compiler $compiler;

    public function __construct($bladeCompiler, array $globalArgs = [])
    {
        $this->compiler = new Compiler($bladeCompiler, $globalArgs);
    }

    public function open(string $path)
    {
        return new Template($path, $this->compiler);
    }
}
