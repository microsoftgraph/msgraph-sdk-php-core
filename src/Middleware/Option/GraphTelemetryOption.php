<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Middleware\Option;


use Microsoft\Graph\Core\GraphConstants;
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
class GraphTelemetryOption extends \Microsoft\Kiota\Http\Middleware\Options\TelemetryOption
{
    private $apiVersion;
    private $sdkVersion;
    private $clientRequestId;
    private $featureFlag;

    /**
     * Create new instance
     *
     * @param string $clientRequestId if not set, defaults to random UUID
     */
    public function __construct(string $clientRequestId = '')
    {
        $this->setClientRequestId($clientRequestId);
        parent::__construct($this->initTelemetryConfigurator());
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion(string $apiVersion): void
    {
        $versions = ['beta' => true, 'v1.0' => true];
        if ($apiVersion && !array_key_exists(strtolower($apiVersion), $versions)) {
            throw new \InvalidArgumentException('Api version can only be beta/v1.0');
        }
        $this->apiVersion = strtolower($apiVersion);
    }

    /**
     * @return string
     */
    public function getSdkVersion(): string
    {
        return $this->sdkVersion;
    }

    /**
     * @param string $sdkVersion
     */
    public function setSdkVersion(string $sdkVersion): void
    {
        $this->sdkVersion = $sdkVersion;
    }

    /**
     * @return string
     */
    public function getClientRequestId(): string
    {
        return ($this->clientRequestId) ?: Uuid::uuid4();
    }

    /**
     * @param string $clientRequestId
     */
    public function setClientRequestId(string $clientRequestId): void
    {
        $this->clientRequestId = $clientRequestId;
    }

    /**
     * @return string
     */
    public function getFeatureFlag(): string
    {
        return ($this->featureFlag) ?: '0x00000000';
    }

    /**
     * @param string $featureFlag
     */
    public function setFeatureFlag(string $featureFlag): void
    {
        $this->featureFlag = $featureFlag;
    }

    /**
     * Overrides existing telemetry option values with values from param $graphTelemetryOption
     *
     * @param GraphTelemetryOption $graphTelemetryOption
     */
    public function merge(GraphTelemetryOption $graphTelemetryOption): void
    {
        $this->clientRequestId = ($graphTelemetryOption->getClientRequestId()) ?: $this->clientRequestId;
        $this->apiVersion = ($graphTelemetryOption->getApiVersion()) ?: $this->apiVersion;
        $this->sdkVersion = ($graphTelemetryOption->getSdkVersion()) ?: $this->sdkVersion;
        $this->featureFlag = ($graphTelemetryOption->getFeatureFlag()) ?: $this->featureFlag;
    }

    /**
     * Returns the telemetry value to be added to SdkVersion header
     *
     * @return string
     */
    protected function getTelemetryHeaderValue(): string
    {
        $telemetryValue = 'graph-php-core/'.GraphConstants::SDK_VERSION.", (featureUsage={$this->getFeatureFlag()}; ";
        // Prepend service lib version
        if ($this->getSdkVersion() && $this->getApiVersion()) {
            if ($this->getApiVersion() == 'beta') {
                $telemetryValue = 'graph-php-beta/'.$this->getSdkVersion().', '.$telemetryValue;
            } else {
                $telemetryValue = 'graph-php/'.$this->getSdkVersion().', '.$telemetryValue;
            }
        }

        $telemetryValue .= 'hostOS='.php_uname('s').'; ';
        $telemetryValue .= 'runtimeEnvironment=PHP/'.phpversion().'; ';
        $telemetryValue .= ')';
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
