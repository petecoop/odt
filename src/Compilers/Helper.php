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

    public function getBase64ImageDimensions(string $value, ?string $maxWidth = null, ?string $maxHeight = null)
    {
        $key = sha1($value);
        if (!isset($this->imageSizeCache[$key])) {
            $image = base64_decode($this->removeBase64Mime($value));
            $size = getimagesizefromstring($image);

            $width = $size[0] * 0.02;
            $height = $size[1] * 0.02;

            $maxWidth = $maxWidth ? (float) $maxWidth : null;
            if ($maxWidth && $width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $width = $maxWidth;
                $height = $ratio * $height;
            }

            $maxHeight = $maxHeight ? (float) $maxHeight : null;
            if ($maxHeight && $height > $maxHeight) {
                $ratio = $maxHeight / $height;
                $height = $maxHeight;
                $width = $ratio * $width;
            }

            $this->imageSizeCache[$key] = [
                round($width, 2) . 'cm',
                round($height, 2) . 'cm',
            ];
        }

        return $this->imageSizeCache[$key];
    }
}
