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
use bovigo\releaseit\Version;

use function bovigo\assert\{
    assert,
    assertEmptyArray,
    assertEmptyString,
    assertTrue,
    expect,
    predicate\equals
};
/**
 * Test for bovigo\releaseit\repository\NoRepository.
 */
class NoRepositoryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NoRepository
     */
    private $noRepository;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->noRepository = new NoRepository();
    }

    /**
     * @test
     */
    public function isAlwaysDirty()
    {
        assertTrue($this->noRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamWithOneLine()
    {
        assert(
                $this->noRepository->readStatus()->readLine(),
                equals('Current directory is not a known type of repository')
        );
    }

    /**
     * @test
     */
    public function hasNoBranch()
    {
        assertEmptyString($this->noRepository->branch());
    }

    /**
     * @test
     */
    public function hasNoLastReleases()
    {
        assertEmptyArray($this->noRepository->lastReleases());
    }

    /**
     * @test
     */
    public function createReleaseThrowsRepositoryError()
    {
        expect(function() { $this->noRepository->createRelease(new Version('1.0.0')); })
                ->throws(RepositoryError::class);
    }
}
