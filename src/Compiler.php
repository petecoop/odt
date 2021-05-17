<?php

namespace Petecoop\ODT;

use Petecoop\ODT\Compilers\Helper;
use Petecoop\ODT\Compilers\VariableCleaner;
use Petecoop\ODT\Directives\ImageDirective;
use Illuminate\View\Compilers\BladeCompiler;
use Petecoop\ODT\Compilers\TableRowCompiler;
use Petecoop\ODT\Compilers\TableDirectiveCompiler;

class Compiler
{
    private BladeCompiler $bladeCompiler;
    private array $globalArgs;

    public function __construct(BladeCompiler $bladeCompiler, array $globalArgs = [])
    {
        $this->bladeCompiler = $bladeCompiler;
        $this->globalArgs = $globalArgs;

        $this->bladeDirectives();
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
        if ($options['cleanVars'] ?? false) {
            $xml = (new VariableCleaner())->compile($xml);
        }
        $xml = $this->precompile($xml, $template, $args);
        $xml = $this->bladeCompile($xml);

        return $this->render($xml, $args);
    }

    private function precompile(string $value, Template $template, array $args): string
    {
        $tableOptions = $template->getTableOptions();
        $value = (new TableDirectiveCompiler($args, $tableOptions))->compile($value);

        $value = (new TableRowCompiler())->compile($value);
        $value = $this->convertOperators($value);

        return $value;
    }

    private function bladeCompile(string $value): string
    {
        $this->bladeCompiler->setEchoFormat('$__compiler->replaceLineBreaks(e(%s))');

        $compiled = $this->bladeCompiler->compileString($value);

        $this->bladeCompiler->setEchoFormat('e(%s)');

        return $compiled;
    }

    private function render(string $renderableContent, $args): string
    {
        $args = $args + [
            '__compiler' => new Helper(),
        ];

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

    /**
     * Convert operators that have been placed in previous compilers
     */
    private function convertOperators(string $value): string
    {
        $value = str_replace('T_OBJECT_OPERATOR', '->', $value);

        return $value;
    }

    private function bladeDirectives()
    {
        $this->bladeCompiler->directive('image', function ($expression) {
            return (new ImageDirective())->compile($expression);
        });
    }
}
