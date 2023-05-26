<?php

namespace Microsoft\Graph\Core\Test\Models;

use Microsoft\Graph\Core\Models\PageResult;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriter;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriterFactory;
use PHPUnit\Framework\TestCase;

class PageResultTest extends TestCase
{
    public function testCanSetAndGetValues(): void {
        $pageResult = new PageResult();
        $writer = (new JsonSerializationWriterFactory())->getSerializationWriter('application/json');
        $pageResult->setValue([['name' => 'Kenny'], ['name' => 'James']]);
        $this->assertCount(2, $pageResult->getValue());
        $pageResult->setOdataNextLink('nextPage');
        $this->assertEquals('nextPage', $pageResult->getOdataNextLink());
        $pageResult->serialize($writer);

        $this->assertEquals('"@odata.nextLink":"nextPage","value":[{"name":"Kenny"},{"name":"James"}]', $writer->getSerializedContent()->getContents());
    }
}
