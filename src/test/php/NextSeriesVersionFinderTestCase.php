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
use bovigo\callmap\NewInstance;
use bovigo\releaseit\composer\Package;
use bovigo\releaseit\repository\Repository;
use stubbles\console\Console;

use function bovigo\callmap\verify;
/**
 * Test for bovigo\releaseit\NextSeriesVersionFinder.
 */
class NextSeriesVersionFinderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NextSeriesVersionFinder
     */
    private $nextSeriesVersionFinder;
    /**
     * @type  Console
     */
    private $console;
    /**
     * package to create release for
     *
     * @type  Package
     */
    private $package;
    /**
     * @type  Repository
     */
    private $repository;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->console                 = NewInstance::stub(Console::class);
        $this->nextSeriesVersionFinder = new NextSeriesVersionFinder($this->console);
        $this->package                 = new Package(
                ['extra' => ['branch-alias' => ['dev-master' => '1.0.x-dev']]]
        );
        $this->repository              = NewInstance::of(Repository::class);
    }

    /**
     * @test
     */
    public function canNotFindVersionIfSeriesCanNotBeDeterminedFromBranch()
    {
        $this->repository->returns(['branch' => 'cool-new-feature']);
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->repository));
        verify($this->console, 'writeLine')->received(
                'Can not determine current series for branch cool-new-feature'
        );
    }

    /**
     * @test
     */
    public function returnsNoVersionIfUserDeniesFirstVersionInSeries()
    {
        $this->repository->returns([
                'branch'       => 'master',
                'lastReleases' => []
        ]);
        $this->console->returns(['confirm' => false]);
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->repository));
        verify($this->repository, 'lastReleases')->received(new Series('1.0'));
        verify($this->console, 'writeLine')->received(
                'No release in series v1.0 yet, determined v1.0.0 as first version number.'
        );
    }

    /**
     * @test
     */
    public function returnsFirstVersionInSeriesIfNoReleaseAvailableInThisSeries()
    {
        $this->repository->returns([
                'branch'       => 'master',
                'lastReleases' => []
        ]);
        $this->console->returns(['confirm' => true]);
        $this->assertEquals(new Version('v1.0.0'),
                            $this->nextSeriesVersionFinder->find($this->package, $this->repository)
        );
        verify($this->console, 'writeLine')->received(
                'No release in series v1.0 yet, determined v1.0.0 as first version number.'
        );
    }

    /**
     * @test
     */
    public function returnsNoVersionIfUserDeniesNextVersionInSeries()
    {
        $this->repository->returns([
                'branch'       => 'master',
                'lastReleases' => ['v1.0.1']
        ]);
        $this->console->returns(['confirm' => false]);
        $this->assertNull($this->nextSeriesVersionFinder->find($this->package, $this->repository));
        verify($this->console, 'writeLine')->received(
                'Last release in series v1.0 was v1.0.1, determined v1.0.2 as next version number.'
        );
    }

    /**
     * @test
     */
    public function returnsNextVersionInSeriesIfReleasesAvailableInThisSeries()
    {
        $this->repository->returns([
                'branch'       => 'master',
                'lastReleases' => ['v1.0.1']
        ]);
        $this->console->returns(['confirm' => true]);
        $this->assertEquals(new Version('v1.0.2'),
                            $this->nextSeriesVersionFinder->find($this->package, $this->repository)
        );
        verify($this->console, 'writeLine')->received(
                'Last release in series v1.0 was v1.0.1, determined v1.0.2 as next version number.'
        );
    }
}
