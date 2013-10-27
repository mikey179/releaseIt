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
use net\stubbles\lang\exception\IllegalArgumentException;
use org\bovigo\releaseit\composer\Package;
use org\bovigo\releaseit\repository\Repository;
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
        return $this->echoLastReleases($repository)->askForVersion();
    }

    /**
     * echos last releases for given repository
     *
     * @param   Repository  $repository
     * @return  AskingVersionFinder
     */
    private function echoLastReleases(Repository $repository)
    {
        $this->console->writeLine('Last 5 releases:');
        foreach ($repository->getLastReleases() as $release) {
            $this->console->writeLine($release);
        }

        $this->console->writeLine('');
        return $this;
    }

    /**
     * asks for the new version
     *
     * @return  Version
     */
    private function askForVersion()
    {
        while (true) {
            try {
                return new Version($this->console->prompt('Please name the version to release (press Ctrl+C to abort): ')
                                                 ->unsecure()
                );
            } catch (IllegalArgumentException $e) {
                $this->console->writeLine($e->getMessage());
            }
        }
    }
}

