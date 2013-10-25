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
use net\stubbles\lang;
/**
 * Test for org\bovigo\releaseit\ReleaseIt.
 */
class ReleaseItTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ReleaseIt
     */
    private $instance;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->instance = new ReleaseIt($this->getMock('net\stubbles\streams\OutputStream'));
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->instance)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function returnsExitCode0()
    {
        $this->assertEquals(0, $this->instance->run());
    }

    /**
     * @test
     */
    public function canCreateInstance()
    {
        $this->assertInstanceOf('org\bovigo\releaseit\ReleaseIt',
                                ReleaseIt::create(\net\stubbles\lang\ResourceLoader::getRootPath())
        );
    }
}
