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
namespace bovigo\releaseit\repository;
use bovigo\callmap\NewInstance;
use bovigo\releaseit\{Series, Version};
use org\bovigo\vfs\vfsStream;
use stubbles\console\Executor;
use stubbles\streams\InputStream;

use function bovigo\callmap\{verify, throws};
/**
 * Test for bovigo\releaseit\repository\SvnRepository.
 */
class SvnRepositoryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  SvnRepository
     */
    private $svnRepository;
    /**
     * @type  Executor
     */
    private $executor;
    /**
     * root path
     *
     * @type  org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/trunk']
        ]);
        $this->svnRepository = new SvnRepository($this->executor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking svn info
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoFails()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'execute' => throws(new \RuntimeException('error'))
        ]);
        new SvnRepository($executor);
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Could not retrieve svn tag url, can not create release for this svn repository
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoDoesNotContainSvnUrl()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => []
        ]);
        new SvnRepository($executor);
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Can not extract tag url from current svn checkout url http://svn.example.org/svn/foo
     */
    public function createInstanceThrowsRepositoryErrorWhenTagUrlCanNotBeDerivedFromSvnUrl()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo']
        ]);
        new SvnRepository($executor);
    }

    /**
     * @test
     */
    public function canCreateInstanceFromBranch()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/branches/v1.1.x']
        ]);
        $this->assertInstanceOf(SvnRepository::class,
                                new SvnRepository($executor)
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking svn status
     */
    public function isDirtyThrowsRepositoryErrorWhenSvnStatusFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        $this->svnRepository->isDirty();
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenCheckoutContainsUncomittedChanges()
    {
        $this->executor->returns([
                    'outputOf' => ['A  readme.md']
        ]);
        $this->assertTrue($this->svnRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenCheckoutContainsNoUncomittedChanges()
    {
        $this->executor->returns([
                    'outputOf' => []
        ]);
        $this->assertFalse($this->svnRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamToReadResultFrom()
    {
        $inputStream = NewInstance::of(InputStream::class);
        $this->executor->returns([
                    'executeAsync' => $inputStream
        ]);
        $this->assertSame($inputStream,
                          $this->svnRepository->readStatus()
        );
    }

    /**
     * @test
     */
    public function branchReturnsTrunkWhenWorkspaceIsTrunkCheckout()
    {
        $this->assertEquals('trunk',
                            $this->svnRepository->branch()
        );
    }

    /**
     * @test
     */
    public function branchReturnsBranchNameWhenWorkspaceIsBranchCheckout()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/branches/cool-new-feature']
        ]);
        $svnRepository = new SvnRepository($executor);
        $this->assertEquals('cool-new-feature',
                            $svnRepository->branch()
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while retrieving last releases
     */
    public function lastReleasesThrowsRepositoryErrorWhenSvnListFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        $this->svnRepository->lastReleases();
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithVWhenNoSeriesGiven()
    {
        $this->executor->returns([
                    'outputOf' => ['v1.0.0/', 'v1.0.1/']
        ]);
        $this->svnRepository->lastReleases();
        verify($this->executor, 'execute')->receivedOn(
                2,
                'svn list http://svn.example.org/svn/foo/tags | grep "v" | sort -r | head -5'
        );
    }

    /**
     * @test
     */
    public function lastReleasesReturnsListOfLastReleases()
    {
        $this->executor->returns([
                    'outputOf' => ['v1.0.0/', 'v1.0.1/']
        ]);
        $this->assertEquals(['v1.0.0', 'v1.0.1'],
                            $this->svnRepository->lastReleases(new Series('1.0'), 2)
        );
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithGivenSeries()
    {
        $this->executor->returns([
                    'outputOf' => ['v1.0.0/', 'v1.0.1/']
        ]);
        $this->svnRepository->lastReleases(new Series('1.0'), 2);
        verify($this->executor, 'execute')->receivedOn(
                2,
                'svn list http://svn.example.org/svn/foo/tags | grep "v1.0" | sort -r | head -2'
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while creating release
     */
    public function createReleaseThrowsRepositoryErrorWhenSvnCpFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        $this->svnRepository->createRelease(new Version('1.1.0'));
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $svnCpOutput = ['Committed revision 303.'];
        $this->executor->returns([
                    'outputOf' => $svnCpOutput
        ]);
        $this->assertEquals($svnCpOutput,
                            $this->svnRepository->createRelease(new Version('1.1.0'))
        );
        verify($this->executor, 'outputOf')->receivedOn(
                2,
                'svn cp . http://svn.example.org/svn/foo/tags/v1.1.0 -m "tag release v1.1.0"'
        );
    }
}
