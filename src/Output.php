<?php

namespace Petecoop\ODT;

use PhpZip\ZipFile;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class Output
{
    public ZipFile $zip;

    public function __construct(ZipFile $zip)
    {
        $this->zip = $zip;
    }

    public function saveAsFile(string $fileName): self
    {
        $this->zip->saveAsFile($fileName);
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
}
