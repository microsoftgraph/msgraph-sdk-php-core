<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Test\Http\Request;


use Microsoft\Graph\Core\Core\Exception\GraphClientException;
use Microsoft\Graph\Core\Core\Exception\GraphServiceException;
use Microsoft\Graph\Core\Core\Http\GraphResponse;
use Microsoft\Graph\Core\Core\Test\TestData\Model\User;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\StreamInterface;

class GraphRequestSyncTest extends BaseGraphRequestTest
{
    public function testExecuteWithNullClientUsesGraphClientHttpClient(): void {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest');
        $this->defaultGraphRequest->execute(null);
    }

    public function testExecuteWithCustomClientUsesCustomClient(): void {
        $customClient = $this->createMock(ClientInterface::class);
        MockHttpClientResponseConfig::configureWithEmptyPayload($customClient);
        $customClient->expects($this->once())->method('sendRequest');
        $this->mockHttpClient->expects($this->never())->method('sendRequest');
        $this->defaultGraphRequest->execute($customClient);
    }

    public function testExecuteThrowsPsr18Exceptions(): void {
        $this->expectException(ClientExceptionInterface::class);
        $this->mockHttpClient->method('sendRequest')
            ->will($this->throwException($this->createMock(NetworkExceptionInterface::class)));
        $this->defaultGraphRequest->execute();
    }

    public function testExecuteWithoutReturnTypeReturnsGraphResponseForSuccessPayload(): void {
        MockHttpClientResponseConfig::configureWithEntityPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->execute();
        $this->assertInstanceOf(GraphResponse::class, $response);
    }

    public function testExecuteWithoutReturnTypeReturnsGraphResponseForEmptyPayload(): void {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->execute();
        $this->assertInstanceOf(GraphResponse::class, $response);
    }

    public function testExecuteWithoutReturnTypeThrowsExceptionFor4xxPayload(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $response = $this->defaultGraphRequest->execute();
    }

    public function testExecuteWithoutReturnTypeThrowsExceptionFor5xxPayload(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $response = $this->defaultGraphRequest->execute();
    }

    public function testExecuteWithoutReturnTypeReturnsGraphResponseForStreamPayload(): void {
        MockHttpClientResponseConfig::configureWithStreamPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->execute();
        $this->assertInstanceOf(GraphResponse::class, $response);
    }

    public function testExecuteWithoutReturnTypeReturnsGraphResponseForCollectionPayload(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->execute();
        $this->assertInstanceOf(GraphResponse::class, $response);
    }

    public function testExecuteWithModelReturnTypeReturnsModelForSuccessPayload(): void {
        MockHttpClientResponseConfig::configureWithEntityPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
        $this->assertInstanceOf(User::class, $response);
    }

    public function testExecuteWithModelReturnTypeReturnsModelForEmptyPayload(): void {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
        $this->assertInstanceOf(User::class, $response);
    }

    public function testExecuteWithModelReturnTypeThrowsExceptionFor4xxPayload(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
    }

    public function testExecuteWithModelReturnTypeThrowsExceptionFor5xxPayload(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
    }

    public function testExecuteWithModelReturnTypeReturnsModelForStreamPayload(): void {
        MockHttpClientResponseConfig::configureWithStreamPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
        $this->assertInstanceOf(User::class, $response);
    }

    public function testExecuteWithModelReturnTypeReturnsArrayOfModelsForCollectionPayload(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(User::class)->execute();
        $this->assertIsArray($response);
        $this->assertEquals(2, sizeof($response));
        $this->assertContainsOnlyInstancesOf(User::class, $response);
    }

    public function testExecuteWithStreamReturnTypeReturnsStreamForSuccessPayload(): void {
        MockHttpClientResponseConfig::configureWithEntityPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
        $this->assertInstanceOf(StreamInterface::class, $response);
    }

    public function testExecuteWithStreamReturnTypeReturnsStreamForEmptyPayload(): void {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
        $this->assertInstanceOf(StreamInterface::class, $response);
    }

    public function testExecuteWithStreamReturnTypeThrowsExceptionFor4xxPayload(): void {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
    }

    public function testExecuteWithStreamReturnTypeThrowsExceptionFor5xxPayload(): void {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
    }

    public function testExecuteWithStreamReturnTypeReturnsStreamForStreamPayload(): void {
        MockHttpClientResponseConfig::configureWithStreamPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
        $this->assertInstanceOf(StreamInterface::class, $response);
    }

    public function testExecuteWithStreamReturnTypeReturnsStreamForCollectionPayload(): void {
        MockHttpClientResponseConfig::configureWithCollectionPayload($this->mockHttpClient);
        $response = $this->defaultGraphRequest->setReturnType(StreamInterface::class)->execute();
        $this->assertInstanceOf(StreamInterface::class, $response);
    }
}
