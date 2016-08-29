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
use function bovigo\assert\{assert, expect, predicate\equals};
/**
 * Test for bovigo\releaseit\Series.
 */
class SeriesTestCase extends \PHPUnit_Framework_TestCase
{
    public function invalidSeriesNumbers(): array
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
     * @dataProvider  invalidSeriesNumbers
     */
    public function createWithInvalidSeriesNumberThrowsInvalidArgumentException(string $invalidNumber)
    {
        expect(function() use ($invalidNumber) { new Series($invalidNumber); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function createWithLeadingV()
    {
        assert((string) new Series('v1.1'), equals('v1.1'));
    }

    /**
     * @test
     */
    public function createWithoutLeading()
    {
        assert((string) new Series('1.1'), equals('v1.1'));
    }

    /**
     * @test
     */
    public function deliversFirstVersionOfMajorSeries()
    {
        $series = new Series('1');
        assert($series->firstVersion(), equals(new Version('v1.0.0')));
    }

    /**
     * @test
     */
    public function deliversFirstVersionOfMinorSeries()
    {
        $series = new Series('1.1');
        assert($series->firstVersion(), equals(new Version('v1.1.0')));
    }

    /**
     * @test
     */
    public function nextVersionInMajorSeriesIncreasesMinorNumber()
    {
        $series = new Series('1');
        assert(
                $series->nextVersion(new Version('v1.1.0')),
                equals(new Version('v1.2.0'))
        );
    }

    /**
     * @test
     */
    public function nextVersionInMinorSeriesIncreasesPatchLevel()
    {
        $series = new Series('1.1');
        assert(
                $series->nextVersion(new Version('v1.1.2')),
                equals(new Version('v1.1.3'))
        );
    }
}
