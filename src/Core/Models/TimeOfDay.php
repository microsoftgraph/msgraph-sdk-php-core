<?php

namespace Microsoft\Graph\Core\Models;

use DateTime;
use Exception;
use JsonSerializable;

class TimeOfDay extends DateTime implements JsonSerializable
{
    /**
     * @throws Exception
     */
    public function jsonSerialize(): string {
        return $this->__toString();
    }

    public function __toString(): string {
        return $this->format('H:i:s');
    }
}
