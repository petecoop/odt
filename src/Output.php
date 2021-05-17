<?php

namespace Petecoop\ODT;

use PhpZip\ZipFile;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class Output
{
    public ZipFile $zip;

    public function __construct(ZipFile $zip)
    {
        $this->zip = $zip;
    }

    public function saveAsODT(string $fileName): self
    {
        $this->zip->saveAsFile($fileName);
        return $this;
    }

    public function saveAsPDF(string $fileName, ?string $bin = 'soffice'): self
    {
        $converter = new OfficeConverter($bin);
        $tmp = (new TemporaryDirectory())->create();

        try {
            $tmpPath = $tmp->path('temp.odt');
            $this->saveAsODT($tmpPath);

            $pdfPath = $tmp->path('temp.pdf');
            $converter->convert($tmpPath, $tmp->path());

            rename($pdfPath, $fileName);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $tmp->delete();
        }

        return $this;
    }

    public function symfonyResponse(string $fileName): Response
    {
        return $this->zip->outputAsSymfonyResponse($fileName, null, false);
    }

    public function psr7Response(ResponseInterface $response, string $fileName): ResponseInterface
    {
        return $this->zip->outputAsPsr7Response($response, $fileName);
    }

    public function content(): string
    {
        return $this->zip->getEntryContents('content.xml');
    }

    public function styles(): string
    {
        return $this->zip->getEntryContents('styles.xml');
    }
}
