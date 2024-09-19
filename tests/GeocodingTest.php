<?php

namespace Geocodio\Tests;

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

describe('Forward Geocoding', function (): void {
    it('can perform single forward geocode', function (): void {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA');

        expect($response['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');
    });

    it('can perform single forward geocode with components', function (): void {
        $response = $this->geocoder->geocode(['street' => '1109 N Highland St', 'postal_code' => '22201']);

        expect($response['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');
    });

    it('can perform batch forward geocode', function (): void {
        $response = $this->geocoder->geocode([
            '1109 N Highland St, Arlington VA',
            '525 University Ave, Toronto, ON, Canada',
        ]);

        expect($response['results'][0]['response']['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');
        expect($response['results'][1]['response']['results'][0]['formatted_address'])
            ->toBe('525 University Ave, Toronto, ON M5G');
    });

    it('can perform batch forward geocode with components', function (): void {
        $response = $this->geocoder->geocode([
            ['street' => '1109 N Highland St', 'postal_code' => '22201'],
            ['street' => '525 University Ave', 'city' => 'Toronto', 'state' => 'Ontario', 'country' => 'Canada'],
        ]);

        expect($response['results'][0]['response']['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');
        expect($response['results'][1]['response']['results'][0]['formatted_address'])
            ->toBe('525 University Ave, Toronto, ON M5G');
    });
});

describe('Reverse Geocoding', function (): void {
    it('can perform single reverse geocode', function (): void {
        $responseStr = $this->geocoder->reverse('38.886665,-77.094733');

        expect($responseStr['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');

        $responseArr = $this->geocoder->reverse([38.886665, -77.094733]);

        expect($responseArr['results'][0]['formatted_address'])
            ->toBe('1109 N Highland St, Arlington, VA 22201');
    });

    it('can perform batch reverse geocode', function (): void {
        $responseStr = $this->geocoder->reverse([
            '35.9746000,-77.9658000',
            '32.8793700,-96.6303900',
        ]);

        expect($responseStr['results'][0]['response']['results'][0]['formatted_address'])
            ->toBe('101 W Washington St, Nashville, NC 27856');
        expect($responseStr['results'][1]['response']['results'][0]['formatted_address'])
            ->toBe('3034 S 1st St, Garland, TX 75041');

        $responseArr = $this->geocoder->reverse([
            ['35.9746000', '-77.9658000'],
            ['32.8793700', '-96.6303900'],
        ]);

        expect($responseArr['results'][0]['response']['results'][0]['formatted_address'])
            ->toBe('101 W Washington St, Nashville, NC 27856');
        expect($responseArr['results'][1]['response']['results'][0]['formatted_address'])
            ->toBe('3034 S 1st St, Garland, TX 75041');
    });
});

describe('Additional Features', function (): void {
    it('can append fields', function (): void {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA', ['timezone']);

        expect($response['results'][0]['fields']['timezone']['abbreviation'])
            ->toBe('EST');

        $response = $this->geocoder->reverse('38.886665,-77.094733', ['timezone']);

        expect($response['results'][0]['fields']['timezone']['abbreviation'])
            ->toBe('EST');

        $response = $this->geocoder->geocode(['1109 N Highland St, Arlington VA'], ['timezone']);

        expect($response['results'][0]['response']['results'][0]['fields']['timezone']['abbreviation'])
            ->toBe('EST');

        $response = $this->geocoder->reverse(['38.886665,-77.094733'], ['timezone']);

        expect($response['results'][0]['response']['results'][0]['fields']['timezone']['abbreviation'])
            ->toBe('EST');
    });

    it('can use limit parameter', function (): void {
        $response = $this->geocoder->geocode('1107 N Highland St, Arlington VA');

        expect(count($response['results']))->toBeGreaterThan(1);

        $response = $this->geocoder->geocode('1107 N Highland St, Arlington VA', [], 1);

        expect(count($response['results']))->toBe(1);
    });

    it('can use format simple parameter', function (): void {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA', [], null, 'simple');

        expect($response['address'])->toBe('1109 N Highland St, Arlington, VA 22201');
        expect($response['lat'])->toBe(38.886672);
        expect($response['lng'])->toBe(-77.094735);

        $response = $this->geocoder->reverse('38.886672,-77.094735', [], null, 'simple');

        expect($response['address'])->toBe('1109 N Highland St, Arlington, VA 22201');
        expect($response['lat'])->toBe(38.886672);
        expect($response['lng'])->toBe(-77.094735);
    });
});
