<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http\Request;


use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\Http\AbstractGraphClient;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Http\HttpClientInterface;

class BaseGraphRequestTest extends \PHPUnit\Framework\TestCase
{
    protected $mockHttpClient;
    protected $mockGraphClient;
    protected $defaultGraphRequest;

    public function setUp(): void {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->setupMockGraphClient();
        $this->defaultGraphRequest = new GraphRequest("GET", "/me/users?\$top=10", $this->mockGraphClient);
    }

    private function setupMockGraphClient(): void {
        $this->mockGraphClient = $this->createStub(AbstractGraphClient::class);

        $this->mockGraphClient->method('getHttpClient')
            ->willReturn($this->mockHttpClient);

        $this->mockGraphClient->method('getSdkVersion')
            ->willReturn("2.0.0");

        $this->mockGraphClient->method('getApiVersion')
            ->willReturn("v1.0");

        $this->mockGraphClient->method('getAccessToken')
            ->willReturn("abc");

        $this->mockGraphClient->method('getNationalCloud')
            ->willReturn(NationalCloud::GLOBAL);

    }
}
