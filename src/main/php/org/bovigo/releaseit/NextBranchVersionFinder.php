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
 * Finds version for release to create by comparing repository branch with
 * package branch alias definitions.
 */
class NextBranchVersionFinder implements VersionFinder
{
    /**
     * finds version
     *
     * @param   Package     $package
     * @param   Repository  $repository
     * @return  Version
     */
    public function find(Package $package, Repository $repository)
    {
        $series = $package->getSeries('dev-' . $repository->getBranch());
        if (empty($series)) {
            return null;
        }

        $lastReleaseInSeries = $repository->getLastReleases($series, 1);
        if (null === $lastReleaseInSeries) {
            return $series->getFirstVersion();
        }

        return $series->getNextVersion(new Version($lastReleaseInSeries));
    }
}
