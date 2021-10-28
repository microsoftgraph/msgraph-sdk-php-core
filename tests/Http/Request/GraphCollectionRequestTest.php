<?php
namespace Microsoft\Graph\Test\Http\Request;

use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphServiceException;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Microsoft\Graph\Http\GraphRequestUtil;
use Microsoft\Graph\Task\PageIterator;
use Microsoft\Graph\Test\Http\SampleGraphResponsePayload;
use Microsoft\Graph\Test\TestData\Model\User;

class GraphCollectionRequestTest extends BaseGraphRequestTest
{
    private $defaultCollectionRequest;
    private $defaultEndpoint = "/endpoint";
    private $defaultPageSize = 2;

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultCollectionRequest = new GraphCollectionRequest("GET", $this->defaultEndpoint, $this->mockGraphClient);
        $this->defaultCollectionRequest->setPageSize($this->defaultPageSize);
        $this->defaultCollectionRequest->setReturnType(User::class);
    }

    public function testSetPageSizeReturnsInstance(): void {
        $this->assertInstanceOf(GraphCollectionRequest::class, $this->defaultCollectionRequest->setPageSize(1));
    }

    public function testSetPageSizeExceedingMaxSizeThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->defaultCollectionRequest->setPageSize(GraphConstants::MAX_PAGE_SIZE + 1);
    }

    public function testGetPageAppendsPageSizeToInitialCollectionRequestUrl(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $this->defaultCollectionRequest->getPage();
        $expectedRequestUrl = GraphRequestUtil::getRequestUri($this->mockGraphClient->getNationalCloud(), $this->defaultEndpoint)."?\$top=".$this->defaultPageSize;
        $this->assertEquals($expectedRequestUrl, strval($this->defaultCollectionRequest->getRequestUri()));
    }

    public function testGetPageUsesNextLinkForSubsequentRequests(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        // First page
        $this->defaultCollectionRequest->getPage();
        // Next page
        $this->defaultCollectionRequest->getPage();
        $expectedRequestUrl = SampleGraphResponsePayload::COLLECTION_PAYLOAD['@odata.nextLink'];
        $this->assertEquals($expectedRequestUrl, strval($this->defaultCollectionRequest->getRequestUri()));
    }

    public function testHitEndOfCollection()
    {
        $this->expectError();
        MockHttpClientResponseConfig::configureWithLastPageCollectionPayload($this->mockHttpClient);
        //Last page
        $this->defaultCollectionRequest->getPage();
        $this->assertTrue($this->defaultCollectionRequest->isEnd());
        //Expect error
        $this->defaultCollectionRequest->getPage();
    }

    public function testGetPageThrowsExceptionOn5xxResponse(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $this->defaultCollectionRequest->getPage();
    }

    public function testGetPageThrowsExceptionOn4xxResponse(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $this->defaultCollectionRequest->getPage();
    }

    public function testCount(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $count = $this->defaultCollectionRequest->count();
        $this->assertEquals(SampleGraphResponsePayload::COLLECTION_PAYLOAD["@odata.count"], $count);
    }

    public function testCountReturnsNullIfNoOdataCountFound(): void {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $this->assertNull($this->defaultCollectionRequest->count());
    }

    public function testCountThrowsExceptionOn5xxResponse(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 503);
        $this->defaultCollectionRequest->count();
    }

    public function testCountThrowsExceptionOn4xxResponse(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 404);
        $this->defaultCollectionRequest->count();
    }

    public function testPageIteratorReturnsValidPageIterator() {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $numEntitiesProcessed = 0;
        $callback = function ($entity) use (&$numEntitiesProcessed) {
            $numEntitiesProcessed ++;
            return true;
        };
        $pageIterator = $this->defaultCollectionRequest->pageIterator($callback);
        $this->assertInstanceOf(PageIterator::class, $pageIterator);
    }

    public function testPageIteratorReturnsValidPageIteratorWithNoReturnType() {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $callback = function (){};
        $pageIterator = (new GraphCollectionRequest("GET", "/users", $this->mockGraphClient))->pageIterator($callback);
        $this->assertInstanceOf(PageIterator::class, $pageIterator);
    }

    public function testPageIteratorInitialisesUsingFirstPageOfResults() {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $numEntitiesProcessed = 0;
        $callback = function ($entity) use (&$numEntitiesProcessed) {
            $numEntitiesProcessed ++;
            return true;
        };
        $pageIterator = $this->defaultCollectionRequest->pageIterator($callback);
        $promise = $pageIterator->iterate();
        $promise->wait();
        $this->assertTrue($numEntitiesProcessed >= sizeof(SampleGraphResponsePayload::COLLECTION_PAYLOAD["value"]));
    }

    public function testPageIteratorThrowsExceptionIfFirstPageRequestGets5xxResponse(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $this->defaultCollectionRequest->pageIterator(function () {});
    }

    public function testPageIteratorThrowsExceptionIfFirstPageRequestGets4xxResponse(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $this->defaultCollectionRequest->pageIterator(function () {});
    }
}
