<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Http;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Microsoft\Graph\Core\NationalCloud;
use Psr\Http\Message\UriInterface;

/**
 * Class GraphRequestUtil
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphRequestUtil
{
    /**
     * Returns full request URI by resolving $baseUrl and $endpoint based on RFC 3986
     * Prepends $apiVersion to $endpoint if $baseUrl contains a national cloud host
     * $endpoint can be a full URI with a national cloud host
     *
     * @param string $baseUrl if empty, is overwritten with $client's national cloud
     * @param string $endpoint can be a full URL
     * @param string $apiVersion
     * @return UriInterface
     * @throws \InvalidArgumentException
     */
    public static function getRequestUri(string $baseUrl, string $endpoint, string $apiVersion = "v1.0"): UriInterface {
        // If endpoint is a full url, ensure the host is a national cloud
        if (parse_url($endpoint, PHP_URL_SCHEME)) {
            return new Uri($endpoint);
        }
        if ($baseUrl) {
            $baseUrlParts = parse_url($baseUrl);
            if (!self::isValidBaseUrl($baseUrlParts)) {
                throw new \InvalidArgumentException("Invalid baseUrl=".$baseUrl.". Ensure URL has scheme and host");
            }
            $relativeUrl = (NationalCloud::containsNationalCloudHostFromUrlParts($baseUrlParts)) ? "/".$apiVersion : "";
            $relativeUrl .= (substr($endpoint, 0, 1) === "/") ? $endpoint : "/".$endpoint;
            return UriResolver::resolve(new Uri($baseUrl), new Uri($relativeUrl));
        }
        throw new \InvalidArgumentException("Unable to create uri with empty baseUrl and endpoint=".$endpoint);
    }

    /**
     * Check whether $urlParts meet criteria for a valid base url
     *
     * @param array<string, string>|false $urlParts return value of parse_url()
     * @return bool
     */
    public static function isValidBaseUrl($urlParts): bool {
        return $urlParts
                && array_key_exists("scheme", $urlParts)
                && array_key_exists("host", $urlParts);
    }

    /**
     * Determine correct symbol to add before concatenating query parameters to $url
     * @param Uri $url
     * @return string
     */
    public static function getQueryParamConcatenator(Uri $url): string {
        return stripos($url, "?") ? "&" : "?";
    }
}
