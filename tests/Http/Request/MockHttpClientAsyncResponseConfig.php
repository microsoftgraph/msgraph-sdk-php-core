<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Test\Http\Request;


use GuzzleHttp\Psr7\Response;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Microsoft\Graph\Core\Test\Http\SampleGraphResponsePayload;

class MockHttpClientAsyncResponseConfig
{
    use MockHttpClientResponseConfigTrait;

    private const METHOD_NAME = "sendAsyncRequest";

    public static function configureWithFulfilledPromise($mockHttpClient, $payload = SampleGraphResponsePayload::ENTITY_PAYLOAD) {
        $promise = new FulfilledPromise(new Response(self::$statusCode, self::$headers, json_encode($payload)));
        $mockHttpClient->method(self::METHOD_NAME)->willReturn($promise);
    }

    public static function configureWithRejectedPromise($mockHttpClient, $exception) {
        $promise = new RejectedPromise($exception);
        $mockHttpClient->method(self::METHOD_NAME)->willReturn($promise);
    }
}
