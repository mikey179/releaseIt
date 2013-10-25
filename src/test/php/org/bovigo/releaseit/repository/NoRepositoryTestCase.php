<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit\repository;
use org\bovigo\releaseit\version\Version;
/**
 * Test for org\bovigo\releaseit\repository\NoRepository.
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
        $this->assertTrue($this->noRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamWithOneLine()
    {
        $this->assertEquals('Current directory is not a known type of repository',
                            $this->noRepository->readStatus()->readLine()
        );
    }

    /**
     * @test
     */
    public function hasNoLastReleases()
    {
        $this->assertEquals(array(), $this->noRepository->getLastReleases());
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\repository\RepositoryError
     */
    public function createReleaseThrowsRepositoryError()
    {
        $this->noRepository->createRelease(new Version('1.0.0'));
    }
}
