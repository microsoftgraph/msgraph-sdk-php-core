<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Middleware;

use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Kiota\Http\Middleware\CompressionHandler;
use Microsoft\Kiota\Http\Middleware\KiotaMiddleware;
use Microsoft\Kiota\Http\Middleware\Options\CompressionOption;
use Microsoft\Kiota\Http\Middleware\Options\RetryOption;
use Microsoft\Kiota\Http\Middleware\RetryHandler;

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
class GraphMiddleware extends KiotaMiddleware
{
    /**
     * Middleware that allows configuration of a Graph request with telemetry data
     *
     * @param GraphTelemetryOption|null $graphTelemetryOption
     * @return callable
     */
    public static function graphTelemetry(?GraphTelemetryOption $graphTelemetryOption = null): callable
    {
        return static function (callable $handler) use ($graphTelemetryOption): GraphTelemetryHandler {
            return new GraphTelemetryHandler($handler, $graphTelemetryOption);
        };
    }

    /**
     * Middleware that retries requests for 429,503 and 504 response status codes (by default) while respecting the Retry-After response header
     * Configurable using {@link RetryOption}
     *
     * @param RetryOption|null $retryOption
     * @return callable
     */
    public static function retry(?RetryOption $retryOption = null): callable
    {
        return static function (callable $handler) use ($retryOption) : RetryHandler {
            return new GraphRetryHandler($handler, $retryOption);
        };
    }

    /**
     * Middleware that compresses a request body based on compression callbacks provided in {@link CompressionOption} and retries
     * the initial request with an uncompressed body only once if a 415 response is received.
     *
     * @param CompressionOption|null $compressionOption
     * @return callable
     */
    public static function compression(?CompressionOption $compressionOption = null): callable
    {
        return static function (callable $handler) use ($compressionOption): CompressionHandler {
            return new GraphCompressionHandler($handler, $compressionOption);
        };
    }
}
