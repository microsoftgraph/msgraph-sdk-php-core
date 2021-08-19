<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Http;

use GuzzleHttp\Psr7\Uri;
use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class GraphCollectionRequest
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphCollectionRequest extends GraphRequest
{
    /**
    * The size of page to divide the collection into
    *
    * @var int
    */
    protected $pageSize;
    /**
    * The next link to use in calling a new page of results
    *
    * @var string
    */
    protected $nextLink;
    /**
    * The delta link to use in calling /delta a subsequent time
    *
    * @var string
    */
    protected $deltaLink;
    /**
    * True if the user has reached the end of the collection
    *
    * @var bool
    */
    protected $end;
    /**
     * The return type that the user specified
     *
     * @var string
     */
    protected $originalReturnType;

    /**
     * Constructs a new GraphCollectionRequest object
     *
     * @param string $requestType The HTTP verb for the request ("GET", "POST", "PUT", etc.)
     * @param string $endpoint The URI of the endpoint to hit
     * @param AbstractGraphClient $graphClient
     * @param string $baseUrl (optional) If empty, it's set to $client's national cloud
     * @throws GraphClientException
     */
    public function __construct(string $requestType, string $endpoint, AbstractGraphClient $graphClient, string $baseUrl = "")
    {
        parent::__construct(
            $requestType,
            $endpoint,
            $graphClient,
            $baseUrl
        );
        $this->end = false;
    }

	/**
	 * Gets the number of entries in the collection
	 *
	 * @return int the number of entries
     * @throws ClientExceptionInterface|GraphClientException
     */
    public function count()
    {
        $query = '$count=true';
        $requestUri = $this->getRequestUri();
        $this->setRequestUri(new Uri( $requestUri . GraphRequestUtil::getQueryParamConcatenator($requestUri) . $query));
        // Temporarily disable returnType in order to get GraphResponse object returned by execute()
        $this->originalReturnType = $this->returnType;
        $this->returnType = null;
        $result = $this->execute();

        if (is_a($result, GraphResponse::class) &&  $result->getCount()) {
            return $result->getCount();
        }

        /* The $count query parameter for the Graph API
           is available on several models but not all */
        throw new GraphClientException('Count unavailable for this collection');
    }

    /**
    * Sets the number of results to return with each call
    * to "getPage()"
    *
    * @param int $pageSize The page size
    *
     * @throws GraphClientException if the requested page size exceeds
     *         the Graph's defined page size limit
    * @return GraphCollectionRequest object
    */
    public function setPageSize(int $pageSize): self
    {
        if ($pageSize > GraphConstants::MAX_PAGE_SIZE) {
            throw new GraphClientException(GraphConstants::MAX_PAGE_SIZE_ERROR);
        }
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Gets the next page of results
     *
     * @return array of objects of class $returnType
     * @throws ClientExceptionInterface
     */
    public function getPage()
    {
        $this->setPageCallInfo();
        $response = $this->execute();

        return $this->processPageCallReturn($response);
    }

    /**
     * Sets the required query information to get a new page
     *
     * @return GraphCollectionRequest
     */
    private function setPageCallInfo(): self
    {
        // Store these to add temporary query data to request
        $this->originalReturnType = $this->returnType;

        /* This allows processPageCallReturn to receive
           all of the response data, not just the objects */
        $this->returnType = null;

        if ($this->end) {
            trigger_error('Reached end of collection');
        }

        if ($this->nextLink) {
            $this->setRequestUri(new Uri($this->nextLink));
        } else {
            // This is the first request to the endpoint
            if ($this->pageSize) {
                $query = '$top='.$this->pageSize;
                $requestUri = $this->getRequestUri();
                $this->setRequestUri(new Uri( $requestUri . GraphRequestUtil::getQueryParamConcatenator($requestUri) . $query));
            }
        }
        return $this;
    }

    /**
    * Clean up after making a page call request
    *
    * @param GraphResponse $response The GraphResponse returned
    *        after making a page call
    *
    * @return mixed result of the call, formatted according
    *         to the returnType set by the user
    */
    private function processPageCallReturn(GraphResponse $response)
    {
        $this->nextLink = $response->getNextLink();
        $this->deltaLink = $response->getDeltaLink();

        /* If no skip token is returned, we have reached the end
           of the collection */
        if (!$this->nextLink) {
            $this->end = true;
        }

        $result = $response->getBody();

        // Cast as user-defined model
        if ($this->originalReturnType) {
            $result = $response->getResponseAsObject($this->originalReturnType);
        }

        // Restore user-defined parameters
        $this->returnType = $this->originalReturnType;

        return $result;
    }

    /**
    * Gets whether the user has reached the end of the collection
    *
    * @return bool The end
    */
    public function isEnd(): bool
    {
        return $this->end;
    }

    /**
    * Gets a delta link to use with subsequent
    * calls to /delta
    *
    * @return string|null The delta link
    */
    public function getDeltaLink(): ?string
    {
        return $this->deltaLink;
    }

    /**
     * Get page size
     * @return int
     */
    public function getPageSize(): int {
        return $this->pageSize;
    }
}
