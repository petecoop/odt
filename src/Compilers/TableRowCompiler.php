<?php

declare(strict_types=1);

namespace Petecoop\ODT\Compilers;

/**
 * Compiles table row directives
 *
 * Anything inside @beforerow ... @endbeforerow is moved to just before the enclosing <table:table-row>
 * Anything inside @afterrow ... @endafterrow is moved to just after the enclosing </table:table-row>
 * @rowforeach(...) is a shorthand for wrapping the row in a foreach loop
 * @rowif(...) is a shorthand for wrapping the row in an if statement
 *
 * This allows other blade directives to be inserted before/after the table row to e.g. create a foreach loop
 */
class TableRowCompiler implements Compiler
{
    public function compile(string $value): string
    {
        $value = $this->compileRowForeach($value);
        $value = $this->compileRowIf($value);
        $value = $this->compileBefore($value);

        return $this->compileAfter($value);
    }

    /**
     * Compile @rowforeach(...) into @beforerow@foreach(...)@endbeforerow@afterrow@endforeach@endafterrow
     * Allows 1 level of nested parentheses inside the expression
     */
    private function compileRowForeach(string $value): string
    {
        // find @rowforeach(...)
        $pattern = '/@rowforeach *\(([^()]*(\([^()]*\)[^()]*)* +as +[^()]*(\([^()]*\)[^()]*)*)\)/s';
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $replacement = '@beforerow@foreach('.$content.')@endbeforerow@afterrow@endforeach@endafterrow';
            $value = substr_replace($value, $replacement, $offset, $length);
        }

        return $value;
    }

    /**
     * Compile @rowif(...) into @beforerow@if(...)@endbeforerow@afterrow@endif@endafterrow
     * Allows 1 level of nested parentheses inside the expression
     */
    private function compileRowIf(string $value): string
    {
        // find @rowif(...)
        $pattern = '/@rowif *\(([^()]*(\([^()]*\)[^()]*)*)\)/s';
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $replacement = '@beforerow@if('.$content.')@endbeforerow@afterrow@endif@endafterrow';
            $value = substr_replace($value, $replacement, $offset, $length);
        }

        return $value;
    }

    private function compileBefore(string $value): string
    {
        // find content between @beforerow @endbeforerow
        $pattern = '/@beforerow(.+?)@endbeforerow/';
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
        return (new TableRowAfterCompiler)->compile($value);
    }
}
