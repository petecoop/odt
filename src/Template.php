<?php

namespace Petecoop\ODT;

use PhpZip\ZipFile;

class Template
{
    private string $path;
    private Compiler $compiler;

    private array $tableOptions = [];

    public function __construct(string $path, Compiler $compiler)
    {
        $this->path = $path;
        $this->compiler = $compiler;

        $this->zip = new ZipFile();
        $this->zip->openFile($path);
    }

    public function render(array $args = [], array $options = []): Output
    {
        $compiled = $this->compiler->compile($this, $args, $options);

        $zip = $this->zip
            ->addFromString('content.xml', $compiled['content'])
            ->addFromString('styles.xml', $compiled['styles']);

        return new Output($zip);
    }

    public function content(): string
    {
        return $this->zip->getEntryContents('content.xml');
    }

    public function styles(): string
    {
        return $this->zip->getEntryContents('styles.xml');
    }

    public function table(string $key, array $options): self
    {
        $this->tableOptions[$key] = $options;
        return $this;
    }

    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }
}
