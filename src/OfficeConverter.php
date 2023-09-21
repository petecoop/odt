<?php

namespace Petecoop\ODT;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class OfficeConverter
{
    protected string $bin;

    protected $includePath = '$PATH:/usr/local/bin:/opt/homebrew/bin';

    public function __construct(string $bin = 'soffice')
    {
        $this->bin = $bin;
    }

    public function convert(string $odtInput, string $outputPath)
    {

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
            $odtInput,
            '--outdir',
            $outputPath,
        ];

        $process = Process::fromShellCommandline(
            "PATH={$this->includePath}" . ' ' .
            $this->bin . ' ' .
            implode(' ', $options)
        );

        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        throw new ProcessFailedException($process);
    }
}
