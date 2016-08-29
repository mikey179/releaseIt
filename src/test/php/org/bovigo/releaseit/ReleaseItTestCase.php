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
use stubbles\input\ValueReader;
use org\bovigo\vfs\vfsStream;

use function stubbles\reflect\annotationsOf;
/**
 * Test for org\bovigo\releaseit\ReleaseIt.
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
     * mocked console interface
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockConsole;
    /**
     * mocked repository detector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRepoDetector;
    /**
     * mocked version finder
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockVersionFinder;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockConsole       = $this->getMockBuilder('stubbles\console\Console')
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->mockRepoDetector  = $this->getMockBuilder('org\bovigo\releaseit\repository\RepositoryDetector')
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->mockVersionFinder = $this->createMock('org\bovigo\releaseit\VersionFinder');
        $root                    = vfsStream::setup();
        vfsStream::newFile('composer.json')->withContent('{}')->at($root);
        $this->releaseIt        = new ReleaseIt($this->mockConsole,
                                                $this->mockRepoDetector,
                                                $this->mockVersionFinder,
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
        $releaseIt = new ReleaseIt($this->mockConsole,
                                   $this->mockRepoDetector,
                                   $this->mockVersionFinder,
                                   vfsStream::setup()->url()
                     );
        $this->mockConsole->expects($this->once())
                          ->method('writeErrorLine');
        $this->mockRepoDetector->expects(($this->never()))
                               ->method('detect');
        $this->assertEquals(21, $releaseIt->run());
    }

    /**
     * creates a mocked repository
     *
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockRepository()
    {
        $mockRepository = $this->createMock('org\bovigo\releaseit\repository\Repository');
        $this->mockRepoDetector->expects(($this->once()))
                               ->method('detect')
                               ->will($this->returnValue($mockRepository));
        return $mockRepository;
    }

    /**
     * @test
     */
    public function stopsWithExitCode22IfRepositoryIsDirty()
    {
        $mockRepository = $this->createMockRepository();
        $mockRepository->expects(($this->once()))
                       ->method('isDirty')
                       ->will($this->returnValue(true));
        $mockRepositoryStatus = $this->createMock('stubbles\streams\InputStream');
        $mockRepositoryStatus->expects($this->once())
                             ->method('readLine')
                             ->will($this->returnValue('A  foo.php'));
        $mockRepositoryStatus->expects($this->exactly(2))
                             ->method('eof')
                             ->will($this->onConsecutiveCalls(false, true));
        $mockRepository->expects(($this->once()))
                       ->method('readStatus')
                       ->will($this->returnValue($mockRepositoryStatus));
        $this->mockConsole->expects($this->at(1))
                          ->method('writeErrorLine')
                          ->with($this->equalTo('A  foo.php'));
        $this->assertEquals(22, $this->releaseIt->run());
    }

    /**
     * @test
     */
    public function stopsWithExitCode23IfVersionFinderCanNotProvideVersionForRelease()
    {
        $mockRepository = $this->createMockRepository();
        $mockRepository->expects(($this->once()))
                       ->method('isDirty')
                       ->will($this->returnValue(false));
        $this->mockVersionFinder->expects(($this->once()))
                               ->method('find')
                               ->will($this->returnValue(null));
        $mockRepository->expects(($this->never()))
                       ->method('createRelease');
        $this->mockConsole->expects($this->once())
                          ->method('writeErrorLine')
                          ->with($this->equalTo('Can not create release, unable to find a version for this release.'));
        $this->assertEquals(23, $this->releaseIt->run());
    }

    /**
     * @test
     */
    public function createsReleaseWithVersionDeliveredByVersionFinder()
    {
        $mockRepository = $this->createMockRepository();
        $mockRepository->expects(($this->once()))
                       ->method('isDirty')
                       ->will($this->returnValue(false));
        $version = new Version('v1.1.0');
        $this->mockVersionFinder->expects(($this->once()))
                               ->method('find')
                               ->will($this->returnValue($version));
        $mockRepository->expects(($this->once()))
                       ->method('createRelease')
                       ->with($this->equalTo($version))
                       ->will($this->returnValue(array('foo', 'bar')));
        $this->mockConsole->expects($this->once())
                          ->method('writeLines')
                          ->with($this->equalTo(array('foo', 'bar')))
                          ->will($this->returnSelf());
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('Successfully created release v1.1.0'));
        $this->assertEquals(0, $this->releaseIt->run());
    }

    /**
     * @test
     */
    public function canCreateInstance()
    {
        $this->assertInstanceOf('org\bovigo\releaseit\ReleaseIt',
                                ReleaseIt::create(new \stubbles\values\Rootpath())
        );
    }
}
