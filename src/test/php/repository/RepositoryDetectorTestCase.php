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
namespace bovigo\releaseit\repository;
use bovigo\callmap\NewInstance;
use org\bovigo\vfs\vfsStream;
use stubbles\console\Executor;

use function bovigo\assert\{assert, predicate\isInstanceOf};
/**
 * Test for bovigo\releaseit\repository\RepositoryDetector.
 */
class RepositoryDetectorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  RepositoryDetector
     */
    private $repositoryDetector;
    /**
     * @type  Executor
     */
    private $executor;
    /**
     * root path
     *
     * @type  org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->executor           = NewInstance::of(Executor::class);
        $this->repositoryDetector = new RepositoryDetector($this->executor);
        $this->root               = vfsStream::setup();
    }

    /**
     * @test
     */
    public function missingRepositoryInfoFolderResultsInNoRepository()
    {
        assert(
                $this->repositoryDetector->detect($this->root->url()),
                isInstanceOf(NoRepository::class)
        );
    }

    /**
     * @test
     */
    public function gitFolderResultsInGitRepository()
    {
        vfsStream::newDirectory('.git')->at($this->root);
        assert(
                $this->repositoryDetector->detect($this->root->url()),
                isInstanceOf(GitRepository::class)
        );
    }
}
