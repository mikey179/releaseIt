<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit;
use Herrera\Version\Validator;
use net\stubbles\lang\exception\IllegalArgumentException;
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
     * @throws  IllegalArgumentException
     */
    public function __construct($number)
    {
        $this->number = $this->stripLeadingV($number);
        if (!Validator::isVersion($this->number)) {
            throw new IllegalArgumentException('Given value ' . $number . ' is not a valid version number');
        }
    }

    /**
     * strips leading v from version string if present
     *
     * @param   string  $value
     * @return  string
     */
    private function stripLeadingV($value)
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
    public function increaseMinor()
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
    public function increasePatchLevel()
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
    public function __toString()
    {
        return 'v' . $this->number;
    }
}
