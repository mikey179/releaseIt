<?php
declare(strict_types=1);
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  bovigo\releaseit
 */
namespace bovigo\releaseit;
use Herrera\Version\Validator;
/**
 * Represents a version number.
 */
class Version
{
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
        $this->number = $this->stripLeadingV($number);
        if (!Validator::isVersion($this->number)) {
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
    private function stripLeadingV(string $value): string
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
