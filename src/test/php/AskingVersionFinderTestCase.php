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
use stubbles\input\ValueReader;

use function stubbles\reflect\annotationsPresentOnConstructor;
/**
 * Test for bovigo\releaseit\AskingVersionFinder.
 */
class AskingVersionFinderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AskingVersionFinder
     */
    private $askingVersionFinder;
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
        $this->mockConsole         = $this->getMockBuilder(Console::class)
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $this->askingVersionFinder = new AskingVersionFinder($this->mockConsole);
        $this->package             = new Package([]);
        $this->mockRepository      = $this->createMock(Repository::class);
    }

    /**
     * @test
     */
    public function writesLastReleasesToConsole()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getLastReleases')
                             ->will($this->returnValue(['v1.0.0', 'v1.0.1']));
        $this->mockConsole->expects($this->at(1))
                          ->method('writeLine')
                          ->with($this->equalTo('v1.0.0'));
        $this->mockConsole->expects($this->at(2))
                          ->method('writeLine')
                          ->with($this->equalTo('v1.0.1'));
        $this->mockConsole->expects($this->at(4))
                          ->method('prompt')
                          ->will($this->returnValue(ValueReader::forValue('v1.1.0')));
        $this->assertEquals(new Version('v1.1.0'),
                            $this->askingVersionFinder->find($this->package, $this->mockRepository)
        );
    }

    /**
     * @test
     */
    public function repromptsUntilValidVersionNumberEntered()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getLastReleases')
                             ->will($this->returnValue([]));
        $this->mockConsole->expects($this->exactly(2))
                          ->method('prompt')
                          ->will($this->onConsecutiveCalls(ValueReader::forValue('foo'),
                                                           ValueReader::forValue('v1.1.0')
                                 )
                            );
        $this->assertEquals(new Version('v1.1.0'),
                            $this->askingVersionFinder->find($this->package, $this->mockRepository)
        );
    }
}
