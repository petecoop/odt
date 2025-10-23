<?php

namespace Petecoop\ODT\Files;

use Illuminate\Contracts\Support\Responsable;
use Stringable;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class File implements Responsable, Stringable
{
    /** @var resource */
    protected $stream;

    protected string $fileName;
    protected string $extension;
    protected bool $asAttachment = false;

    private const DEFAULT_MIME_TYPES = [
        'odt' => 'application/vnd.oasis.opendocument.text',
        'pdf' => 'application/pdf',
    ];

    public function name(string $fileName): self
    {
        if (!str_ends_with($fileName, '.' . $this->extension)) {
            $fileName .= '.' . $this->extension;
        }
        $this->fileName = $this->addExtensionToFileName($fileName);
        return $this;
    }

    public function getName(): string
    {
        return $this->fileName;
    }

    public function open(string $path): self
    {
        $this->name(basename($path));
        $handle = fopen($path, 'rb');
        return $this->fromStream($handle);
    }

    protected function isOpen(): bool
    {
        return isset($this->stream);
    }

    public function save(null|string $fileName): self
    {
        $handle = fopen($this->addExtensionToFileName($fileName ?? $this->fileName), 'w+b');
        $this->toStream($handle);
        fclose($handle);
        return $this;
    }

    public function fromString(string $content): self
    {
        $handle = fopen('php://temp', 'r+b');
        fwrite($handle, $content);
        rewind($handle);

        return $this->fromStream($handle);
    }

    public function __toString()
    {
        $handle = fopen('php://temp', 'r+b');
        $this->toStream($handle);
        rewind($handle);

        try {
            return stream_get_contents($handle);
        } finally {
            fclose($handle);
        }
    }

    public function fromStream($stream): self
    {
        $this->stream = $stream;
        return $this;
    }

    public function toStream($handle): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException(
                'No stream available. Open a file or set the content from a string or stream first.',
            );
        }

        rewind($this->stream);
        stream_copy_to_stream($this->stream, $handle);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $response = new StreamedResponse();
        $resource = fopen('php://temp', 'r+b');
        $this->toStream($resource);

        $headers = [
            'Content-Type' => $this->getMimeType(),
            'Content-Length' => fstat($resource)['size'],
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $this->asAttachment ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
                $this->fileName,
            ),
        ];
        $response->headers->replace($headers);

        $response->setCallback(function () use ($resource) {
            $output = fopen('php://output', 'w+b');
            rewind($resource);
            stream_copy_to_stream($resource, $output);
            fclose($output);
            fclose($resource);
        });

        return $response;
    }

    public function asAttachment(null|string $fileName = null): self
    {
        if ($fileName) {
            $this->name($fileName);
        }
        $this->asAttachment = true;
        return $this;
    }

    protected function getMimeType(): string
    {
        $ext = strtolower(pathinfo($this->fileName, \PATHINFO_EXTENSION));

        if (!empty($ext) && isset(self::DEFAULT_MIME_TYPES[$ext])) {
            return self::DEFAULT_MIME_TYPES[$ext];
        }

        return self::DEFAULT_MIME_TYPES['odt'];
    }

    protected function addExtensionToFileName(string $fileName): string
    {
        if (!str_ends_with($fileName, '.' . $this->extension)) {
            $fileName .= '.' . $this->extension;
        }
        return $fileName;
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            fclose($this->stream);
            unset($this->stream);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
