<?php

namespace Microsoft\Graph\Core\Models;

use Exception;

class TimeOfDay extends \DateTime
{
    /**
     * @throws Exception
     */
    public function jsonSerialize(): string {
        return $this->__toString();
    }

    public function __toString(): string {
        return $this->format('H-i-s');
    }
}
