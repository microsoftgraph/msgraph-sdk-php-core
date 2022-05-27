<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/

namespace Microsoft\Graph\Core\Core\Http;

use GuzzleHttp\Psr7\Uri;
use Microsoft\Graph\Core\Core\GraphConstants;
use Microsoft\Graph\Core\Core\Exception\GraphClientException;
use Microsoft\Graph\Core\Core\Exception\GraphServiceException;
use Microsoft\Graph\Core\Core\Task\PageIterator;
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
     * Graph client to use for the request
     *
     * @var AbstractGraphClient
     */
    private $graphClient;

    /**
     * Constructs a new GraphCollectionRequest object
     *
     * @param string $requestType The HTTP verb for the request ("GET", "POST", "PUT", etc.)
     * @param string $endpoint The URI of the endpoint to hit
     * @param AbstractGraphClient $graphClient
     * @param string $baseUrl (optional) If empty, it's set to $client's national cloud
     * @throws \InvalidArgumentException
     */
    public function __construct(string $requestType, string $endpoint, AbstractGraphClient $graphClient, string $baseUrl = "")
    {
        parent::__construct(
            $requestType,
            $endpoint,
            $graphClient,
            $baseUrl
        );
        $this->graphClient = $graphClient;
        $this->end = false;
    }

	/**
	 * Gets the number of entries in the collection
	 *
	 * @return int|null the number of entries | null if @odata.count doesn't exist for that collection
     * @throws ClientExceptionInterface if an error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx response is returned.
     * @throws GraphServiceException containing error payload if 5xx response is returned.
     */
    public function count(): ?int
    {
        $query = '$count=true';
        $requestUri = $this->getRequestUri();
        $this->setRequestUri(new Uri( $requestUri . GraphRequestUtil::getQueryParamConcatenator($requestUri) . $query));
        // Temporarily disable returnType in order to get GraphResponse object returned by execute()
        $this->originalReturnType = $this->returnType;
        $this->returnType = null;
        $result = $this->execute();
        $this->returnType = $this->originalReturnType;
        return ($result->getCount()) ?: null; // @phpstan-ignore-line
    }

    /**
    * Sets the number of results to return with each call
    * to "getPage()"
    *
    * @param int $pageSize The page size
     * @return GraphCollectionRequest object
     * @throws \InvalidArgumentException if the requested page size exceeds Graph's defined page size limit
    */
    public function setPageSize(int $pageSize): self
    {
        if ($pageSize > GraphConstants::MAX_PAGE_SIZE) {
            throw new \InvalidArgumentException(GraphConstants::MAX_PAGE_SIZE_ERROR);
        }
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Gets the next page of results
     *
     * @return GraphResponse|array of objects of class $returnType| GraphResponse if no $returnType
     * @throws ClientExceptionInterface if an error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx response is returned.
     * @throws GraphServiceException containing error payload if 5xx response is returned.
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
     * @return void
     */
    private function setPageCallInfo(): void
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
    }

    /**
    * Clean up after making a page call request
    *
    * @param GraphResponse $response The GraphResponse returned
    *        after making a page call
    *
    * @return GraphResponse|array result of the call, formatted according
    *         to the returnType set by the user. If no return type, returns GraphResponse
    */
    private function processPageCallReturn(GraphResponse $response)
    {
        $this->nextLink = $response->getNextLink();
        $this->deltaLink = $response->getDeltaLink();

        /* If no next link is returned, we have reached the end
           of the collection */
        if (!$this->nextLink) {
            $this->end = true;
        }

        // Return GraphResponse if no return type
        $result = $response;

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

    /**
     * Creates a page iterator. Initiates its collectionResponse with the result of getPage()
     *
     * @param callable(): bool $callback function to execute against each element of $entityCollection. Must return boolean which determines if iteration should pause/proceed
     * @return PageIterator call iterate() to start the iterator
     * @throws ClientExceptionInterface if error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx is returned while fetching the initial page
     * @throws GraphServiceException containing error payload if 5xx is returned while fetching the initial page
     */
    public function pageIterator(callable $callback): PageIterator {
        // temporarily disable return type in order to get first page as GraphResponse object
        $returnType = $this->returnType;
        $this->returnType = null;
        $collectionResponse = $this->getPage();
        $this->returnType = $returnType;

        return new PageIterator(
            $this->graphClient,
            $collectionResponse,
            $callback,
            $this->returnType,
            new RequestOptions(array_diff_key($this->getHeaders(), $this->defaultHeaders)) // remove default headers to prevent duplication in next page requests
        );
    }
}
