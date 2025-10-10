<?php

namespace Petecoop\ODT\Converters;

use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\Files\PdfFile;

interface Converter
{
    public function convert(OdtFile $inputFile): PdfFile;
}
