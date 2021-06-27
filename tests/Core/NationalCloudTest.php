<?php


namespace Core;


use Microsoft\Graph\Core\NationalCloud;

class NationalCloudTest extends \PHPUnit\Framework\TestCase
{
    function testGetValues() {
        $this->assertContains("https://graph.microsoft.com", NationalCloud::getValues());
    }
}
