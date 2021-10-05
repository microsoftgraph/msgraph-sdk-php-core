<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Core;


use Microsoft\Graph\Core\Enum;

class SampleEnum extends Enum {
    const VALUE_1 = "value1";
    const VALUE_2 = "value2";
}

class EnumTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void {
        $sampleEnum = new SampleEnum("value1");
        $this->assertInstanceOf(SampleEnum::class, $sampleEnum);
    }

    public function testConstructorWithInvalidValueThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $sampleEnum = new SampleEnum("test");
    }

    public function testEnumHasValue(): void {
        $this->assertFalse(SampleEnum::has("test"));
        $this->assertTrue(SampleEnum::has("value1"));
    }

    public function testEnumIs(): void {
        $sampleEnum = new SampleEnum("value2");
        $this->assertTrue($sampleEnum->is("value2"));
    }

    public function testToArray(): void {
        $constants = SampleEnum::toArray();
        $this->assertEquals([
            'VALUE_1' => 'value1',
            'VALUE_2' => 'value2'
        ], $constants);
    }

    public function testGetEnumValue(): void {
        $sampleEnum = new SampleEnum("value1");
        $this->assertEquals("value1", $sampleEnum->value());
    }
}
