<?php

namespace Petecoop\ODT\Directives;

/**
 * Image directive for embedding base64 images in ODT
 *
 * Usage: @image($base64ImageString, $maxWidth, $maxHeight)
 * $maxWidth and $maxHeight are optional and in cm e.g. '3cm'
 *
 * Requires GD extension for image size calculation
 */
class ImageDirective
{
    public function compile(string $expression): string
    {
        return $this->xml($expression);
    }

    private function xml(string $expression)
    {
        return (
            '<draw:frame
            svg:width="<?php echo $__compiler->getBase64ImageDimensions('
            . $expression
            . ')[0]; ?>"
            svg:height="<?php echo $__compiler->getBase64ImageDimensions('
            . $expression
            . ')[1]; ?>"
        >
            <draw:image xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad">
                <office:binary-data><?php echo $__compiler->removeBase64Mime('
            . $expression
            . '); ?></office:binary-data>
            </draw:image>
        </draw:frame>'
        );
    }
}
