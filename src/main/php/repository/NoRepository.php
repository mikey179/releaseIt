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
use stubbles\streams\InputStream;
use stubbles\streams\memory\MemoryInputStream;
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
    public function isDirty(): bool
    {
        return true;
    }

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function readStatus(): InputStream
    {
        return new MemoryInputStream(
                'Current directory is not a known type of repository'
        );
    }

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function getBranch(): string
    {
        return '';
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
        return [];
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @return  string[]
     * @throws  RepositoryError
     */
    public function createRelease(Version $version): array
    {
        throw new RepositoryError(
                'Can\'t create release here, is not a known repository'
        );
    }
}
