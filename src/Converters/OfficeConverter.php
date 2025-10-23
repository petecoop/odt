<?php

namespace Petecoop\ODT\Converters;

use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\Files\PdfFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class OfficeConverter implements Converter
{
    protected $includePath = '$PATH:/usr/local/bin:/opt/homebrew/bin';

    public function __construct(
        protected string $bin = 'soffice',
    ) {}

    public function setBinaryPath(string $bin): self
    {
        $this->bin = $bin;
        return $this;
    }

    public function binaryPath(): string
    {
        return $this->bin;
    }

    public function convert(OdtFile $file): PdfFile
    {
        $tmp = $this->getSystemTemporaryDirectory();

        try {
            $tmpPath = $tmp . '/temp.odt';
            $file->save($tmpPath);

            $pdfPath = $tmp . '/temp.pdf';

            $options = [
                '--headless',
                '--invisible',
                '--nocrashreport',
                '--nodefault',
                '--nofirststartwizard',
                '--nolockcheck',
                '--nologo',
                '--norestore',
                '--convert-to',
                'pdf',
                $tmpPath,
                '--outdir',
                $tmp,
            ];

            $process = Process::fromShellCommandline(
                "PATH={$this->includePath}" . ' ' . $this->bin . ' ' . implode(' ', $options),
            );

            $process->run();

            if ($process->isSuccessful()) {
                if (!file_exists($pdfPath)) {
                    throw new \Exception("PDF file was not created at expected path: {$pdfPath}");
                }

                // copy file to stream
                $handle = fopen($pdfPath, 'rb');
                $stream = fopen('php://temp', 'r+b');
                stream_copy_to_stream($handle, $stream);
                fclose($handle);
                rewind($stream);

                $pdf = new PdfFile();
                $pdf->name(basename($file->getName(), '.odt'));
                return $pdf->fromStream($stream);
            }

            throw new ProcessFailedException($process);
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            if (is_dir($tmp)) {
                rmdir($tmp);
            }
        }
    }

    protected function getSystemTemporaryDirectory(): string
    {
        $location = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $name = mt_rand() . '-' . str_replace([' ', '.'], '', microtime());

        $path = $location . DIRECTORY_SEPARATOR . $name;
        mkdir($path, 0777, true);

        return $path;
    }
}
