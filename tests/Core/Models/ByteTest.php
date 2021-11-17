<?php

namespace Microsoft\Graph\Test\Core\Models;

use Microsoft\Graph\Core\Models\Byte;
use PHPUnit\Framework\TestCase;
use ValueError;

class ByteTest extends TestCase {

    /**
     * @var Byte|null
     */
    private $byteObject;

    protected function setUp(): void {
        $this->byteObject = new Byte(12);
    }

    public function testCanCreateCorrectObject(): void{
        self::assertInstanceOf(Byte::class, $this->byteObject);
    }

    public function testWillThrowExceptionOnInvalidValue(): void {
        $this->expectException(ValueError::class);
        $invalid = new Byte(-12929);
        $this->assertNull($invalid->getValue());
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->byteObject = null;
    }
}
