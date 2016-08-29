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
use bovigo\releaseit\{Series, Version};
use org\bovigo\vfs\vfsStream;
use stubbles\console\Executor;
use stubbles\streams\InputStream;

use function bovigo\assert\{
    assert,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isSameAs
};
use function bovigo\callmap\{verify, throws};
/**
 * Test for bovigo\releaseit\repository\GitRepository.
 */
class GitRepositoryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  GitRepository
     */
    private $gitRepository;
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
        $this->executor      = NewInstance::of(Executor::class);
        $this->gitRepository = new GitRepository($this->executor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     */
    public function isDirtyThrowsRepositoryErrorWhenGitStatusFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->gitRepository->isDirty(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while checking git status');
    }

    /**
     * @test
     */
    public function isDirtyThrowsRepositoryErrorWhenCurrentFolderIsNoGitRepository()
    {
        $this->executor->returns(['outputOf' => []]);
        expect(function() { $this->gitRepository->isDirty(); })
                ->throws(RepositoryError::class)
                ->withMessage('Current directory is not a git repository');
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenRepositoryContainsUncomittedChanges()
    {
        $this->executor->returns([
                    'outputOf' => ['# Changes to be committed:']
        ]);
        assertTrue($this->gitRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenRepositoryIsClean()
    {
        $this->executor->returns([
                    'outputOf' => ['nothing to commit, working directory clean']
        ]);
        assertFalse($this->gitRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamToReadResultFrom()
    {
        $inputStream = NewInstance::of(InputStream::class);
        $this->executor->returns([
                    'executeAsync' => $inputStream
        ]);
        assert(
                $this->gitRepository->readStatus(),
                isSameAs($inputStream)
        );
    }

    /**
     * @test
     */
    public function branchThrowsRepositoryErrorWhenGitBranchFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->gitRepository->branch(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while retrieving current branch');
    }

    /**
     * @test
     */
    public function branchThrowsRepositoryErrorWhenGitBranchReturnsNoOutput()
    {
        $this->executor->returns(['outputOf' => []]);
        expect(function() { $this->gitRepository->branch(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while retrieving current branch');
    }

    /**
     * @test
     */
    public function branchReturnsCurrentBranchName()
    {
        $this->executor->returns(['outputOf' => ['* master']]);
        assert($this->gitRepository->branch(), equals('master'));
    }

    /**
     * @test
     */
    public function lastReleasesThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->gitRepository->lastReleases(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while retrieving last releases');
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithVWhenNoSeriesGiven()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0', 'v1.0.1']]);
        $this->gitRepository->lastReleases();
        verify($this->executor, 'execute')->received(
                'git tag -l | grep "v" | sort -r | head -5'
        );
    }

    /**
     * @test
     */
    public function lastReleasesReturnsListOfLastReleases()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0', 'v1.0.1']]);
        assert(
                $this->gitRepository->lastReleases(new Series('1.0'), 2),
                equals(['v1.0.0', 'v1.0.1'])
        );
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithGivenSeries()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0', 'v1.0.1']]);
        $this->gitRepository->lastReleases(new Series('1.0'), 2);
        verify($this->executor, 'execute')->received(
                'git tag -l | grep "v1.0" | sort -r | head -2'
        );
    }

    /**
     * @test
     */
    public function createReleaseThrowsRepositoryErrorWhenGitTagFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() {
                $this->gitRepository->createRelease(new Version('1.1.0'));
        })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while creating release');
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $gitTagOutput = [
                'Counting objects: 1, done.',
                'Writing objects: 100% (1/1), 159 bytes | 0 bytes/s, done.',
                'Total 1 (delta 0), reused 0 (delta 0)',
                'To git@github.com:example/foo.git',
                ' * [new tag]         v1.1.0 -> v1.1.0'
        ];
        $this->executor->returns([
                    'outputOf' => $gitTagOutput
        ]);
        assert(
                $this->gitRepository->createRelease(new Version('1.1.0')),
                equals($gitTagOutput)
        );
    }
}
