# PHP URL Builder

A dependency-free URL builder for PHP 7.4+: parse, mutate and (optionally) sign URLs with an immutable, fluent API. No more string concatenation bugs, double slashes, or hand-rolled `http_build_query` calls.

## Features

- **Parse and rebuild** — `UrlBuilder::from()` handles absolute and relative URLs; `fromParts()` builds from an array.
- **Immutable mutators** — `withScheme()`, `withHost()`, `withPort()`, `withPath()`, `appendPath()`, `withQueryParam()`, `withQueryParams()`, `withoutQueryParam()`, `withFragment()` — every call returns a new instance.
- **RFC 3986 encoding** — query values use `rawurlencode` (spaces become `%20`, not `+`); array values use the `key[]=value` form; default ports (80/443) are dropped.
- **Resolve relative URLs** — `toAbsolute($base)` merges paths and removes `.` / `..` segments per RFC 3986 §5.2.4.
- **HMAC signing** — `sign($secret)` appends a `sig` parameter covering scheme, host, path and sorted query (fragments excluded, since browsers never send them); `verifySigned($secret)` checks it timing-safely with `hash_equals`. Perfect for password-reset, invite and signed-download links.

## Usage

```php
require 'src/UrlBuilder.php';

use Azanumer\UrlBuilder\UrlBuilder;

$url = UrlBuilder::from('https://example.com/blog?page=2')
    ->withQueryParam('utm_source', 'newsletter')
    ->withoutQueryParam('page')
    ->withFragment('comments');

echo $url; // https://example.com/blog?utm_source=newsletter#comments

$signed = UrlBuilder::from('https://example.com/reset?user=7')
    ->sign('my-app-secret');

UrlBuilder::from((string) $signed)->verifySigned('my-app-secret'); // true
```

See `examples/url-example.php` for a runnable demo of all features.

## Requirements

PHP 7.4+ (no extensions beyond core `hash`).
