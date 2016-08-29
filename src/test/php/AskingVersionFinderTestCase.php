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
use stubbles\input\ValueReader;

use function bovigo\assert\{assert, predicate\equals};
use function bovigo\callmap\{verify, onConsecutiveCalls, throws};
use function stubbles\reflect\annotationsPresentOnConstructor;
/**
 * Test for bovigo\releaseit\AskingVersionFinder.
 */
class AskingVersionFinderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AskingVersionFinder
     */
    private $askingVersionFinder;
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
        $this->console             = NewInstance::stub(Console::class);
        $this->askingVersionFinder = new AskingVersionFinder($this->console);
        $this->package             = new Package([]);
        $this->repository          = NewInstance::of(Repository::class);
    }

    /**
     * @test
     */
    public function writesLastReleasesToConsole()
    {
        $this->repository->returns(['lastReleases' => ['v1.0.0', 'v1.0.1']]);
        $this->console->returns(['prompt' => ValueReader::forValue('v1.1.0')]);
        assert(
                $this->askingVersionFinder->find($this->package, $this->repository),
                equals(new Version('v1.1.0'))
        );
        verify($this->console, 'writeLine')->receivedOn(2, 'v1.0.0');
        verify($this->console, 'writeLine')->receivedOn(3, 'v1.0.1');
    }

    /**
     * @test
     */
    public function repromptsUntilValidVersionNumberEntered()
    {
        $this->repository->returns(['lastReleases' => []]);
        $this->console->returns(['prompt' => onConsecutiveCalls(
                ValueReader::forValue('foo'),
                ValueReader::forValue('v1.1.0')
        )]);
        assert(
                $this->askingVersionFinder->find($this->package, $this->repository),
                equals(new Version('v1.1.0'))
        );
    }
}
