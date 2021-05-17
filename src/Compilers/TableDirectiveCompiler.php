<?php

namespace Petecoop\ODT\Compilers;

/**
 * Finds the @table @endtable directives and sets up the TableCompiler
 */
class TableDirectiveCompiler implements Compiler
{
    protected array $args;
    protected array $tableOptions;

    public function __construct(array $args = [], array $tableOptions = [])
    {
        $this->args = $args;
        $this->tableOptions = $tableOptions;
    }

    public function compile(string $value): string
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

            // if no key or key empty remove table
            if (!isset($this->args[$key]) || empty($this->args[$key])) {
                $value = substr_replace($value, '', $offset, $length);
                continue;
            }

            $compiler = new TableCompiler($name, $key, $this->args[$key] ?? [], $this->tableOptions[$key] ?? []);
            $value = $compiler->compile($value);
        }

        return $value;
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
}
