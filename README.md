# ODT

Compile ODT files with Blade.

Requires a `BladeCompiler` to be passed into it, with the intention that it can be used outside of Laravel but still be based on Blade.

```php
$compiler = app(BladeCompiler::class);
$shared = app(Factory::class)->getShared();

$odt = new ODT($compiler);

$odt->open(resource_path('file.odt'))
    ->render(array_merge($shared, [
        'some' => 'arguments'
    ]))
    ->outputAsSymfonyResponse('file.odt');

```

## Tables

When in Libre/Open Office you can't wrap a `@foreach` around a table row - use `@beforerow / @endbeforerow` and `@afterrow / @endafterrow`

```
@beforerow@foreach ($users as $user)@endbeforerow
{{ $user->name }}
@afterrow@endforeach@endafterrow
```
