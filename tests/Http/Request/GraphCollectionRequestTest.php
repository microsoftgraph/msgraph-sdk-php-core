<?php
namespace Microsoft\Graph\Test\Http\Request;

use Microsoft\Graph\Core\GraphConstants;
use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Http\GraphCollectionRequest;
use Microsoft\Graph\Http\GraphRequestUtil;

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
        $this->defaultCollectionRequest->setReturnType(TestModel::class);
    }

    public function testSetPageSizeReturnsInstance(): void {
        $this->assertInstanceOf(GraphCollectionRequest::class, $this->defaultCollectionRequest->setPageSize(1));
    }

    public function testSetPageSizeExceedingMaxSizeThrowsException(): void {
        $this->expectException(GraphClientException::class);
        $this->defaultCollectionRequest->setPageSize(GraphConstants::MAX_PAGE_SIZE + 1);
    }

    public function testGetPageAppendsPageSizeToInitialCollectionRequestUrl(): void {
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

    public function testProcessPageCallReturn()
    {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $this->defaultCollectionRequest->setPageCallInfo();
        $response = $this->defaultCollectionRequest->execute();
        $result = $this->defaultCollectionRequest->processPageCallReturn($response);
        $this->assertIsArray($result);
        array_filter($result, function ($item) { $this->assertInstanceOf(TestModel::class, $item); });
    }
}
