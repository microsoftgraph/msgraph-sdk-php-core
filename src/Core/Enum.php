<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*/
namespace Microsoft\Graph\Core;

/**
 * Class Enum
 *
 * @package Microsoft\Graph\Core
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
abstract class Enum
{
    /**
     * Constant variable names and their values per Enum class
     *
     * @var array<string, array<string, string>>
     */
    private static $constants = [];
    /**
    * The value of the enum
    *
    * @var string
    */
    private $value;

    /**
    * Create a new enum
    *
    * @param string $value The value of the enum
     *
     * @throws \InvalidArgumentException if enum value is invalid
    */
    public function __construct(string $value)
    {
        if (!self::has($value)) {
            throw new \InvalidArgumentException("Invalid enum value $value");
        }
        $this->value = $value;
    }

    /**
     * Check if the enum has the given value
     *
     * @param string $value
     * @return bool
     */
    public static function has(string $value): bool
    {
        return in_array($value, self::toArray(), true);
    }

    /**
    * Compare enum object's value with $value
    *
    * @param string $value the value of the enum
    *
    * @return bool True if the value is defined
    */
    public function is(string $value): bool
    {
        return $this->value === $value;
    }

	/**
	 * Returns the enum's constants and their values
	 *
	 * @return array<string, string> constant variable name, constant value
	 */
    public static function toArray(): array
    {
        $class = static::class;

        if (!(array_key_exists($class, self::$constants)))
        {
            $reflectionObj = new \ReflectionClass($class);
            self::$constants[$class] = $reflectionObj->getConstants();
        }
        return self::$constants[$class];
    }

    /**
    * Get the value of the enum
    *
    * @return string value of the enum
    */
    public function value(): string
    {
        return $this->value;
    }
}
