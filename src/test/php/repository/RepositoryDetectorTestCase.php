<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  bovigo\releaseit
 */
namespace bovigo\releaseit\repository;
use org\bovigo\vfs\vfsStream;
/**
 * Test for bovigo\releaseit\repository\RepositoryDetector.
 */
class RepositoryDetectorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  RepositoryDetector
     */
    private $repositoryDetector;
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
        $this->mockExecutor       = $this->createPartialMock('stubbles\console\Executor', ['outputOf', 'executeAsync']);
        $this->repositoryDetector = new RepositoryDetector($this->mockExecutor);
        $this->root               = vfsStream::setup();
    }

    /**
     * @test
     */
    public function missingRepositoryInfoFolderResultsInNoRepository()
    {
        $this->assertInstanceOf(NoRepository::class,
                               $this->repositoryDetector->detect($this->root->url())
        );
    }

    /**
     * @test
     */
    public function svnFolderResultsInSvnRepository()
    {
        vfsStream::newDirectory('.svn')->at($this->root);
        $this->mockExecutor->expects($this->once())
                           ->method('outputOf')
                           ->will($this->returnValue(array('URL: http://svn.example.org/svn/foo/trunk')));
        $this->assertInstanceOf(SvnRepository::class,
                               $this->repositoryDetector->detect($this->root->url())
        );
    }

    /**
     * @test
     */
    public function gitFolderResultsInGitRepository()
    {
        vfsStream::newDirectory('.git')->at($this->root);
        $this->assertInstanceOf(GitRepository::class,
                               $this->repositoryDetector->detect($this->root->url())
        );
    }

    /**
     * @test
     */
    public function gitAndSvnFolderResultsInGitRepository()
    {
        vfsStream::newDirectory('.git')->at($this->root);
        vfsStream::newDirectory('.svn')->at($this->root);
        $this->assertInstanceOf(GitRepository::class,
                               $this->repositoryDetector->detect($this->root->url())
        );
    }
}