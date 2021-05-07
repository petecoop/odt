<?php

namespace Petecoop\ODT;

class ODT
{
    private Compiler $compiler;

    public function __construct($bladeCompiler)
    {
        $this->compiler = new Compiler($bladeCompiler);
    }

    public function open(string $path)
    {
        return new Template($path, $this->compiler);
    }
}
