<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Middleware;

use Microsoft\Graph\Middleware\Option\GraphTelemetryOption;

/**
 * Class GraphMiddleware
 *
 * Util methods to initialise graph-specific middleware
 *
 * @package Microsoft\Graph\Middleware
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphMiddleware
{
    /**
     * Middleware that allows configuration of a Graph request with telemetry data
     *
     * @param GraphTelemetryOption|null $graphTelemetryOption
     * @return callable
     */
    public static function telemetry(?GraphTelemetryOption $graphTelemetryOption = null): callable
    {
        return static function (callable $handler) use ($graphTelemetryOption): GraphTelemetryHandler {
            return new GraphTelemetryHandler($handler, $graphTelemetryOption);
        };
    }
}
