<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit;
use org\bovigo\releaseit\composer\Package;
use org\bovigo\releaseit\repository\Repository;
/**
 * Finds version for release to create.
 *
 * @ImplementedBy(org\bovigo\releaseit\VersionFinderChain.class)
 */
interface VersionFinder
{
    /**
     * finds version
     *
     * @param   Package     $package
     * @param   Repository  $repository
     * @return  Version
     */
    public function find(Package $package, Repository $repository);
}
