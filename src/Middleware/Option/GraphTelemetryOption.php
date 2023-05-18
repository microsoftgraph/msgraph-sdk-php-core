<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Middleware\Option;


use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Kiota\Http\Middleware\Options\TelemetryOption;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class GraphTelemetryOption
 *
 * Request options for Graph telemetry
 *
 * @package Microsoft\Graph\Middleware\Option
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphTelemetryOption extends TelemetryOption
{
    public const DEFAULT_FEATURE_FLAG = 0x00000000;
    private string $apiVersion;
    private string $serviceLibraryVersion;
    private string $clientRequestId = '';
    private int $featureFlag = self::DEFAULT_FEATURE_FLAG;

    /**
     * Create new instance
     *
     * @param string $apiVersion
     * @param string $serviceLibraryVersion
     */
    public function __construct(string $apiVersion = GraphConstants::V1_API_VERSION, string $serviceLibraryVersion = '')
    {
        $this->setApiVersion($apiVersion);
        $this->serviceLibraryVersion = $serviceLibraryVersion;
        parent::__construct($this->initTelemetryConfigurator());
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion(string $apiVersion): void
    {
        if ($apiVersion && strtolower($apiVersion) !== GraphConstants::BETA_API_VERSION && strtolower($apiVersion) !== GraphConstants::V1_API_VERSION) {
            throw new \InvalidArgumentException("Invalid API version='{$apiVersion}'. Api version can only be ".GraphConstants::BETA_API_VERSION.' or '.GraphConstants::V1_API_VERSION);
        }
        $this->apiVersion = strtolower($apiVersion);
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * @return string
     */
    public function getClientRequestId(): string
    {
        if (!$this->clientRequestId) {
            $this->clientRequestId = Uuid::uuid4();
        }
        return $this->clientRequestId;
    }

    /**
     * @param string $clientRequestId
     */
    public function setClientRequestId(string $clientRequestId): void
    {
        $this->clientRequestId = $clientRequestId;
    }

    /**
     * Handles bitwise OR-ing if there is an already existing feature flag value
     * @param int $featureFlag hex value
     */
    public function setFeatureFlag(int $featureFlag): void
    {
        $this->featureFlag = self::DEFAULT_FEATURE_FLAG | $featureFlag;
    }

    /**
     * Overrides existing telemetry option values with values from param $graphTelemetryOption
     *
     * @param GraphTelemetryOption $graphTelemetryOption
     */
    public function override(GraphTelemetryOption $graphTelemetryOption): void
    {
        $this->clientRequestId = ($graphTelemetryOption->getClientRequestId()) ?: $this->clientRequestId;
        $this->featureFlag = ($graphTelemetryOption->featureFlag) ?: $this->featureFlag;
    }

    /**
     * Returns the telemetry value to be added to SdkVersion header
     *
     * @return string
     */
    protected function getTelemetryHeaderValue(): string
    {
        $telemetryValue = 'graph-php-core/'.GraphConstants::SDK_VERSION
                            .', (featureUsage='.sprintf('0x%08X', $this->featureFlag)
                            .'; hostOS='.php_uname('s')
                            .'; runtimeEnvironment=PHP/'.phpversion().')';
        // Prepend service lib version
        if ($this->serviceLibraryVersion && $this->apiVersion) {
            if ($this->apiVersion == GraphConstants::BETA_API_VERSION) {
                $telemetryValue = 'graph-php-beta/'.$this->serviceLibraryVersion.', '.$telemetryValue;
            } else {
                $telemetryValue = 'graph-php/'.$this->serviceLibraryVersion.', '.$telemetryValue;
            }
        }
        return $telemetryValue;
    }

    /**
     * @return callable
     */
    private function initTelemetryConfigurator(): callable
    {
        return function (RequestInterface $request) {
            return $request->withHeader('SdkVersion', $this->getTelemetryHeaderValue())
                            ->withHeader('client-request-id', $this->getClientRequestId());
        };
    }
}
