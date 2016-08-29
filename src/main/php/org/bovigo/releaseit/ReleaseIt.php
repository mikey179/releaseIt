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
use stubbles\console\Console;
use stubbles\console\ConsoleApp;
use stubbles\ioc\Binder;
use org\bovigo\releaseit\composer\Package;
use org\bovigo\releaseit\composer\InvalidPackage;
use org\bovigo\releaseit\repository\Repository;
use org\bovigo\releaseit\repository\RepositoryDetector;
/**
 * Console app to create composer package releases directly from within a checkout.
 *
 * @AppDescription('Create composer package releases from your checkout')
 */
class ReleaseIt extends ConsoleApp
{
    /**
     * console in- and output
     *
     * @type  Console
     */
    private $console;
    /**
     * detects repository type to create a release from
     *
     * @type  RepositoryDetector
     */
    private $repoDetector;
    /**
     * finds version for release to create
     *
     * @type  VersionFinder
     */
    private $versionFinder;
    /**
     * current working directory
     *
     * @type  string
     */
    private $cwd;

    /**
     * returns list of bindings used for this application
     *
     * @return  \stubbles\ioc\module\BindingModule[]
     */
    public static function __bindings()
    {
        return [
                self::argumentParser(),
                self::currentWorkingDirectory(),
                function(Binder $binder)
                {
                    $binder->bindList('org\bovigo\releaseit\VersionFinder')
                            ->withValue('org\bovigo\releaseit\NextSeriesVersionFinder')
                            ->withValue('org\bovigo\releaseit\AskingVersionFinder');
                }
        ];
    }

    /**
     * constructor
     *
     * @param  Console             $console
     * @param  RepositoryDetector  $repoDetector
     * @param  VersionFinder       $versionFinder
     * @param  string              $cwd
     * @Named{cwd}('stubbles.cwd')
     */
    public function __construct(
            Console $console,
            RepositoryDetector $repoDetector,
            VersionFinder $versionFinder,
            $cwd
    ) {
        $this->console       = $console;
        $this->repoDetector  = $repoDetector;
        $this->versionFinder = $versionFinder;
        $this->cwd           = $cwd;
    }

    /**
     * runs the command and returns an exit code
     *
     * @return  int
     */
    public function run()
    {
        try {
            $package = Package::fromFile($this->cwd . DIRECTORY_SEPARATOR . 'composer.json');
        } catch (InvalidPackage $ip) {
            $this->console->writeErrorLine($ip->getMessage());
            return 21;
        }

        $repository = $this->repoDetector->detect($this->cwd);
        if ($this->isDirty($repository)) {
            return 22;
        }

        $version = $this->versionFinder->find($package, $repository);
        if (null === $version) {
            $this->console->writeErrorLine('Can not create release, unable to find a version for this release.');
            return 23;
        }

        $this->console->writeLines($repository->createRelease($version))
                      ->writeLine('Successfully created release ' . $version);
        return 0;
    }

    /**
     * checks if repository is dirty
     *
     * @param   Repository  $repository
     * @return  bool
     */
    private function isDirty(Repository $repository)
    {
        if ($repository->isDirty()) {
            $this->console->writeErrorLine('Can\'t create release, working directory not clean.');
            $repositoryStatus = $repository->readStatus();
            while (!$repositoryStatus->eof()) {
                $this->console->writeErrorLine($repositoryStatus->readLine());
            }

            return true;
        }

        return false;
    }
}
