<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory;
use Petecoop\ODT\Compiler;
use Petecoop\ODT\Facades\ODT as ODTFacade;
use Petecoop\ODT\ODT;

test('the Laravel service provider resolves ODT with shared view data', function () {
    $this->app->make(Factory::class)->share('organisation', 'Acme Ltd');

    $template = $this->template('<text:p>{{ $organisation }}</text:p>');
    $template->setCompiler($this->app->make(ODT::class)->compiler());

    expect(ODTFacade::compiler())->toBeInstanceOf(Compiler::class);
    expect($template->render()->content())->toBe('<text:p>Acme Ltd</text:p>');
});
