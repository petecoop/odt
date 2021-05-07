<?php

namespace Petecoop\ODT;

class Compiler
{
    private $bladeCompiler;
    private array $globalArgs;

    public function __construct($bladeCompiler, array $globalArgs = [])
    {
        $this->bladeCompiler = $bladeCompiler;
        $this->globalArgs = $globalArgs;

        $this->bladeCompiler->precompiler(fn($value) => $this->compileTableRow($value));
    }

    public function compile(string $template, array $args = [])
    {
        $bladeCompiled = $this->bladeCompiler->compileString($template);

        ob_start();
        extract(array_merge($this->globalArgs, $args), EXTR_SKIP);
        try {
            eval('?>'.$bladeCompiled);
        } catch (\Exception $e) {
            ob_get_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    public function compileTableRow($value)
    {

        // find content between @beforerow @endbeforerow
        $pattern = "/@beforerow(.+?)@endbeforerow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            // find closest table start & insert content
            $tableStart = strrpos($value, '<table:table-row', $offset - strlen($value));
            $value = substr_replace($value, $content, $tableStart, 0);
            // remove from row
            $value = substr_replace($value, '', $offset + strlen($content), $length);
        }

        // find @afterrow
        // insert after </table:table-row>
        $pattern = "/@afterrow(.+?)@endafterrow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $tableEnd = '</table:table-row>';
            $pos = strpos($value, $tableEnd, $offset);
            $value = substr_replace($value, $content, $pos + strlen($tableEnd), 0);
            $value = substr_replace($value, '', $offset, $length);
        }

        return $value;
    }
}
