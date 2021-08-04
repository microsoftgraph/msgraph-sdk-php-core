<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Test\Http\Request;


use GuzzleHttp\Psr7\Uri;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Http\AbstractGraphClient;
use Microsoft\Graph\Http\GraphRequestUtil;

class GraphRequestUtilTest extends \PHPUnit\Framework\TestCase
{
    private $apiVersion;

    function setUp(): void {
        $graphClient = (new class extends AbstractGraphClient {
            public function getSdkVersion(): string {
                return "";
            }

            public function getApiVersion(): string {
                return "v1.0";
            }
        });
        $this->apiVersion = $graphClient->getApiVersion();
    }

    function testGetRequestUriWithFullNationalCloudEndpointUrlReturnsUri() {
        $endpoint = NationalCloud::GLOBAL."/me/events?\$skip=100&\$top=10";
        $result = GraphRequestUtil::getRequestUri("", $endpoint, $this->apiVersion);
        self::assertEquals($endpoint, strval($result));
    }

    function testGetRequestUriWithFullNonNationalCloudEndpointThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $endpoint = "https://www.outlook.com/mail?user=me";
        $uri = GraphRequestUtil::getRequestUri("", $endpoint, $this->apiVersion);
    }

    function testGetRequestUriWithValidBaseUrlResolvesCorrectly() {
        $validBaseUrls = [
            "https://graph.microsoft.com",
            "https://graph.microsoft.com/",
            "https://graph.microsoft.com/beta",
            "https://graph.microsoft.com/v1.0/"
        ];
        $endpoints = ["/me/events", "me/events"];
        $expected = "https://graph.microsoft.com/v1.0/me/events";
        foreach ($validBaseUrls as $baseUrl) {
            foreach ($endpoints as $endpoint) {
                $uri = GraphRequestUtil::getRequestUri($baseUrl, $endpoint, $this->apiVersion);
                self::assertEquals($expected, strval($uri));
            }
        }
    }

    function testGetRequestUriWithEmptyBaseUriThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $endpoint= "/me/events";
        GraphRequestUtil::getRequestUri("", $endpoint);
    }

    function testGetRequestUriWithoutNationalCloudHostDoesntSetApiVersion() {
        $baseUrl = "https://outlook.microsoft.com/";
        $endpoint = "?startDate=2020-10-02&sort=desc";
        $expected = $baseUrl.$endpoint;
        $uri = GraphRequestUtil::getRequestUri($baseUrl, $endpoint, $this->apiVersion);
        self::assertEquals($expected, strval($uri));

    }

    function testGetRequestUriWithInvalidFullEndpointUrlThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $endpoint = "http:/microsoft.com:localhost\$endpoint";
        $uri = GraphRequestUtil::getRequestUri("", $endpoint, $this->apiVersion);
    }

    function testGetRequestUriWithInvalidBaseUrlAndEndpointThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $baseUrl = "https://graph.microsoft.com";
        $endpoint = "http:/microsoft.com:localhost\$endpoint";
        $uri = GraphRequestUtil::getRequestUri($baseUrl, $endpoint, $this->apiVersion);
    }

    function testGetQueryParamConcatenatorWithExistingQueryParams() {
        $uri = new Uri("https://graph.microsoft.com?\$skip=10");
        $result = GraphRequestUtil::getQueryParamConcatenator($uri);
        self::assertEquals("&", $result);
    }

    function testGetQueryParamConcatenatorWithoutQueryParams() {
        $uri = new Uri("https://graph.microsoft.com");
        $result = GraphRequestUtil::getQueryParamConcatenator($uri);
        self::assertEquals("?", $result);
    }
}
