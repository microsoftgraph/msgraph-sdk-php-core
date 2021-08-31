<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Http;

use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Exception\GraphClientException;

/**
 * Class BaseClient
 *
 * Base class to be extended by Graph client classes in v1 and beta packages
 *
 * @package Microsoft\Graph
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
abstract class AbstractGraphClient
{
    /**
    * The access_token provided after authenticating
    * with Microsoft Graph (required)
    *
    * @var string
    */
    private $accessToken;

    /**
     * Host to use as the base URL and for authentication
     * @var string
     */
    private $nationalCloud = NationalCloud::GLOBAL;

    /**
     * HttpClient to use for requests
     * @var HttpClientInterface
     */
    private $httpClient = null;


    /**
     * BaseClient constructor.
     *
     * Creates a Graph client object used to make requests to the Graph API
     *
     * @param string|null $nationalCloud if null defaults to "https://graph.microsoft.com"
     * @param HttpClientInterface|null $httpClient if null creates default Guzzle client
     * @throws GraphClientException
     */
    public function __construct(?string $nationalCloud = NationalCloud::GLOBAL,
                                ?HttpClientInterface  $httpClient = null)
    {
        $this->nationalCloud = ($nationalCloud) ?: NationalCloud::GLOBAL;
        $this->httpClient = ($httpClient) ?: HttpClientFactory::nationalCloud($this->nationalCloud)::createAdapter();
    }

    /**
    * Sets the access token. A valid access token is required
    * to run queries against Graph
    *
    * @param string $accessToken The user's access token, retrieved from
    *                     MS auth
    *
    * @return $this object
    */
    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken(): ?string {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getNationalCloud(): string
    {
        return $this->nationalCloud;
    }

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

	/**
	 * Creates a new request object with the given Graph information
	 *
	 * @param string $requestType The HTTP method to use, e.g. "GET" or "POST"
	 * @param string $endpoint    The Graph endpoint to call
	 *
	 * @return GraphRequest The request object, which can be used to
	 *                      make queries against Graph
	 * @throws GraphClientException
	 */
    public function createRequest(string $requestType, string $endpoint): GraphRequest
    {
        return new GraphRequest(
            $requestType,
            $endpoint,
            $this
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
	 * @throws GraphClientException
	 */
    public function createCollectionRequest(string $requestType, string $endpoint): GraphCollectionRequest
    {
        return new GraphCollectionRequest(
            $requestType,
            $endpoint,
            $this
        );
    }

    /**
     * Return SDK version used in the service library client.
     *
     * @return string
     */
    public abstract function getSdkVersion(): string;

    /**
     * Returns API version used in the service library
     *
     * @return string
     */
    public abstract function getApiVersion(): string;
}