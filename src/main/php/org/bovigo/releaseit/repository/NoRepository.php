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
use net\stubbles\streams\memory\MemoryInputStream;
use org\bovigo\releaseit\Version;
/**
 * Denotes a non- or unknown type of repository.
 */
class NoRepository implements Repository
{
    /**
     * checks whether repository is dirty and therefore can't be released
     *
     * @return  bool
     */
    public function isDirty()
    {
        return true;
    }

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus()
    {
        return new MemoryInputStream('Current directory is not a known type of repository');
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
        return array();
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     * @throws  RepositoryError
     */
    public function createRelease(Version $version)
    {
        throw new RepositoryError('Can\'t create release here, is not a known repository');
    }
}
