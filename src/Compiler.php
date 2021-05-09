<?php

namespace Petecoop\ODT;

use DOMDocument;
use Petecoop\ODT\Compilers\TableCompiler;
use Petecoop\ODT\Compilers\TableRowCompiler;

class Compiler
{
    private $bladeCompiler;
    private array $globalArgs;

    public function __construct($bladeCompiler, array $globalArgs = [])
    {
        $this->bladeCompiler = $bladeCompiler;
        $this->globalArgs = $globalArgs;
    }

    public function compile(Template $template, array $args = [])
    {
        $content = $template->content();
        $content = $this->precompile($content, $args);
        $content = $this->bladeCompile($content);

        return $this->render($content, $args);
    }

    private function precompile(string $content, array $args)
    {
        $content = $this->compileTableTemplate($content, $args);
        $content = (new TableRowCompiler())->compile($content);
        $content = $this->convertOperators($content);

        return $content;
    }

    private function bladeCompile(string $content)
    {
        return $this->bladeCompiler->compileString($content);
    }

    private function render(string $renderableContent, $args)
    {
        ob_start();
        extract(array_merge($this->globalArgs, $args), EXTR_SKIP);
        try {
            eval('?>'.$renderableContent);
        } catch (\Exception $e) {
            ob_get_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    public function compileTableTemplate(string $value, array $args = [])
    {
        $pattern = "/@table\((.+?)\)(.+?)@endtable/";
        $offset = 0;
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[0][0];
            $length = strlen($content);
            $key = str_replace('$', '', strip_tags($match[1][0]));

            preg_match("/table:name=\"(.+?)\"/", $content, $m);
            $name = $m[1];

            // remove the @table tags
            [$value, $offset,, $removedEnd] = $this->removeClosestTag($value, 'text:p', $offset);
            $length -= $removedEnd;
            [$value,, $removedStart] = $this->removeClosestTag($value, 'text:p', $offset + $length);
            $length -= $removedStart;

            // if no key remove table
            if (!isset($args[$key])) {
                $value = substr_replace($value, '', $offset, $length);
                continue;
            }

            $value = (new TableCompiler($name, $key, $args[$key] ?? []))->compile($value);
        }

        return $value;
    }

    private function convertOperators(string $value)
    {
        return str_replace('T_OBJECT_OPERATOR', '->', $value);
    }

    private function removeClosestTag(string $value, string $tag, int $offset)
    {
        $tagStart = '<' . $tag;
        $tagEnd = '</' . $tag . '>';

        // search backwards from offset to find the start
        $start = strrpos($value, $tagStart, $offset - strlen($value));

        // search forwards to find the end
        $end = strpos($value, $tagEnd, $offset) + strlen($tagEnd);

        $len = $end - $start;
        $value = substr_replace($value, '', $start, $len);

        $removedStart = $offset - $start;
        $removedEnd = $end - $offset;

        return [$value, $offset - $removedStart, $removedStart, $removedEnd];
    }
}
