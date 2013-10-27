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
use net\stubbles\console\Console;
use org\bovigo\releaseit\composer\Package;
use org\bovigo\releaseit\repository\Repository;
/**
 * Finds version for release to create by comparing repository branch with
 * package branch alias definitions.
 */
class NextSeriesVersionFinder implements VersionFinder
{
    /**
     * console in- and output
     *
     * @type  Console
     */
    private $console;

    /**
     * constructor
     *
     * @param  Console  $console
     * @Inject
     */
    public function __construct(Console $console)
    {
        $this->console = $console;
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
        $series = $package->getSeries('dev-' . $repository->getBranch());
        if (empty($series)) {
            $this->console->writeLine('Can not determine current series for branch ' . $repository->getBranch());
            return null;
        }

        $lastReleaseInSeries = $this->getLastReleaseInSeries($series, $repository);
        if (null === $lastReleaseInSeries) {
            $version = $series->getFirstVersion();
            $this->console->writeLine('No release in series ' . $series . ' yet, determined ' . $version . ' as first version number.');
        } else {
            $version = $series->getNextVersion($lastReleaseInSeries);
            $this->console->writeLine('Last release in series ' . $series . ' was ' . $lastReleaseInSeries . ', determined ' . $version . ' as next version number.');
        }

        if ($this->console->confirm('Do you want to create a release with this version number? ')) {
            return $version;
        }

        return null;
    }

    /**
     * determines last release in given series
     *
     * If series has no release yet return value is null.
     *
     * @param   Series  $series
     * @return  Version
     */
    private function getLastReleaseInSeries(Series $series, Repository $repository)
    {
        $lastReleaseInSeries = $repository->getLastReleases($series, 1);
        if (count($lastReleaseInSeries) === 0) {
            return null;
        }

        return new Version($lastReleaseInSeries[0]);
    }
}
