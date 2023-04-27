<?php

namespace Geocodio\Tests;

use PHPUnit\Framework\TestCase;
use Geocodio\Geocodio;
use Geocodio\Exceptions\GeocodioException;

class ListsTest extends TestCase
{
    use InteractsWithAPI;

    /** @var Geocodio */
    private $geocoder;

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

    public function testListUploadFromNonExistentFile()
    {
        $this->expectException(GeocodioException::class);
        $geocoder->listUpload('stubs/does_not_exist.csv', 'forward', '{{B}} {{C}} {{D}} {{E}');
    }

    public function testListUploadFromInvalidFile()
    {
        $this->expectException(GeocodioException::class);
        $list = $geocoder->listUpload('stubs/invalid.csv', 'forward', '{{B}} {{C}} {{D}} {{E}');
    }

    public function testListUploadFromValidFile()
    {
        $list = $geocoder->listUpload('stubs/simple.csv', 'forward', '{{B}} {{C}} {{D}} {{E}');
        $this->assertTrue(is_int($list->id));

        do {
          $list = $geocoder->listStatus($list->id);
          sleep(10);
        } while ($list->status->state === 'ENQUEUED' || $list->status->state === 'PROCESSING');

        $this->assertEquals('COMPLETED', $list->status->state);
        $output = $geocoder->listDownload($list->id);
        $geocoder->listDelete($list->id);
    }

    public function testListUploadFromData()
    {
        // ...or using inline data
        $csvData = <<<CSV
        name,street,city,state,zip
        "Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003
        "Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003
        CSV;
        $list = $geocoder->listUpload($csvData, 'forward', '{{B}} {{C}} {{D}} {{E}}');
    }

    public function testListAll()
    {
        $lists = $geocoder->listAll();
        $this->assertGreaterThan(0, count($lists->data));
    }
}
