<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  bovigo\releaseit
 */
namespace bovigo\releaseit\repository;
/**
 * Denotes a repository error.
 */
class RepositoryError extends \Exception
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
