<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Test\Http\Request;


use Microsoft\Graph\Core\Core\Http\RequestOptions;

class RequestOptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateRequestOptions() {
        $headers = ["header" => "value"];
        $requestOptions = new RequestOptions($headers);
        $this->assertInstanceOf(RequestOptions::class, $requestOptions);
        $this->assertEquals($headers, $requestOptions->getHeaders());
    }
}
