<?php
declare(strict_types=1);
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Version number validation ported from abondoned package herrera-io/version,
 * Copyright (c) 2013 Kevin Herrera and licensed under MIT
 * https://github.com/kherge-abandoned/php-version/blob/master/LICENSE
 *
 * @package  bovigo\releaseit
 */
namespace bovigo\releaseit;
/**
 * Represents a version number.
 */
class Version
{
    /**
     * The regular expression for a valid semantic version number.
     */
    const VALIDATE = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';

    /**
     * Checks if the string representation of a version number is valid.
     *
     * @param   string  $version  The string representation.
     * @return  bool    TRUE if the string representation is valid, FALSE if not.
     */
    public static function isValid(string $version): bool
    {
        return (true == preg_match(self::VALIDATE, self::stripLeadingV($version)));
    }

    /**
     * actual version number
     *
     * @type  string
     */
    private $number;

    /**
     * constructor
     *
     * @param   string  $number
     * @throws  \InvalidArgumentException
     */
    public function __construct(string $number)
    {
        $this->number = self::stripLeadingV($number);
        if (!self::isValid($this->number)) {
            throw new \InvalidArgumentException(
                    'Given value ' . $number . ' is not a valid version number'
            );
        }
    }

    /**
     * strips leading v from version string if present
     *
     * @param   string  $value
     * @return  string
     */
    private static function stripLeadingV(string $value): string
    {
        if (substr($value, 0, 1) === 'v') {
            return substr($value, 1);
        }

        return $value;
    }

    /**
     * increases minor number
     *
     * Will return a new instance and leave current instance unchanged.
     *
     * @return  Version
     */
    public function increaseMinor(): self
    {
        list($major, $minor) = explode('.', $this->number);
        $minor++;
        return new Version($major . '.' . $minor . '.0');
    }

    /**
     * increases patch level
     *
     * Will return a new instance and leave current instance unchanged.
     *
     * @return  Version
     */
    public function increasePatchLevel(): self
    {
        list($major, $minor, $patchLevel) = explode('.', $this->number);
        $patchLevel++;
        return new Version($major . '.' . $minor . '.' . $patchLevel);
    }

    /**
     * returns string representation
     *
     * @return  string
     */
    public function __toString(): string
    {
        return 'v' . $this->number;
    }
}
