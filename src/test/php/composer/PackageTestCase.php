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
namespace bovigo\releaseit\composer;
use bovigo\releaseit\Series;
use org\bovigo\vfs\vfsStream;
/**
 * Test for bovigo\releaseit\composer\Package.
 */
class PackageTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  bovigo\releaseit\composer\InvalidPackage
     */
    public function createFromNonExistingFileThrowsInvalidPackage()
    {
        Package::fromFile('doesNotExist.json');
    }

    /**
     * @test
     * @expectedException  bovigo\releaseit\composer\InvalidPackage
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
    public function branchAliasWithoutAnyBranchAliasDefinedReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())
                                 ->branchAlias('dev-foo')
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
                                 ->branchAlias('dev-foo')
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
                                   ->branchAlias('dev-master')
        );
    }

    /**
     * @test
     */
    public function seriesWithoutAnyBranchAliasDefinedReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())->series('dev-foo'));
    }

    /**
     * @test
     */
    public function seriesForNonConfiguredBranchAliasReturnsNull()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertNull(Package::fromFile($file->url())->series('dev-foo'));
    }

    /**
     * @test
     */
    public function seriesForConfiguredBranchAlias()
    {
        $file = vfsStream::newFile('composer.json')
                         ->withContent('{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}')
                         ->at(vfsStream::setup());
        $this->assertEquals(new Series('1.0'),
                            Package::fromFile($file->url())->series('dev-master')
        );
    }
}
