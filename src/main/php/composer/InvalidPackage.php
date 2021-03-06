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
namespace bovigo\releaseit\composer;
/**
 * Denotes a package config error.
 */
class InvalidPackage extends \Exception
{
    /**
     * constructor
     *
     * @param  string      $message  failure message
     * @param  \Throwable  $cause    optional  actual failure cause
     */
    public function __construct(string $message, \Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}
