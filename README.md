# geocod.io PHP library [![Latest Version][packagist-image]][packagist-url] [![Total Downloads][downloads-image]][downloads-url]

> Library for performing forward and reverse address geocoding for addresses or coordinates in the US and Canada.

<!-- toc -->

- [Installation](#installation)
- [Usage](#usage)
  * [Single geocoding](#single-geocoding)
  * [Batch geocoding](#batch-geocoding)
  * [Field appends](#field-appends)
  * [Address components](#address-components)
  * [Limit results](#limit-results)
  * [Uploading Lists](#uploading-lists)
    + [Upload list from a file](#upload-list-from-a-file)
    + [Upload list of inline data](#upload-list-of-inline-data)
    + [List processing status](#list-processing-status)
    + [Download a list](#download-a-list)
    + [Fetch all uploaded lists](#fetch-all-uploaded-lists)
    + [Delete uploaded list](#delete-uploaded-list)
  * [Distance calculations](#distance-calculations)
    + [Coordinate format with custom IDs](#coordinate-format-with-custom-ids)
    + [Distance mode and units](#distance-mode-and-units)
    + [Add distance to geocoding requests](#add-distance-to-geocoding-requests)
    + [Single origin to multiple destinations](#single-origin-to-multiple-destinations)
    + [Distance matrix (multiple origins × destinations)](#distance-matrix-multiple-origins--destinations)
    + [Nearest mode (find closest destinations)](#nearest-mode-find-closest-destinations)
    + [Async Distance Matrix Jobs](#async-distance-matrix-jobs)
- [Usage with Laravel](#usage-with-laravel)
- [Testing](#testing)
- [Changelog](#changelog)
- [Security](#security)
- [License](#license)

<!-- tocstop -->

## Installation

You can install the package via composer:

```bash
composer require geocodio/geocodio-library-php
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
array:2 [
  "input" => array:2 [
    "address_components" => array:8 [
      "number" => "1109"
      "predirectional" => "N"
      "street" => "Highland"
      "suffix" => "St"
      "formatted_street" => "N Highland St"
      "city" => "Arlington"
      "state" => "VA"
      "country" => "US"
    ]
    "formatted_address" => "1109 N Highland St, Arlington, VA"
  ]
  "results" => array:1 [
    0 => array:6 [
      "address_components" => array:10 [
        "number" => "1109"
        "predirectional" => "N"
        "street" => "Highland"
        "suffix" => "St"
        "formatted_street" => "N Highland St"
        "city" => "Arlington"
        "county" => "Arlington County"
        "state" => "VA"
        "zip" => "22201"
        "country" => "US"
      ]
      "formatted_address" => "1109 N Highland St, Arlington, VA 22201"
      "location" => array:2 [
        "lat" => 38.886672
        "lng" => -77.094735
      ]
      "accuracy" => 1
      "accuracy_type" => "rooftop"
      "source" => "Arlington"
    ]
  ]
]
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

### Uploading Lists

The lists API lets you upload and process spreadsheet with addresses or coordinates. Similar to the spreadsheet feature in the dashboard, the spreadsheet will be processed as a job on Geocodio's infrastructure and can be downloaded at a later time. While a spreadsheet is being processed it is possible to query the status and progress.

> [!IMPORTANT]
> Data for spreadsheets processed through the lists API are automatically deleted 72 hours after they have finished processing. In addition to a 1GB file size limit, we recommend a maximum of 10M lookups per list batch. Larger batches should be split up into multiple list jobs.

See the [API docs for geocoding lists](https://www.geocod.io/docs/#geocoding-lists) for additional details.

#### Upload list from a file
Creates a new spreadsheet list job and starts processing the list in the background. The response returns a list id that can be used to retrieve the job progress as well as download the processed list when it has completed.

```php
$response = $geocoder->uploadList(
    file: 'path/to/file.csv',
    direction: GeocodeDirection::Forward,
    format: '{{B}} {{C}} {{D}} {{E}}',
    callbackWebhook: 'https://example.com/callbacks/list-upload',
);

/*
array:2 [
  "id" => 11953719
  "file" => array:3 [
    "headers" => array:5 [
      0 => "Name"
      1 => "Address"
      2 => "City"
      3 => "State"
      4 => "Zip"
    ]
    "estimated_rows_count" => 4
    "filename" => "simple.csv"
  ]
]
*/
```

#### Upload list of inline data

```php
$csvData = <<<'CSV'
name,street,city,state,zip
"Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003
"Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003
CSV;

$geocodio->uploadInlineList(
    $csvData,
    'coffee-shops.csv',
    GeocodeDirection::Forward,
    '{{B}} {{C}} {{D}} {{E}}'
);
```

#### List status
View the metadata and status for a single uploaded list.

```php
$geocoder->listStatus(11950669);

/*
array:6 [
  "id" => 11953719
  "fields" => []
  "file" => array:2 [
    "estimated_rows_count" => 4
    "filename" => "simple.csv"
  ]
  "status" => array:5 [
    "state" => "COMPLETED"
    "progress" => 100
    "message" => "Completed"
    "time_left_description" => null
    "time_left_seconds" => null
  ]
  "download_url" => "https://api.geocod.io/v1.9/lists/11953719/download"
  "expires_at" => "2025-08-22T20:36:10.000000Z"
]
*/
```

#### Download a list
Download a fully geocoded list, the returned format will always be a UTF-8 encoded, comma-separated csv file.

```php
$geocoder->downloadList(11950669, 'path/to/file.csv');
```

#### Fetch all uploaded lists
Show all lists that have been created. The endpoint is paginated, showing 15 lists at a time, ordered by recency.

```php
$geocoder->lists();

/*
array:9 [
  "current_page" => 1
  "data" => array:1 [
    0 => array:6 [
      "id" => 11953719
      "fields" => []
      "file" => array:2 [
        "estimated_rows_count" => 4
        "filename" => "simple.csv"
      ]
      "status" => array:5 [
        "state" => "COMPLETED"
        "progress" => 100
        "message" => "Completed"
        "time_left_description" => null
        "time_left_seconds" => null
      ]
      "download_url" => "https://api.geocod.io/v1.9/lists/11953719/download"
      "expires_at" => "2025-08-22T20:36:10.000000Z"
    ]
  "first_page_url" => "https://api.geocod.io/v1.9/lists?page=1"
  "from" => 1
  "next_page_url" => null
  "path" => "https://api.geocod.io/v1.9/lists"
  "per_page" => 15
  "prev_page_url" => null
  "to" => 3
]
*/
```

#### Delete uploaded list
Delete a previously uploaded list and its underlying spreadsheet data permanently. This can also be used to cancel and delete a spreadsheet that is currently processing.

Geocodio Unlimited customers can cancel a spreadsheet at any time. Pay as You Go customers can only cancel a spreadsheet if it was just recently started.

The spreadsheet data will always be deleted automatically after 72 hours if it is not deleted manually first.

```php
$geocoder->deleteList(11950669);
```

### Distance calculations

Calculate distances from a single origin to multiple destinations, or compute full distance matrices.

#### Coordinate format with custom IDs

You can add custom identifiers to coordinates using the `lat,lng,id` format. The ID will be returned in the response, making it easy to match results back to your data:

```php
// String format with ID
'37.7749,-122.4194,warehouse_1'

// Array format with ID
[37.7749, -122.4194, 'warehouse_1']

// The ID is returned in the response:
// [
//     'query' => '37.7749,-122.4194,warehouse_1',
//     'location' => [37.7749, -122.4194],
//     'id' => 'warehouse_1',
//     'distance_miles' => 3.2,
//     'distance_km' => 5.1
// ]
```

#### Distance mode and units

The SDK provides enums for type-safe distance configuration:

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;

// Available modes
DistanceMode::Straightline  // Default - great-circle (as the crow flies)
DistanceMode::Driving       // Road network routing with duration
DistanceMode::Haversine     // Alias for Straightline (backward compat)

// Available units
DistanceUnits::Miles  // Default
DistanceUnits::Kilometers

// Sorting options
DistanceOrderBy::Distance  // Default
DistanceOrderBy::Duration

DistanceSortOrder::Asc   // Default
DistanceSortOrder::Desc
```

> **Note:** The default mode is `straightline` (great-circle distance). Use `DistanceMode::Driving` if you need road network routing with duration estimates.

#### Add distance to geocoding requests

You can add distance calculations to existing geocode or reverse geocode requests. Each geocoded result will include a `destinations` array with distances to each destination.

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;

// Geocode an address and calculate distances to store locations
$response = $geocoder->geocode(
    '1600 Pennsylvania Ave NW, Washington DC',
    [],     // fields
    null,   // limit
    null,   // format
    [       // destinations with custom IDs
        '38.9072,-77.0369,store_dc',
        '39.2904,-76.6122,store_baltimore',
        '39.9526,-75.1652,store_philly'
    ],
    DistanceMode::Driving,
    DistanceUnits::Miles
);

/*
Response includes destinations for each geocoded result:
[
    'input' => [...],
    'results' => [
        [
            'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
            'location' => ['lat' => 38.8977, 'lng' => -77.0365],
            'destinations' => [
                [
                    'query' => '38.9072,-77.0369,store_dc',
                    'location' => [38.9072, -77.0369],
                    'id' => 'store_dc',
                    'distance_miles' => 0.8,
                    'distance_km' => 1.3,
                    'duration_seconds' => 180
                ],
                [
                    'query' => '39.2904,-76.6122,store_baltimore',
                    'location' => [39.2904, -76.6122],
                    'id' => 'store_baltimore',
                    'distance_miles' => 38.2,
                    'distance_km' => 61.5,
                    'duration_seconds' => 2820
                ],
                // ...
            ]
        ]
    ]
]
*/

// Reverse geocode with distances
$response = $geocoder->reverse(
    query: '38.8977,-77.0365',
    destinations: [
        '38.9072,-77.0369,capitol',
        '38.8895,-77.0353,monument'
    ],
    distanceMode: DistanceMode::Straightline
);

// With filtering - find nearest 3 stores within 50 miles
$response = $geocoder->geocode(
    '1600 Pennsylvania Ave NW, Washington DC',
    destinations: [
        '38.9072,-77.0369,store_1',
        '39.2904,-76.6122,store_2',
        '39.9526,-75.1652,store_3',
        '40.7128,-74.0060,store_4'
    ],
    distanceMode: DistanceMode::Driving,
    distanceMaxResults: 3,
    distanceMaxDistance: 50.0,
    distanceOrderBy: DistanceOrderBy::Distance,
    distanceSortOrder: DistanceSortOrder::Asc
);
```

#### Single origin to multiple destinations

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;

// Calculate distances from one origin to multiple destinations
$response = $geocoder->distance(
    '37.7749,-122.4194,headquarters',  // Origin with ID
    [
        '37.7849,-122.4094,customer_a',
        '37.7949,-122.3994,customer_b',
        '37.8049,-122.4294,customer_c'
    ]
);

/*
Response:
[
    'origin' => [
        'query' => '37.7749,-122.4194,headquarters',
        'location' => [37.7749, -122.4194],
        'id' => 'headquarters'
    ],
    'destinations' => [
        [
            'query' => '37.7849,-122.4094,customer_a',
            'location' => [37.7849, -122.4094],
            'id' => 'customer_a',
            'distance_miles' => 0.9,
            'distance_km' => 1.4
        ],
        // ...
    ]
]
*/

// Use driving mode for road network routing (includes duration)
$response = $geocoder->distance(
    '37.7749,-122.4194',
    ['37.7849,-122.4094'],
    mode: DistanceMode::Driving
);

// With all filtering and sorting options
$response = $geocoder->distance(
    origin: '37.7749,-122.4194,warehouse',
    destinations: [
        '37.7849,-122.4094,store_1',
        '37.7949,-122.3994,store_2',
        '37.8049,-122.4294,store_3'
    ],
    mode: DistanceMode::Driving,
    units: DistanceUnits::Kilometers,
    maxResults: 2,
    maxDistance: 10.0,
    orderBy: DistanceOrderBy::Distance,
    sortOrder: DistanceSortOrder::Asc
);

// Array format for coordinates (with or without ID)
$response = $geocoder->distance(
    [37.7749, -122.4194],                    // Without ID
    [[37.7849, -122.4094, 'dest_1']]         // With ID as third element
);
```

#### Distance matrix (multiple origins × destinations)

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;

// Calculate full distance matrix with custom IDs
$response = $geocoder->distanceMatrix(
    origins: [
        '37.7749,-122.4194,warehouse_sf',
        '37.8049,-122.4294,warehouse_oak'
    ],
    destinations: [
        '37.7849,-122.4094,customer_1',
        '37.7949,-122.3994,customer_2'
    ]
);

/*
Response structure:
[
    'results' => [
        [
            'origin' => [
                'query' => '37.7749,-122.4194,warehouse_sf',
                'location' => [37.7749, -122.4194],
                'id' => 'warehouse_sf'
            ],
            'destinations' => [
                [
                    'query' => '37.7849,-122.4094,customer_1',
                    'location' => [37.7849, -122.4094],
                    'id' => 'customer_1',
                    'distance_miles' => 0.9,
                    'distance_km' => 1.4
                ],
                // ...
            ]
        ],
        [
            'origin' => [..., 'id' => 'warehouse_oak'],
            'destinations' => [...]
        ]
    ]
]
*/

// With driving mode and kilometers
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: ['37.7849,-122.4094'],
    mode: DistanceMode::Driving,
    units: DistanceUnits::Kilometers
);
```

#### Nearest mode (find closest destinations)

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;

// Find up to 2 nearest destinations from each origin
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: ['37.7849,-122.4094', '37.7949,-122.3994', '37.8049,-122.4294'],
    maxResults: 2
);

// Filter by maximum distance (in miles or km depending on units)
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: [...],
    maxDistance: 2.0
);

// Filter by minimum and maximum distance
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: [...],
    minDistance: 1.0,
    maxDistance: 10.0
);

// Filter by duration (seconds, driving mode only)
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: [...],
    mode: DistanceMode::Driving,
    maxDuration: 300,  // 5 minutes
    minDuration: 60    // 1 minute minimum
);

// Sort by duration descending
$response = $geocoder->distanceMatrix(
    origins: ['37.7749,-122.4194'],
    destinations: [...],
    mode: DistanceMode::Driving,
    maxResults: 5,
    orderBy: DistanceOrderBy::Duration,
    sortOrder: DistanceSortOrder::Desc
);
```

#### Async Distance Matrix Jobs

For large distance matrix calculations, use async jobs that process in the background.

```php
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceUnits;

// Create a new distance matrix job
$job = $geocoder->createDistanceMatrixJob(
    name: 'My Distance Calculation',
    origins: ['37.7749,-122.4194', '37.8049,-122.4294'],
    destinations: ['37.7849,-122.4094', '37.7949,-122.3994'],
    mode: DistanceMode::Driving,
    units: DistanceUnits::Miles,
    callbackUrl: 'https://example.com/webhook'  // Optional
);

// Or use list IDs from previously uploaded lists
$job = $geocoder->createDistanceMatrixJob(
    name: 'Distance from List',
    origins: 12345,       // List ID
    destinations: 67890,  // List ID
    mode: DistanceMode::Straightline
);

echo $job['id'];  // Job identifier

// Check job status
$status = $geocoder->distanceMatrixJobStatus($job['id']);
/*
[
    'id' => 'abc123',
    'name' => 'My Distance Calculation',
    'status' => [
        'state' => 'COMPLETED',  // or 'PROCESSING', 'FAILED'
        'progress' => 100,
        'message' => 'Completed'
    ],
    'download_url' => 'https://api.geocod.io/v1.9/distance-matrix/abc123/download',
    'expires_at' => '2025-01-15T12:00:00.000000Z'
]
*/

// List all jobs
$jobs = $geocoder->distanceMatrixJobs();
$jobs = $geocoder->distanceMatrixJobs(page: 2);  // Paginated

// Get job results as parsed array (same format as distance POST)
$results = $geocoder->getDistanceMatrixJobResults($job['id']);
/*
[
    'results' => [
        [
            'origin' => ['query' => '...', 'location' => [...], 'id' => '...'],
            'destinations' => [
                ['query' => '...', 'location' => [...], 'id' => '...', 'distance_miles' => 1.2, ...]
            ]
        ],
        // ...
    ]
]
*/

// Or download to file for very large results
$geocoder->downloadDistanceMatrixJob($job['id'], '/path/to/results.json');

// Delete a job
$geocoder->deleteDistanceMatrixJob($job['id']);
```

> Note: Billing is based on the `elements_billed` value returned in the response. Driving mode has a 2× multiplier compared to straightline mode.

## Usage with Laravel

This library works well without Laravel, but if you happen to be using Laravel you can enjoy a few Laravel-specific features.

The package will be auto-discovered by newer Laravel versions, so the only thing left to do is to publish the config file

```
php artisan vendor:publish --provider="Geocodio\GeocodioServiceProvider"
```

You can now go ahead and edit your config file at `config/geocodio.php`.

You will now be able to use the `Geocodio` facade, or [dependency inject](https://laravel.com/docs/10.x/container) the fully-configured `Geocodio` class.

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
[downloads-image]: https://img.shields.io/packagist/dt/geocodio/geocodio-library-php.svg?style=flat-square
[downloads-url]: https://packagist.org/packages/geocodio/geocodio-library-php
