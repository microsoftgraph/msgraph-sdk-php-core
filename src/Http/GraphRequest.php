<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Microsoft\Graph\Core\ExceptionWrapper;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphException;

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
    * A valid access token
    *
    * @var string
    */
    protected $accessToken;
    /**
    * An array of headers to send with the request
    *
    * @var array(string => string)
    */
    protected $headers;
    /**
    * The body of the request (optional)
    *
    * @var string
    */
    protected $requestBody;
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
     * @var BaseClient
     */
    protected $graphClient;
    /**
     * PSR-7 Request to be passed to HTTP client
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    protected $httpRequest;
    /**
     * Full Request URI (base URL + endpoint)
     *
     * @var Uri
     */
    protected $requestUri;

    /**
     * GraphRequest constructor.
     * Sets $baseUrl by default to $graphClient's national cloud
     * Resolves $baseUrl and $endpoint based on RFC 3986
     *
     * @param string $requestType The HTTP method to use e.g. "GET" or "POST"
     * @param string $endpoint The url path on the host to be called-
     * @param BaseClient $graphClient The Graph client to use
     * @param string $baseUrl (optional) Use to pass a custom host defined on the graph client. If empty, it's set to $client's national cloud
     * @throws GraphClientException
     */
    public function __construct(string $requestType, string $endpoint, BaseClient $graphClient, string $baseUrl = "")
    {
        if (!$requestType || !$endpoint || !$graphClient) {
            throw new GraphClientException("Request type, endpoint and client cannot be null or empty");
        }
        $this->requestType = $requestType;
        $this->graphClient = $graphClient;
        $this->headers = $this->_getDefaultHeaders();

        if (!$graphClient->getAccessToken()) {
            throw new GraphClientException(GraphConstants::NO_ACCESS_TOKEN);
        }
        $this->accessToken = $graphClient->getAccessToken();
        $baseUrl = ($baseUrl) ?: $graphClient->getNationalCloud();
        $this->initRequestUri($baseUrl, $endpoint);
        // Initialise PSR-7 Request object
        $this->httpRequest = new Request($requestType, $this->requestUri, $this->headers);
    }

    /**
     * Creates full request URI by resolving $baseUrl and $endpoint based on RFC 3986
     *
     * @param string $baseUrl
     * @param $endpoint
     * @throws GraphClientException
     */
    private function initRequestUri(string $baseUrl, $endpoint): void {
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

    public function getHttpRequest(): Request
    {
        return $this->httpRequest;
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
    * @return GraphRequest object
    */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->headers['Authorization'] = 'Bearer ' . $this->accessToken;
        return $this;
    }

    /**
    * Sets the return type of the response object
    *
    * @param mixed $returnClass The object class to use
    *
    * @return GraphRequest object
    */
    public function setReturnType($returnClass)
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
    public function addHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
    * Get the request headers
    *
    * @return array of headers
    */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
    * Attach a body to the request. Will JSON encode
    * any Microsoft\Graph\Model objects as well as arrays
    *
    * @param mixed $obj The object to include in the request
    *
    * @return GraphRequest object
    */
    public function attachBody($obj)
    {
        // Attach streams & JSON automatically
        if (is_string($obj) || is_a($obj, 'GuzzleHttp\\Psr7\\Stream')) {
            $this->requestBody = $obj;
        }
        // By default, JSON-encode
        else {
            $this->requestBody = json_encode($obj);
        }
        return $this;
    }

    /**
    * Get the body of the request
    *
    * @return mixed request body of any type
    */
    public function getBody()
    {
        return $this->requestBody;
    }

    /**
    * Executes the HTTP request using Guzzle
    *
    * @param mixed $client The client to use in the request
    *
    * @throws \GuzzleHttp\Exception\GuzzleException
    *
    * @return mixed object or array of objects
    *         of class $returnType
    */
    public function execute($client = null)
    {
        if (is_null($client)) {
            $client = $this->createGuzzleClient();
        }

        try {
            $result = $client->request(
                $this->requestType,
                $this->_getRequestUrl(),
                [
                    'body' => $this->requestBody,
                    'timeout' => $this->timeout
                ]
            );
        } catch(BadResponseException $e) {
            throw ExceptionWrapper::wrapGuzzleBadResponseException($e);
        }

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
    * Executes the HTTP request asynchronously using Guzzle
    *
    * @param mixed $client The client to use in the request
    *
    * @return mixed object or array of objects
    *         of class $returnType
    */
    public function executeAsync($client = null)
    {
        if (is_null($client)) {
            $client = $this->createGuzzleClient();
        }

        $promise = $client->requestAsync(
            $this->requestType,
            $this->_getRequestUrl(),
            [
                'body' => $this->requestBody,
                'timeout' => $this->timeout
            ]
        )->then(
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
            // On fail, log the error and return null
            function ($reason) {
                if ($reason instanceof BadResponseException) {
                    $reason = ExceptionWrapper::wrapGuzzleBadResponseException($reason);
                }
                trigger_error("Async call failed: " . $reason->getMessage());
                return null;
            }
        );
        return $promise;
    }

    /**
    * Download a file from OneDrive to a given location
    *
    * @param string $path   The path to download the file to
    * @param mixed  $client The client to use in the request
    *
    * @throws GraphException if file path is invalid
    * @throws \GuzzleHttp\Exception\GuzzleException
    *
    * @return null
    */
    public function download($path, $client = null)
    {
        if (is_null($client)) {
            $client = $this->createGuzzleClient();
        }
        try {
            $file = fopen($path, 'w');
            if (!$file) {
                throw new GraphException(GraphConstants::INVALID_FILE);
            }

            $client->request(
                $this->requestType,
                $this->_getRequestUrl(),
                [
                    'body' => $this->requestBody,
                    'sink' => $file,
                    'timeout' => $this->timeout
                ]
            );
            if(is_resource($file)){
                fclose($file);
            }

        } catch(GraphException $e) {
            throw new GraphException(GraphConstants::INVALID_FILE);
        } catch(BadResponseException $e) {
            throw ExceptionWrapper::wrapGuzzleBadResponseException($e);
        }

        return null;
    }

    /**
    * Upload a file to OneDrive from a given location
    *
    * @param string $path   The path of the file to upload
    * @param mixed  $client The client to use in the request
    *
    * @throws GraphException if file is invalid
    * @throws \GuzzleHttp\Exception\GuzzleException
    *
    * @return mixed DriveItem or array of DriveItems
    */
    public function upload($path, $client = null)
    {
        if (is_null($client)) {
            $client = $this->createGuzzleClient();
        }
        try {
            if (file_exists($path) && is_readable($path)) {
                $file = fopen($path, 'r');
                $stream = \GuzzleHttp\Psr7\stream_for($file);
                $this->requestBody = $stream;
                return $this->execute($client);
            } else {
                throw new GraphException(GraphConstants::INVALID_FILE);
            }
        } catch(GraphException $e) {
            throw new GraphException(GraphConstants::INVALID_FILE);
        }
    }

    /**
    * Get a list of headers for the request
    *
    * @return array The headers for the request
    */
    private function _getDefaultHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'SdkVersion' => 'Graph-php-' . GraphConstants::SDK_VERSION,
            'Authorization' => 'Bearer ' . $this->graphClient->getAccessToken()
        ];
        return $headers;
    }

    /**
    * Checks whether the endpoint currently contains query
    * parameters and returns the relevant concatenator for
    * the new query string
    *
    * @return string "?" or "&"
    */
    protected function getConcatenator()
    {
        if (stripos($this->endpoint, "?") === false) {
            return "?";
        }
        return "&";
    }

    /**
    * Create a new Guzzle client
    * To allow for user flexibility, the
    * client is not reused. This allows the user
    * to set and change headers on a per-request
    * basis
    *
    * If a proxyPort was passed in the constructor, all
    * requests will be forwared through this proxy.
    *
    * @return \GuzzleHttp\Client The new client
    */
    protected function createGuzzleClient()
    {
        $clientSettings = [
            'base_uri' => $this->baseUrl,
            'http_errors' => $this->http_errors,
            'headers' => $this->headers
        ];
        if ($this->proxyPort !== null) {
            $clientSettings['verify'] = $this->proxyVerifySSL;
            $clientSettings['proxy'] = $this->proxyPort;
        }
        $client = new Client($clientSettings);

        return $client;
    }
}
