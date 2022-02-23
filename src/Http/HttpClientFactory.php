<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Http\Promise\Promise;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Http\KiotaClientFactory;
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
final class HttpClientFactory extends KiotaClientFactory
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
    private static $nationalCloud = NationalCloud::GLOBAL;

    /**
     * @var HttpClientFactory|null Store singleton instance of the HttpClientFactory
     */
    private static $instance = null;

    /**
     * HttpClientFactory constructor.
     */
    private function __construct() {}

    /**
     * Returns singleton instance
     *
     * @return HttpClientFactory
     */
    private static function getInstance(): HttpClientFactory {
        if (!self::$instance) {
            self::$instance = new HttpClientFactory();
        }
        return self::$instance;
    }

    /**
     * Set national cloud to be used as the base URL
     *
     * @param string $nationalCloud
     * @return $this
     * @throws \InvalidArgumentException if $nationalCloud is empty or an invalid national cloud Host
     */
    public static function setNationalCloud(string $nationalCloud = NationalCloud::GLOBAL): HttpClientFactory {
        if (!$nationalCloud || !NationalCloud::containsNationalCloudHost($nationalCloud)) {
            throw new \InvalidArgumentException("Invalid national cloud passed. See https://docs.microsoft.com/en-us/graph/deployments#microsoft-graph-and-graph-explorer-service-root-endpoints.");
        }
        self::$nationalCloud = $nationalCloud;
        return self::getInstance();
    }

    /**
     * Create Guzzle client configured for Graph
     *
     * @param array $guzzleConfig
     * @return Client
     */
    public static function createWithConfig(array $guzzleConfig): Client
    {
        return parent::createWithConfig(array_merge(self::getDefaultConfig(), $guzzleConfig));
    }

    /**
     * Creates a Guzzle client with the custom configs provided or a default client if no config was given
     * Creates default Guzzle client if no custom configs were passed
     *
     * @return Client
     */
    public static function create(): Client {
        return parent::createWithConfig(self::getDefaultConfig());
    }

    /**
     * Initialises a Guzzle client with middleware and default Graph configs
     *
     * @param HandlerStack $handlerStack
     * @return Client
     */
    public static function createWithMiddleware(HandlerStack $handlerStack): Client
    {
        return parent::createWithConfig(array_merge(self::getDefaultConfig(), ['handler' => $handlerStack]));
    }

    /**
     * Creates an HttpClientInterface implementation that wraps around a Guzzle client
     *
     * @return HttpClientInterface
     */
    public static function createAdapter(): HttpClientInterface {
        return new class(self::create()) implements HttpClientInterface {
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
     * Return default handler stack for Graph
     *
     * @return HandlerStack
     */
    public static function getDefaultHandlerStack(): HandlerStack
    {
        return parent::getDefaultHandlerStack();
        //TODO: Add Telemetry handler, compression handler?
    }

    /**
     * Returns Graph-specific config for Guzzle
     *
     * @return array
     */
    private static function getDefaultConfig(): array {
        return [
            RequestOptions::CONNECT_TIMEOUT => self::CONNECTION_TIMEOUT_SEC,
            RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT_SEC,
            RequestOptions::HEADERS => [
                "Content-Type" => "application/json",
                "SdkVersion" => "graph-php-core/".GraphConstants::SDK_VERSION
            ],
            RequestOptions::HTTP_ERRORS => false,
            "base_uri" => self::$nationalCloud,
            'handler' => self::getDefaultHandlerStack()
        ];
    }
}
