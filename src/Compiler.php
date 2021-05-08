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

    public function compile(Template $template, array $args = [])
    {
        $content = $template->content();
        $content = $this->precompile($content, $args);
        $content = $this->bladeCompile($content);

        return $this->render($content, $args);
    }

    private function precompile(string $content, array $args)
    {
        $content = (new TableRowCompiler())->compile($content);
        $content = $this->compileTableTemplate($content, $args);

        return $content;
    }

    private function bladeCompile(string $content)
    {
        return $this->bladeCompiler->compileString($content);
    }

    private function render(string $renderableContent, $args)
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

    public function compileTableTemplate($value, array $args = [])
    {
        $pattern = "/@table\((.+?)\)(.+?)@endtable/";

        preg_match_all($pattern, $value, $matches);
        foreach ($matches[0] as $index => $match) {
            $key = str_replace('$', '', strip_tags($matches[1][$index]));
            preg_match("/table:name=\"(.+?)\"/", $match, $m);
            $name = $m[1];

            $value = (new TableCompiler($name, $key, $args[$key] ?? []))->compile($value);

            // replace @table($users) with $foreach($users as $users_item)
            // replace @endtable with @endforeach
            // finding nearest table-rows...
        }

        // dd($value);
        return $value;
    }
}
