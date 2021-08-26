<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Exception;


use Microsoft\Graph\Exception\GraphServiceException;
use Microsoft\Graph\Http\GraphError;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Test\Http\SampleGraphResponsePayload;

class GraphServiceExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $mockGraphRequest;
    private $responseStatusCode;
    private $responseBody = SampleGraphResponsePayload::ERROR_PAYLOAD;
    private $responseHeaders;
    private $defaultException;

    protected function setUp(): void {
        $this->mockGraphRequest = $this->createMock(GraphRequest::class);
        $this->responseStatusCode = 404;
        $this->responseHeaders = [
            "Content-Type" => "application/json",
            "request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
            "client-request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
        ];
        $this->defaultException = new GraphServiceException(
            $this->mockGraphRequest,
            $this->responseStatusCode,
            $this->responseBody,
            $this->responseHeaders
        );
    }

    public function testGraphServiceExceptionIsThrowable(): void {
        $this->assertInstanceOf(\Throwable::class, $this->defaultException);
    }

    public function testGetters(): void {
        $this->assertEquals($this->mockGraphRequest, $this->defaultException->getRequest());
        $this->assertEquals($this->responseStatusCode, $this->defaultException->getResponseStatusCode());
        $this->assertEquals($this->responseBody, $this->defaultException->getRawResponseBody());
        $this->assertEquals($this->responseHeaders, $this->defaultException->getResponseHeaders());
        $this->assertInstanceOf(GraphError::class, $this->defaultException->getError());
    }
}
