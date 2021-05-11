<?php

namespace Petecoop\ODT;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class OfficeConverter
{
    private string $bin;

    public function __construct(string $bin = 'soffice')
    {
        $this->bin = $bin;
    }

    public function convert(string $odtInput, string $outputPath)
    {
        $process = new Process([
            $this->bin,
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
            $odtInput,
            '--outdir',
            $outputPath,
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        throw new ProcessFailedException($process);
    }
}
