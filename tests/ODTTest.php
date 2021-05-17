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
}
