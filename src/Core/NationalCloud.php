<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 *
 * HttpResponse File
 * PHP version 7
 *
 * @category  Library
 * @package   Microsoft.Graph
 * @copyright 2020 Microsoft Corporation
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   GIT: 1.13.0
 * @link      https://graph.microsoft.io/
 */

namespace Microsoft\Graph\Core;

/**
 * Class NationalCloud
 * Defines Graph Hosts for the various national clouds
 * @package Microsoft\Graph\Core
 */
final class NationalCloud
{
    const GLOBAL = "https://graph.microsoft.com";
    const US_GOV = "https://graph.microsoft.us";
    const US_DOD = "https://dod-graph.microsoft.us";
    const GERMANY = "https://graph.microsoft.de";
    const CHINA = "https://microsoftgraph.chinacloudapi.cn";

    /**
     * Stores all enum values as list
     * Prevents having to do reflection for each call to getValues()
     *
     * @var array
     */
    private static $values = [];

    /**
     * Returns a list of the constant values
     *
     * @return array
     */
    public static function getValues(): array {
        if (!self::$values) {
            $reflectedClass = new \ReflectionClass(__CLASS__);
            self::$values = array_values($reflectedClass->getConstants());
        }
        return self::$values;
    }
}
