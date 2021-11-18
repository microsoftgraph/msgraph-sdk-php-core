<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Test\Core\Models;

use DateTime;
use Exception;
use JsonException;
use Microsoft\Graph\Core\Models\Date;
use Microsoft\Graph\Core\Models\TimeOfDay;
use Microsoft\Graph\Test\TestData\Model\Event;
use PHPUnit\Framework\TestCase;

class TimeOfDayTest extends TestCase {
    private $event;

    protected function setUp(): void {
        $this->event = new Event(['startTime' => new TimeOfDay('12:30:24'),
            'eventDate' => new Date('2021-11-17 12:30:24.000000'),
            'timestamp' => new DateTime('2021-11-17T12:29:24+00:00') ]);
    }

    /**
     * @throws JsonException
     */
    public function testCanSeeCorrectlyHandleDate(): void {
        $encoded = json_decode(json_encode($this->event, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('12:30:24', $encoded['startTime']);
    }

    /**
     * @throws Exception|JsonException
     */
    public function testCanCreateFromDateTimeObject(): void {
        $eventCopy = $this->event;
        $eventCopy->setStartTime(TimeOfDay::createFromDateTime(new DateTime('2021-11-18 12:30:24.000000')));
        $decoded = json_decode(json_encode($eventCopy, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('12:30:24', $decoded['startTime']);
    }

    /**
     * @throws Exception|JsonException
     */
    public function testCanCreateFromYearMonthDay(): void {
        $eventCopy = $this->event;
        $this->event->setStartTime(TimeOfDay::createFrom(12, 12, 31));
        $decoded = json_decode(json_encode($eventCopy, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('12:12:31', $decoded['startTime']);
    }
}
