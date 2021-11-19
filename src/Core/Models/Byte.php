<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Models;

use InvalidArgumentException;
use JsonSerializable;

/**
 * This class is a wrapper around unsigned int values upto 255.
 */
class Byte implements JsonSerializable
{
    /**
     * The byte value
     * @var int $value
     */
    private $value = 0;

    /**
     * @param int $value The byte value
     */
    public function __construct(int $value) {
        if($value < 0 || $value > 255) {
            throw new InvalidArgumentException("Byte should be a value between 0-255 inclusive {$value} given");
        }
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function jsonSerialize(): int {
        return $this->value;
    }

    public function __toString(): string
    {
       return (string)$this->value;
    }
}
