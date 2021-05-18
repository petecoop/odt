<?php

namespace Petecoop\ODT\Directives;

class ImageDirective
{
    public function compile(string $expression): string
    {
        return $this->xml($expression);
    }

    private function xml(string $expression)
    {
        return '<draw:frame
            svg:width="<?php echo $__compiler->getBase64ImageDimensions(' . $expression . ')[0]; ?>"
            svg:height="<?php echo $__compiler->getBase64ImageDimensions(' . $expression . ')[1]; ?>"
        >
            <draw:image xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad">
                <office:binary-data><?php echo $__compiler->removeBase64Mime(' . $expression . '); ?></office:binary-data>
            </draw:image>
        </draw:frame>';
    }
}
