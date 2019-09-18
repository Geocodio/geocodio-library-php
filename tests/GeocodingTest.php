<?php

namespace Geocodio\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Geocodio\Geocodio;

class GeocodingTest extends TestCase
{
    use InteractsWithAPI;

    /** @var Geocodio */
    private $gecoder;

    public function setUp(): void
    {
        parent::setUp();

        $this->geocoder = new Geocodio();
        $this->geocoder->setApiKey($this->getApiKeyFromEnvironment());

        $hostname = $this->getHostnameFromEnvironment();
        if ($hostname) {
            $this->geocoder->setHostname($hostname);
        }
    }

    public function testSingleForwardGeocode()
    {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA');
        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $response->results[0]->formatted_address);
    }

    public function testSingleForwardGeocodeComponents()
    {
        $response = $this->geocoder->geocode(['street' => '1109 N Highland St', 'postal_code' => '22201']);
        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $response->results[0]->formatted_address);
    }

    public function testSingleReverseGeocode()
    {
        $responseStr = $this->geocoder->reverse('38.886665,-77.094733');
        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $responseStr->results[0]->formatted_address);

        $responseArr = $this->geocoder->reverse([38.886665, -77.094733]);
        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $responseArr->results[0]->formatted_address);
    }

    public function testBatchForwardGeocode()
    {
        $response = $this->geocoder->geocode([
            '1109 N Highland St, Arlington VA',
            '525 University Ave, Toronto, ON, Canada'
        ]);

        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $response->results[0]->response->results[0]->formatted_address);
        $this->assertEquals('525 University Ave, Toronto, ON', $response->results[1]->response->results[0]->formatted_address);
    }

    public function testBatchForwardGeocodeComponents()
    {
        $response = $this->geocoder->geocode([
            ['street' => '1109 N Highland St', 'postal_code' => '22201'],
            ['street' => '525 University Ave', 'city' => 'Toronto', 'state' => 'Ontario', 'country' => 'Canada'],
        ]);

        $this->assertEquals('1109 N Highland St, Arlington, VA 22201', $response->results[0]->response->results[0]->formatted_address);
        $this->assertEquals('525 University Ave, Toronto, ON', $response->results[1]->response->results[0]->formatted_address);
    }

    public function testBatchReverseGeocode()
    {
        $responseStr = $this->geocoder->reverse([
            '35.9746000,-77.9658000',
            '32.8793700,-96.6303900'
        ]);
        $this->assertEquals('101 W Washington St, Nashville, NC 27856', $responseStr->results[0]->response->results[0]->formatted_address);
        $this->assertEquals('3034 S 1st St, Garland, TX 75041', $responseStr->results[1]->response->results[0]->formatted_address);

        $responseArr = $this->geocoder->reverse([
            ['35.9746000', '-77.9658000'],
            ['32.8793700', '-96.6303900']
        ]);
        $this->assertEquals('101 W Washington St, Nashville, NC 27856', $responseArr->results[0]->response->results[0]->formatted_address);
        $this->assertEquals('3034 S 1st St, Garland, TX 75041', $responseArr->results[1]->response->results[0]->formatted_address);
    }

    public function testFieldAppends()
    {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA', ['timezone']);
        $this->assertEquals('EST', $response->results[0]->fields->timezone->abbreviation);

        $response = $this->geocoder->reverse('38.886665,-77.094733', ['timezone']);
        $this->assertEquals('EST', $response->results[0]->fields->timezone->abbreviation);

        $response = $this->geocoder->geocode(['1109 N Highland St, Arlington VA'], ['timezone']);
        $this->assertEquals('EST', $response->results[0]->response->results[0]->fields->timezone->abbreviation);

        $response = $this->geocoder->reverse(['38.886665,-77.094733'], ['timezone']);
        $this->assertEquals('EST', $response->results[0]->response->results[0]->fields->timezone->abbreviation);
    }

    public function testLimitParameter()
    {
        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA');
        $this->assertGreaterThan(1, count($response->results));

        $response = $this->geocoder->geocode('1109 N Highland St, Arlington VA', [], 1);
        $this->assertEquals(1, count($response->results));
    }
}
