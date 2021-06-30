<?php


namespace Core;


use Microsoft\Graph\Core\NationalCloud;

class NationalCloudTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudConstantsAreValid() {
        $nationalClouds = array_values((new \ReflectionClass(NationalCloud::class))->getConstants());
        foreach ($nationalClouds as $nationalCloud) {
            $this->assertTrue(NationalCloud::isValidNationalCloudHost($nationalCloud));
        }
    }

    function testNationalCloudWithPortIsValid() {
        $this->assertTrue(NationalCloud::isValidNationalCloudHost(NationalCloud::GLOBAL.":1234"));
    }

    function testEmptyNationalCloudUrlInvalid() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost(""));
    }

    function testNullNationalCloudThrowsError() {
        $this->expectException(\TypeError::class);
        NationalCloud::isValidNationalCloudHost(null);
    }

    function testInvalidNationalCloud() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost("https://www.microsoft.com"));
    }

    function testNationalCloudWithoutSchemeInvalid() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost("graph.microsoft.com"));
    }

    function testNationalCloudWithUrlPathInvalid() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost(NationalCloud::GLOBAL."/v1.0"));
    }

    function testNationalCloudWithQueryParamsInvalid() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost(NationalCloud::GLOBAL."?key=value"));
    }

    function testMalformedNationalCloudInvalid() {
        $this->assertFalse(NationalCloud::isValidNationalCloudHost("https:///"));
    }
}
