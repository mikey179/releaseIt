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
use function bovigo\assert\{
    assert,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isNotSameAs
};
/**
 * Test for bovigo\releaseit\Version.
 */
class VersionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createWithInvalidVersionNumberThrowsInvalidArgumentException()
    {
        expect(function() { new Version('foo'); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function createWithLeadingV()
    {
        assert((string) new Version('v1.1.0'), equals('v1.1.0'));
    }

    /**
     * @test
     */
    public function createWithoutLeading()
    {
        assert((string) new Version('1.1.0'), equals('v1.1.0'));
    }

    /**
     * @test
     */
    public function increaseMinorReturnsNewInstance()
    {
        $version = new Version('1.1.0');
        assert($version->increaseMinor(), isNotSameAs($version));
    }

    /**
     * @test
     */
    public function increaseMinorReturnsNewVersionNumber()
    {
        $version = new Version('1.1.0');
        assert((string) $version->increaseMinor(), equals('v1.2.0'));
    }

    /**
     * @test
     */
    public function increasePatchLevelReturnsNewInstance()
    {
        $version = new Version('1.1.0');
        assert($version->increasePatchLevel(), isNotSameAs($version));
    }

    /**
     * @test
     */
    public function increasePatchLevelReturnsNewVersionNumber()
    {
        $version = new Version('1.1.0');
        assert((string) $version->increasePatchLevel(), equals('v1.1.1'));
    }
}
