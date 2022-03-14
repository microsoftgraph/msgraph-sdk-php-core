<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Test\Http;


use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class SampleGraphResponsePayload
{
    const ENTITY_PAYLOAD = [
        "@odata.id" => "https://graph.microsoft.com/v2/dcd219dd-bc68-4b9b-bf0b-4a33a796be35",
        "jobTitle" => "developer",
        "givenName" => "user1"
    ];

    const COLLECTION_PAYLOAD = [
        "@odata.count" => 2,
        "@odata.nextLink" => 'https://graph.microsoft.com/me/users?$skip=2&$top=2',
        "value" => [
            [
                "id" => 1,
                "givenName" => "user1"
            ],
            [
                "id" => 2,
                "givenName" => "user2"
            ]
        ]
    ];

    const LAST_PAGE_COLLECTION_PAYLOAD = [
        "@odata.count" => 2,
        "value" => [
            [
                "id" => 3,
                "givenName" => "user3"
            ],
            [
                "id" => 4,
                "givenName" => "user4"
            ]
        ]
    ];

    const ERROR_PAYLOAD = [
        "error" => [
            "code" => "BadRequest",
            "message" => "Resource not found for the segment",
            "innerError" => [
                "date" => "2021-07-02T01:40:19",
                "request-id" => "1a0ffbc0-086f-4e8f-93f9-bf99881c65f6",
                "client-request-id" => "225aed2b-cf4a-d456-b313-16ab196c2364"
            ]
        ]
    ];

    public static function STREAM_PAYLOAD(): StreamInterface {
        return Utils::streamFor("content");
    }
}
