<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Exception;


use GuzzleHttp\Psr7\Response;
use Microsoft\Graph\Exception\GraphResponseException;
use Microsoft\Graph\Exception\ODataErrorContent;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Test\Http\SampleGraphResponsePayload;

class GraphResponseExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $mockGraphRequest;
    private $responseStatusCode = 404;
    private $responseBody = SampleGraphResponsePayload::ERROR_PAYLOAD;
    private $responseHeaders = [
        "Content-Type" => "application/json",
        "request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
        "client-request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
    ];
    private $defaultException;
    private $psr7Response;

    protected function setUp(): void {
        $this->mockGraphRequest = $this->createMock(GraphRequest::class);
        $this->psr7Response = new Response($this->responseStatusCode, $this->responseHeaders, json_encode($this->responseBody));
        $this->defaultException = new GraphResponseException(
            $this->mockGraphRequest,
            $this->psr7Response->getStatusCode(),
            json_decode($this->psr7Response->getBody(), true),
            $this->psr7Response->getHeaders()
        );
    }

    public function testGraphServiceExceptionIsThrowable(): void {
        $this->assertInstanceOf(\Throwable::class, $this->defaultException);
    }

    public function testGetters(): void {
        $this->assertEquals($this->mockGraphRequest, $this->defaultException->getRequest());
        $this->assertEquals($this->responseStatusCode, $this->defaultException->getResponseStatusCode());
        $this->assertEquals($this->responseBody, $this->defaultException->getRawResponseBody());
        $this->assertEquals($this->psr7Response->getHeaders(), $this->defaultException->getResponseHeaders());
        $this->assertInstanceOf(ODataErrorContent::class, $this->defaultException->getError());
    }

    public function testGetClientRequestId(): void {
        $headerName = "client-request-id";
        $this->assertEquals(
            $this->responseHeaders[$headerName],
            $this->defaultException->getClientRequestId()
        );
        unset($this->responseHeaders[$headerName]);
        $this->setUp();
        $this->assertNull($this->defaultException->getClientRequestId());
    }

    public function testGetRequestId(): void {
        $headerName = "request-id";
        $this->assertEquals(
            $this->responseHeaders[$headerName],
            $this->defaultException->getRequestId()
        );
        unset($this->responseHeaders[$headerName]);
        $this->setUp();
        $this->assertNull($this->defaultException->getRequestId());
    }
}
