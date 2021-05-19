<?php

use Petecoop\ODT\ODT;
use Petecoop\ODT\Output;
use Petecoop\ODT\Template;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class ODTTest extends TestCase
{
    public function setUp(): void
    {
        $blade = new BladeCompiler(new Filesystem(), 'tests/cache');
        $this->odt = new ODT($blade);
        $this->compiler = $this->odt->compiler();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(ODT::class, $this->odt);
    }

    public function testTemplateOpen()
    {
        $template = $this->odt->open('tests/files/basic-template.odt');
        $this->assertInstanceOf(Template::class, $template);

        $content = $template->content();
        $this->assertStringContainsString('Basic template {{ $test }}', $content);
    }

    public function testRenderTemplate()
    {
        $template = $this->odt->open('tests/files/basic-template.odt');
        $output = $template->render(['test' => 'Hello Tests']);
        $this->assertInstanceOf(Output::class, $output);

        $this->assertStringContainsString('Basic template Hello Tests', $output->content());
    }

    public function testRenderTemplateWithoutArgs()
    {
        $this->expectError();
        $this->odt->open('tests/files/basic-template.odt')->render();
    }

    /**
     * @dataProvider compileProvider
     */
    public function testCompile($xml, $args, $expected)
    {
        $compiled = $this->compile($xml, $args);
        $this->assertEquals($expected, $compiled['content']);
    }

    public function compileProvider()
    {
        return [
            [
                '<text:p>{{ $test }}</text:p>',
                ['test' => 'Hello tests'],
                '<text:p>Hello tests</text:p>'
            ],
            [
                '<text:p>{{ $test ?? "n/a" }}</text:p>',
                [],
                '<text:p>n/a</text:p>'
            ],
            [
                '<text:p>@if(!empty($value))</text:p>
                <text:p>{{ $value }}</text:p>
                <text:p>@endif</text:p>',
                [],
                '<text:p></text:p>',
            ],
            [
                '<text:p>@if(!empty($value))</text:p>
                <text:p>{{ $value }}</text:p>
                <text:p>@endif</text:p>',
                ['value' => 'some value'],
                '<text:p></text:p>
                <text:p>some value</text:p>
                <text:p></text:p>',
            ],
            [
                '<text:p>@foreach($items as $item)</text:p>
                <text:p>{{ $item }}</text:p>
                <text:p>@endforeach</text:p>',
                ['items' => [1, 2, 3]],
                '<text:p>@foreach($items as $item)</text:p>
                <text:p>1</text:p>
                <text:p>2</text:p>
                <text:p>3</text:p>
                <text:p>@endforeach</text:p>',
            ],
        ];
    }

    private function compile(string $xml, ?array $args = [])
    {
        $template = $this->mockTemplate($xml);
        return $this->compiler->compile($template, $args);
    }

    private function mockTemplate(string $xml)
    {
        $template = $this->createMock(Template::class);
        $template->method('content')->willReturn($xml);
        $template->method('styles')->willReturn('');

        return $template;
    }
}
