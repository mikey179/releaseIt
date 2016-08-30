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
use bovigo\releaseit\composer\{InvalidPackage, Package};
use bovigo\releaseit\repository\{Repository, RepositoryDetector};
use stubbles\console\{Console, ConsoleApp};
use stubbles\ioc\Binder;
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
     * command line arguments
     *
     * @type  array
     */
    private $args;

    /**
     * returns list of bindings used for this application
     *
     * @return  \stubbles\ioc\module\BindingModule[]
     */
    public static function __bindings(): array
    {
        return [
                self::argumentParser()->withOptions('hv')->withLongOptions(['help', 'version']),
                self::currentWorkingDirectory(),
                function(Binder $binder)
                {
                    $binder->bindList(VersionFinder::class)
                            ->withValue(NextSeriesVersionFinder::class)
                            ->withValue(AskingVersionFinder::class);
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
     * @param  array               $args
     * @Named{cwd}('stubbles.cwd')
     * @Named{args}('argv')
     */
    public function __construct(
            Console $console,
            RepositoryDetector $repoDetector,
            VersionFinder $versionFinder,
            $cwd,
            array $args
    ) {
        $this->console       = $console;
        $this->repoDetector  = $repoDetector;
        $this->versionFinder = $versionFinder;
        $this->cwd           = $cwd;
        $this->args          = $args;
    }

    /**
     * runs the command and returns an exit code
     *
     * @return  int
     */
    public function run(): int
    {
        if (isset($this->args['h']) || isset($this->args['help'])) {
            $this->console->writeLine('Usage: ' . basename($_SERVER['argv']['0']) . ' [options]');
            $this->console->writeEmptyLine();
            $this->console->writeLine('Options:');
            $this->console->writeLine(' -h|--help      Print this usage info.');
            $this->console->writeLine(' -v|--version   Print current version.');
            $this->console->writeLine(' -s             Create signed release, using the default e-mail address\'s key.');
            $this->console->writeLine(' -u <keyid>     Create signed release, using the given key.');
            $this->console->writeEmptyLine();
            return 0;
        } elseif (isset($this->args['v']) || isset($this->args['version'])) {
            $this->console->writeLine('ReleaseIt v2.0.0 by Frank Kleine, (c) 2012-2016');
            $this->console->writeEmptyLine();
            return 0;
        }

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
            $this->console->writeErrorLine(
                    'Can not create release, unable to find a version for this release.'
            );
            return 23;
        }

        $this->console->writeLines($repository->createRelease($version, $this->signingKey()))
                ->writeLine('Successfully created release ' . $version);
        return 0;
    }

    /**
     * checks if repository is dirty
     *
     * @param   Repository  $repository
     * @return  bool
     */
    private function isDirty(Repository $repository): bool
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

    /**
     * creates key to use for signing the release
     *
     * @return  Key
     */
    private function signingKey()
    {
        if (isset($this->args['s'])) {
            return Key::default();
        } elseif (isset($this->args['u'])) {
            return new Key($this->args['u']);
        }

        return null;
    }
}
