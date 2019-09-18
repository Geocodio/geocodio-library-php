# geocod.io PHP library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/geocodio/geocodio-library-php.svg?style=flat-square)](https://packagist.org/packages/geocodio/geocodio-library-php)
[![Build Status](https://img.shields.io/travis/geocodio/geocodio-library-php/master.svg?style=flat-square)](https://travis-ci.org/geocodio/geocodio-library-php)
[![Total Downloads](https://img.shields.io/packagist/dt/geocodio/geocodio-library-php.svg?style=flat-square)](https://packagist.org/packages/geocodio/geocodio-library-php)


Library for performing forward and reverse address geocoding for addresses or coordinates in the US and Canada.

The library contains an optional Laravel service provider, for easy integration into your [Laravel](https://laravel.com) app.

## Don't have an API key yet?

Sign up at [https://dash.geocod.io](https://dash.geocod.io) to get an API key. The first 2,500 lookups per day are free.

## Installation

You can install the package via composer:

```bash
composer require geocodio/geocodio-library-php
```

## Usage

```php
$geocoder = new Geocodio\Geocodio();
$geocoder->setApiKey('YOUR_API_KEY');
// $geocoder->setHostname('api-hipaa.geocod.io'); // optionally overwrite the API hostname

$results = $geocoder->geocode('1109 N Highland St, Arlington, VA');
$results = $geocoder->reverse('38.9002898,-76.9990361');
$results = $geocoder->reverse([38.9002898, -76.9990361]);
```

### Batch geocoding

To batch geocode, simply pass an array of addresses or coordinates instead of a single string

```php
$results = $geocoder->geocode([
    '1109 N Highland St, Arlington VA',
    '525 University Ave, Toronto, ON, Canada',
    '4410 S Highway 17 92, Casselberry FL',
    '15000 NE 24th Street, Redmond WA',
    '17015 Walnut Grove Drive, Morgan Hill CA'
]);

$results = $geocoder->reverse([
    '35.9746000,-77.9658000',
    '32.8793700,-96.6303900',
    '33.8337100,-117.8362320',
    '35.4171240,-80.6784760'
]);

// Optionally supply a custom key that will be returned along with results
$results = $geocoder->geocode([
    'MyId1' => '1109 N Highland St, Arlington VA',
    'MyId2' => '525 University Ave, Toronto, ON, Canada',
    'MyId3' => '4410 S Highway 17 92, Casselberry FL',
    'MyId4' => '15000 NE 24th Street, Redmond WA',
    'MyId5' => '17015 Walnut Grove Drive, Morgan Hill CA'
]);
```

### Field appends

Geocodio allows you to append additional data points such as congressional districts, census codes, timezone, ACS survey results and [https://www.geocod.io/docs/#fields](much much more).

To request additional fields, simply supply them as an array as the second parameter

```php
$results = $geocoder->geocode(
    [
        '1109 N Highland St, Arlington VA',
        '525 University Ave, Toronto, ON, Canada'
    ],
    [
        'cd',
        'timezone'
    ]
);

$results = $geocoder->reverse('38.9002898,-76.9990361', ['census2010']);
```

### Address components

For forward geocoding requests it is possible to supply [individual address components](https://www.geocod.io/docs/#single-address) instead of a full address string. This works for both single and batch geocoding requests.

```php
$results = $geocoder->geocode([
    'street' => '1109 N Highland St',
    'city' => 'Arlington',
    'state' => 'VA',
    'postal_code' => '22201'
]);

$results = $geocoder->geocode([
    [
        'street' => '1109 N Highland St',
        'city' => 'Arlington',
        'state' => 'VA'
    ],
    [
        'street' => '525 University Ave',
        'city' => 'Toronto',
        'state' => 'ON',
        'country' => 'Canada',
    ],
);
```

### Limit results

Optionally limit the number of maximum geocoding results by using the third parameter on `geocode(...)` or `reverse(...)`

```php
$results = $geocoder->geocode('1109 N Highland St, Arlington, VA', [], 1); // Only get the first result
$results = $geocoder->reverse('38.9002898,-76.9990361', ['timezone'], 5); // Return up to 5 geocoding results
```

## Usage with Laravel

This library works well without Laravel, but if you happen to be using Laravel you can enjoy a few Laravel-specific features.

The package will be auto-discovered by newer Laravel versions, so the only thing left to do is to publish the config file

```
php artisan vendor:publish --provider="Geocodio\GeocodioServiceProvider"
```

You can now go ahead and edit your config file at `config/geocodio.php`.

You will now be able to use the `Geocodio` facade, or [dependency inject](https://laravel.com/docs/6.x/container) the fully-configured `Geocodio` class.

```php
// Using facade
$results = Geocodio::geocode('1109 N Highland St, Arlington, VA');
```

```php
use Geocodio\Geocodio;

public function __construct(Geocodio $geocoder) {
    $results = $geocoder->geocode('1109 N Highland St, Arlington, VA');
}
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email security@geocod.io instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
