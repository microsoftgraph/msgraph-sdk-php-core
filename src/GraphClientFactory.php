<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Microsoft\Graph\Core\Middleware\GraphMiddleware;
use Microsoft\Graph\Core\Middleware\GraphRetryHandler;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Kiota\Http\KiotaClientFactory;
use Microsoft\Kiota\Http\Middleware\Options\UrlReplaceOption;
use Microsoft\Kiota\Http\Middleware\RetryHandler;

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
    private static string $nationalCloud = NationalCloud::GLOBAL;

    /**
     * @var GraphClientFactory|null Store singleton instance of the GraphClientFactory
     */
    private static ?GraphClientFactory $instance = null;

    /**
     * @var GraphTelemetryOption|null telemetry config
     */
    private static ?GraphTelemetryOption $graphTelemetryOption = null;

    /** @var array<string, string> $urlReplacementPairs  */
    private static array $urlReplacementPairs = [ "/users/me-token-to-replace" => "/me" ];

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
     * @throws InvalidArgumentException if $nationalCloud is empty or an invalid national cloud Host
     */
    public static function setNationalCloud(string $nationalCloud = NationalCloud::GLOBAL): GraphClientFactory {
        if (!$nationalCloud || !NationalCloud::containsNationalCloudHost($nationalCloud)) {
            throw new InvalidArgumentException(
                "Invalid national cloud passed. See https://learn.microsoft.com/en-us/graph/deployments."
            );
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
     * @param array<string, mixed> $guzzleConfig
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
     * Return default handler stack for Graph
     *
     * @param callable|null $handler final handler
     * @return HandlerStack
     */
    public static function getDefaultHandlerStack(callable $handler = null): HandlerStack
    {
        $handlerStack = parent::getDefaultHandlerStack();
        if ($handler) {
            $handlerStack->setHandler($handler);
        }
        $handlerStack->unshift(GraphMiddleware::urlReplace(new UrlReplaceOption(true, self::$urlReplacementPairs)));
        // Replace default retry handler
        $handlerStack->before(
            RetryHandler::HANDLER_NAME,
            GraphMiddleware::retry(),
            GraphRetryHandler::HANDLER_NAME
        );
        $handlerStack->remove(RetryHandler::HANDLER_NAME);
        $handlerStack->push(GraphMiddleware::graphTelemetry(self::$graphTelemetryOption));
        return $handlerStack;
    }

    /**
     * Returns Graph-specific config for Guzzle
     *
     * @return array<string, mixed>
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
        if (extension_loaded('curl') && defined('CURL_VERSION_HTTP2')) {
            $curlVersion = curl_version();
            if ($curlVersion && ($curlVersion["features"] & CURL_VERSION_HTTP2) == CURL_VERSION_HTTP2) {
                // Enable HTTP/2 if curl extension exists and supports it
                $config['version'] = '2';
            }
        }
        return $config;
    }
}
