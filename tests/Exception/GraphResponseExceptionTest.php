<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Test\Exception;


use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Core\Exception\GraphResponseException;
use Microsoft\Graph\Core\Exception\ODataErrorContent;
use Microsoft\Graph\Core\Http\GraphRequest;
use Microsoft\Graph\Core\Test\Http\SampleGraphResponsePayload;
use Psr\Http\Message\StreamInterface;

class GraphResponseExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $mockGraphRequest;
    private $responseStatusCode = 404;
    private $responseBody;
    private $responseHeaders = [
        "Content-Type" => "application/json",
        "request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
        "client-request-id" => "2f91ee0a-e013-425b-a5bf-b1f251163969",
    ];
    private $defaultException;
    private $psr7Response;

    protected function setUp(): void {
        $this->responseBody = Utils::streamFor(json_encode(SampleGraphResponsePayload::ERROR_PAYLOAD));
        $this->mockGraphRequest = $this->createMock(GraphRequest::class);
        $this->psr7Response = new Response($this->responseStatusCode, $this->responseHeaders, $this->responseBody);
        $this->defaultException = new GraphResponseException(
            $this->mockGraphRequest,
            $this->psr7Response->getStatusCode(),
            $this->psr7Response->getBody(),
            $this->psr7Response->getHeaders()
        );
    }

    public function testGraphResponseExceptionIsThrowable(): void {
        $this->assertInstanceOf(\Throwable::class, $this->defaultException);
    }

    public function testGetters(): void {
        $this->assertEquals($this->mockGraphRequest, $this->defaultException->getRequest());
        $this->assertEquals($this->responseStatusCode, $this->defaultException->getResponseStatusCode());
        $this->assertInstanceOf(StreamInterface::class, $this->defaultException->getRawResponseBody());
        $this->assertEquals(SampleGraphResponsePayload::ERROR_PAYLOAD, $this->defaultException->getResponseBodyJson());
        $this->assertEquals(json_encode(SampleGraphResponsePayload::ERROR_PAYLOAD), $this->defaultException->getResponseBodyAsString());
        $this->assertEquals($this->psr7Response->getHeaders(), $this->defaultException->getResponseHeaders());
        $this->assertInstanceOf(ODataErrorContent::class, $this->defaultException->getError());
    }

    public function testGettersWithStringResponsePayload(): void {
        $responseBody = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN""http://www.w3.org/TR/html4/strict.dtd"> <HTML><HEAD><TITLE>Bad Request</TITLE> <META HTTP-EQUIV="Content-Type" Content="text/html; charset=us-ascii"></HEAD> <BODY><h2>Bad Request - Invalid Header</h2> <hr><p>HTTP Error 400. The request has an invalid header name.</p> </BODY></HTML>';
        $responseException = new GraphResponseException(
            $this->mockGraphRequest,
            $this->psr7Response->getStatusCode(),
            Utils::streamFor($responseBody),
            $this->psr7Response->getHeaders()
        );

        $this->assertInstanceOf(StreamInterface::class, $responseException->getRawResponseBody());
        $this->assertNull($responseException->getResponseBodyJson());
        $this->assertEquals($responseBody, $responseException->getResponseBodyAsString());

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
