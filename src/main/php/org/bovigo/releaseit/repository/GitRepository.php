<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit\repository;
use net\stubbles\console\Executor;
use net\stubbles\lang\exception\RuntimeException;
use org\bovigo\releaseit\version\Version;
/**
 * Provides access to a git repository.
 */
class GitRepository implements Repository
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
     * checks whether repository is dirty and therefore can't be released
     *
     * @return  bool
     * @throws  RepositoryError
     */
    public function isDirty()
    {
        $output = $this->execute('git status 2> /dev/null | tail -n1', 'Failure while checking git status');
        if (!isset($output[0])) {
            throw new RepositoryError('Current directory is not a git repository');
        }

        return strstr($output[0], 'nothing to commit') === false || strstr($output[0], 'working directory clean') === false;
    }

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus()
    {
        return $this->execute('git status', 'Failure while checking git status', 'executeAsync');
    }

    /**
     * returns a list of the last releases
     *
     * @return  string[]
     */
    public function getLastReleases()
    {
        return $this->execute('git tag -l | grep "v" | sort -r | head -5', 'Failure while retrieving last releases');
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     */
    public function createRelease(Version $version)
    {
        return $this->execute('git tag -a ' . $version . ' -m "tag release ' . $version . '" && git push --tags',
                              'Failure while creating release'
        );
    }

    /**
     * executes command with given method
     *
     * In case command fails a RepositoryError with given error message is thrown.
     *
     * @param   string  $command
     * @param   string  $errorMessage
     * @param   string  $method
     * @return  string[]|InputStream
     * @throws  RepositoryError
     */
    private function execute($command, $errorMessage, $method = 'executeDirect')
    {
        try {
            return $this->executor->$method($command);
        } catch (RuntimeException $e) {
            throw new RepositoryError($errorMessage, $e);
        }
    }
}
