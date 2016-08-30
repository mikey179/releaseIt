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
use bovigo\releaseit\Key;
use bovigo\releaseit\Series;
use bovigo\releaseit\Version;
use stubbles\streams\InputStream;
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
    public function isDirty(): bool;

    /**
     * provides an input stream to read the current repository status
     *
     * @return  InputStream
     */
    public function status(): InputStream;

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function branch(): string;

    /**
     * returns a list of the last releases
     *
     * @param   Series  $series  limit releases to those of a certain series, i.e. v2 or v2.1, defaults to all
     * @param   int     $amount  limit amount of releases to retrieve, defaults to 5
     * @return  string[]
     */
    public function lastReleases(Series $series = null, int $amount = 5): array;

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @param   Key      $key      optional  by passing a key the release will be signed
     * @return  string[]
     */
    public function createRelease(Version $version, Key $key = null): array;
}
