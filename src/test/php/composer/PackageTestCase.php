<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\releaseit
 */
namespace org\bovigo\releaseit\composer;
use org\bovigo\releaseit\Series;
use org\bovigo\vfs\vfsStream;
/**
 * Test for org\bovigo\releaseit\composer\Package.
 */
class PackageTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  org\bovigo\releaseit\composer\InvalidPackage
     */
    public function createFromNonExistingFileThrowsInvalidPackage()
    {
        Package::fromFile('doesNotExist.json');
    }

    /**
     * @test
     * @expectedException  org\bovigo\releaseit\composer\InvalidPackage
     */
    public function createFromInvalidJsonFileThrowsInvalidPackage()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('invalid')
                         ->at(vfsStream::setup());
        Package::fromFile($file->url());
    }

    /**
     * @test
     */
    public function getBranchAliasWithoutAnyBranchAliasDefinedReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())
                                 ->getBranchAlias('dev-foo')
        );
    }

    /**
     * @test
     */
    public function getNonConfiguredBranchAliasReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())
                                 ->getBranchAlias('dev-foo')
        );
    }

    /**
     * @test
     */
    public function getConfiguredBranchAliasReturnsVersionInfo()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertEquals('1.0.x-dev',
                            Package::fromFile($file->url())
                                   ->getBranchAlias('dev-master')
        );
    }

    /**
     * @test
     */
    public function getSeriesWithoutAnyBranchAliasDefinedReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())
                                 ->getSeries('dev-foo')
        );
    }

    /**
     * @test
     */
    public function getSeriesForNonConfiguredBranchAliasReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())
                                 ->getSeries('dev-foo')
        );
    }

    /**
     * @test
     */
    public function getSeriesForConfiguredBranchAlias()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertEquals(new Series('1.0'),
                            Package::fromFile($file->url())
                                   ->getSeries('dev-master')
        );
    }
}
