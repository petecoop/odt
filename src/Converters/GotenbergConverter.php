<?php

namespace Petecoop\ODT\Converters;

use Gotenberg\Gotenberg;
use Gotenberg\Stream as GotenbergStream;
use GuzzleHttp\Psr7\Stream;
use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\Files\PdfFile;

class GotenbergConverter implements Converter
{
    public function __construct(
        protected string $url,
    ) {}

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function convert(OdtFile $file): PdfFile
    {
        $handle = fopen('php://temp', 'r+b');
        try {
            $file->toStream($handle);
            $stream = new Stream($handle);
            $request = Gotenberg::libreOffice($this->url)->convert(new GotenbergStream($file->getName(), $stream));
            $response = Gotenberg::send($request);
        } finally {
            fclose($handle);
        }

        $stream = fopen('php://temp', 'r+b');
        $response->getBody()->rewind();
        while (!$response->getBody()->eof()) {
            fwrite($stream, $response->getBody()->read(1024));
        }

        $pdf = new PdfFile();
        $pdf->name(basename($file->getName(), '.odt'));
        return $pdf->fromStream($stream);
    }
}
