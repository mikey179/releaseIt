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
 * Test for org\bovigo\releaseit\Series.
 */
class SeriesTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * data provider for invalid series numbers
     *
     * @return  array
     */
    public function getInvalidSeriesNumbers()
    {
        return array(array('foo'),
                     array('1.0.x-dev'),
                     array('1.0.1'),
                     array('v1.0.1')
        );
    }
    /**
     * @test
     * @dataProvider  getInvalidSeriesNumbers
     * @expectedException  \InvalidArgumentException
     */
    public function createWithInvalidSeriesNumberThrowsIllegalArgumentException($invalidNumber)
    {
        new Series($invalidNumber);
    }

    /**
     * @test
     */
    public function createWithLeadingV()
    {
        $this->assertEquals('v1.1', (string) new Series('v1.1'));
    }

    /**
     * @test
     */
    public function createWithoutLeading()
    {
        $this->assertEquals('v1.1', (string) new Series('1.1'));
    }

    /**
     * @test
     */
    public function deliversFirstVersionOfMajorSeries()
    {
        $series = new Series('1');
        $this->assertEquals(new Version('v1.0.0'), $series->getFirstVersion());
    }

    /**
     * @test
     */
    public function deliversFirstVersionOfMinorSeries()
    {
        $series = new Series('1.1');
        $this->assertEquals(new Version('v1.1.0'), $series->getFirstVersion());
    }

    /**
     * @test
     */
    public function nextVersionInMajorSeriesIncreasesMinorNumber()
    {
        $series = new Series('1');
        $this->assertEquals(new Version('v1.2.0'), $series->getNextVersion(new Version('v1.1.0')));
    }

    /**
     * @test
     */
    public function nextVersionInMinorSeriesIncreasesPatchLevel()
    {
        $series = new Series('1.1');
        $this->assertEquals(new Version('v1.1.3'), $series->getNextVersion(new Version('v1.1.2')));
    }
}
