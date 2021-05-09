<?php

namespace Petecoop\ODT\Compilers;

class TableRowCompiler implements Compiler
{
    public function compile(string $value): string
    {
        $value = $this->compileBefore($value);
        $value = $this->compileAfter($value);

        return $value;
    }

    private function compileBefore(string $value): string
    {
        // find content between @beforerow @endbeforerow
        $pattern = "/@beforerow(.+?)@endbeforerow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            // find closest table start & insert content
            $rowStart = strrpos($value, '<table:table-row', $offset - strlen($value));
            $value = substr_replace($value, $content, $rowStart, 0);
            // remove from row
            $value = substr_replace($value, '', $offset + strlen($content), $length);
        }

        return $value;
    }

    private function compileAfter(string $value): string
    {
        // find @afterrow
        // insert after </table:table-row>
        $pattern = "/@afterrow(.+?)@endafterrow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $rowEnd = '</table:table-row>';
            $pos = strpos($value, $rowEnd, $offset);
            $value = substr_replace($value, $content, $pos + strlen($rowEnd), 0);
            $value = substr_replace($value, '', $offset, $length);
        }

        return $value;
    }
}
