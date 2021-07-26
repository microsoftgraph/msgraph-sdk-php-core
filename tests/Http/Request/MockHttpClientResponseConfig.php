<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http\Request;


use GuzzleHttp\Psr7\Response;

class MockHttpClientResponseConfig
{
    use MockHttpClientResponseConfigTrait;

    private const METHOD_NAME = "sendRequest";

    public static function configureWithEntityPayload($mockHttpClient) {
        $mockHttpClient->method(self::METHOD_NAME)
                        ->willReturn(
                            new Response(
                                self::$statusCode,
                                self::$headers,
                                json_encode(SampleGraphResponsePayload::ENTITY_PAYLOAD)
                            )
                        );
        return $mockHttpClient;
    }

    public static function configureWithEmptyPayload($mockHttpClient) {
        $mockHttpClient->method(self::METHOD_NAME)
                        ->willReturn(new Response(self::$statusCode, self::$headers));
        return $mockHttpClient;
    }

    public static function configureWithCollectionPayload($mockHttpClient) {
        $mockHttpClient->method(self::METHOD_NAME)
                        ->willReturn(
                            new Response(
                                self::$statusCode,
                                self::$headers,
                                json_encode(SampleGraphResponsePayload::COLLECTION_PAYLOAD)
                            )
                        );
        return $mockHttpClient;
    }

    public static function configureWithErrorPayload($mockHttpClient, $statusCode = 400) {
        $mockHttpClient->method(self::METHOD_NAME)
                        ->willReturn(
                            new Response(
                                $statusCode,
                                self::$headers,
                                json_encode(SampleGraphResponsePayload::ERROR_PAYLOAD)
                            )
                        );
        return $mockHttpClient;
    }

    public static function configureWithStreamPayload($mockHttpClient) {
        $mockHttpClient->method(self::METHOD_NAME)
                        ->willReturn(
                            new Response(
                                self::$statusCode,
                                self::$headers,
                                SampleGraphResponsePayload::STREAM_PAYLOAD()
                            )
                        );
        return $mockHttpClient;
    }
}
