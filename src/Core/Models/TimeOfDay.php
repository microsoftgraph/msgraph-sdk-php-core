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

/**
 * This object represents time in hours minutes and seconds
 */
class TimeOfDay implements JsonSerializable
{

    /**
     * The final string representation of the TimeOfDay
     * @var string $value
     */
    private $value;

    /**
     * @param string $timeString The time value in string format HH:MM:SS
     * H - Hour
     * M - Minutes
     * S - Seconds
     * @throws Exception
     */
    public function __construct(string $timeString) {
        $this->value = (new DateTime($timeString))->format('H:i:s');
    }

    /**
     * Creates a TimeOfDay object from a DateTime object
     * @param DateTime $dateTime
     * @return TimeOfDay
     * @throws Exception
     */
    public static function createFromDateTime(DateTime $dateTime): TimeOfDay {
        return new self($dateTime->format('H:i:s'));
    }

    /**
     * Creates a new TimeOfDay object from $hour,$minute and $seconds
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


    /**
     * Serialize for json serialization
     * @return string
     */
    public function jsonSerialize(): string {
        return $this->__toString();
    }
}
