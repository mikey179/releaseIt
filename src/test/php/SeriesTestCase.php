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
/**
 * Test for bovigo\releaseit\Series.
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
        return [
                ['foo'],
                ['1.0.x-dev'],
                ['1.0.1'],
                ['v1.0.1']
        ];
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
