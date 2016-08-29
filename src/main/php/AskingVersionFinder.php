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
use stubbles\console\Console;
/**
 * Finds version by asking the user what the next version should be.
 */
class AskingVersionFinder implements VersionFinder
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
        return $this->echoLastReleases($repository)->askForVersion();
    }

    /**
     * echos last releases for given repository
     *
     * @param   Repository  $repository
     * @return  AskingVersionFinder
     */
    private function echoLastReleases(Repository $repository): self
    {
        $this->console->writeLine('Last 5 releases:');
        foreach ($repository->lastReleases() as $release) {
            $this->console->writeLine($release);
        }

        $this->console->writeEmptyLine();
        return $this;
    }

    /**
     * asks for the new version
     *
     * @return  Version
     */
    private function askForVersion(): Version
    {
        while (true) {
            try {
                return new Version(
                        $this->console->prompt(
                                'Please name the version to release (press Ctrl+C to abort): '
                        )->unsecure()
                );
            } catch (\InvalidArgumentException $e) {
                $this->console->writeLine($e->getMessage());
            }
        }
    }
}
