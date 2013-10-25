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
use net\stubbles\console\ConsoleApp;
use net\stubbles\lang\exception\IllegalArgumentException;
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
     * current working directory
     *
     * @type  string
     */
    private $cwd;

    /**
     * returns list of bindings used for this application
     *
     * @param   string  $projectPath
     * @return  \net\stubbles\ioc\module\BindingModule[]
     */
    public static function __bindings($projectPath)
    {
        return array(self::createArgumentsBindingModule(),
                     self::createConsoleBindingModule(),
                     self::createPropertiesBindingModule($projectPath)
                         ->withCurrentWorkingDirectory()
        );
    }

    /**
     * constructor
     *
     * @param  Console             $console
     * @param  RepositoryDetector  $repoDetector
     * @param  string              $cwd
     * @Inject
     * @Named{cwd}('net.stubbles.cwd')
     */
    public function __construct(Console $console, RepositoryDetector $repoDetector, $cwd)
    {
        $this->console      = $console;
        $this->repoDetector = $repoDetector;
        $this->cwd          = $cwd;
    }

    /**
     * runs the command and returns an exit code
     *
     * @return  int
     */
    public function run()
    {
        if (!$this->isComposerPackage()) {
            return 21;
        }

        $repository = $this->repoDetector->detect($this->cwd);
        if ($this->isDirty($repository)) {
            return 22;
        }

        $version = $this->echoLastReleases($repository)->askVersion();
        $this->console->writeLines($this->createRelease($version))
                      ->writeLine('Successfully created release ' . $version);
        return 0;
    }

    /**
     * checks if working directory is a composer package
     *
     * @return  bool
     */
    private function isComposerPackage()
    {
        if (!file_exists($this->cwd . DIRECTORY_SEPARATOR . 'composer.json')) {
            $this->console->writeLine('No composer.json found - are you sure this is a composer package?');
            return false;
        }

        return true;
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
            $this->console->writeLine('Can\'t create release, working directory not clean.');
            $repositoryStatus = $repository->readStatus();
            while (!$repositoryStatus->eof()) {
                $this->console->writeLine($repositoryStatus->readLine());
            }

            return false;
        }

        return true;
    }

    /**
     * echos last releases for given repository
     *
     * @param   Repository  $repository
     * @return  ReleaseIt
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
    private function askVersion()
    {
        while (true) {
            try {
                return new Version($this->console->prompt('Please name the version to release (press Ctrl-C to abort): ')
                                                 ->unsecure()
                );
            } catch (IllegalArgumentException $e) {
                $this->console->writeLine($e->getMessage());
            }
        }
    }
}
