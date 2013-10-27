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
use net\stubbles\lang;
use org\bovigo\releaseit\composer\Package;
/**
 * Test for org\bovigo\releaseit\VersionFinderChain.
 */
class VersionFinderChainTestCase extends \PHPUnit_Framework_TestCase
{
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
        $this->package            = new Package(array());
        $this->mockRepository     = $this->getMock('org\bovigo\releaseit\repository\Repository');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $constructor = lang\reflectConstructor('org\bovigo\releaseit\VersionFinderChain');
        $this->assertTrue($constructor->hasAnnotation('Inject'));
        $this->assertTrue($constructor->hasAnnotation('List'));
    }

    /**
     * @test
     */
    public function isDefaultImplementationForVersionFinder()
    {
        $this->assertEquals('org\bovigo\releaseit\VersionFinderChain',
                            lang\reflect('org\bovigo\releaseit\VersionFinder')
                                  ->getAnnotation('ImplementedBy')
                                  ->getValue()
                                  ->getName()
        );
    }

    /**
     * @test
     */
    public function returnsNoVersionIfNoFinderReturnsOne()
    {
        $mockVersionFinder1 = $this->getMock('org\bovigo\releaseit\VersionFinder');
        $mockVersionFinder1->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $mockVersionFinder2 = $this->getMock('org\bovigo\releaseit\VersionFinder');
        $mockVersionFinder2->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $versionFinderChain = new VersionFinderChain(array($mockVersionFinder1, $mockVersionFinder2));
        $this->assertNull($versionFinderChain->find($this->package, $this->mockRepository));
    }

    /**
     * @test
     */
    public function returnsVersionByFirstFinderWhichReturnsOne()
    {
        $version = new Version('1.0.1');
        $mockVersionFinder1 = $this->getMock('org\bovigo\releaseit\VersionFinder');
        $mockVersionFinder1->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $mockVersionFinder2 = $this->getMock('org\bovigo\releaseit\VersionFinder');
        $mockVersionFinder2->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue($version));
        $mockVersionFinder3 = $this->getMock('org\bovigo\releaseit\VersionFinder');
        $mockVersionFinder3->expects($this->never())
                           ->method('find');
        $versionFinderChain = new VersionFinderChain(array($mockVersionFinder1, $mockVersionFinder2, $mockVersionFinder3));
        $this->assertEquals($version, $versionFinderChain->find($this->package, $this->mockRepository));
    }
}