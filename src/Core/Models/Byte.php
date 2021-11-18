<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Models;

use InvalidArgumentException;
use JsonSerializable;

class Byte implements JsonSerializable
{
    /**
     * @var int|null $value
     */
    private $value;

    /**
     * @param int|null $value
     */
    public function __construct(?int $value) {
        if (is_null($value)) {
            return;
        }
        $this->value = $value;
        if($this->value < 0 || $this->value > 255) {
            throw new InvalidArgumentException("Byte should be a value between 0-255 inclusive {$value} given");
        }
    }

    /**
     * @return int
     */
    public function jsonSerialize(): int {
        return $this->value;
    }

    public function __toString(): string
    {
       return (string)$this->jsonSerialize();
    }

    /**
     * @return int|null
     */
    public function getValue(): ?int{
        return $this->value;
    }
}
