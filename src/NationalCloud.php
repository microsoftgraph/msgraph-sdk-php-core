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
     * Unique hostnames from constant values graph.microsoft.com, graph.microsoft.us, ...
     *
     * @var array<string, bool>
     */
    private static array $hosts = [];

    /**
     * Checks if url contains a valid National Cloud host
     *
     * @param string $url
     * @return bool
     */
    public static function containsNationalCloudHost(string $url): bool {
        $validUrlParts = parse_url($url);
        return self::containsNationalCloudHostFromUrlParts($validUrlParts);
    }

    /**
     * Checks if $urlParts contain a valid National Cloud host
     *
     * @param array<string, mixed>|false $urlParts return value of parse_url()
     * @return bool
     */
    public static function containsNationalCloudHostFromUrlParts($urlParts): bool {
        self::initHosts();
        return $urlParts
            && array_key_exists("scheme", $urlParts)
            && $urlParts["scheme"] == "https"
            && array_key_exists("host", $urlParts)
            && array_key_exists(strtolower(strval($urlParts["host"])), self::$hosts);
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
                $urlParts = parse_url(strval($url));
                $hostname = $urlParts["host"] ?? null;
                if ($hostname) {
                    self::$hosts[strval($hostname)] = true;
                }
            }
        }
    }
}
