<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Middleware;

use Microsoft\Graph\Core\Core\Middleware\Option\GraphTelemetryOption;

/**
 * Adds feature flag to Guzzle options
 */
trait FeatureFlagTrait
{
    function setFeatureFlag(int $featureFlag, array &$options): void {
        if (!array_key_exists(GraphTelemetryOption::class, $options)) {
            $telemetry = new GraphTelemetryOption();
            $options[GraphTelemetryOption::class] = $telemetry;
        }
        if ($options[GraphTelemetryOption::class] instanceof GraphTelemetryOption) {
            $options[GraphTelemetryOption::class]->setFeatureFlag($featureFlag);
        }
    }
}
