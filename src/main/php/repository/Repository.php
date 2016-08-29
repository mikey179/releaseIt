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
use bovigo\releaseit\Version;
/**
 * Provides access to a repository.
 */
interface Repository
{
    /**
     * checks whether repository is dirty and therefore can't be released
     *
     * @return  bool
     */
    public function isDirty();

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus();

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function getBranch();

    /**
     * returns a list of the last releases
     *
     * @param   string  $series  limit releases to those of a certain series, i.e. v2 or v2.1, defaults to all
     * @param   int     $amount  limit amount of releases to retrieve, defaults to 5
     * @return  string[]
     */
    public function getLastReleases($series = 'v', $amount = 5);

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     */
    public function createRelease(Version $version);
}
