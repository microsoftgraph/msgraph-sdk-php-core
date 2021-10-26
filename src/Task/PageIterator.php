<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Task;

use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphServiceException;
use Microsoft\Graph\Http\AbstractGraphClient;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Http\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class PageIterator
 * Allows passing a callback function to execute against each entity in a collection.
 * The callback function must return a boolean to tell the PageIterator to pause/stop iterating
 *
 * @package Microsoft\Graph\Task
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class PageIterator
{
    /**
     * Graph client to use to make the request
     *
     * @var AbstractGraphClient
     */
    private $graphClient;
    /**
     * Response from a collection request
     *
     * @var GraphResponse
     */
    private $collectionResponse;
    /**
     * Collection of items to iterate through.
     *
     * @var array
     */
    private $entityCollection;
    /**
     * Callback function to execute against each entity in $entityCollection.
     * Must return bool. If false, iteration pauses and can be resumed by calling resume(). If true, iteration proceeds
     *
     * @var callable(): bool
     */
    private $callback;
    /**
     * Custom request configs to add to subsequent calls to $entityCollection's @odata.nextLink
     *
     * @var RequestOptions|null
     */
    private $requestOptions;
    /**
     * Class type to deserialize future nextLink response payloads to
     * Should match argument type expected by $callback
     * If empty, an array of each JSON-decoded entity is passed to $callback
     *
     * @var string
     */
    private $returnType;
    /**
     * Index of next item to be passed to $callback.
     * Allows resumable iteration
     *
     * @var int
     */
    private $currentIndex = 0;
    /**
     * Determine if iteration is complete or not
     *
     * @var bool
     */
    private $complete = false;

    /**
     * PageIterator constructor.
     * @param AbstractGraphClient $graphClient to be used to make the request
     * @param GraphResponse $collectionResponse initial collection of items to iterate through.
     * @param callable(): bool $callback function to execute against each element of $entityCollection. Must return boolean which determines if iteration should pause/proceed
     * @param string $returnType (optional) class to cast subsequent responses to after requesting the next link. Should be compatible with $callback's expected argument type
     *                                 if empty, each entity will be JSON-decoded to an array and passed to $callback
     * @param RequestOptions|null $requestOptions (optional) custom headers/middleware to use for subsequent calls to $entityCollection's nextLink
     *
     * @throws \InvalidArgumentException if GraphResponse does not contain a collection of values
     */
    public function __construct(AbstractGraphClient $graphClient,
                                GraphResponse $collectionResponse,
                                callable $callback,
                                string $returnType = '',
                                ?RequestOptions $requestOptions = null) {

        if (!array_key_exists("value", $collectionResponse->getBody())
            || !is_array($collectionResponse->getBody()["value"])) {
            throw new \InvalidArgumentException("Collection response must contain a collection of values");
        }

        $this->graphClient = $graphClient;
        $this->collectionResponse = $collectionResponse;
        $this->entityCollection =  ($returnType) ? $collectionResponse->getResponseAsObject($returnType) : $collectionResponse->getBody()["value"];
        $this->callback = $callback;
        $this->returnType = $returnType;
        $this->requestOptions = $requestOptions;
    }

    /**
     * Passes each item in $entityCollection to $callback and continues calling nextLink there are no more items in the collection
     * Iteration pauses if $callback returns false
     *
     * @return Promise that resolves to true on completion and throws error on rejection
     *
     * @throws ClientExceptionInterface if error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     */
    public function iterate(): Promise {
        $promise = new FulfilledPromise(false);
        return $promise->then(function ($result) {
            while (!$this->complete) {
                if (empty($this->entityCollection)) {
                    $this->complete = true;
                    break;
                }

                if ($this->currentIndex == (sizeof($this->entityCollection))) {
                    if (!$this->getNextLink()) {
                        $this->complete = true;
                        break;
                    }
                    $this->getNextPage();
                    $this->currentIndex = 0;
                    continue;
                }
                $entity = $this->entityCollection[$this->currentIndex];
                $callbackContinue = call_user_func($this->callback, $entity);
                $this->currentIndex++;
                if (!$callbackContinue) {
                    break;
                }
            }
            return true;

        }, function ($reason) {
            throw $reason;
        });
    }

    /**
     * Resume iteration after $callback returning false
     *
     * @return Promise
     * @throws ClientExceptionInterface if error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     */
    public function resume(): Promise {
        return $this->iterate();
    }

    /**
     * Updates the access token
     *
     * @param string $token
     * @return $this
     */
    public function setAccessToken(string $token): self {
        $this->graphClient->setAccessToken($token);
        return $this;
    }

    /**
     * Check if iteration has happened across all pages of the collection.
     * Can be false if iteration has been paused by $callback before all pages were processed
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->complete;
    }

    /**
     * Get delta link of the current collection
     *
     * @return string|null
     */
    public function getDeltaLink(): ?string {
        return $this->collectionResponse->getDeltaLink();
    }

    /**
     * Get next link of the current collection
     *
     * @return string|null
     */
    public function getNextLink(): ?string {
        return $this->collectionResponse->getNextLink();
    }

    /**
     * Fetches the next page of results
     *
     * @throws ClientExceptionInterface if error occurs while making the request
     * @throws GraphClientException containing error payload if 4xx is returned
     * @throws GraphServiceException containing error payload if 5xx is returned
     */
    private function getNextPage(): void {
        $nextLink = $this->getNextLink();
        $request = $this->graphClient->createRequest("GET", $nextLink);
        if ($this->requestOptions) {
            $request = $request->addHeaders($this->requestOptions->getHeaders());
        }
        $this->collectionResponse = $request->execute();
        if (!$this->collectionResponse || empty($this->collectionResponse->getBody()) || !array_key_exists("value", $this->collectionResponse->getBody())) { // @phpstan-ignore-line
            $this->entityCollection = [];
            return;
        }
        if (!$this->returnType) {
            if (array_key_exists("value", $this->collectionResponse->getBody())) { // @phpstan-ignore-line
                $this->entityCollection = $this->collectionResponse->getBody()["value"]; // @phpstan-ignore-line
                return;
            }
        }
        $this->entityCollection = $this->collectionResponse->getResponseAsObject($this->returnType); // @phpstan-ignore-line
    }
}
