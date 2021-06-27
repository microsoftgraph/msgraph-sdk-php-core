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

namespace Microsoft\Graph\Http;

use GuzzleHttp\Client;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\ClientInitialisationException;

/**
 * Class HttpClientFactory
 *
 * Configures a Guzzle HTTP client for use with Graph API
 *
 * @category Library
 * @package Microsoft\Graph\Http
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://graph.microsoft.io/
 */
final class HttpClientFactory
{
    /**
     * @var int Default connection timeout
     */
    const CONNECTION_TIMEOUT_SEC = 30;

    /**
     * @var int Default request timeout
     */
    const REQUEST_TIMEOUT_SEC = 100;

    /**
     * @var string Graph API host to use as base URL
     */
    private $nationalCloud = NationalCloud::GLOBAL;

    private $httpClientConfig = [];

    //TODO: Init default middleware pipeline
    //TODO: Add custom hosts

    /**
     * Set national cloud to be used as the base URL
     *
     * @param string $nationalCloud
     * @return $this
     * @throws ClientInitialisationException if $nationalCloud is empty or an invalid national cloud Host
     */
    public function nationalCloud(string $nationalCloud = NationalCloud::GLOBAL): self {
        if (!$nationalCloud) {
            throw new ClientInitialisationException("National cloud cannot be empty string");
        }
        // Verify $nationalCloud is valid
        if (!in_array($nationalCloud, NationalCloud::getValues())) {
            throw new ClientInitialisationException("Invalid national cloud passed. See NationalCloud constants");
        }
        $this->nationalCloud = $nationalCloud;
        return $this;
    }

    /**
     * Set configuration options for the Guzzle client
     *
     * @param array $config
     * @return $this
     */
    public function httpClientConfig(array $config): self {
        $this->httpClientConfig = $config;
        return $this;
    }

    /**
     * Creates a Guzzle client with the custom configs provided or a default client if no config was given
     * Creates default Guzzle client if no custom configs were passed
     *
     * @return \GuzzleHttp\Client
     */
    public function create(): \GuzzleHttp\Client {
        if (!$this->httpClientConfig) {
            return new Client($this->getDefaultConfig());
        }
        $this->prepareConfig();
        return new Client($this->httpClientConfig);
    }

    /**
     * Returns Graph-specific config for Guzzle
     *
     * @return array
     */
    private function getDefaultConfig(): array {
        return [
            "connect_timeout" => self::CONNECTION_TIMEOUT_SEC,
            "timeout" => self::REQUEST_TIMEOUT_SEC,
            "headers" => [
                "Content-Type" => "application/json"
            ],
            "http_errors" => false,
            "base_uri" => $this->nationalCloud
        ];
    }

    /**
     * Merges client defined config array with Graph's default config.
     * Provides defaults for timeouts and headers if none have been provided.
     * Overrides base_uri.
     */
    private function prepareConfig(): void {
        $defaultConfig = $this->getDefaultConfig();

        if (!isset($this->httpClientConfig["connect_timeout"])) {
            $this->httpClientConfig["connect_timeout"] = $defaultConfig["connect_timeout"];
        }
        if (!isset($this->httpClientConfig["timeout"])) {
            $this->httpClientConfig["timeout"] = $defaultConfig["timeout"];
        }
        if (!isset($this->httpClientConfig["headers"])) {
            $this->httpClientConfig["headers"] = $defaultConfig["headers"];
        }
        $this->httpClientConfig["base_uri"] = $this->nationalCloud;
    }
}
