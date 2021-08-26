<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http;


use Microsoft\Graph\Http\GraphError;

class GraphErrorTest extends \PHPUnit\Framework\TestCase
{
    private $defaultGraphError;

    protected function setUp(): void {
        parent::setUp();
        $this->defaultGraphError = new GraphError(SampleGraphResponsePayload::ERROR_PAYLOAD["error"]);
    }

    public function testGetCode(): void {
        $this->assertEquals(
            SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["code"],
            $this->defaultGraphError->getCode()
        );

        $graphError = $this->unsetProperty("code");
        $this->assertNull($graphError->getCode());
    }

    public function testGetMessage(): void {
        $this->assertEquals(
            SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["message"],
            $this->defaultGraphError->getMessage()
        );
        $graphError = $this->unsetProperty("message");
        $this->assertNull($graphError->getMessage());
    }

    public function testGetInnerError(): void {
        $this->assertInstanceOf(GraphError::class, $this->defaultGraphError->getInnerError());
        $graphError = $this->unsetProperty("innerError");
        $this->assertNull($graphError->getInnerError());
    }

    public function testGetClientRequestId(): void {
        $this->assertNull($this->defaultGraphError->getClientRequestId());
        $this->assertEquals(
            SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]["client-request-id"],
            $this->defaultGraphError->getInnerError()->getClientRequestId()
        );
        $graphError = $this->unsetProperty("client-request-id", SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]);
        $this->assertNull($graphError->getClientRequestId());
    }

    public function testGetRequestId(): void {
        $this->assertNull($this->defaultGraphError->getRequestId());
        $this->assertEquals(
            SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]["request-id"],
            $this->defaultGraphError->getInnerError()->getRequestId()
        );
        $graphError = $this->unsetProperty("request-id", SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]);
        $this->assertNull($graphError->getRequestId());
    }

    public function testGetDate(): void {
        $this->assertNull($this->defaultGraphError->getDate());
        $this->assertEquals(
            SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]["date"],
            $this->defaultGraphError->getInnerError()->getDate()
        );
        $graphError = $this->unsetProperty("date", SampleGraphResponsePayload::ERROR_PAYLOAD["error"]["innerError"]);
        $this->assertNull($graphError->getDate());
    }

    public function testGetProperties(): void {
        $this->assertEquals(SampleGraphResponsePayload::ERROR_PAYLOAD["error"], $this->defaultGraphError->getProperties());
    }

    private function unsetProperty(string $property,
                                   array $payload = SampleGraphResponsePayload::ERROR_PAYLOAD["error"]): GraphError {
        unset($payload[$property]);
        return new GraphError($payload);
    }
}
