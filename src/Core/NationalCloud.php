<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core;

/**
 * Class NationalCloud
 *
 * Defines Graph hosts for the various national clouds
 *
 * @package Microsoft\Graph\Core
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
final class NationalCloud
{
    const GLOBAL = "https://graph.microsoft.com";
    const US_GOV = "https://graph.microsoft.us";
    const US_DOD = "https://dod-graph.microsoft.us";
    const GERMANY = "https://graph.microsoft.de";
    const CHINA = "https://microsoftgraph.chinacloudapi.cn";

    /**
     * Unique hostnames from constant values [graph.microsoft.com, graph.microsoft.us, ...]
     *
     * @var array
     */
    private static $hosts = [];

    /**
     * Checks if url contains a valid National Cloud host
     *
     * @param string $url
     * @return bool
     */
    public static function containsNationalCloudHost(string $url): bool {
        self::initHosts();
        $validUrlParts = parse_url($url);
        return $validUrlParts
                && array_key_exists("scheme", $validUrlParts)
                && $validUrlParts["scheme"] == "https"
                && array_key_exists("host", $validUrlParts)
                && array_key_exists($validUrlParts["host"], self::$hosts);
    }

    /**
     * Extracts hostnames from constant values to an array with unique keys for optimal lookup
     */
    private static function initHosts(): void {
        if (!self::$hosts) {
            $reflectedClass = new \ReflectionClass(__CLASS__);
            $constants = $reflectedClass->getConstants();
            foreach ($constants as $constName => $url) {
                // Create associative array for O(1) key lookup
                $hostname = parse_url($url)["host"];
                self::$hosts[$hostname] = true;
            }
        }
    }
}
