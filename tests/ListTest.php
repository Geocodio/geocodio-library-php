<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Exception;
use Geocodio\Enums\GeocodeDirection;
use Geocodio\Geocodio;

uses(InteractsWithAPI::class);

beforeEach(function (): void {
    $this->geocoder = new Geocodio;
    $this->geocoder->setApiKey($this->getApiKeyFromEnvironment());
    $hostname = $this->getHostnameFromEnvironment();
    if ($hostname) {
        $this->geocoder->setHostname($hostname);
    }
});

it('uploads throws an exception if the file doesn\'t exist', function (): void {
    $response = $this->geocoder->uploadList(
        __DIR__.'/Fixtures/not-found.csv',
        GeocodeDirection::Forward,
        '{{B}} {{C}} {{D}} {{E}}'
    );

    ray('uploadList', $response);
})->throws(Exception::class, 'File ('.__DIR__.'/Fixtures/not-found.csv) not found');

it('uploads a list', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->uploadList(
        __DIR__.'/Fixtures/simple.csv',
        GeocodeDirection::Forward,
        '{{B}} {{C}} {{D}} {{E}}'
    );

    ray('uploadList', $response);
});

it('uploads an inline list', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $csvData = <<<'CSV'
    name,street,city,state,zip
    "Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003
    "Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003
    CSV;

    $response = $this->geocoder->uploadInlineList(
        $csvData,
        'coffee-shops.csv',
        GeocodeDirection::Forward,
        '{{B}} {{C}} {{D}} {{E}}'
    );

    ray('uploadInlineList', $response);
});

it('can fetch your lists', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->lists();

    ray('lists', $response);
});

it('can delete lists', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->lists();

    foreach ($response['data'] as $list) {
        $response = $this->geocoder->deleteList($list['id']);
        ray('deleteList', $response);
    }
});

it('can fetch list status', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->listStatus(11950669);

    ray('listStatus', $response);
});

it('throws exception when uploading from invalid file', function (): void {
    $this->markTestIncomplete();
});
