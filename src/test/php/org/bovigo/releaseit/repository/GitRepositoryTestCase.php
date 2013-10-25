<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit\repository;
use net\stubbles\lang\exception\RuntimeException;
use org\bovigo\releaseit\Version;
use org\bovigo\vfs\vfsStream;
/**
 * Test for org\bovigo\releaseit\repository\GitRepository.
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
        $this->mockExecutor  = $this->getMock('net\stubbles\console\Executor');
        $this->gitRepository = new GitRepository($this->mockExecutor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking git status
     */
    public function isDirtyThrowsRepositoryErrorWhenGitStatusFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->throwException(new RuntimeException('error')));
        $this->gitRepository->isDirty();
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Current directory is not a git repository
     */
    public function isDirtyThrowsRepositoryErrorWhenCurrentFolderIsNoGitRepository()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->returnValue(array()));
        $this->gitRepository->isDirty();
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenRepositoryContainsUncomittedChanges()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->returnValue(array('# Changes to be committed:')));
        $this->assertTrue($this->gitRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenRepositoryIsClean()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->returnValue(array('nothing to commit, working directory clean')));
        $this->assertFalse($this->gitRepository->isDirty());
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking git status
     */
    public function readStatusThrowsRepositoryErrorWhenGitStatusFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeAsync')
                           ->will($this->throwException(new RuntimeException('error')));
        $this->gitRepository->readStatus();
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamToReadResultFrom()
    {
        $mockInputStream = $this->getMock('net\stubbles\streams\InputStream');
        $this->mockExecutor->expects($this->once())
                           ->method('executeAsync')
                           ->will(($this->returnValue($mockInputStream)));
        $this->assertSame($mockInputStream,
                          $this->gitRepository->readStatus()
        );
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while retrieving last releases
     */
    public function getLastReleasesThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->throwException(new RuntimeException('error')));
        $this->gitRepository->getLastReleases();
    }

    /**
     * @test
     */
    public function getLastReleasesReturnsListOfLastReleases()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will(($this->returnValue(array('v1.0.0', 'v1.0.1'))));
        $this->assertEquals(array('v1.0.0', 'v1.0.1'),
                            $this->gitRepository->getLastReleases()
        );
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while creating release
     */
    public function createReleaseThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will($this->throwException(new RuntimeException('error')));
        $this->gitRepository->createRelease(new Version('1.1.0'));
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $gitTagOutput = array('Counting objects: 1, done.',
                              'Writing objects: 100% (1/1), 159 bytes | 0 bytes/s, done.',
                              'Total 1 (delta 0), reused 0 (delta 0)',
                              'To git@github.com:example/foo.git',
                              ' * [new tag]         v1.1.0 -> v1.1.0'
        );
        $this->mockExecutor->expects($this->once())
                           ->method('executeDirect')
                           ->will(($this->returnValue($gitTagOutput)));
        $this->assertEquals($gitTagOutput,
                            $this->gitRepository->createRelease(new Version('1.1.0'))
        );
    }
}
