<?php
/**
 * This file is part of ReleaseIt.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  bovigo\releaseit
 */
namespace bovigo\releaseit;
/**
 * Test for bovigo\releaseit\Version.
 */
class VersionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  \InvalidArgumentException
     */
    public function createWithInvalidVersionNumberThrowsIllegalArgumentException()
    {
        new Version('foo');
    }

    /**
     * @test
     */
    public function createWithLeadingV()
    {
        $this->assertEquals('v1.1.0', (string) new Version('v1.1.0'));
    }

    /**
     * @test
     */
    public function createWithoutLeading()
    {
        $this->assertEquals('v1.1.0', (string) new Version('1.1.0'));
    }

    /**
     * @test
     */
    public function increaseMinorReturnsNewInstance()
    {
        $version = new Version('1.1.0');
        $this->assertNotSame($version, $version->increaseMinor());
    }

    /**
     * @test
     */
    public function increaseMinorReturnsNewVersionNumber()
    {
        $version = new Version('1.1.0');
        $this->assertEquals('v1.2.0', (string) $version->increaseMinor());
    }

    /**
     * @test
     */
    public function increasePatchLevelReturnsNewInstance()
    {
        $version = new Version('1.1.0');
        $this->assertNotSame($version, $version->increasePatchLevel());
    }

    /**
     * @test
     */
    public function increasePatchLevelReturnsNewVersionNumber()
    {
        $version = new Version('1.1.0');
        $this->assertEquals('v1.1.1', (string) $version->increasePatchLevel());
    }
}
