<?php

use Petecoop\ODT\Compiler;
use Petecoop\ODT\Converters\GotenbergConverter;
use Petecoop\ODT\Converters\OfficeConverter;
use Petecoop\ODT\Files\OdtFile;
use Petecoop\ODT\ODT;

test('create instance', function () {
    expect($this->odt())->toBeInstanceOf(ODT::class);

    $odt = ODT::make();
    expect($odt)->toBeInstanceOf(ODT::class);
    expect($odt->converter())->toBeInstanceOf(OfficeConverter::class);
    expect($odt->compiler())->toBeInstanceOf(Compiler::class);
});

test('open a template', function () {
    $odt = $this->odt();
    $template = $odt->open('tests/files/basic-template.odt');
    expect($template)->toBeInstanceOf(OdtFile::class);
    expect($template->compiler())->toBe($odt->compiler());
    expect($template->converter())->toBe($odt->converter());
    expect($template->content())->toContain('Basic template {{ $test }}');
});

test('render template', function () {
    $template = $this->odt()->open('tests/files/basic-template.odt');
    $output = $template->render(['test' => 'Hello Tests']);
    expect($output)->toBeInstanceOf(OdtFile::class);
    expect($output->content())->toContain('Basic template Hello Tests');
});

test('ensure template errors are caught and rethrown', function () {
    $template = $this->template(<<<'BLADE'
    @if
    <text:p>{{ $test }}</text:p>
    BLADE);
    $template->setCompiler($this->odt()->compiler());
    $template->render([]);
})->throws(ParseError::class);

test('render', function ($xml, $args, $expected) {
    $template = $this->template($xml);
    $template->setCompiler($this->odt()->compiler());
    $rendered = $template->render($args);

    expect($rendered->content())->toEqual($expected);
})->with('compileProvider');

dataset('compileProvider', function () {
    return [
        [
            <<<'BLADE'
            <text:p>{{ $test }}</text:p>
            BLADE,
            ['test' => 'Hello tests'],
            <<<'XML'
            <text:p>Hello tests</text:p>
            XML,
        ],
        [
            <<<'BLADE'
            <text:p>{{ $test ?? "n/a" }}</text:p>
            BLADE,
            [],
            <<<'XML'
            <text:p>n/a</text:p>
            XML,
        ],
        [
            <<<'BLADE'
            <text:p>@if(!empty($value))</text:p>
            <text:p>{{ $value }}</text:p>
            <text:p>@endif</text:p>
            BLADE,
            [],
            <<<'XML'
            <text:p></text:p>
            XML,
        ],
        [
            <<<'BLADE'
            <text:p>@if(!empty($value))</text:p>
            <text:p>{{ $value }}</text:p>
            <text:p>@endif</text:p>
            BLADE,
            ['value' => 'some value'],
            <<<'XML'
            <text:p></text:p>
            <text:p>some value</text:p>
            <text:p></text:p>
            XML,
        ],
        [
            <<<'BLADE'
            <text:p>@foreach($items as $item)</text:p>
            <text:p>{{ $item }}</text:p>
            <text:p>@endforeach</text:p>
            BLADE,
            ['items' => [1, 2, 3]],
            <<<'XML'
            <text:p></text:p>
            <text:p>1</text:p>
            <text:p></text:p>
            <text:p>2</text:p>
            <text:p></text:p>
            <text:p>3</text:p>
            <text:p></text:p>
            XML,
        ],
        [
            <<<'BLADE'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Some Heading</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>@beforerow@foreach($items as $item)@endbeforerow{{ $item }}@afterrow@endforeach@endafterrow</text:p>
                    </table:table-cell>
                </table:table-row>
            </table:table>
            BLADE,
            [
                'items' => ['Row 1', 'Row 2', 'Row 3'],
            ],
            <<<'XML'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Some Heading</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Row 1</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row>
                    <table:table-cell>
                        <text:p>Row 2</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row>
                    <table:table-cell>
                        <text:p>Row 3</text:p>
                    </table:table-cell>
                </table:table-row></table:table>
            XML,
        ],
        [
            <<<'BLADE'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row some="attribute">
                    <table:table-cell>
                        <text:p>@beforerow@foreach($items as $item)@endbeforerow{{ $loop->iteration }}@afterrow@endforeach@endafterrow</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>{{ $item }}</text:p>
                    </table:table-cell>
                </table:table-row>
            </table:table>
            BLADE,
            [
                'items' => ['Row 1', 'Row 2', 'Row 3'],
            ],
            <<<'XML'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row some="attribute">
                    <table:table-cell>
                        <text:p>1</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 1</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row some="attribute">
                    <table:table-cell>
                        <text:p>2</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 2</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row some="attribute">
                    <table:table-cell>
                        <text:p>3</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 3</text:p>
                    </table:table-cell>
                </table:table-row></table:table>
            XML,
        ],
        [
            <<<'BLADE'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>@beforerow@foreach($items as $item)@endbeforerow{{ $loop->iteration }}@afterrow@endforeach@endafterrow</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <table:table>
                            <table:table-row>
                                <table:table-cell>
                                    <text:p>{{ $item }}</text:p>
                                </table:table-cell>
                            </table:table-row>
                        </table:table>
                    </table:table-cell>
                </table:table-row>
            </table:table>
            BLADE,
            [
                'items' => ['Row 1', 'Row 2'],
            ],
            <<<'XML'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>1</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <table:table>
                            <table:table-row>
                                <table:table-cell>
                                    <text:p>Row 1</text:p>
                                </table:table-cell>
                            </table:table-row>
                        </table:table>
                    </table:table-cell>
                </table:table-row><table:table-row>
                    <table:table-cell>
                        <text:p>2</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <table:table>
                            <table:table-row>
                                <table:table-cell>
                                    <text:p>Row 2</text:p>
                                </table:table-cell>
                            </table:table-row>
                        </table:table>
                    </table:table-cell>
                </table:table-row></table:table>
            XML,
        ],
        [
            <<<'BLADE'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>@rowforeach($items as $item){{ $loop->iteration }}</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>{{ $item }}</text:p>
                    </table:table-cell>
                </table:table-row>
            </table:table>
            BLADE,
            [
                'items' => ['Row 1', 'Row 2', 'Row 3'],
            ],
            <<<'XML'
            <table:table>
                <table:table-row>
                    <table:table-cell>
                        <text:p>Iteration</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Item</text:p>
                    </table:table-cell>
                </table:table-row>
                <table:table-row>
                    <table:table-cell>
                        <text:p>1</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 1</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row>
                    <table:table-cell>
                        <text:p>2</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 2</text:p>
                    </table:table-cell>
                </table:table-row><table:table-row>
                    <table:table-cell>
                        <text:p>3</text:p>
                    </table:table-cell>
                    <table:table-cell>
                        <text:p>Row 3</text:p>
                    </table:table-cell>
                </table:table-row></table:table>
            XML,
        ],
    ];
});

test('set soffice binary', function () {
    $odt = ODT::make('/usr/bin/libreoffice');
    expect($odt->converter())->toBeInstanceOf(OfficeConverter::class);

    /** @var OfficeConverter */
    $converter = $odt->converter();
    expect($converter->binaryPath())->toEqual('/usr/bin/libreoffice');

    $odt->officeBinary('/custom/path/to/soffice');
    expect($converter->binaryPath())->toEqual('/custom/path/to/soffice');
});

test('soffice pdf conversion', function () {
    $template = $this->odt()->open('tests/files/basic-template.odt');
    $rendered = $template->render(['test' => 'PDF Test']);

    $converter = Mockery::mock(OfficeConverter::class);
    $rendered->setConverter($converter);

    $converter->shouldReceive('convert')->once()->with($rendered);

    $rendered->pdf();
});

test('set gotenberg url', function () {
    $odt = ODT::make()->gotenberg('http://localhost:3000');
    expect($odt->converter())->toBeInstanceOf(GotenbergConverter::class);

    /** @var GotenbergConverter */
    $converter = $odt->converter();
    expect($converter->url())->toEqual('http://localhost:3000');

    $odt->gotenberg('http://gotenberg:3000');
    expect($converter->url())->toEqual('http://gotenberg:3000');
});

test('gotenberg pdf conversion', function () {
    $template = $this->odt()->open('tests/files/basic-template.odt');
    $rendered = $template->render(['test' => 'PDF Test']);

    $converter = Mockery::mock(GotenbergConverter::class);
    $rendered->setConverter($converter);

    $converter->shouldReceive('convert')->once()->with($rendered);

    $rendered->pdf();
});

test('@image directive', function () {
    $template = $this->template(<<<'BLADE'
    <text:p>@image($image, '4cm', '4cm')</text:p>
    BLADE);
    $template->setCompiler($this->odt()->compiler());

    // get base64 image string
    $imageData = file_get_contents('tests/files/image.png');
    $rendered = $template->render(['image' => 'data:image/png;base64,' . base64_encode($imageData)]);

    expect($rendered->content())
        ->toContain('<office:binary-data>' . base64_encode($imageData) . '</office:binary-data>')
        ->toContain('svg:width="4cm"')
        ->toContain('svg:height="1.23cm"');
});
