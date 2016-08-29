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
use bovigo\releaseit\Series;
use bovigo\releaseit\Version;
use stubbles\console\Executor;
use stubbles\streams\InputStream;

use function stubbles\console\collect;
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
     */
    private $svnUrl;
    /**
     * svn url for tags
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
     */
    private function parseCheckout()
    {
        foreach ($this->execute('svn info', 'Failure while checking svn info') as $svnInfoLine) {
            if (substr($svnInfoLine, 0, 5) === 'URL: ') {
                $this->svnUrl     = str_replace('URL: ', '', $svnInfoLine);
                $this->svnTagsUrl = $this->findTagsUrl($this->svnUrl);
            }
        }

        if (null === $this->svnTagsUrl) {
            throw new RepositoryError(
                    'Could not retrieve svn tag url, can not create release for this svn repository'
            );
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

         throw new RepositoryError(
                'Can not extract tag url from current svn checkout url '
                . $svnUrl
        );
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
    public function isDirty(): bool
    {
        return (0 !== count($this->execute(
                'svn status 2>&1 | tail -n1',
                'Failure while checking svn status'
        )));
    }

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus(): InputStream
    {
        return $this->executor->executeAsync('svn status');
    }

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function getBranch(): string
    {
        if (strstr($this->svnUrl, '/trunk') !== false) {
            return 'trunk';
        }

        $svnUrlParts = explode('/', $this->svnUrl);
        return array_pop($svnUrlParts);
    }

    /**
     * returns a list of the last releases
     *
     * @param   Series  $series  limit releases to those of a certain series, i.e. v2 or v2.1, defaults to all
     * @param   int     $amount  limit amount of releases to retrieve, defaults to 5
     * @return  string[]
     */
    public function getLastReleases(Series $series = null, int $amount = 5): array
    {
        if (null === $series) {
            $series = 'v';
        }

        return array_map(
                function($value) { return rtrim($value, '/'); },
                $this->execute(
                        'svn list ' . $this->svnTagsUrl . ' | grep "' . $series
                        . '" | sort -r | head -' . $amount,
                        'Failure while retrieving last releases'
                )
        );
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     */
    public function createRelease(Version $version): array
    {
        return $this->execute(
                'svn cp . ' . $this->svnTagsUrl . '/' . $version
                . ' -m "tag release ' . $version . '"',
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
     * @return  string[]
     * @throws  RepositoryError
     */
    private function execute(string $command, string $errorMessage): array
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
