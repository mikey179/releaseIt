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
use stubbles\streams\memory\MemoryInputStream;
/**
 * Denotes a non- or unknown type of repository.
 */
class NoRepository implements Repository
{
    private $path;

    /**
     * constructor
     *
     * @param  string  $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

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
    public function status(): InputStream
    {
        return new MemoryInputStream(
                'Directory ' . $this->path . ' is not a known type of repository'
        );
    }

    /**
     * returns branch of repository
     *
     * @return  string
     */
    public function branch(): string
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
    public function lastReleases(Series $series = null, int $amount = 5): array
    {
        return [];
    }

    /**
     * creates a release with given version number
     *
     * @param   Version  $version
     * @param   Key      $key      optional  by passing a key the release will be signed
     * @return  string[]
     * @throws  RepositoryError
     */
    public function createRelease(Version $version, Key $key = null): array
    {
        throw new RepositoryError(
                'Can\'t create release from ' . $this->path . ', is not a known repository'
        );
    }
}
