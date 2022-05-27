<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Test\Exception;


use Microsoft\Graph\Core\Core\Exception\GraphErrorContent;
use Microsoft\Graph\Core\Core\Exception\ODataErrorContent;
use Microsoft\Graph\Core\Core\Test\Http\SampleGraphResponsePayload;

class ErrorContentTest extends \PHPUnit\Framework\TestCase
{
    private $rawErrorContent;
    private $odataErrorContent;

    protected function setUp(): void
    {
        $this->rawErrorContent = SampleGraphResponsePayload::ERROR_PAYLOAD["error"];
        $this->odataErrorContent = new ODataErrorContent($this->rawErrorContent);
        parent::setUp();
    }

    public function testGetters(): void {
        $this->assertEquals($this->rawErrorContent["code"], $this->odataErrorContent->getCode());
        $this->assertEquals($this->rawErrorContent["message"], $this->odataErrorContent->getMessage());
        $this->assertNull($this->odataErrorContent->getTarget());
        $this->assertNull($this->odataErrorContent->getDetails());
        $this->assertInstanceOf(GraphErrorContent::class, $this->odataErrorContent->getInnerError());
        $this->assertNotEmpty($this->odataErrorContent->getProperties());
        $this->assertNotEmpty(strval($this->odataErrorContent));
    }
}
