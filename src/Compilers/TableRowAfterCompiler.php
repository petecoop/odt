<?php

declare(strict_types=1);

namespace Petecoop\ODT\Compilers;

class TableRowAfterCompiler
{
    private const OPEN_TAG = '<table:table-row';
    private const CLOSE_TAG = '</table:table-row>';

    public function compile(string $value): string
    {
        $pattern = '/@afterrow(.+?)@endafterrow/';

        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $value = $this->compileDirective($value, $match);
        }

        return $value;
    }

    /**
     * @param array<int, array{0: string, 1: int}> $match
     */
    private function compileDirective(string $value, array $match): string
    {
        $offset = $match[0][1];
        $content = $match[1][0];
        $length = strlen($match[0][0]);
        $insertPosition = $this->findInsertPosition($value, $offset);

        if ($insertPosition === null) {
            return substr_replace($value, '', $offset, $length);
        }

        $value = substr_replace($value, $content, $insertPosition, 0);

        return substr_replace($value, '', $offset, $length);
    }

    private function findInsertPosition(string $value, int $offset): ?int
    {
        $rowStart = strrpos($value, self::OPEN_TAG, $offset - strlen($value));

        return $rowStart === false
            ? $this->findNextCloseTag($value, $offset)
            : $this->findMatchingCloseTag($value, $rowStart) ?? $this->findNextCloseTag($value, $offset);
    }

    private function findMatchingCloseTag(string $value, int $rowStart): ?int
    {
        $offset = $rowStart + strlen(self::OPEN_TAG);
        $depth = 0;

        while (true) {
            $nextOpen = strpos($value, self::OPEN_TAG, $offset);
            $nextClose = strpos($value, self::CLOSE_TAG, $offset);

            if ($nextClose === false) {
                return null;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $offset = $nextOpen + strlen(self::OPEN_TAG);

                continue;
            }

            if ($depth === 0) {
                return $nextClose + strlen(self::CLOSE_TAG);
            }

            $depth--;
            $offset = $nextClose + strlen(self::CLOSE_TAG);
        }
    }

    private function findNextCloseTag(string $value, int $offset): ?int
    {
        $closeTagPosition = strpos($value, self::CLOSE_TAG, $offset);

        return $closeTagPosition === false ? null : $closeTagPosition + strlen(self::CLOSE_TAG);
    }
}
