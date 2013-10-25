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
use org\bovigo\releaseit\Version;
/**
 * Provides access to a svn repository.
 */
class SvnRepository implements Repository
{
    /**
     * executor for command line commands
     *
     * @type  Executor
     */
    private $executor;
    /**
     * svn url of checkout from repository
     *
     * @type  string
     */
    private $svnTagsUrl;
    /**
     * constructor
     *
     * @param  Executor  $executor
     */
    public function __construct(Executor $executor)
    {
        $this->executor = $executor;
        $this->parseCheckout();
    }

    /**
     * retrieves SVN URL of project
     *
     * @return string
     */
    private function parseCheckout()
    {
        foreach ($this->execute('svn info', 'Failure while checking svn info') as $svnInfoLine) {
            if (substr($svnInfoLine, 0, 5) === 'URL: ') {
                $this->svnTagsUrl = $this->findTagsUrl(str_replace('URL: ', '', $svnInfoLine));
            }
        }

        if (null === $this->svnTagsUrl) {
            throw new RepositoryError('Could not retrieve svn tag url, can not create release for this svn repository');
        }
    }

    /**
     * retrieves svn tags url
     *
     * @param   string  $svnUrl
     * @return  string
     * @throws  RepositoryError
     */
    private function findTagsUrl($svnUrl)
    {
         if (strstr($svnUrl, '/trunk') !== false) {
             return strstr($svnUrl, '/trunk', true) . '/tags';
         }

         if (strstr($svnUrl, '/branches/') !== false) {
             return strstr($svnUrl, '/branches/', true) . '/tags';
         }

         throw new RepositoryError('Can not extract tag url from current svn checkout url ' . $svnUrl);
    }

    /**
     * checks whether repository is dirty and therefore can't be released
     *
     * A SVN checkout is considered dirty when there's more than one line output
     * from svn status.
     *
     * @return  bool
     * @throws  RepositoryError
     */
    public function isDirty()
    {
        return (count($this->execute('svn status 2>&1 | tail -n1', 'Failure while checking svn status')) !== 0);
    }

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus()
    {
        return $this->execute('svn status', 'Failure while checking svn status', 'executeAsync');
    }

    /**
     * returns a list of the last releases
     *
     * @return  string[]
     */
    public function getLastReleases()
    {
        return $this->execute('svn list ' . $this->svnTagsUrl . ' | grep "v" | sort -r | head -5',
                              'Failure while retrieving last releases'
        );
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     */
    public function createRelease(Version $version)
    {
        return $this->execute('svn cp . ' . $this->svnTagsUrl . '/' . $version . ' -m "tag release ' . $version . '"',
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
