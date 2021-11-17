<?php

namespace Microsoft\Graph\Core\Models;

class Date extends \DateTime implements \JsonSerializable {
    public function __toString() {
        return $this->format('Y-m-d');
    }

    public function jsonSerialize(): string {
        return $this->__toString();
    }
}
