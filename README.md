# geocod.io PHP library [![Latest Version][packagist-image]][packagist-url] [![Build Status][travis-image]][travis-url] [![Total Downloads][downloads-image]][downloads-url]

> Library for performing forward and reverse address geocoding for addresses or coordinates in the US and Canada.

<!-- toc -->

- [Installation](#installation)
- [Usage](#usage)
  * [Single geocoding](#single-geocoding)
  * [Batch geocoding](#batch-geocoding)
  * [Field appends](#field-appends)
  * [Address components](#address-components)
  * [Limit results](#limit-results)
- [Usage with Laravel](#usage-with-laravel)
- [Testing](#testing)
- [Changelog](#changelog)
- [Security](#security)
- [License](#license)

<!-- tocstop -->

## Installation

You can install the package via composer:

```bash
$ composer require geocodio/geocodio-library-php
```

> Using [Laravel](https://laravel.com)? Great! There's an optional Laravel service provider, for easy integration into your app.

## Usage

> Don't have an API key yet? Sign up at [https://dash.geocod.io](https://dash.geocod.io) to get an API key. The first 2,500 lookups per day are free.

### Single geocoding

> Using the [Laravel](https://laravel.com) integration? Check out [Laravel-specific usage examples](#usage-with-laravel) below.

```php
$geocoder = new Geocodio\Geocodio();
$geocoder->setApiKey('YOUR_API_KEY');
// $geocoder->setHostname('api-hipaa.geocod.io'); // optionally overwrite the API hostname

$response = $geocoder->geocode('1109 N Highland St, Arlington, VA');
dump($response);
/*
{
  "input": {
    "address_components": {
      "number": "1109"                                                       
      "predirectional": "N"                                                   
      "street": "Highland"                                                   
      "suffix": "St"                                                    
      "formatted_street": "N Highland St"                                     
      "city": "Arlington"                                                     
      "state": "VA"                                                           
      "country": "US"                                                         
    }                                                                        
    "formatted_address": "1109 N Highland St, Arlington, VA"                
  }             
  "results": array:2 [                                                         
    0 => {
      "address_components": {
        "number": "1109"                                                       
        "predirectional": "N"
        "street": "Highland"   
        "suffix": "St"
        "formatted_street": "N Highland St"
        "city": "Arlington"
        "county": "Arlington County"
        "state": "VA"
        "zip": "22201"
        "country": "US"
      }
      "formatted_address": "1109 N Highland St, Arlington, VA 22201"
      "location": {
        "lat": 38.886672
        "lng": -77.094735
      }
      "accuracy": 1
      "accuracy_type": "rooftop"
      "source": "Arlington"
    }
    1 => {
      "address_components": {
        "number": "1109"
        "predirectional": "N"
        "street": "Highland"
        "suffix": "St"
        "formatted_street": "N Highland St"
        "city": "Arlington"
        "county": "Arlington County"
        "state": "VA"
        "zip": "22201"
        "country": "US"
      }
      "formatted_address": "1109 N Highland St, Arlington, VA 22201"
      "location": {
        "lat": 38.886665
        "lng": -77.094733
      }
      "accuracy": 1
      "accuracy_type": "rooftop"
      "source": "Virginia Geographic Information Network (VGIN)"
    }
  ]
}
*/

$response = $geocoder->reverse('38.9002898,-76.9990361');
$response = $geocoder->reverse([38.9002898, -76.9990361]);
```

> Note: You can read more about accuracy scores, accuracy types, input formats and more at https://www.geocod.io/docs/

### Batch geocoding

To batch geocode, simply pass an array of addresses or coordinates instead of a single string

```php
$response = $geocoder->geocode([
    '1109 N Highland St, Arlington VA',
    '525 University Ave, Toronto, ON, Canada',
    '4410 S Highway 17 92, Casselberry FL',
    '15000 NE 24th Street, Redmond WA',
    '17015 Walnut Grove Drive, Morgan Hill CA'
]);

$response = $geocoder->reverse([
    '35.9746000,-77.9658000',
    '32.8793700,-96.6303900',
    '33.8337100,-117.8362320',
    '35.4171240,-80.6784760'
]);

// Optionally supply a custom key that will be returned along with results
$response = $geocoder->geocode([
    'MyId1' => '1109 N Highland St, Arlington VA',
    'MyId2' => '525 University Ave, Toronto, ON, Canada',
    'MyId3' => '4410 S Highway 17 92, Casselberry FL',
    'MyId4' => '15000 NE 24th Street, Redmond WA',
    'MyId5' => '17015 Walnut Grove Drive, Morgan Hill CA'
]);
```

### Field appends

Geocodio allows you to append additional data points such as congressional districts, census codes, timezone, ACS survey results and [much much more](https://www.geocod.io/docs/#fields).

To request additional fields, simply supply them as an array as the second parameter

```php
$response = $geocoder->geocode(
    [
        '1109 N Highland St, Arlington VA',
        '525 University Ave, Toronto, ON, Canada'
    ],
    [
        'cd',
        'timezone'
    ]
);

$response = $geocoder->reverse('38.9002898,-76.9990361', ['census2010']);
```

### Address components

For forward geocoding requests it is possible to supply [individual address components](https://www.geocod.io/docs/#single-address) instead of a full address string. This works for both single and batch geocoding requests.

```php
$response = $geocoder->geocode([
    'street' => '1109 N Highland St',
    'city' => 'Arlington',
    'state' => 'VA',
    'postal_code' => '22201'
]);

$response = $geocoder->geocode([
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
$response = $geocoder->geocode('1109 N Highland St, Arlington, VA', [], 1); // Only get the first result
$response = $geocoder->reverse('38.9002898,-76.9990361', ['timezone'], 5); // Return up to 5 geocoding results
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
use Geocodio;

$response = Geocodio::geocode('1109 N Highland St, Arlington, VA');
```

```php
// Using dependency injection
use Geocodio\Geocodio;

class SomeController {
  public function __construct(Geocodio $geocoder) {
      $response = $geocoder->geocode('1109 N Highland St, Arlington, VA');
  }
}
```

## Testing

```bash
$ composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email security@geocod.io instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[packagist-image]: https://img.shields.io/packagist/v/geocodio/geocodio-library-php.svg?style=flat-square
[packagist-url]: https://packagist.org/packages/geocodio/geocodio-library-php
[travis-image]: https://img.shields.io/travis/geocodio/geocodio-library-php/master.svg?style=flat-square
[travis-url]: https://travis-ci.org/geocodio/geocodio-library-php
[downloads-image]: https://img.shields.io/packagist/dt/geocodio/geocodio-library-php.svg?style=flat-square
[downloads-url]: https://packagist.org/packages/geocodio/geocodio-library-php
