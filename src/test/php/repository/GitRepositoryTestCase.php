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
use bovigo\releaseit\Series;
use bovigo\releaseit\Version;
use org\bovigo\vfs\vfsStream;
/**
 * Test for bovigo\releaseit\repository\GitRepository.
 */
class GitRepositoryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  GitRepository
     */
    private $gitRepository;
    /**
     * mocked command executor
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockExecutor;
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
        $this->mockExecutor  = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $this->gitRepository = new GitRepository($this->mockExecutor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking git status
     */
    public function isDirtyThrowsRepositoryErrorWhenGitStatusFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->gitRepository->isDirty();
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Current directory is not a git repository
     */
    public function isDirtyThrowsRepositoryErrorWhenCurrentFolderIsNoGitRepository()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue([]));
        $this->gitRepository->isDirty();
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenRepositoryContainsUncomittedChanges()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue(['# Changes to be committed:']));
        $this->assertTrue($this->gitRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenRepositoryIsClean()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue(['nothing to commit, working directory clean']));
        $this->assertFalse($this->gitRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamToReadResultFrom()
    {
        $mockInputStream = $this->createMock('stubbles\streams\InputStream');
        $this->mockExecutor->expects($this->once())
                           ->method('executeAsync')
                           ->will(($this->returnValue($mockInputStream)));
        $this->assertSame($mockInputStream,
                          $this->gitRepository->readStatus()
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while retrieving current branch
     */
    public function branchThrowsRepositoryErrorWhenGitBranchFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->gitRepository->branch();
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while retrieving current branch
     */
    public function branchThrowsRepositoryErrorWhenGitBranchReturnsNoOutput()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will(($this->returnValue([])));
        $this->gitRepository->branch();
    }

    /**
     * @test
     */
    public function branchReturnsCurrentBranchName()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will(($this->returnValue(['* master'])));
        $this->assertEquals('master',
                            $this->gitRepository->branch()
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while retrieving last releases
     */
    public function lastReleasesThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->with($this->equalTo('git tag -l | grep "v" | sort -r | head -5'))
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->gitRepository->lastReleases();
    }

    /**
     * @test
     */
    public function lastReleasesReturnsListOfLastReleases()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->with($this->equalTo('git tag -l | grep "v1.0" | sort -r | head -2'))
                           ->will(($this->returnValue(['v1.0.0', 'v1.0.1'])));
        $this->assertEquals(['v1.0.0', 'v1.0.1'],
                            $this->gitRepository->lastReleases(new Series('1.0'), 2)
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while creating release
     */
    public function createReleaseThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->gitRepository->createRelease(new Version('1.1.0'));
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $gitTagOutput = [
                'Counting objects: 1, done.',
                'Writing objects: 100% (1/1), 159 bytes | 0 bytes/s, done.',
                'Total 1 (delta 0), reused 0 (delta 0)',
                'To git@github.com:example/foo.git',
                ' * [new tag]         v1.1.0 -> v1.1.0'
        ];
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will(($this->returnValue($gitTagOutput)));
        $this->assertEquals($gitTagOutput,
                            $this->gitRepository->createRelease(new Version('1.1.0'))
        );
    }
}
