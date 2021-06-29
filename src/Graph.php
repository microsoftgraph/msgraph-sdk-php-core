<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*
* Graph File
* PHP version 7
*
* @category  Library
* @package   Microsoft.Graph
* @copyright 2016 Microsoft Corporation
* @license   https://opensource.org/licenses/MIT MIT License
* @version   GIT: 0.1.0
* @link      https://graph.microsoft.io/
*/

namespace Microsoft\Graph;

use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\ClientInitialisationException;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Http\HttpClientFactory;
use Microsoft\Graph\Http\HttpClientInterface;

/**
 * Class Graph
 *
 * @category Library
 * @package  Microsoft.Graph
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://graph.microsoft.io/
 */
class Graph
{
    /**
    * The access_token provided after authenticating
    * with Microsoft Graph (required)
    *
    * @var string
    */
    private $_accessToken;

    /**
    * The api version to use ("v1.0", "beta")
    * Default is "v1.0"
    *
    * @var string
    */
    private $_apiVersion = GraphConstants::API_VERSION;

    /**
     * Host to use as the base URL and for authentication
     * @var string
     */
    private $_nationalCloud = NationalCloud::GLOBAL;

    /**
     * HttpClient to use for requests
     * @var HttpClientInterface
     */
    private $_httpClient;

    
    /**
     * Graph constructor.
     *
     * Creates a Graph client object used to make requests to the Graph API
     * @param string $apiVersion
     * @param string $nationalCloud
     * @throws \InvalidArgumentException|Exception\ClientInitialisationException
     */
    public function __construct(string $apiVersion = GraphConstants::API_VERSION,
                                string $nationalCloud = NationalCloud::GLOBAL)
    {
        if (!$apiVersion) {
            throw new \InvalidArgumentException("Api version string cannot be empty");
        }
        if (!in_array($nationalCloud, NationalCloud::getValues())) {
            throw new \InvalidArgumentException("Invalid national cloud passed. See NationalCloud constants");
        }
        $this->_apiVersion = $apiVersion;
        $this->_nationalCloud = $nationalCloud;
        $this->_httpClient = (new HttpClientFactory())->nationalCloud($nationalCloud)->createAdapter();
    }

    /**
     * Sets HTTP client to any implementation of HttpClientInterface
     *
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient) {
        $this->_httpClient = $httpClient;
    }

    /**
     * Sets HTTP client using Guzzle request options
     * Creates a Guzzle client and wraps it under an implementation of HttpClientInterface
     *
     * @param array $guzzleConfig
     * @throws ClientInitialisationException
     */
    public function setHttpClientFromConfig(array $guzzleConfig) {
        $this->_httpClient = (new HttpClientFactory())->clientConfig($guzzleConfig)
                                                    ->nationalCloud($this->_nationalCloud)
                                                    ->createAdapter();
    }

    /**
    * Sets the access token. A valid access token is required
    * to run queries against Graph
    *
    * @param string $accessToken The user's access token, retrieved from
    *                     MS auth
    *
    * @return Graph object
    */
    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;
        return $this;
    }

	/**
	 * Creates a new request object with the given Graph information
	 *
	 * @param string $requestType The HTTP method to use, e.g. "GET" or "POST"
	 * @param string $endpoint    The Graph endpoint to call
	 *
	 * @return GraphRequest The request object, which can be used to
	 *                      make queries against Graph
	 * @throws Exception\GraphException
	 */
    public function createRequest($requestType, $endpoint)
    {
        return new GraphRequest(
            $requestType,
            $endpoint,
            $this->_accessToken,
            $this->_nationalCloud,
            $this->_apiVersion,
            $this->_httpClient
        );
    }

	/**
	 * Creates a new collection request object with the given
	 * Graph information
	 *
	 * @param string $requestType The HTTP method to use, e.g. "GET" or "POST"
	 * @param string $endpoint    The Graph endpoint to call
	 *
	 * @return GraphCollectionRequest The request object, which can be
	 *                                used to make queries against Graph
	 * @throws Exception\GraphException
	 */
    public function createCollectionRequest($requestType, $endpoint)
    {
        return new GraphCollectionRequest(
            $requestType,
            $endpoint,
            $this->_accessToken,
            $this->_nationalCloud,
            $this->_apiVersion,
            $this->_httpClient
        );
    }
}
