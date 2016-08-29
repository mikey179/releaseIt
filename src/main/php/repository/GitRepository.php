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
use stubbles\console\Executor;
use bovigo\releaseit\Version;

use function stubbles\console\collect;
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
        return $this->executor->executeAsync('git status');
    }

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function getBranch()
    {
        $output = $this->execute('git branch', 'Failure while retrieving current branch');
        if (!isset($output[0])) {
            throw new RepositoryError('Failure while retrieving current branch');
        }

        return substr($output[0], 2);
    }

    /**
     * returns a list of the last releases
     *
     * @param   string  $series  limit releases to those of a certain series, i.e. v2 or v2.1, defaults to all
     * @param   int     $amount  limit amount of releases to retrieve, defaults to 5
     * @return  string[]
     */
    public function getLastReleases($series = 'v', $amount = 5)
    {
        return $this->execute('git tag -l | grep "' . $series . '" | sort -r | head -' . $amount, 'Failure while retrieving last releases');
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
            $data = [];
            $this->executor->execute($command, collect($data));
            return $data;
        } catch (\RuntimeException $e) {
            throw new RepositoryError($errorMessage, $e);
        }
    }
}
