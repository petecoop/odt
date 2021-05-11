# ODT

Compile ODT files with Blade.

`composer require petecoop/odt`

```php
use Petecoop\ODT\Facades\ODT;

ODT::open(resource_path('file.odt'))
    ->render([
        'some' => 'arguments'
    ])
    ->symfonyResponse('file.odt');
```

## Tables

When in Libre/Open Office you can't wrap a `@foreach` around a table row - use `@beforerow / @endbeforerow` and `@afterrow / @endafterrow`

```
@beforerow@foreach ($users as $user)@endbeforerow
{{ $user->name }}
@afterrow@endforeach@endafterrow
```
