<?php

namespace Petecoop\ODT;

use Illuminate\View\Compilers\BladeCompiler;

class ODT
{
    private Compiler $compiler;

    public function __construct(BladeCompiler $bladeCompiler, array $globalArgs = [])
    {
        $this->compiler = new Compiler($bladeCompiler, $globalArgs);
    }

    public function open(string $path)
    {
        return new Template($path, $this->compiler);
    }
}
