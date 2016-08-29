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
        $this->mockExecutor->expects($this->at(0))
                           ->method('outputOf')
                           ->will($this->returnValue(['URL: http://svn.example.org/svn/foo/trunk']));
        $this->svnRepository = new SvnRepository($this->mockExecutor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking svn info
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoFails()
    {
        $mockExecutor  = $this->createMock('stubbles\console\Executor');
        $mockExecutor->expects($this->once())
                     ->method('execute')
                     ->will($this->throwException(new \RuntimeException('error')));
        new SvnRepository($mockExecutor);
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Could not retrieve svn tag url, can not create release for this svn repository
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoDoesNotContainSvnUrl()
    {
        $mockExecutor  = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $mockExecutor->expects($this->once())
                     ->method('outputOf')
                     ->will($this->returnValue([]));
        new SvnRepository($mockExecutor);
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Can not extract tag url from current svn checkout url http://svn.example.org/svn/foo
     */
    public function createInstanceThrowsRepositoryErrorWhenTagUrlCanNotBeDerivedFromSvnUrl()
    {
        $mockExecutor  = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $mockExecutor->expects($this->once())
                     ->method('outputOf')
                     ->will($this->returnValue(['URL: http://svn.example.org/svn/foo']));
        new SvnRepository($mockExecutor);
    }

    /**
     * @test
     */
    public function canCreateInstanceFromBranch()
    {
        $mockExecutor  = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $mockExecutor->expects($this->once())
                     ->method('outputOf')
                     ->will($this->returnValue(['URL: http://svn.example.org/svn/foo/branches/v1.1.x']));
        $this->assertInstanceOf(SvnRepository::class,
                                new SvnRepository($mockExecutor)
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while checking svn status
     */
    public function isDirtyThrowsRepositoryErrorWhenSvnStatusFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->svnRepository->isDirty();
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenCheckoutContainsUncomittedChanges()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue(['A  readme.md']));
        $this->assertTrue($this->svnRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenCheckoutContainsNoUncomittedChanges()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue([]));
        $this->assertFalse($this->svnRepository->isDirty());
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
        $mockExecutor  = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $mockExecutor->expects($this->once())
                     ->method('outputOf')
                     ->will($this->returnValue(['URL: http://svn.example.org/svn/foo/branches/cool-new-feature']));
        $svnRepository = new SvnRepository($mockExecutor);
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
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->with($this->equalTo('svn list http://svn.example.org/svn/foo/tags | grep "v" | sort -r | head -5'))
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->svnRepository->lastReleases();
    }

    /**
     * @test
     */
    public function lastReleasesReturnsListOfLastReleases()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->with($this->equalTo('svn list http://svn.example.org/svn/foo/tags | grep "v1.0" | sort -r | head -2'))
                           ->will(($this->returnValue(['v1.0.0/', 'v1.0.1/'])));
        $this->assertEquals(['v1.0.0', 'v1.0.1'],
                            $this->svnRepository->lastReleases(new Series('1.0'), 2)
        );
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\repository\RepositoryError
     * @expectedExceptionMessage   Failure while creating release
     */
    public function createReleaseThrowsRepositoryErrorWhenSvnCpFails()
    {
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->throwException(new \RuntimeException('error')));
        $this->svnRepository->createRelease(new Version('1.1.0'));
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $svnCpOutput = ['Committed revision 303.'];
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->with($this->equalTo('svn cp . http://svn.example.org/svn/foo/tags/v1.1.0 -m "tag release v1.1.0"'))
                           ->will(($this->returnValue($svnCpOutput)));
        $this->assertEquals($svnCpOutput,
                            $this->svnRepository->createRelease(new Version('1.1.0'))
        );
    }
}
