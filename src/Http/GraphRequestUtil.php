<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Http;

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
        // If endpoint is a full url, ensure the host is a national cloud or custom host
        if (parse_url($endpoint, PHP_URL_SCHEME)) {
            if (NationalCloud::containsNationalCloudHost($endpoint)) {
                return new Uri($endpoint);
            }
            throw new \InvalidArgumentException("Invalid national cloud host in endpoint=".$endpoint.". See https://docs.microsoft.com/en-us/graph/deployments#microsoft-graph-and-graph-explorer-service-root-endpoints.");
        }
        if ($baseUrl) {
            $urlParts = parse_url($baseUrl);
            if (!$urlParts || !array_key_exists("scheme", $urlParts) || !array_key_exists("host", $urlParts)) {
                throw new \InvalidArgumentException("Invalid baseUrl=".$baseUrl.". Ensure URL has scheme and host");
            }
        }
        $relativeUrl = (NationalCloud::containsNationalCloudHost($baseUrl)) ? "/".$apiVersion : "";
        $relativeUrl .= (substr($endpoint, 0, 1) == "/") ? $endpoint : "/".$endpoint;
        return UriResolver::resolve(new Uri($baseUrl), new Uri($relativeUrl));
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
