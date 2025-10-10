<?php

namespace Petecoop\ODT\Compilers;

/**
 * Compiles table row directives
 *
 * Anything inside @beforerow ... @endbeforerow is moved to just before the enclosing <table:table-row>
 * Anything inside @afterrow ... @endafterrow is moved to just after the enclosing </table:table-row>
 * @rowforeach(...) is a shorthand for wrapping the row in a foreach loop
 *
 * This allows other blade directives to be inserted before/after the table row to e.g. create a foreach loop
 */
class TableRowCompiler implements Compiler
{
    public function compile(string $value): string
    {
        $value = $this->compileRowForeach($value);
        $value = $this->compileBefore($value);
        $value = $this->compileAfter($value);

        return $value;
    }

    private function compileRowForeach(string $value): string
    {
        // find @rowforeach(...)
        $pattern = '/@rowforeach(\(.+ +as +.*\))/s';
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            // replace with @bef(...)
            $replacement = '@beforerow@foreach' . $content . '@endbeforerow@afterrow@endforeach@endafterrow';
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
        // find @afterrow
        // insert after </table:table-row>
        $pattern = '/@afterrow(.+?)@endafterrow/';
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $openTag = '<table:table-row';
            $closeTag = '</table:table-row>';

            // find the enclosing row start (nearest <table:table-row before the directive)
            $rowStart = strrpos($value, $openTag, $offset - strlen($value));

            if ($rowStart === false) {
                // fallback: find the first closing tag after the directive
                $pos = strpos($value, $closeTag, $offset);
                if ($pos === false) {
                    // nothing to insert into; just remove directive
                    $value = substr_replace($value, '', $offset, $length);
                    continue;
                }
                $insertPos = $pos + strlen($closeTag);
            } else {
                // walk forward from the found opening tag and match nested tags
                $i = $rowStart + strlen($openTag);
                $depth = 0;
                $insertPos = false;

                while (true) {
                    $nextOpen = strpos($value, $openTag, $i);
                    $nextClose = strpos($value, $closeTag, $i);

                    if ($nextClose === false) {
                        // unmatched; abort to fallback
                        break;
                    }

                    if ($nextOpen !== false && $nextOpen < $nextClose) {
                        // nested opening before next closing
                        $depth++;
                        $i = $nextOpen + strlen($openTag);
                    } else {
                        // encountered a closing tag
                        if ($depth === 0) {
                            $insertPos = $nextClose + strlen($closeTag);
                            break;
                        }
                        $depth--;
                        $i = $nextClose + strlen($closeTag);
                    }
                }

                if ($insertPos === false) {
                    // fallback to next close after directive
                    $pos = strpos($value, $closeTag, $offset);
                    if ($pos === false) {
                        $value = substr_replace($value, '', $offset, $length);
                        continue;
                    }
                    $insertPos = $pos + strlen($closeTag);
                }
            }

            // insert content after the matched closing tag
            $value = substr_replace($value, $content, $insertPos, 0);
            // remove the directive
            $value = substr_replace($value, '', $offset, $length);
        }

        return $value;
    }
}
