<?php

use Petecoop\ODT\Compilers\TableRowCompiler;

test('TableRowCompiler', function ($template, $expected) {
    $compiler = new TableRowCompiler();
    expect($compiler->compile($template))->toBe($expected);
})->with([
    'compiles @rowforeach' => [
        <<<'BLADE'
        <table:table-row>
            <table:table-cell>
                <text:p>@rowforeach($items as $item){{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>
        BLADE,
        <<<'BLADE'
        @foreach($items as $item)<table:table-row>
            <table:table-cell>
                <text:p>{{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>@endforeach
        BLADE,
    ],
    'compiles @rowforeach with inner brackets' => [
        <<<'BLADE'
        <table:table-row>
            <table:table-cell>
                <text:p>@rowforeach($thing->items() as $item->thing()){{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>
        BLADE,
        <<<'BLADE'
        @foreach($thing->items() as $item->thing())<table:table-row>
            <table:table-cell>
                <text:p>{{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>@endforeach
        BLADE,
    ],
    'compiles @rowif' => [
        <<<'BLADE'
        <table:table-row>
            <table:table-cell>
                <text:p>@rowif($item){{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>
        BLADE,
        <<<'BLADE'
        @if($item)<table:table-row>
            <table:table-cell>
                <text:p>{{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>@endif
        BLADE,
    ],
    'compiles @rowif with inner brackets' => [
        <<<'BLADE'
        <table:table-row>
            <table:table-cell>
                <text:p>@rowif($item->test()){{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>
        BLADE,
        <<<'BLADE'
        @if($item->test())<table:table-row>
            <table:table-cell>
                <text:p>{{ $item }}</text:p>
            </table:table-cell>
        </table:table-row>@endif
        BLADE,
    ],
]);
