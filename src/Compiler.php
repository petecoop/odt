<?php

namespace Petecoop\ODT;

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

    public function compile(Template $template, array $args = [], array $options = []): array
    {
        return [
            'content' => $this->compileXML($template->content(), $template, $args, $options),
            'styles' => $this->compileXML($template->styles(), $template, $args, $options),
        ];
    }

    private function compileXML(string $xml, Template $template, array $args = [], array $options = []): string
    {
        if ($options['cleanVariables']) {
            $xml = $this->cleanVariables($xml);
        }
        $xml = $this->precompile($xml, $template, $args);
        $xml = $this->bladeCompile($xml);

        return $this->render($xml, $args);
    }

    private function precompile(string $value, Template $template, array $args): string
    {
        $tableOptions = $template->getTableOptions();
        $value = $this->compileTableTemplate($value, $args, $tableOptions);

        $value = (new TableRowCompiler())->compile($value);
        $value = $this->convertOperators($value);

        return $value;
    }

    private function bladeCompile(string $value): string
    {
        return $this->bladeCompiler->compileString($value);
    }

    private function render(string $renderableContent, $args): string
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

    private function compileTableTemplate(string $value, array $args = [], array $tableOptions = []): string
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

            $compiler = new TableCompiler($name, $key, $args[$key] ?? [], $tableOptions[$key] ?? []);
            $value = $compiler->compile($value);
        }

        return $value;
    }

    private function convertOperators(string $value): string
    {
        return str_replace('T_OBJECT_OPERATOR', '->', $value);
    }

    private function removeClosestTag(string $value, string $tag, int $offset): array
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

    /**
     * Find Blade vars and strip out any tags inside them so blade can compile
     */
    private function cleanVariables(string $value): string
    {
        $pattern = '/{[^}]*{[^}]*\$.+?}[^{]*}/s';
        $offset = 0;
        $length = 0;
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE, $offset + $length)) {
            $offset = $match[0][1];
            $content = $match[0][0];
            $length = strlen($content);

            $cleaned = strip_tags($content);
            $cleanedLength = strlen($cleaned);
            if ($cleanedLength !== $length) {
                preg_match_all('/<([^\/]+?)\s/', $content, $openingTags);
                preg_match_all('/<\/(.+?)>/', $content, $closingTags);
                if (count($openingTags[0]) > count($closingTags[0])) {
                    // more opening tags - remove next closing tag
                    $tag = '</' . array_slice($openingTags[1], -1)[0] . '>';
                    $end = strpos($value, $tag, $offset + $length) + strlen($tag);
                    $diff = $end - ($offset + $length);
                    $cleanedLength += $diff;
                    $length += $diff;
                }

                if (count($openingTags[0]) < count($closingTags[0])) {
                    // more closing - remove previous opening tag
                    $tag = '<' . array_slice($closingTags[1], -1)[0];
                    $start = strrpos($value, $tag, $offset - strlen($value));
                    $diff = $offset - $start;
                    $cleanedLength += $diff;
                    $length += $diff;
                    $offset -= $diff;
                }

                $value = substr_replace($value, $cleaned, $offset, $length);
                $length = $cleanedLength;
            }
        }

        return $value;
    }
}
