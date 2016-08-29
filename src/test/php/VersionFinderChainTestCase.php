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
use bovigo\callmap\NewInstance;
use bovigo\releaseit\composer\Package;
use bovigo\releaseit\repository\Repository;

use function bovigo\assert\{
    assert,
    assertNull,
    assertTrue,
    predicate\equals
};
use function bovigo\callmap\verify;
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
     * @type  Repository
     */
    private $repository;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->package    = new Package([]);
        $this->repository = NewInstance::of(Repository::class);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        assertTrue(
                annotationsOfConstructor(VersionFinderChain::class)->contain('List')
        );
    }

    /**
     * @test
     */
    public function isDefaultImplementationForVersionFinder()
    {
        assert(
                annotationsOf(VersionFinder::class)
                        ->firstNamed('ImplementedBy')
                        ->getValue()
                        ->getName(),
                equals(VersionFinderChain::class)
        );
    }

    private function createVersionFinder($find = null): VersionFinder
    {
        return NewInstance::of(VersionFinder::class)->returns([
                'find' => $find
        ]);
    }

    /**
     * @test
     */
    public function returnsNoVersionIfNoFinderReturnsOne()
    {
        $versionFinder1 = $this->createVersionFinder();
        $versionFinder2 = $this->createVersionFinder();
        $versionFinderChain = new VersionFinderChain([
                $versionFinder1, $versionFinder2
        ]);
        assertNull($versionFinderChain->find($this->package, $this->repository));
    }

    /**
     * @test
     */
    public function returnsVersionByFirstFinderWhichReturnsOne()
    {
        $version = new Version('1.0.1');
        $versionFinder1 = $this->createVersionFinder();
        $versionFinder2 = $this->createVersionFinder($version);
        $versionFinder3 = $this->createVersionFinder();
        $versionFinderChain = new VersionFinderChain([
                $versionFinder1, $versionFinder2, $versionFinder3
        ]);
        assert(
                $versionFinderChain->find($this->package, $this->repository),
                equals($version)
        );
        verify($versionFinder3, 'find')->wasNeverCalled();
    }
}
