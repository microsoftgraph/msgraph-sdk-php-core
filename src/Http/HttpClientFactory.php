<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Http;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use GuzzleHttp\RequestOptions;
use Http\Promise\Promise;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\ClientInitialisationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpClientFactory
 *
 * Configures a Guzzle HTTP client for use with Graph API
 *
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
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
     * @var string Graph API host to use as base URL and for authentication
     */
    private $nationalCloud = NationalCloud::GLOBAL;

    private $clientConfig = [];

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
    public function clientConfig(array $config): self {
        $this->clientConfig = $config;
        return $this;
    }

    /**
     * Creates a Guzzle client with the custom configs provided or a default client if no config was given
     * Creates default Guzzle client if no custom configs were passed
     *
     * @return \GuzzleHttp\Client
     */
    public function create(): \GuzzleHttp\Client {
        if (!$this->clientConfig) {
            return new Client($this->getDefaultConfig());
        }
        $this->mergeConfig();
        return new Client($this->clientConfig);
    }

    /**
     * Creates an HttpClientInterface implementation that wraps around a Guzzle client
     *
     * @return HttpClientInterface
     */
    public function createAdapter(): HttpClientInterface {
        return new class($this->create()) implements HttpClientInterface {
            private $clientAdapter;

            public function __construct(Client $guzzleClient) {
                $this->clientAdapter = new GuzzleAdapter($guzzleClient);
            }

            public function sendRequest(RequestInterface $request): ResponseInterface {
                return $this->clientAdapter->sendRequest($request);
            }

            public function sendAsyncRequest(RequestInterface $request): Promise {
                return $this->clientAdapter->sendAsyncRequest($request);
            }
        };
    }

    /**
     * Returns Graph-specific config for Guzzle
     *
     * @return array
     */
    private function getDefaultConfig(): array {
        return [
            RequestOptions::CONNECT_TIMEOUT => self::CONNECTION_TIMEOUT_SEC,
            RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT_SEC,
            RequestOptions::HEADERS => [
                "Content-Type" => "application/json"
            ],
            RequestOptions::HTTP_ERRORS => false,
            "base_uri" => $this->nationalCloud
        ];
    }

    /**
     * Merges client defined config array with Graph's default config.
     * Provides defaults for timeouts and headers if none have been provided.
     * Overrides base_uri.
     */
    private function mergeConfig(): void {
        $defaultConfig = $this->getDefaultConfig();

        if (!isset($this->clientConfig[RequestOptions::CONNECT_TIMEOUT])) {
            $this->clientConfig[RequestOptions::CONNECT_TIMEOUT] = $defaultConfig[RequestOptions::CONNECT_TIMEOUT];
        }
        if (!isset($this->clientConfig[RequestOptions::TIMEOUT])) {
            $this->clientConfig[RequestOptions::TIMEOUT] = $defaultConfig[RequestOptions::TIMEOUT];
        }
        if (!isset($this->clientConfig[RequestOptions::HEADERS])) {
            $this->clientConfig[RequestOptions::HEADERS] = $defaultConfig[RequestOptions::HEADERS];
        }
        $this->clientConfig["base_uri"] = $this->nationalCloud;
    }
}
