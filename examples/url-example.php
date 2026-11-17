<?php

declare(strict_types=1);

require __DIR__ . '/../src/UrlBuilder.php';

use Azanumer\UrlBuilder\UrlBuilder;

// 1. Build a URL with tracking parameters.
$url = UrlBuilder::from('https://example.com/blog/my-post')
    ->withQueryParams(array(
        'utm_source'   => 'newsletter',
        'utm_medium'   => 'email',
        'utm_campaign' => 'november',
    ))
    ->withFragment('comments');
echo $url . PHP_EOL;
// https://example.com/blog/my-post?utm_source=newsletter&utm_medium=email&utm_campaign=november#comments

// 2. Resolve a relative link against a base URL.
$absolute = UrlBuilder::from('../images/logo.png')
    ->toAbsolute('https://example.com/blog/my-post');
echo $absolute . PHP_EOL;
// https://example.com/images/logo.png

// 3. Append path segments without worrying about slashes.
$api = UrlBuilder::from('https://api.example.com/v1')
    ->appendPath('users')
    ->appendPath('42')
    ->withQueryParam('fields', 'name,email');
echo $api . PHP_EOL;
// https://api.example.com/v1/users/42?fields=name%2Cemail

// 4. Sign a URL (e.g. a password-reset or download link) and verify it.
$signed = UrlBuilder::from('https://example.com/reset?user=7&expires=1731500000')
    ->sign('my-app-secret');
echo $signed . PHP_EOL;

$received = UrlBuilder::from((string) $signed);
var_dump($received->verifySigned('my-app-secret')); // bool(true)

$tampered = UrlBuilder::from((string) $signed)->withQueryParam('user', '8');
var_dump($tampered->verifySigned('my-app-secret')); // bool(false)

// 5. Build from parts.
$built = UrlBuilder::fromParts(array(
    'scheme' => 'https',
    'host'   => 'example.com',
    'path'   => '/search',
    'query'  => array('q' => 'wordpress speed', 'page' => 2),
));
echo $built . PHP_EOL;
// https://example.com/search?q=wordpress%20speed&page=2
