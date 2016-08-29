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
use stubbles\streams\InputStream;
use stubbles\values\Rootpath;
use org\bovigo\vfs\vfsStream;

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
                $root->url()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(annotationsOf($this->releaseIt)->contain('AppDescription'));
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
                vfsStream::setup()->url()
        );
        $this->assertEquals(21, $releaseIt->run());
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
        $repositoryStatus = NewInstance::of(InputStream::class)->returns([
                'readLine' => 'A  foo.php',
                'eof'      => onConsecutiveCalls(false, true)
        ]);
        $repository = $this->createRepository()->returns([
                'isDirty'    => true,
                'readStatus' => $repositoryStatus
        ]);
        $this->assertEquals(22, $this->releaseIt->run());
        verify($this->console, 'writeErrorLine')->receivedOn(2, 'A  foo.php');
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
        $this->assertEquals(23, $this->releaseIt->run());
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
        $this->assertEquals(0, $this->releaseIt->run());
        verify($this->console, 'writeLines')->received(['foo', 'bar']);
        verify($this->console, 'writeLine')->received('Successfully created release v1.1.0');
        verify($repository, 'createRelease')->received($version);
    }

    /**
     * @test
     */
    public function canCreateInstance()
    {
        $this->assertInstanceOf(ReleaseIt::class,
                                ReleaseIt::create(Rootpath::default())
        );
    }
}
