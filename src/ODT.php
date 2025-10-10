<?php

namespace Petecoop\ODT;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Petecoop\ODT\Converters\Converter;
use Petecoop\ODT\Converters\GotenbergConverter;
use Petecoop\ODT\Converters\OfficeConverter;
use Petecoop\ODT\Files\OdtFile;

class ODT
{
    protected Compiler $compiler;
    protected Converter $converter;

    public function __construct(BladeCompiler $bladeCompiler, array $globalArgs = [])
    {
        $this->compiler = new Compiler($bladeCompiler, $globalArgs);
        $this->converter = new OfficeConverter();
    }

    /**
     * Create a new instance of ODT
     * For use outside of Laravel.
     */
    public static function make(null|string $bin = null): self
    {
        $blade = new BladeCompiler(new Filesystem(), '/', false);
        $odt = new self($blade);
        if ($bin) {
            $odt->officeBinary($bin);
        }
        return $odt;
    }

    public function open(string $path)
    {
        $template = new OdtFile();
        $template->open($path);
        $template->setInstance($this);

        return $template;
    }

    public function compiler(): Compiler
    {
        return $this->compiler;
    }

    public function converter(): Converter
    {
        return $this->converter;
    }

    public function officeBinary(string $binaryPath): self
    {
        if ($this->converter instanceof OfficeConverter) {
            $this->converter->setBinaryPath($binaryPath);
        } else {
            $this->converter = new OfficeConverter($binaryPath);
        }

        return $this;
    }

    public function gotenberg(string $url): self
    {
        if ($this->converter instanceof GotenbergConverter) {
            $this->converter->setUrl($url);
        } else {
            $this->converter = new GotenbergConverter($url);
        }

        return $this;
    }
}
