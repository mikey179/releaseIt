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
use bovigo\callmap\NewInstance;
use bovigo\releaseit\repository\{Repository, RepositoryDetector};
use stubbles\console\Console;
use stubbles\input\ValueReader;
use stubbles\streams\{InputStream, memory\MemoryInputStream};
use stubbles\values\Rootpath;
use org\bovigo\vfs\vfsStream;

use function bovigo\assert\{
    assert,
    assertTrue,
    predicate\equals,
    predicate\isInstanceOf,
    predicate\startsWith
};
use function bovigo\callmap\{verify, onConsecutiveCalls};
use function stubbles\reflect\annotationsOf;
/**
 * Test for bovigo\releaseit\ReleaseIt.
 */
class ReleaseItTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ReleaseIt
     */
    private $releaseIt;
    /**
     * @type  Console
     */
    private $console;
    /**
     * @type  RepositoryDetector
     */
    private $repoDetector;
    /**
     * @type  VersionFinder
     */
    private $versionFinder;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->console       = NewInstance::stub(Console::class);
        $this->repoDetector  = NewInstance::stub(RepositoryDetector::class);
        $this->versionFinder = NewInstance::of(VersionFinder::class);
        $root                = vfsStream::setup();
        vfsStream::newFile('composer.json')->withContent('{}')->at($root);
        $this->releaseIt     = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                $root->url(),
                []
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        assertTrue(annotationsOf($this->releaseIt)->contain('AppDescription'));
    }

    /**
     * @test
     * @since  2.0.0
     */
    public function printsUsageWhenRequestedWithH()
    {
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::setup()->url(),
                ['h' => false]
        );
        assert($releaseIt->run(), equals(0));
        verify($this->console, 'writeLine')->received(startsWith('Usage'));
    }

    /**
     * @test
     * @since  2.0.0
     */
    public function printsUsageWhenRequestedWithHelp()
    {
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::setup()->url(),
                ['help' => false]
        );
        assert($releaseIt->run(), equals(0));
        verify($this->console, 'writeLine')->received(startsWith('Usage'));
    }

    /**
     * @test
     * @since  2.0.0
     */
    public function printsVersionWhenRequestedWithV()
    {
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::setup()->url(),
                ['v' => false]
        );
        assert($releaseIt->run(), equals(0));
        verify($this->console, 'writeLine')->received(startsWith('ReleaseIt v'));
    }

    /**
     * @test
     * @since  2.0.0
     */
    public function printsVersionWhenRequestedWithVersion()
    {
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::setup()->url(),
                ['version' => false]
        );
        assert($releaseIt->run(), equals(0));
        verify($this->console, 'writeLine')->received(startsWith('ReleaseIt v'));
    }

    /**
     * @test
     */
    public function stopsWithExitCode21IfComposerJsonIsMissing()
    {
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::setup()->url(),
                []
        );
        assert($releaseIt->run(), equals(21));
        verify($this->console, 'writeErrorLine')->wasCalledOnce();
        verify($this->repoDetector, 'detect')->wasNeverCalled();
    }

    private function createRepository(): Repository
    {
        $repository = NewInstance::of(Repository::class);
        $this->repoDetector->returns(['detect' => $repository]);
        return $repository;
    }

    /**
     * @test
     */
    public function stopsWithExitCode22IfRepositoryIsDirty()
    {
        $repositoryStatus = new MemoryInputStream('A  foo.php');
        $repository = $this->createRepository()->returns([
                'isDirty' => true,
                'status'  => $repositoryStatus
        ]);
        assert($this->releaseIt->run(), equals(22));
        verify($this->console, 'writeError')->received($repositoryStatus);
    }

    /**
     * @test
     */
    public function stopsWithExitCode23IfVersionFinderCanNotProvideVersionForRelease()
    {
        $repository = $this->createRepository()->returns([
                'isDirty' => false
        ]);
        $this->versionFinder->returns(['find' => null]);
        assert($this->releaseIt->run(), equals(23));
        verify($this->console, 'writeErrorLine')->received(
                'Can not create release, unable to find a version for this release.'
        );
        verify($repository, 'createRelease')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function createsReleaseWithVersionDeliveredByVersionFinder()
    {
        $repository = $this->createRepository()->returns([
                'isDirty'       => false,
                'createRelease' => ['foo', 'bar']
        ]);
        $version = new Version('v1.1.0');
        $this->versionFinder->returns(['find' => $version]);
        assert($this->releaseIt->run(), equals(0));
        verify($this->console, 'writeLines')->received(['foo', 'bar']);
        verify($this->console, 'writeLine')->received('Successfully created release v1.1.0');
        verify($repository, 'createRelease')->received($version);
    }

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createsUnsignedReleaseWhenNoSigningRequested()
    {
        $repository = $this->createRepository()->returns([
                'isDirty'       => false,
                'createRelease' => ['foo', 'bar']
        ]);
        $version = new Version('v1.1.0');
        $this->versionFinder->returns(['find' => $version]);
        assert($this->releaseIt->run(), equals(0));
        verify($repository, 'createRelease')->received($version, null);
    }

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createsSignedReleaseWhenSigningWithDefaultKeyRequested()
    {
        $repository = $this->createRepository()->returns([
                'isDirty'       => false,
                'createRelease' => ['foo', 'bar']
        ]);
        $version = new Version('v1.1.0');
        $this->versionFinder->returns(['find' => $version]);
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::url('root'),
                ['s' => false]
        );
        assert($releaseIt->run(), equals(0));
        verify($repository, 'createRelease')->received($version, Key::default());
    }

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createsSignedReleaseWhenSigningWithKeyRequested()
    {
        $repository = $this->createRepository()->returns([
                'isDirty'       => false,
                'createRelease' => ['foo', 'bar']
        ]);
        $version = new Version('v1.1.0');
        $this->versionFinder->returns(['find' => $version]);
        $releaseIt = new ReleaseIt(
                $this->console,
                $this->repoDetector,
                $this->versionFinder,
                vfsStream::url('root'),
                ['u' => 'abc123']
        );
        assert($releaseIt->run(), equals(0));
        verify($repository, 'createRelease')->received($version, new Key('abc123'));
    }

    /**
     * @test
     */
    public function canCreateInstance()
    {
        assert(
                ReleaseIt::create(Rootpath::default()),
                isInstanceOf(ReleaseIt::class)
        );
    }
}
