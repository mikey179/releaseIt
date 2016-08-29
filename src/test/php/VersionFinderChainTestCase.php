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

use function stubbles\reflect\annotationsOf;
use function stubbles\reflect\annotationsOfConstructor;
/**
 * Test for bovigo\releaseit\VersionFinderChain.
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
        $this->package        = new Package([]);
        $this->mockRepository = $this->createMock(Repository::class);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(annotationsOfConstructor(VersionFinderChain::class)->contain('List'));
    }

    /**
     * @test
     */
    public function isDefaultImplementationForVersionFinder()
    {
        $this->assertEquals(VersionFinderChain::class,
                            annotationsOf(VersionFinder::class)
                                  ->firstNamed('ImplementedBy')
                                  ->getValue()
                                  ->getName()
        );
    }

    /**
     * @test
     */
    public function returnsNoVersionIfNoFinderReturnsOne()
    {
        $mockVersionFinder1 = $this->createMock(VersionFinder::class);
        $mockVersionFinder1->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $mockVersionFinder2 = $this->createMock(VersionFinder::class);
        $mockVersionFinder2->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $versionFinderChain = new VersionFinderChain([$mockVersionFinder1, $mockVersionFinder2]);
        $this->assertNull($versionFinderChain->find($this->package, $this->mockRepository));
    }

    /**
     * @test
     */
    public function returnsVersionByFirstFinderWhichReturnsOne()
    {
        $version = new Version('1.0.1');
        $mockVersionFinder1 = $this->createMock(VersionFinder::class);
        $mockVersionFinder1->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue(null));
        $mockVersionFinder2 = $this->createMock(VersionFinder::class);
        $mockVersionFinder2->expects($this->once())
                           ->method('find')
                           ->will($this->returnValue($version));
        $mockVersionFinder3 = $this->createMock(VersionFinder::class);
        $mockVersionFinder3->expects($this->never())
                           ->method('find');
        $versionFinderChain = new VersionFinderChain([$mockVersionFinder1, $mockVersionFinder2, $mockVersionFinder3]);
        $this->assertEquals($version, $versionFinderChain->find($this->package, $this->mockRepository));
    }
}
