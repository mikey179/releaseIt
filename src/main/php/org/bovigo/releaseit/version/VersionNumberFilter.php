<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit\version;
use net\stubbles\input\Param;
use net\stubbles\input\Filter;
use net\stubbles\lang\exception\IllegalArgumentException;
/**
 * Filters user input to be a real version number.
 */
class VersionNumberFilter implements Filter
{
    /**
     * apply filter on given param
     *
     * @param   Param  $param
     * @return  Version
     */
    public function apply(Param $param)
    {
        if ($param->isEmpty()) {
            return null;
        }

        try {
            return new Version($param->getValue());
        } catch (IllegalArgumentException $ex) {
            return null;
        }
    }
}
