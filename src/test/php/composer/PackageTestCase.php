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

use function bovigo\assert\{
    assert,
    assertNull,
    expect,
    predicate\equals
};
/**
 * Test for bovigo\releaseit\composer\Package.
 */
class PackageTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createFromNonExistingFileThrowsInvalidPackage()
    {
        expect(function() { Package::fromFile('doesNotExist.json'); })
                ->throws(InvalidPackage::class);
    }

    private function composerJson($content): string
    {
        return vfsStream::newFile('composer.json')
                ->withContent($content)
                ->at(vfsStream::setup())
                ->url();
    }

    /**
     * @test
     */
    public function createFromInvalidJsonFileThrowsInvalidPackage()
    {
        $file = $this->composerJson('invalid');
        expect(function() use ($file) { Package::fromFile($file); })
                ->throws(InvalidPackage::class);

    }

    /**
     * @test
     */
    public function branchAliasWithoutAnyBranchAliasDefinedReturnsNull()
    {
        assertNull(
                Package::fromFile($this->composerJson('{}'))
                        ->branchAlias('dev-foo')
        );
    }

    /**
     * @test
     */
    public function nonConfiguredBranchAliasReturnsNull()
    {
        assertNull(
                Package::fromFile($this->composerJson(
                        '{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}'
                ))->branchAlias('dev-foo')
        );
    }

    /**
     * @test
     */
    public function getConfiguredBranchAliasReturnsVersionInfo()
    {
        assert(
                Package::fromFile($this->composerJson(
                        '{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}'
                ))->branchAlias('dev-master'),
                equals('1.0.x-dev')
        );
    }

    /**
     * @test
     */
    public function seriesWithoutAnyBranchAliasDefinedReturnsNull()
    {
        assertNull(
                Package::fromFile($this->composerJson('{}'))->series('dev-foo')
        );
    }

    /**
     * @test
     */
    public function seriesForNonConfiguredBranchAliasReturnsNull()
    {
        assertNull(
                Package::fromFile($this->composerJson(
                        '{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}'
                ))->series('dev-foo')
        );
    }

    /**
     * @test
     */
    public function seriesForConfiguredBranchAlias()
    {
        assert(
                Package::fromFile($this->composerJson(
                        '{"extra": { "branch-alias": { "dev-master": "1.0.x-dev"}}}'
                ))->series('dev-master'),
                equals(new Series('1.0'))
        );
    }
}
