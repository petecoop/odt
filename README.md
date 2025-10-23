# ODT

Use Open Document Text (.odt) files as templates in PHP/Laravel projects, rendering them with Blade syntax and outputting as ODT or PDF.

This allows you to create complex document templates in LibreOffice or OpenOffice, including loops and conditionals using Blade syntax, render them with dynamic data and convert into PDF.

### Why

I've found that there are some things that just can't be done when generating PDF's with HTML or PDF libraries that word processors have solved. This is usually where dynamic content can flow over multiple pages, and you want to do things like repeat table headers, or have some items of text stay together, or other more complex layouts like anchoring a section to the bottom of the final page. This also allows non-developers to create and edit document templates in a familiar word processor rather than having to write HTML or code.

### Installation

```bash
composer require petecoop/odt
```

### Usage

#### In Laravel

Access via the `ODT` facade:

```php
use Petecoop\ODT\Facades\ODT;

ODT::open(resource_path("file.odt"))
    ->render([
        "some" => "arguments",
    ]);
```

#### Other PHP Projects

Call `ODT::make()` to create an instance:

```php
use Petecoop\ODT\ODT;

ODT::make()
    ->open(__DIR__ . "/templates/file.odt")
    ->render([
        "some" => "arguments",
    ]);
```

#### Converting to PDF

Conversion can be done using either the `soffice` binary directly, or via a [Gotenberg](https://gotenberg.dev/) server. `soffice` is the default and is slower due to the overhead of starting the LibreOffice process for each conversion, but requires no additional setup (other than installing LibreOffice). Gotenberg is faster for multiple conversions as it keeps a LibreOffice process running in the background, but requires a Gotenberg server to be running.

#### Using `soffice` for conversion

The `soffice` binary is required to be installed for PDF conversion. This should be available if LibreOffice or OpenOffice is installed on the system.

To convert to PDF, use the `pdf()` method after rendering.

```php
$pdf = ODT::open(resource_path("file.odt"))
    ->render([
        "some" => "arguments",
    ])
    ->pdf();
```

If `soffice` is not in the system PATH, you can specify the path to the binary using the `officeBinary` method:

```php
ODT::officeBinary('/path/to/soffice')
    ->open(resource_path("file.odt"));

// if using ODT::make()
ODT::make('/path/to/soffice')
    ->open(__DIR__ . "/templates/file.odt");
```

#### Using Gotenberg for conversion

To use Gotenberg for PDF conversion, you need to have a Gotenberg server running. You can specify the Gotenberg server URL using the `gotenberg` method:

```php
$pdf = ODT::gotenberg('http://localhost:3000')
    ->open(resource_path("file.odt"))
    ->render([
        "some" => "arguments",
    ])
    ->pdf();
```

A HTTP Client is required to be installed, if outside of Laravel you can install the Guzzle client:

```bash
composer require php-http/guzzle7-adapter
```

See a full [list of clients](https://docs.php-http.org/en/latest/clients.html) if you'd like to use a different one.

#### Saving or Downloading

The resulting .odt or PDF file can be saved to disk or returned as a download response:

```php
$odt = ODT::open(resource_path("file.odt"))
    ->render([
        "some" => "arguments",
    ]);

// Save ODT file
$odt->save(storage_path("file.odt"));

// Convert to PDF and save
$pdf = $odt->pdf()->save(storage_path("file.pdf"));

// Return as download response in a Laravel Controller
return $pdf;
```

### Templating

Some Blade directives are provided to help with common templating tasks in ODT files.

### Image Embedding

Use the `@image` directive to embed base64 encoded images into your ODT templates. This directive accepts a base64 image string and optional maximum width and height parameters (in cm).

```blade
@image($base64ImageString, '5cm', '5cm')
```

#### Table Rows

When in Libre/Open Office you can't wrap a `@foreach` around a table row - use `@rowforeach`, this can be done inside a table cell. The entire row will be repeated for each item.

```blade
@rowforeach ($users as $user)
{{ $user->name }}
```

All other cells in the row will have access to the `$user` variable.

If you need more control over what is before / after the row use `@beforerow / @endbeforerow` and `@afterrow / @endafterrow`. This example is the equivalent of the above but allows you to put any other directives before or after the row.

```blade
@beforerow@foreach ($users as $user)@endbeforerow
{{ $user->name }}
@afterrow@endforeach@endafterrow
```
