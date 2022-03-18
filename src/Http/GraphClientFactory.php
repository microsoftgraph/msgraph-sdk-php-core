<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Hoa\Math\Util;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Http\Promise\Promise;
use Microsoft\Graph\Core\Middleware\GraphMiddleware;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Http\KiotaClientFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GraphClientFactory
 *
 * Configures a Guzzle HTTP client for use with Graph API
 *
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
final class GraphClientFactory extends KiotaClientFactory
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
     * @var GraphClientFactory|null Store singleton instance of the GraphClientFactory
     */
    private static $instance = null;

    /**
     * @var GraphTelemetryOption|null telemetry config
     */
    private static $graphTelemetryOption = null;

    /**
     * GraphClientFactory constructor.
     */
    private function __construct() {}

    /**
     * Returns singleton instance
     *
     * @return GraphClientFactory
     */
    private static function getInstance(): GraphClientFactory {
        if (!self::$instance) {
            self::$instance = new GraphClientFactory();
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
    public static function setNationalCloud(string $nationalCloud = NationalCloud::GLOBAL): GraphClientFactory {
        if (!$nationalCloud || !NationalCloud::containsNationalCloudHost($nationalCloud)) {
            throw new \InvalidArgumentException("Invalid national cloud passed. See https://docs.microsoft.com/en-us/graph/deployments#microsoft-graph-and-graph-explorer-service-root-endpoints.");
        }
        self::$nationalCloud = $nationalCloud;
        return self::getInstance();
    }

    /**
     * Set telemetry configuration
     *
     * @param GraphTelemetryOption $telemetryOption
     * @return GraphClientFactory
     */
    public static function setTelemetryOption(GraphTelemetryOption $telemetryOption): GraphClientFactory
    {
        self::$graphTelemetryOption = $telemetryOption;
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
     * @param null $handler final handler
     * @return HandlerStack
     */
    public static function getDefaultHandlerStack($handler = null): HandlerStack
    {
        $handler = ($handler) ?: Utils::chooseHandler();
        $handlerStack = new HandlerStack($handler);
        $handlerStack->push(GraphMiddleware::retry());
        $handlerStack->push(GuzzleMiddleware::redirect());
        $handlerStack->push(GraphMiddleware::graphTelemetry(self::$graphTelemetryOption));
        return $handlerStack;
    }

    /**
     * Returns Graph-specific config for Guzzle
     *
     * @return array
     */
    private static function getDefaultConfig(): array {
        $config = [
            RequestOptions::CONNECT_TIMEOUT => self::CONNECTION_TIMEOUT_SEC,
            RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT_SEC,
            RequestOptions::HEADERS => [
                "Content-Type" => "application/json",
            ],
            RequestOptions::HTTP_ERRORS => false,
            "base_uri" => self::$nationalCloud,
            'handler' => self::getDefaultHandlerStack()
        ];
        if (extension_loaded('curl') && defined('CURL_VERSION_HTTP2') && curl_version()["features"] & CURL_VERSION_HTTP2 !== 0) {
            // Enable HTTP/2 if curl extension exists and supports it
            $config['version'] = '2';
        }
        return $config;
    }
}
