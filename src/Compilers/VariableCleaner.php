<?php

namespace Petecoop\ODT\Compilers;

/**
 * Find Blade vars and strip out any tags inside them so blade can compile
 */
class VariableCleaner implements Compiler
{
    public function compile(string $value): string
    {
        $variablePattern = '/{[^}]*{[^}]*\$.+?}[^{]*}/s';
        $value = $this->cleanPattern($value, $variablePattern);

        $bladeDirectives = '/(@[a-z]+\(.+?\))</s';
        $value = $this->cleanPattern($value, $bladeDirectives);

        return $value;
    }

    private function cleanPattern(string $value, string $pattern): string
    {
        $offset = 0;
        $length = 0;
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE, $offset + $length)) {
            [$value, $offset, $length] = $this->cleanMatch($value, $match, $offset, $length);
        }

        return $value;
    }

    private function cleanMatch(string $value, array $match, int $offset, int $length): array
    {
        [$content, $offset] = end($match);
        $length = strlen($content);

        $cleaned = strip_tags($content);

        // replace unicode characters
        $cleaned = str_replace("\u{2192}", '->', $cleaned);
        $cleaned = preg_replace("/\u{2018}|\u{2019}|&apos;/", "'", $cleaned);
        $cleaned = preg_replace("/\u{201C}|\u{201D}/", '"', $cleaned);

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

        return [$value, $offset, $length];
    }
}
