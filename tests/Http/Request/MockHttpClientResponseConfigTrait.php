<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Test\Http\Request;


trait MockHttpClientResponseConfigTrait
{
    private static $statusCode = 200;
    private static $headers = [];

    public static function statusCode(int $code = 200): self {
        self::$statusCode = $code;
        return new self();
    }

    public static function headers(array $headers = []): self {
        self::$headers = $headers;
        return new self();
    }
}
