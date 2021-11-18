<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Models;

use DateTime;
use Exception;
use JsonSerializable;

class TimeOfDay implements JsonSerializable
{

    /**
     * The final string representation of the TimeOfDay
     * @var string $value
     */
    private $value;

    /**
     * @throws Exception
     */
    public function __construct(string $dateString) {
        $this->value = (new DateTime($dateString))->format('H:i:s');
    }

    /**
     * Creates a date object from a DateTime object
     * @param DateTime $dateTime
     * @return TimeOfDay
     * @throws Exception
     */
    public static function createFromDateTime(DateTime $dateTime): TimeOfDay {
        return new self($dateTime->format('H:i:s'));
    }

    /**
     * Creates a new Date object from $year,$month and $day
     * @param int $hour
     * @param int $minutes
     * @param int $seconds
     * @return TimeOfDay
     * @throws Exception
     */
    public static function createFrom(int $hour, int $minutes, int $seconds = 0): TimeOfDay {
        $date = new DateTime('1970-12-12T00:00:00Z');
        $date->setTime($hour, $minutes, $seconds);
        return self::createFromDateTime($date);
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->value;
    }

    public function jsonSerialize(): string {
        return $this->__toString();
    }
}
