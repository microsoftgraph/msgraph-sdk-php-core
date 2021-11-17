<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Models;

class Date extends \DateTime implements \JsonSerializable {
    public function __toString() {
        return $this->format('Y-m-d');
    }

    public function jsonSerialize(): string {
        return $this->__toString();
    }
}
