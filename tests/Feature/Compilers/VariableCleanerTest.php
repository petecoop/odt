<?php

use Petecoop\ODT\Compilers\VariableCleaner;

test('VariableCleaner', function ($template, $expected) {
    $compiler = new VariableCleaner();
    expect($compiler->compile($template))->toBe($expected);
})->with([
    'basic variable' => [
        <<<'BLADE'
        {{ $item }}
        BLADE,
        <<<'BLADE'
        {{ $item }}
        BLADE,
    ],
    'basic directive' => [
        <<<'BLADE'
        @foreach($items as $item)
        BLADE,
        <<<'BLADE'
        @foreach($items as $item)
        BLADE,
    ],
    'variable with tags' => [
        <<<'BLADE'
        {{ <text:span>$item</text:span> }}
        BLADE,
        <<<'BLADE'
        {{ $item }}
        BLADE,
    ],
    'variable with multiple opening tags' => [
        <<<'BLADE'
        {<text:span>{ <text:span>$item</text:span> }}
        BLADE,
        <<<'BLADE'
        {{ $item }}
        BLADE,
    ],
    'variable with multiple closing tags' => [
        <<<'BLADE'
        {{ <text:span>$item</text:span> }</text:span>}
        BLADE,
        <<<'BLADE'
        {{ $item }}
        BLADE,
    ],
    'directive with tags' => [
        <<<'BLADE'
        @foreach(<text:span>$items as $item</text:span>)
        BLADE,
        <<<'BLADE'
        @foreach($items as $item)
        BLADE,
    ],
    'fix unicode arrow' => [
        <<<BLADE
        {{ \$item\u{2192}property }}
        BLADE,
        <<<'BLADE'
        {{ $item->property }}
        BLADE,
    ],
    'fix single quote' => [
        <<<BLADE
        {{ \$item[\u{2018}some\u{2018}][\u{2019}nested\u{2019}][&apos;value&apos;] }}
        BLADE,
        <<<'BLADE'
        {{ $item['some']['nested']['value'] }}
        BLADE,
    ],
    'fix double quote' => [
        <<<BLADE
        {{ \$item[\u{201C}value\u{201D}] }}
        BLADE,
        <<<'BLADE'
        {{ $item["value"] }}
        BLADE,
    ],
]);
