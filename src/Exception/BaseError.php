<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Exception;

/**
 * Class Error
 *
 * Defines common Error class logic
 *
 * @package Microsoft\Graph\Exception
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BaseError
{
    /**
     * Contains all properties of the error response
     *
     * @var array
     */
    private $propDict;

    public function __construct(array $propDict) {
        $this->propDict = $propDict;
    }

    /**
     * Returns all properties passed to the constructor
     *
     * @return array
     */
    public function getProperties(): array {
        return $this->propDict;
    }

    /**
     * Returns value of $property in $propDict
     *
     * @param $property
     * @return mixed|null
     */
    protected function getProperty($property) {
        if (array_key_exists($property, $this->propDict)) {
            return $this->propDict[$property];
        }
        return null;
    }
}
