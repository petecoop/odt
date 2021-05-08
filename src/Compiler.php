<?php

namespace Petecoop\ODT;

use Petecoop\ODT\Compilers\TableCompiler;

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
        $content = $this->compileTableRow($content);
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

    private function compileTableRow($value)
    {

        // find content between @beforerow @endbeforerow
        $pattern = "/@beforerow(.+?)@endbeforerow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            // find closest table start & insert content
            $tableStart = strrpos($value, '<table:table-row', $offset - strlen($value));
            $value = substr_replace($value, $content, $tableStart, 0);
            // remove from row
            $value = substr_replace($value, '', $offset + strlen($content), $length);
        }

        // find @afterrow
        // insert after </table:table-row>
        $pattern = "/@afterrow(.+?)@endafterrow/";
        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $content = $match[1][0];
            $length = strlen($match[0][0]);

            $tableEnd = '</table:table-row>';
            $pos = strpos($value, $tableEnd, $offset);
            $value = substr_replace($value, $content, $pos + strlen($tableEnd), 0);
            $value = substr_replace($value, '', $offset, $length);
        }

        return $value;
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
