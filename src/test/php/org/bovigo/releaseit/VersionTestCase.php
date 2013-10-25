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
/**
 * Test for org\bovigo\releaseit\Version.
 */
class VersionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
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
}
