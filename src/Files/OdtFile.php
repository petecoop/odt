<?php

namespace Petecoop\ODT\Files;

use Petecoop\ODT\Compiler;
use Petecoop\ODT\Converters\Converter;
use Petecoop\ODT\ODT;
use PhpZip\ZipFile;

class OdtFile extends File
{
    protected string $extension = 'odt';

    protected ZipFile $zip;
    protected null|string $content = null;
    protected null|string $styles = null;
    protected array $tableOptions = [];

    protected null|ODT $instance = null;
    protected null|Compiler $compiler = null;
    protected null|Converter $converter = null;

    private function read(): self
    {
        $this->content = $this->readEntry('content.xml');
        $this->styles = $this->readEntry('styles.xml');

        return $this;
    }

    /**
     *  @param resource $stream Input stream resource
     */
    public function fromStream($stream): self
    {
        $this->zip = new ZipFile();
        $this->zip->openFromStream($stream);
        $this->read();

        return $this;
    }

    /**
     *  @param resource $handle Output stream resource
     */
    public function toStream($handle): void
    {
        if (!$this->isOpen()) {
            $this->zip = new ZipFile();
        }

        if (isset($this->content)) {
            $this->zip->addFromString('content.xml', $this->content);
        }

        if (isset($this->styles)) {
            $this->zip->addFromString('styles.xml', $this->styles);
        }

        $zipContent = $this->zip->outputAsString();
        fwrite($handle, $zipContent);
        rewind($handle);
    }

    protected function isOpen(): bool
    {
        return isset($this->zip);
    }

    public function close(): void
    {
        if ($this->isOpen()) {
            $this->zip->close();
            unset($this->zip);
        }
    }

    private function readEntry(string $entryName): string
    {
        if (!$this->isOpen()) {
            throw new \Exception('ODT file is not open');
        }

        return $this->zip->getEntryContents($entryName);
    }

    /**
     * An ODT file must have either content or styles
     */
    public function isValid()
    {
        return $this->content !== null || $this->styles !== null;
    }

    public function content(): string
    {
        if ($this->content === null) {
            $this->content = $this->readEntry('content.xml');
        }

        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function styles(): string
    {
        if ($this->styles === null) {
            $this->styles = $this->readEntry('styles.xml');
        }

        return $this->styles;
    }

    public function setStyles(string $styles): self
    {
        $this->styles = $styles;
        return $this;
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

    /**
     * @param array{cleanVars: bool} $options
     */
    public function render(array $args = [], array $options = []): OdtFile
    {
        if (!$this->compiler()) {
            throw new \Exception('Compiler not set');
        }

        if (!$this->isValid()) {
            throw new \Exception('ODT file is not valid, it must have either content or styles');
        }

        $compiled = $this->compiler()->compile($this, $args, $options);
        $rendered = $this->compiler()->render($compiled, $args);

        $output = clone $this;
        $handle = fopen('php://temp', 'r+b');
        $this->toStream($handle);
        $output->fromStream($handle);
        $output->setContent($rendered['content'])->setStyles($rendered['styles']);

        return $output;
    }

    public function pdf(null|string $fileName = null): PdfFile
    {
        if (!$this->converter()) {
            throw new \Exception('Converter not set');
        }

        $pdf = $this->converter()->convert($this);

        if ($fileName) {
            $pdf->name($fileName);
        }

        return $pdf;
    }

    public function setinstance(ODT $odt): self
    {
        $this->instance = $odt;
        return $this;
    }

    public function compiler(): null|Compiler
    {
        return $this->compiler ?? $this->instance?->compiler();
    }

    public function setCompiler(Compiler $compiler): self
    {
        $this->compiler = $compiler;
        return $this;
    }

    public function converter(): null|Converter
    {
        return $this->converter ?? $this->instance?->converter();
    }

    public function setConverter(Converter $converter): self
    {
        $this->converter = $converter;
        return $this;
    }
}
