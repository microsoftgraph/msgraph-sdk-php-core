<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Test\Http\Request;


use Microsoft\Graph\Core\Core\NationalCloud;
use Microsoft\Graph\Core\Core\Http\AbstractGraphClient;
use Microsoft\Graph\Core\Core\Http\GraphRequest;
use Microsoft\Graph\Core\Core\Http\HttpClientInterface;

abstract class BaseGraphRequestTest extends \PHPUnit\Framework\TestCase
{
    const DEFAULT_NEXT_LINK = "https://graph.microsoft.com/me/users?\$skip=2&\$top=2";
    const DEFAULT_REQUEST_ENDPOINT = "/endpoint";

    protected $mockHttpClient;
    protected $mockGraphClient;
    protected $defaultGraphRequest;

    public function setUp(): void {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->setupMockGraphClient();
        $this->defaultGraphRequest = new GraphRequest("GET", self::DEFAULT_REQUEST_ENDPOINT, $this->mockGraphClient);
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
