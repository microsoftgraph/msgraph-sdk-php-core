<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Test\Http\Request;


class TestModel implements \JsonSerializable
{
    private $propDict;

    public function __construct($propDict = array())
    {
        $this->propDict = $propDict;
    }

    public function jsonSerialize()
    {
        return $this->propDict;
    }

}
