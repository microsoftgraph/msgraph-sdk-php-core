<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Test\Core\Models;

use InvalidArgumentException;
use Microsoft\Graph\Core\Models\Byte;
use Microsoft\Graph\Test\TestData\Model\Entity;
use PHPUnit\Framework\TestCase;

class ByteTest extends TestCase {

    /**
     * @var Entity|null
     */
    private $byteObject;

    protected function setUp(): void {
        $this->byteObject = new Entity(['size' => new Byte(200)]);
    }

    public function testCanCreateCorrectObject(): void{
        $this->assertInstanceOf(Byte::class, $this->byteObject->getProperties()['size']);
    }

    public function testWillThrowExceptionOnInvalidValue(): void {
        $this->expectException(InvalidArgumentException::class);
        new Byte(-12929);
    }

    /**
     * @throws \JsonException
     */
    public function testSerialization(): void {
        $serialized = json_decode(json_encode($this->byteObject, JSON_THROW_ON_ERROR), true,512, JSON_THROW_ON_ERROR);
        $this->assertEquals(200, $serialized['size']);
    }
    protected function tearDown(): void {
        parent::tearDown();
        $this->byteObject = null;
    }
}
