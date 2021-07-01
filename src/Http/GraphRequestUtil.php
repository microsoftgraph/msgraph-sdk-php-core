<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Http;

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
     * Determine if $url meets criteria for use as a base URL.
     * Returns null if $url is invalid
     * Returns an array of URL parts if $url is valid
     *
     * @param string $url
     * @return array|null
     */
    public static function isValidBaseUrl(string $url): ?array {
        $urlParts = parse_url($url);
        if ($urlParts
            && array_key_exists("scheme", $urlParts)
            && strtolower($urlParts["scheme"]) == "https"
            && array_key_exists("host", $urlParts)
            && (
                // if there's a path, must end with "/" e.g. https://graph.microsoft.com/beta/
                (array_key_exists("path", $urlParts) && substr($url, -1) == "/")
                // hostname alone without path is also valid e.g. https://graph.microsoft.com
                || !array_key_exists("path", $urlParts)
            )
            && !(array_key_exists("query", $urlParts))
        ) {
            return $urlParts;
        }
        return null;
    }

}
