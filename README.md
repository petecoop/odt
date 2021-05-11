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

## Output as PDF

Libreoffice or OpenOffice must be installed and the path to the `soffice` binary must be given.

```php
ODT::open(resource_path('file.odt'))
    ->render([
        'some' => 'arguments'
    ])
    ->saveAsPDF('/tmp/output.pdf', 'path/to/soffice');
```

## Tables

When in Libre/Open Office you can't wrap a `@foreach` around a table row - use `@beforerow / @endbeforerow` and `@afterrow / @endafterrow`

```
@beforerow@foreach ($users as $user)@endbeforerow
{{ $user->name }}
@afterrow@endforeach@endafterrow
```
