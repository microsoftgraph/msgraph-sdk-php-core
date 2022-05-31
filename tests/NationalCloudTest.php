<?php


namespace Microsoft\Graph\Core\Test;


use Microsoft\Graph\Core\NationalCloud;

class NationalCloudTest extends \PHPUnit\Framework\TestCase
{
    function testNationalCloudConstantsContainNationalCloudHost() {
        $nationalClouds = array_values((new \ReflectionClass(NationalCloud::class))->getConstants());
        foreach ($nationalClouds as $nationalCloud) {
            $this->assertTrue(NationalCloud::containsNationalCloudHost($nationalCloud));
        }
    }

    function testContainsNationalCloudHostWithPortInUrl() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL.":1234"));
    }

    function testContainsNationalCloudHostWithTrailingForwardSlash() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL."/"));
    }

    function testContainsNationalCloudHostWithPathInUrl() {
        $this->assertTrue(NationalCloud::containsNationalCloudHost(NationalCloud::GLOBAL."/v1.0/"));
    }

    function testContainsNationalCloudWithCapitalisedHost() {
        $url = "https://GRAPH.microsoft.COM";
        self::assertTrue(NationalCloud::containsNationalCloudHost($url));
    }

    function testContainsNationalCloudHostWithEmptyUrl() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost(""));
    }

    function testContainsNationalCloudHostWithNullUrlThrowsError() {
        $this->expectException(\TypeError::class);
        NationalCloud::containsNationalCloudHost(null);
    }

    function testInvalidNationalCloud() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("https://www.microsoft.com"));
    }

    function testContainsNationalCloudHostWithoutSchemeInvalid() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("graph.microsoft.com"));
    }

    function testContainsNationalCloudHostWithMalformedUrlInvalid() {
        $this->assertFalse(NationalCloud::containsNationalCloudHost("https:///"));
    }
}
