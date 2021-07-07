<?php


namespace Core;


use Microsoft\Graph\Core\NationalCloud;

class NationalCloudTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudConstantsAreValid() {
        $nationalClouds = array_values((new \ReflectionClass(NationalCloud::class))->getConstants());
        foreach ($nationalClouds as $nationalCloud) {
            $this->assertTrue(NationalCloud::containsNationalCloudHost($nationalCloud));
        }
    }

    function testNationalCloudWithPortIsValid() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL.":1234"));
    }

    function testNationalCloudWithTrailingForwardSlashIsValid() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL."/"));
    }

    function testNationalCloudWithPathIsValid() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL."/v1.0/"));
    }

    function testEmptyNationalCloudUrlInvalid() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost(""));
    }

    function testNullNationalCloudThrowsError() {
        $this->expectException(\TypeError::class);
        NationalCloud::containsNationalCloudHost(null);
    }

    function testInvalidNationalCloud() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("https://www.microsoft.com"));
    }

    function testNationalCloudWithoutSchemeInvalid() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("graph.microsoft.com"));
    }

    function testMalformedNationalCloudInvalid() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("https:///"));
    }
}
