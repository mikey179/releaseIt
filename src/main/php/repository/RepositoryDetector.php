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
namespace bovigo\releaseit\repository;
use stubbles\console\Executor;
/**
 * The repository detector checks the current path and tries to find out what type
 * of repository it is.
 */
class RepositoryDetector
{
    /**
     * executor for command line commands
     *
     * @type  Executor
     */
    private $executor;

    /**
     * constructor
     *
     * @param  Executor  $executor
     */
    public function __construct(Executor $executor)
    {
        $this->executor = $executor;
    }

    /**
     * detects repository at given path
     *
     * @param   string  $path
     * @return  Repository
     */
    public function detect(string $path): Repository
    {
        if (file_exists($path . DIRECTORY_SEPARATOR . '.git')) {
            return new GitRepository($path, $this->executor);
        }

        return new NoRepository($path);
    }
}
