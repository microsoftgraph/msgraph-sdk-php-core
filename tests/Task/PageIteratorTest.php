<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Test\Task;


use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\Core\Http\GraphCollectionRequest;
use Microsoft\Graph\Core\Core\Http\GraphResponse;
use Microsoft\Graph\Core\Core\Http\RequestOptions;
use Microsoft\Graph\Core\Core\Task\PageIterator;
use Microsoft\Graph\Core\Core\Test\Http\Request\BaseGraphRequestTest;
use Microsoft\Graph\Core\Core\Test\Http\Request\MockHttpClientResponseConfig;
use Microsoft\Graph\Core\Core\Test\Http\SampleGraphResponsePayload;
use Microsoft\Graph\Core\Core\Test\TestData\Model\User;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;


class PageIteratorTest extends BaseGraphRequestTest
{
    private $defaultCollectionResponse;
    private $defaultPageIterator;
    private $defaultCallback;
    private $callbackNumEntitiesProcessed = 0;
    private $callbackIsEntityUser = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupCallback();
        $this->defaultCollectionResponse = $this->createCollectionResponse(SampleGraphResponsePayload::COLLECTION_PAYLOAD);
        $this->defaultPageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $this->defaultCallback,
        );
        $this->setupNextPageRequest();
    }

    private function setupNextPageRequest() {
        $this->mockGraphClient->method('createRequest')
                                ->willReturn($this->defaultGraphRequest);
    }

    private function setupCallback() {
        $this->callbackNumEntitiesProcessed = 0;
        $this->callbackIsEntityUser = false;
        $this->defaultCallback = function ($entity) {
            if (is_a($entity, User::class)) {
                $this->callbackIsEntityUser = true;
                $this->callbackNumEntitiesProcessed ++;
                return false;
            }

            $this->callbackNumEntitiesProcessed ++;
            return true;
        };
    }

    public function testConstructorCreatesPageIterator() {
        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $this->defaultCallback
        );
        $this->assertInstanceOf(PageIterator::class, $pageIterator);
    }

    public function testConstructorThrowsExceptionIfGraphResponseIsNotACollection() {
        $this->expectException(\InvalidArgumentException::class);
        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->createCollectionResponse(SampleGraphResponsePayload::ENTITY_PAYLOAD),
            $this->defaultCallback
        );
    }

    public function testIterateCallsCallbackForEachItemInCollection() {
        // Set response for call to get the next page
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);

        $numEntitiesInCollection = sizeof(SampleGraphResponsePayload::COLLECTION_PAYLOAD["value"]) + sizeof(SampleGraphResponsePayload::LAST_PAGE_COLLECTION_PAYLOAD["value"]);
        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
        $this->assertEquals($numEntitiesInCollection, $this->callbackNumEntitiesProcessed);
    }

    public function testIterateWithStronglyTypedCallbackArgument() {
        $numEntitiesProcessed = 0;
        $callback = function (User $user) use (&$numEntitiesProcessed) {
            $numEntitiesProcessed ++;
            return true;
        };
        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->createCollectionResponse(SampleGraphResponsePayload::LAST_PAGE_COLLECTION_PAYLOAD),
            $callback,
            User::class
        );
        $pageIterator->iterate()->wait();
        $this->assertEquals(sizeof(SampleGraphResponsePayload::LAST_PAGE_COLLECTION_PAYLOAD), $numEntitiesProcessed);
    }

    public function testIteratePausesIfCallbackReturnsFalse() {
        $numProcessed = 0;
        $callback = function (User $user) use (&$numProcessed) {
            $numProcessed ++;
            return ($numProcessed % 2 != 0);
        };

        $iterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $callback,
            User::class
        );
        $promise = $iterator->iterate();
        $promise->wait();
        $this->assertEquals(2, $numProcessed);
    }

    public function testIterateCastsNextPageResultsToExpectedReturnType() {
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);

        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $this->defaultCallback,
            User::class
        );
        $promise = $pageIterator->iterate();
        $promise->wait();
        $this->assertTrue($this->callbackIsEntityUser);
    }

    public function testIterateCompletesIfTheresNoNextLinkToFetch() {
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);

        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
        $this->assertTrue($this->defaultPageIterator->isComplete());
    }

    public function testIterateCompletesIfNextPageIsEmpty() {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
        $this->assertTrue($this->defaultPageIterator->isComplete());
    }

    public function testIterateThrowsExceptionOnErrorGettingNextPage() {
        $this->expectException(ClientExceptionInterface::class);
        $this->mockHttpClient->method('sendRequest')
                                ->willThrowException($this->createMock(NetworkExceptionInterface::class));
        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
    }

    public function testIterateReturnsPromiseThatResolvesToTrueOnFulfilled() {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $promise = $this->defaultPageIterator->iterate();
        $this->assertTrue($promise->wait());
    }

    public function testIterateUsingCallbackWithNoReturnValueIsOnlyCalledOnce() {
        $numEntitiesProcessed = 0;
        $callback = function ($entity) use (&$numEntitiesProcessed) {
            $numEntitiesProcessed ++;
        };

        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $callback
        );

        $promise = $pageIterator->iterate();
        $promise->wait();
        $this->assertEquals(1, $numEntitiesProcessed);
    }

    public function testIteratorGetsNextPageUsingRequestOptions() {
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);
        $header = ["SampleHeader" => ["value"]];
        $requestOptions = new RequestOptions($header);
        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $this->defaultCallback,
            '',
            $requestOptions
        );
        $promise = $pageIterator->iterate();
        $promise->wait();
        $this->assertArrayHasKey("SampleHeader", $this->defaultGraphRequest->getHeaders());
        $this->assertEquals($header["SampleHeader"], $this->defaultGraphRequest->getHeaders()["SampleHeader"]);
    }

    public function testResumeContinuesIteration() {
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);

        $numProcessed = 0;
        $callback = function (User $user) use (&$numProcessed) {
            $numProcessed ++;
            return ($numProcessed % 2 != 0);
        };

        $pageIterator = new PageIterator(
            $this->mockGraphClient,
            $this->defaultCollectionResponse,
            $callback,
            User::class
        );

        $promise = $pageIterator->iterate();
        $promise->wait();
        // iterator pauses
        $this->assertEquals(2, $numProcessed);
        $promise = $pageIterator->resume();
        $expectedNumEntities = sizeof(SampleGraphResponsePayload::COLLECTION_PAYLOAD["value"]) + sizeof(SampleGraphResponsePayload::LAST_PAGE_COLLECTION_PAYLOAD["value"]);
        $this->assertEquals($expectedNumEntities, $numProcessed);
    }

    public function testGetNextLinkChangesAfterNextPageIsFetched() {
        $this->assertEquals($this->defaultCollectionResponse->getNextLink(), $this->defaultPageIterator->getNextLink());
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);
        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
        $this->assertNull($this->defaultPageIterator->getNextLink());
    }

    public function testGetDeltaLinkChangesAfterNextPageIsFetched() {
        $this->assertEquals($this->defaultCollectionResponse->getDeltaLink(), $this->defaultPageIterator->getDeltaLink());
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);
        $promise = $this->defaultPageIterator->iterate();
        $promise->wait();
        $this->assertNull($this->defaultPageIterator->getDeltaLink());
    }

    public function testSetAccessToken() {
        $token = "new token";
        $this->mockGraphClient->expects($this->once())
                                ->method("setAccessToken")
                                ->with($token);
        $instance = $this->defaultPageIterator->setAccessToken($token);
        $this->assertInstanceOf(PageIterator::class, $instance);
    }

    private function createCollectionResponse(array $payload): GraphResponse {
        return new GraphResponse(
            $this->createMock(GraphCollectionRequest::class),
            Utils::streamFor(json_encode($payload)),
            200
        );
    }
}
