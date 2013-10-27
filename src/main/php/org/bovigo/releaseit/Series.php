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
 * Represents a series of versions.
 */
class Series
{
    /**
     * series number, i.e. 1 or 1.1
     *
     * @type  string
     */
    private $number;
    /**
     * series type: major or minor
     * @type  int
     */
    private $type;
    /**
     * series type major: 1
     */
    const TYPE_MAJOR = 1;
    /**
     * series type minor: 1.1
     */
    const TYPE_MINOR = 3;

    /**
     * constructor
     *
     * @param   string  $number
     * @throws  IllegalArgumentException
     */
    public function __construct($number)
    {
        $this->number = $this->stripLeadingV($number);
        $this->type   = $this->calculateSeriesType($number);
        if (null === $this->type || !Validator::isVersion($this->getAppendedNumber())) {
            throw new IllegalArgumentException('Given value ' . $number . ' is not a valid series number');
        }
    }

    /**
     * strips leading v from version string if present
     *
     * @param   string  $value
     * @return  string
     */
    private function stripLeadingV($number)
    {
        if (substr($number, 0, 1) === 'v') {
            return substr($number, 1);
        }

        return $number;
    }

    /**
     * calculates type of series
     *
     * @return  int
     */
    private function calculateSeriesType()
    {
        if (strlen($this->number) === self::TYPE_MAJOR) {
            return self::TYPE_MAJOR;
        } elseif (strlen($this->number) === self::TYPE_MINOR) {
            return self::TYPE_MINOR;
        }
    }

    /**
     * creates a number that will satisfy the validator which expects a complete version number
     *
     * @param   string  $number
     * @return  string
     */
    private function getAppendedNumber()
    {
        if (self::TYPE_MAJOR === $this->type) {
            return $this->number . '.0.0';
        }

        return $this->number . '.0';
    }

    /**
     * returns first version in this series
     *
     * For 1 this would be 1.0.0, for 1.1 it is 1.1.0.
     *
     * @return  Version
     */
    public function getFirstVersion()
    {
        return new Version($this->getAppendedNumber());
    }

    /**
     * calculates next version based on series and current version
     *
     * @param   Version  $current
     * @return  Version
     */
    public function getNextVersion(Version $current)
    {
        if (self::TYPE_MAJOR === $this->type) {
            return $current->increaseMinor();
        }

        return $current->increasePatchLevel();
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

