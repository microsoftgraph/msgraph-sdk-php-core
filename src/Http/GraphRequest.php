<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;

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
    * @var array(string => string)
    */
    private $headers;
    /**
    * The body of the request (optional)
    *
    * @var string
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
    * @var string
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
     * @var Uri
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
     * @throws GraphClientException
     */
    public function __construct(string $requestType, string $endpoint, AbstractGraphClient $graphClient, string $baseUrl = "")
    {
        if (!$requestType || !$endpoint || !$graphClient) {
            throw new GraphClientException("Request type, endpoint and client cannot be null or empty");
        }
        if (!$graphClient->getAccessToken()) {
            throw new GraphClientException(GraphConstants::NO_ACCESS_TOKEN);
        }
        $this->requestType = $requestType;
        $this->graphClient = $graphClient;
        $baseUrl = ($baseUrl) ?: $graphClient->getNationalCloud();
        $this->initRequestUri($baseUrl, $endpoint);
        $this->initHeaders($baseUrl);
        $this->initPsr7HttpRequest();
    }

    public function getHttpRequest(): Request
    {
        return $this->httpRequest;
    }

    protected function setRequestUri(Uri $uri): void {
        $this->requestUri = $uri;
        $this->initPsr7HttpRequest();
    }

    protected function getRequestUri(): Uri {
        return $this->requestUri;
    }

    /**
     * Gets whether request returns a stream or not
     *
     * @return boolean
     */
    public function getReturnsStream()
    {
        return $this->returnsStream;
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
        $this->addHeaders(['Authorization' => 'Bearer '.$accessToken]);
        return $this;
    }

    /**
    * Sets the return type of the response object
    *
    * @param string $returnClass The class name to use
    *
    * @return $this object
    */
    public function setReturnType(string $returnClass): self
    {
        $this->returnType = $returnClass;
        if ($this->returnType == "GuzzleHttp\Psr7\Stream") {
            $this->returnsStream  = true;
        } else {
            $this->returnsStream = false;
        }
        return $this;
    }

    /**
    * Adds custom headers to the request
    *
    * @param array $headers An array of custom headers
    *
    * @return GraphRequest object
    */
    public function addHeaders(array $headers): self
    {
        $this->headers = array_merge_recursive($this->headers, $headers);
        $this->initPsr7HttpRequest();
        return $this;
    }

    /**
    * Get the request headers
    *
    * @return array of headers
    */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
    * Attach a body to the request. Will JSON encode
    * any Microsoft\Graph\Model objects as well as arrays
    *
    * @param string|StreamInterface|object $obj The object to include in the request
    *
    * @return $this object
    */
    public function attachBody($obj): self
    {
        // Attach streams & JSON automatically
        if (is_string($obj) || is_a($obj, StreamInterface::class)) {
            $this->requestBody = $obj;
        }
        // By default, JSON-encode
        else {
            $this->requestBody = json_encode($obj);
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
     * @throws ClientExceptionInterface
     */
    public function execute(?ClientInterface $client = null)
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }

        $result = $client->sendRequest($this->httpRequest);

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
     * @throws \Exception when promise fails
     */
    public function executeAsync(?HttpAsyncClient $client = null): Promise
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }

        return $client->sendAsyncRequest($this->httpRequest)->then(
            // On success, return the result/response
            function ($result) {

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
     * @throws ClientExceptionInterface|GraphClientException when unable to open $path for writing
     */
    public function download(string $path, ?ClientInterface $client = null): void
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }
        try {
            $resource = Utils::tryFopen($path, 'w');
            $stream = Utils::streamFor($resource);
            $response = $client->sendRequest($this->httpRequest);
            $stream->write($response->getBody());
            $stream->close();
        } catch (\RuntimeException $ex) {
            throw new GraphClientException(GraphConstants::INVALID_FILE, $ex->getCode(), $ex);
        }
    }

    /**
     * Upload a file from $path to Graph API
     *
     * @param string $path path of file to be uploaded
     * @param ClientInterface|null $client (optional)
     * @return array|GraphResponse|StreamInterface|object Graph Response object or response body cast to $returnType
     * @throws ClientExceptionInterface|GraphClientException if $path cannot be opened for reading
     */
    public function upload(string $path, ?ClientInterface $client = null)
    {
        if (is_null($client)) {
            $client = $this->graphClient->getHttpClient();
        }
        try {
            $resource = Utils::tryFopen($path, 'r');
            $stream = Utils::streamFor($resource);
            $this->attachBody($stream);
            return $this->execute($client);
        } catch(\RuntimeException $e) {
            throw new GraphClientException(GraphConstants::INVALID_FILE, $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Sets default headers based on baseUrl being a Graph endpoint or not
     */
    private function initHeaders(string $baseUrl): void
    {
        $coreSdkVersion = "graph-php-core/".GraphConstants::SDK_VERSION;
        $serviceLibSdkVersion = "Graph-php-".$this->graphClient->getSdkVersion();
        if (NationalCloud::containsNationalCloudHost($baseUrl)) {
            $this->headers = [
                'Content-Type' => 'application/json',
                'SdkVersion' => $coreSdkVersion.", ".$serviceLibSdkVersion,
                'Authorization' => 'Bearer ' . $this->graphClient->getAccessToken()
            ];
        } else {
            $this->headers = [
                'Content-Type' => 'application/json',
            ];
        }
    }

    /**
     * Creates full request URI by resolving $baseUrl and $endpoint based on RFC 3986
     *
     * @param string $baseUrl
     * @param $endpoint
     * @throws GraphClientException
     */
    protected function initRequestUri(string $baseUrl, $endpoint): void {
        try {
            $this->requestUri = GraphRequestUtil::getRequestUri($baseUrl, $endpoint, $this->graphClient->getApiVersion());
            if (!$this->requestUri) {
                // $endpoint is a full URL but doesn't meet criteria
                throw new GraphClientException("Endpoint is not a valid URL. Must contain national cloud host.");
            }
        } catch (\InvalidArgumentException $ex) {
            throw new GraphClientException("Unable to resolve base URL=".$baseUrl."\" with endpoint=".$endpoint."\"", 0, $ex);
        }
    }

    protected function initPsr7HttpRequest(): void {
        $this->httpRequest = new Request($this->requestType, $this->requestUri, $this->headers, $this->requestBody);
    }
}
