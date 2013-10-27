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
use net\stubbles\input\ValueReader;
use net\stubbles\lang;
use org\bovigo\releaseit\composer\Package;
/**
 * Test for org\bovigo\releaseit\AskingVersionFinder.
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
        $this->mockConsole         = $this->getMockBuilder('net\stubbles\console\Console')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $this->askingVersionFinder = new AskingVersionFinder($this->mockConsole);
        $this->package             = new Package(array());
        $this->mockRepository      = $this->getMock('org\bovigo\releaseit\repository\Repository');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->askingVersionFinder)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function writesLastReleasesToConsole()
    {
        $this->mockRepository->expects($this->once())
                             ->method('getLastReleases')
                             ->will($this->returnValue(array('v1.0.0', 'v1.0.1')));
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
                             ->will($this->returnValue(array()));
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