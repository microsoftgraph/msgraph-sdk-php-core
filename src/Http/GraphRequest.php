<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphServiceException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class GraphRequest
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphRequest
{
    /**
    * An array of headers to send with the request
    *
    * @var array<string, string|string[]>
    */
    private $headers;
    /**
     * Default headers for a request
     *
     * @var array<string, string|string[]>
     */
    protected $defaultHeaders;
    /**
    * The body of the request (optional)
    *
    * @var StreamInterface|string
    */
    private $requestBody = null;
    /**
    * The type of request to make ("GET", "POST", etc.)
    *
    * @var string
    */
    protected $requestType;
    /**
    * True if the response should be returned as
    * a stream
    *
    * @var bool
    */
    protected $returnsStream;
    /**
    * The object type to cast the response to
    *
    * @var string|null
    */
    protected $returnType;
    /**
     * The Graph client
     *
     * @var AbstractGraphClient
     */
    private $graphClient;
    /**
     * PSR-7 Request to be passed to HTTP client
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    private $httpRequest;
    /**
     * Full Request URI (base URL + endpoint)
     *
     * @var UriInterface
     */
    private $requestUri;

    /**
     * GraphRequest constructor.
     * Sets $baseUrl by default to $graphClient's national cloud
     * Resolves $baseUrl and $endpoint based on RFC 3986
     *
     * @param string $requestType The HTTP method to use e.g. "GET" or "POST"
     * @param string $endpoint The url path on the host to be called-
     * @param AbstractGraphClient $graphClient The Graph client to use
     * @param string $baseUrl (optional) If empty, it's set to $client's national cloud
     * @throws \InvalidArgumentException
     */
    public function __construct(string $requestType, string $endpoint, AbstractGraphClient $graphClient, string $baseUrl = "")
    {
        if (!$requestType || !$endpoint) {
            throw new \InvalidArgumentException("Request type and endpoint cannot be null or empty");
        }
        if (!$graphClient->getAccessToken()) {
            throw new \InvalidArgumentException(GraphConstants::NO_ACCESS_TOKEN);
        }
        $this->requestType = $requestType;
        $this->graphClient = $graphClient;
        $baseUrl = ($baseUrl) ?: $graphClient->getNationalCloud();
        $this->initRequestUri($baseUrl, $endpoint);
        $this->initHeaders();
        $this->initPsr7HttpRequest();
    }

    /**
     * Sets the request URI and updates the Psr7 request with the new URI
     *
     * @param UriInterface $uri
     */
    protected function setRequestUri(UriInterface $uri): void {
        $this->requestUri = $uri;
        $this->httpRequest = $this->httpRequest->withUri($uri);
    }

    /**
     * Returns the final request URI after resolving $endpoint to base URL
     *
     * @return UriInterface
     */
    public function getRequestUri(): UriInterface {
        return $this->requestUri;
    }

    /**
     * Returns the HTTP method used
     *
     * @return string
     */
    public function getRequestType(): string {
        return $this->requestType;
    }

    /**
    * Sets a new accessToken
    *
    * @param string $accessToken A valid access token to validate the Graph call
    *
    * @return $this object
    */
    public function setAccessToken(string $accessToken): self
    {
        unset($this->headers['Authorization']); // Prevents appending new token
        $this->addHeaders(['Authorization' => 'Bearer '.$accessToken]);
        return $this;
    }

    /**
     * Sets the return type of the response object
     * Can be set to a model or \Psr\Http\Message\StreamInterface
     *
     * @param string $returnClass The class name to use
     *
     * @return $this object
     * @throws \InvalidArgumentException when $returnClass is not an existing class
     */
    public function setReturnType(string $returnClass): self
    {
        if (!class_exists($returnClass) && !interface_exists($returnClass)) {
            throw new \InvalidArgumentException("Return type specified does not match an existing class definition");
        }
        $this->returnType = $returnClass;
        $this->returnsStream = ($returnClass === StreamInterface::class);
        return $this;
    }

    /**
     * Adds custom headers to the request
     *
     * @param array<string, string|string[]> $headers An array of custom headers
     *
     * @return GraphRequest object
     */
    public function addHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        $this->initPsr7HttpRequest();
        return $this;
    }

    /**
    * Get the request headers
    *
    * @return array<string, string|string[]> of headers
    */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
    * Attach a body to the request. Will JSON encode
    * any Microsoft\Graph\Model objects as well as arrays
    *
    * @param string|StreamInterface|object|array $body The payload to include in the request
    *
    * @return $this object
    */
    public function attachBody($body): self
    {
        // Attach streams & JSON automatically
        if (is_string($body) || is_a($body, StreamInterface::class)) {
            $this->requestBody = $body;
        }
        // By default, JSON-encode
        else {
            $this->requestBody = json_encode($body);
        }
        $this->initPsr7HttpRequest();
        return $this;
    }

    /**
    * Get the body of the request
    *
    * @return string|StreamInterface request body
    */
    public function getBody()
    {
        return $this->requestBody;
    }

    /**
     * Executes the HTTP request using $graphClient's http client or a PSR-18 compliant HTTP client
     *
     * @param ClientInterface|null $client (optional) When null, uses $graphClient's http client
     * @return array|GraphResponse|StreamInterface|object Graph Response object or response body cast to $returnType
     * @throws ClientExceptionInterface if an error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx response is returned.
     * @throws GraphServiceException containing error payload if 5xx response is returned.
     */
    public function execute(?ClientInterface $client = null)
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }

        $result = $client->sendRequest($this->httpRequest);
        $this->handleErrorResponse($result);

        // Check to see if returnType is a stream, if so return it immediately
        if($this->returnsStream) {
            return $result->getBody();
        }

        // Wrap response in GraphResponse layer
        $response = new GraphResponse(
            $this,
            $result->getBody(),
            $result->getStatusCode(),
            $result->getHeaders()
        );

        // If no return type is specified, return GraphResponse
        $returnObj = $response;

        if ($this->returnType) {
            $returnObj = $response->getResponseAsObject($this->returnType);
        }
        return $returnObj;
    }

    /**
     * Executes the HTTP request asynchronously using $client
     *
     * @param HttpAsyncClient|null $client (optional) When null, uses $graphClient's http client
     * @return Promise Resolves to GraphResponse object|response body cast to $returnType. Fails throwing the exception
     * @throws ClientExceptionInterface if there are any errors while making the HTTP request
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     * @throws \Exception
     */
    public function executeAsync(?HttpAsyncClient $client = null): Promise
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }

        return $client->sendAsyncRequest($this->httpRequest)->then(
            // On success, return the result/response
            function ($result) {
                $this->handleErrorResponse($result);

                // Check to see if returnType is a stream, if so return it immediately
                if($this->returnsStream) {
                    return $result->getBody();
                }

                $response = new GraphResponse(
                    $this,
                    $result->getBody(),
                    $result->getStatusCode(),
                    $result->getHeaders()
                );
                $returnObject = $response;
                if ($this->returnType) {
                    $returnObject = $response->getResponseAsObject(
                        $this->returnType
                    );
                }
                return $returnObject;
            },
            // On fail, forward the exception
            function ($reason) {
                throw $reason;
            }
        );
    }

    /**
     * Download a file from OneDrive to a given location
     *
     * @param string $path path to download the file contents to
     * @param ClientInterface|null $client (optional) When null, defaults to $graphClient's http client
     * @throws \RuntimeException when unable to open $path for writing
     * @throws ClientExceptionInterface if an error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     */
    public function download(string $path, ?ClientInterface $client = null): void
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }

        $resource = Utils::tryFopen($path, 'w');
        $stream = Utils::streamFor($resource);
        $response = $client->sendRequest($this->httpRequest);
        $this->handleErrorResponse($response);
        $stream->write($response->getBody()->getContents());
        $stream->close();
    }

    /**
     * Upload a file from $path to Graph API
     *
     * @param string $path path of file to be uploaded
     * @param ClientInterface|null $client (optional)
     * @return array|GraphResponse|StreamInterface|object Graph Response object or response body cast to $returnType
     * @throws ClientExceptionInterface if an error occurs while making the request
     * @throws  \RuntimeException if $path cannot be opened for reading
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     */
    public function upload(string $path, ?ClientInterface $client = null)
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }
        $resource = Utils::tryFopen($path, 'r');
        $stream = Utils::streamFor($resource);
        $this->attachBody($stream);
        return $this->execute($client);
    }

    /**
     * Sets default headers based on baseUrl being a Graph endpoint or not
     */
    private function initHeaders(): void
    {
        $coreSdkVersion = "graph-php-core/".GraphConstants::SDK_VERSION;
        if ($this->graphClient->getApiVersion() === GraphConstants::BETA_API_VERSION) {
            $serviceLibSdkVersion = "graph-php-beta/".$this->graphClient->getSdkVersion();
        } else {
            $serviceLibSdkVersion = "graph-php/".$this->graphClient->getSdkVersion();
        }
        if (NationalCloud::containsNationalCloudHost($this->requestUri)) {
            $this->defaultHeaders = [
                'Content-Type' => 'application/json',
                'SdkVersion' => $serviceLibSdkVersion.", ".$coreSdkVersion,
                'Authorization' => 'Bearer ' . $this->graphClient->getAccessToken()
            ];
        } else {
            $this->defaultHeaders = [
                'Content-Type' => 'application/json',
            ];
        }
        $this->headers = $this->defaultHeaders;
    }

    /**
     * Creates full request URI by resolving $baseUrl and $endpoint based on RFC 3986
     *
     * @param string $baseUrl
     * @param string $endpoint
     * @throws \InvalidArgumentException
     */
    protected function initRequestUri(string $baseUrl, string $endpoint): void {
        $this->requestUri = GraphRequestUtil::getRequestUri($baseUrl, $endpoint, $this->graphClient->getApiVersion());
    }

    /**
     * Initialises a PSR-7 Http Request object
     */
    protected function initPsr7HttpRequest(): void {
        $this->httpRequest = new Request($this->requestType, $this->requestUri, $this->headers, $this->requestBody);
    }

    /**
     * Check if response status code is a client error
     *
     * @param int $httpStatusCode
     * @return bool
     */
    private function is4xx(int $httpStatusCode): bool {
        return ($httpStatusCode >= 400 && $httpStatusCode < 500);
    }

    /**
     * Check if response status code is a server error
     *
     * @param int $httpStatusCode
     * @return bool
     */
    private function is5xx(int $httpStatusCode): bool {
        return ($httpStatusCode >= 500 && $httpStatusCode < 600);
    }

    /**
     * Throws appropriate exception type
     *
     * @param Response $httpResponse
     * @throws GraphServiceException for server errors
     * @throws GraphClientException for client errors
     */
    private function handleErrorResponse(Response $httpResponse) {
        if ($this->is5xx($httpResponse->getStatusCode())) {
            throw new GraphServiceException(
                $this,
                $httpResponse->getStatusCode(),
                json_decode($httpResponse->getBody(), true),
                $httpResponse->getHeaders()
            );
        }
        if ($this->is4xx($httpResponse->getStatusCode())) {
            throw new GraphClientException(
                $this,
                $httpResponse->getStatusCode(),
                json_decode($httpResponse->getBody(), true),
                $httpResponse->getHeaders()
            );
        }
    }
}
