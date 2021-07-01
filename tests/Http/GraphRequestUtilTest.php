<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Http;


use Microsoft\Graph\Http\GraphRequestUtil;

class GraphRequestUtilTest extends \PHPUnit\Framework\TestCase
{
    function testValidBaseUrlReturnsUrlParts() {
        $url = "https://graph.microsoft.com";
        $expected = [
            "scheme" => "https",
            "host" => "graph.microsoft.com"
        ];
        $this->assertEquals($expected, GraphRequestUtil::isValidBaseUrl($url));
    }

    function testValidBaseUrlWithPathReturnsUrlParts() {
        $url = "https://graph.microsoft.com/beta/";
        $expected = [
            "scheme" => "https",
            "host" => "graph.microsoft.com",
            "path" => "/beta/"
        ];
        $this->assertEquals($expected, GraphRequestUtil::isValidBaseUrl($url));
    }

    function testBaseUrlWithPathButNoTrailingBackslashReturnsNull() {
        $url = "https://graph.microsoft.com/v1.0";
        $this->assertNull(GraphRequestUtil::isValidBaseUrl($url));
    }

    function testBaseUrlWithoutHttpsReturnsNull() {
        $url = "http://graph.microsoft.com";
        $this->assertNull(GraphRequestUtil::isValidBaseUrl($url));
    }

    function testBaseUrlWithoutHostReturnsNull() {
        $url = "https:///beta";
        $this->assertNull(GraphRequestUtil::isValidBaseUrl($url));
    }

    function testBaseUrlWithQueryParamsReturnsNull() {
        $url = "https://graph.microsoft.com/v1.0?key=value";
        $this->assertNull(GraphRequestUtil::isValidBaseUrl($url));
    }

    function testEmptyBaseUrlReturnsNull() {
        $this->assertNull(GraphRequestUtil::isValidBaseUrl(""));
    }

    function testNullBaseUrlThrowsException() {
        $this->expectException(\TypeError::class);
        GraphRequestUtil::isValidBaseUrl(null);
    }
}
