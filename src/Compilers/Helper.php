<?php

namespace Petecoop\ODT\Compilers;

/**
 * Methods to be used within the blade template
 */
class Helper
{
    private array $imageSizeCache = [];

    /**
     * Replace line breaks in text with ODT equivalent
     */
    public function replaceLineBreaks(string $value)
    {
        return str_replace("\n", '<text:line-break />', $value);
    }

    /**
     * Remove e.g. date:image/png;base64, from a string for image embedding
     */
    public function removeBase64Mime(string $value)
    {
        $find = 'base64,';
        $pos = strpos($value, $find);
        return $pos !== false ? substr_replace($value, '', 0, $pos + strlen($find)) : $value;
    }

    public function getBase64ImageWidth(string $value)
    {
        [$x] = $this->getBase64ImageDimensions($value);
        return ($x * 0.02) . 'cm';
    }

    public function getBase64ImageHeight(string $value)
    {
        [, $y] = $this->getBase64ImageDimensions($value);
        return ($y * 0.02) . 'cm';
    }

    private function getBase64ImageDimensions(string $value)
    {
        $key = sha1($value);
        if (!isset($this->imageSizeCache[$key])) {
            $image = base64_decode($this->removeBase64Mime($value));
            $size = getimagesizefromstring($image);
            $this->imageSizeCache[$key] = [$size[0], $size[1]];
        }

        return $this->imageSizeCache[$key];
    }
}
