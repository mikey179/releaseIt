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
use bovigo\releaseit\composer\Package;
use bovigo\releaseit\repository\Repository;
use stubbles\console\Console;
/**
 * Test for bovigo\releaseit\NextSeriesVersionFinder.
 */
class NextSeriesVersionFinderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NextSeriesVersionFinder
     */
    private $nextSeriesVersionFinder;
    /**
     * mocked console interface
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockConsole;
    /**
     * package to create release for
     *
     * @type  Package
     */
    private $package;
    /**
     * mocked repository to create release from
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRepository;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockConsole             = $this->getMockBuilder(Console::class)
                                              ->disableOriginalConstructor()
                                              ->getMock();
        $this->nextSeriesVersionFinder = new NextSeriesVersionFinder($this->mockConsole);
        $this->package                 = new Package(['extra' => ['branch-alias' => ['dev-master' => '1.0.x-dev']]]);
        $this->mockRepository          = $this->createMock(Repository::class);
    }

    /**
     * @test
     */
    public function canNotFindVersionIfSeriesCanNotBeDeterminedFromBranch()
    {
        $this->mockRepository->expects($this->any())
                             ->method('getBranch')
                             ->will($this->returnValue('cool-new-feature'));
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('Can not determine current series for branch cool-new-feature'));
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->mockRepository));
    }

    /**
     * @test
     */
    public function returnsNoVersionIfUserDeniesFirstVersionInSeries()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getBranch')
                             ->will($this->returnValue('master'));
        $this->mockRepository->expects(($this->once()))
                             ->method('getLastReleases')
                             ->with($this->equalTo(new Series('1.0')), $this->equalTo(1))
                             ->will($this->returnValue([]));
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('No release in series v1.0 yet, determined v1.0.0 as first version number.'));
        $this->mockConsole->expects($this->once())
                          ->method('confirm')
                          ->will($this->returnValue(false));
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->mockRepository));
    }

    /**
     * @test
     */
    public function returnsFirstVersionInSeriesIfNoReleaseAvailableInThisSeries()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getBranch')
                             ->will($this->returnValue('master'));
        $this->mockRepository->expects(($this->once()))
                             ->method('getLastReleases')
                             ->with($this->equalTo(new Series('1.0')), $this->equalTo(1))
                             ->will($this->returnValue([]));
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('No release in series v1.0 yet, determined v1.0.0 as first version number.'));
        $this->mockConsole->expects($this->once())
                          ->method('confirm')
                          ->will($this->returnValue(true));
        $this->assertEquals(new Version('v1.0.0'),
                            $this->nextSeriesVersionFinder->find($this->package, $this->mockRepository)
        );
    }

    /**
     * @test
     */
    public function returnsNoVersionIfUserDeniesNextVersionInSeries()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getBranch')
                             ->will($this->returnValue('master'));
        $this->mockRepository->expects(($this->once()))
                             ->method('getLastReleases')
                             ->with($this->equalTo(new Series('1.0')), $this->equalTo(1))
                             ->will($this->returnValue(['v1.0.1']));
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('Last release in series v1.0 was v1.0.1, determined v1.0.2 as next version number.'));
        $this->mockConsole->expects($this->once())
                          ->method('confirm')
                          ->will($this->returnValue(false));
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->mockRepository));
    }

    /**
     * @test
     */
    public function returnsNextVersionInSeriesIfReleasesAvailableInThisSeries()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getBranch')
                             ->will($this->returnValue('master'));
        $this->mockRepository->expects(($this->once()))
                             ->method('getLastReleases')
                             ->with($this->equalTo(new Series('1.0')), $this->equalTo(1))
                             ->will($this->returnValue(['v1.0.1']));
        $this->mockConsole->expects($this->once())
                          ->method('writeLine')
                          ->with($this->equalTo('Last release in series v1.0 was v1.0.1, determined v1.0.2 as next version number.'));
        $this->mockConsole->expects($this->once())
                          ->method('confirm')
                          ->will($this->returnValue(true));
        $this->assertEquals(new Version('v1.0.2'),
                            $this->nextSeriesVersionFinder->find($this->package, $this->mockRepository)
        );
    }
}
