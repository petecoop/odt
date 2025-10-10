<?php

namespace Petecoop\ODT;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Concerns\ManagesLoops;
use Petecoop\ODT\Compilers\Helper;
use Petecoop\ODT\Compilers\TableDirectiveCompiler;
use Petecoop\ODT\Compilers\TableRowCompiler;
use Petecoop\ODT\Compilers\VariableCleaner;
use Petecoop\ODT\Directives\ImageDirective;
use Petecoop\ODT\Files\OdtFile;

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

    /**
     * Compile the content and styles of an ODT template
     * @param array{cleanVars: bool} $options
     * @return array{content: string, styles: string}
     */
    public function compile(OdtFile $template, array $args = [], array $options = []): array
    {
        return [
            'content' => $this->compileTemplate($template->content(), $template, $args, $options),
            'styles' => $this->compileTemplate($template->styles(), $template, $args, $options),
        ];
    }

    /**
     * @param array{content: string, styles: string} $compiled
     */
    public function render(array $compiled, array $args = []): array
    {
        return [
            'content' => $this->renderTemplate($compiled['content'], $args),
            'styles' => $this->renderTemplate($compiled['styles'], $args),
        ];
    }

    /**
     * @param array{cleanVars: bool} $options
     */
    private function compileTemplate(string $xml, OdtFile $template, array $args = [], array $options = []): string
    {
        if (!isset($options['cleanVars']) || $options['cleanVars'] !== false) {
            $xml = (new VariableCleaner())->compile($xml);
        }
        $xml = $this->precompile($xml, $template, $args);
        $xml = $this->bladeCompile($xml);

        return $xml;
    }

    private function precompile(string $value, OdtFile $template, array $args): string
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

    private function renderTemplate(string $renderableContent, $args): string
    {
        $args = $args
        + [
            '__compiler' => new Helper(),
        ];

        // If using outside of Laravel provide the bare minimum __env
        if (!isset($args['__env'])) {
            $args['__env'] = new class {
                use ManagesLoops;
            };
        }

        ob_start();
        extract(array_merge($this->globalArgs, $args), EXTR_SKIP);
        try {
            eval('?>' . $renderableContent);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $output = ob_get_clean();

        return $this->postRender($output);
    }

    /**
     * Convert operators that have been placed in previous compilers
     */
    private function convertOperators(string $value): string
    {
        $value = str_replace('T_OBJECT_OPERATOR', '->', $value);
        $value = str_replace('<?', 'PHP_OPEN_TAG', $value);
        $value = str_replace('?>', 'PHP_CLOSE_TAG', $value);

        return $value;
    }

    private function bladeDirectives()
    {
        $this->bladeCompiler->directive('image', function ($expression) {
            return (new ImageDirective())->compile($expression);
        });
    }

    private function postRender(string $xml): string
    {
        $xml = str_replace('PHP_OPEN_TAG', '<?', $xml);
        $xml = str_replace('PHP_CLOSE_TAG', '?>', $xml);

        return $xml;
    }
}
