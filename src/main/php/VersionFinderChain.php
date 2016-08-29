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
namespace bovigo\releaseit;
use bovigo\releaseit\composer\Package;
use bovigo\releaseit\repository\Repository;
/**
 * Finds version by asking a list of version finders.
 *
 * The result from the first finder which responds with a version will be used.
 */
class VersionFinderChain implements VersionFinder
{
    /**
     * list of version finders to ask
     *
     * @type  VersionFinder[]
     */
    private $finders;

    /**
     * constructor
     *
     * @param  VersionFinder[]  $finders
     * @List(bovigo\releaseit\VersionFinder.class)
     */
    public function __construct(array $finders)
    {
        $this->finders = $finders;
    }

    /**
     * finds version
     *
     * @param   Package     $package
     * @param   Repository  $repository
     * @return  Version
     */
    public function find(Package $package, Repository $repository)
    {
        foreach ($this->finders as $versionFinder) {
            $version = $versionFinder->find($package, $repository);
            if (null !== $version) {
                return $version;
            }
        }

        return null;
    }
}
