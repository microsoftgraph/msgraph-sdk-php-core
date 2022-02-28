<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Microsoft\Graph\Middleware\Option\GraphTelemetryOption;
use Psr\Http\Message\RequestInterface;

/**
 * Class GraphTelemetryHandler
 *
 * Adds Graph telemetry information to a request
 *
 * @package Microsoft\Graph\Middleware
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphTelemetryHandler extends \Microsoft\Kiota\Http\Middleware\TelemetryHandler
{
    private $graphTelemetryOption;

    /**
     * Create new instance
     *
     * @param callable $nextHandler
     * @param GraphTelemetryOption|null $graphTelemetryOption
     */
    public function __construct(callable $nextHandler, ?GraphTelemetryOption $graphTelemetryOption = null)
    {
        $this->graphTelemetryOption = ($graphTelemetryOption) ?: new GraphTelemetryOption();
        parent::__construct($nextHandler, $this->graphTelemetryOption);
    }

    /**
     * Handles the request
     *
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        // Merge custom request-level options with initial telemetry options
        if (array_key_exists(GraphTelemetryOption::class, $options)) {
            $graphTelemetryOption = $options[GraphTelemetryOption::class];
            if (is_a($graphTelemetryOption, GraphTelemetryOption::class)) {
                $this->graphTelemetryOption->merge($options[GraphTelemetryOption::class]);
                unset($options[GraphTelemetryOption::class]);
            }
        }
        return parent::__invoke($request, $options);
    }
}
