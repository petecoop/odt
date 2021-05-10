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

    public function render(array $args): ZipFile
    {
        $content = $this->compiler->compile($this, $args);

        return $this->zip->addFromString('content.xml', $content);
    }

    public function content(): string
    {
        return $this->zip->getEntryContents('content.xml');
    }

    public function table(string $key, array $options)
    {
        $this->tableOptions[$key] = $options;
        return $this;
    }

    public function getTableOptions()
    {
        return $this->tableOptions;
    }
}
