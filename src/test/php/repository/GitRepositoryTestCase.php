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
use bovigo\releaseit\{Key, Series, Version};
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
     * set up test environment
     */
    public function setUp()
    {
        $this->executor      = NewInstance::of(Executor::class);
        $this->gitRepository = new GitRepository(__DIR__, $this->executor);
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
                ->withMessage('Failure while retrieving current branch: no branches available');
    }

    /**
     * @test
     * @since  2.0.0
     */
    public function branchThrowsRepositoryErrorWhenGitBranchReturnsBranchesWithoutSelection()
    {
        $this->executor->returns(['outputOf' => ['master', 'feature/foo', 'bug/fix']]);
        expect(function() { $this->gitRepository->branch(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while retrieving current branch: no branches available');
    }

    /**
     * @since  2.0.0
     */
    public function branches(): array
    {
        return [
                ['single line'          => ['* master']],
                ['multi line, issue #7' => ['feature/foo', '* master', 'bug/fix']]
        ];
    }

    /**
     * @test
     * @dataProvider  branches
     */
    public function branchReturnsCurrentBranchName(array $output)
    {
        $this->executor->returns(['outputOf' => $output]);
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
                'git -C ' . __DIR__ . ' tag -l | grep "v" | sort -r | head -5'
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
                'git -C ' . __DIR__ . ' tag -l | grep "v1.0" | sort -r | head -2'
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

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createReleaseDoesNotSignReleaseWhenNoKeyProvided()
    {
        $this->executor->returns(['outputOf' => []]);
        $this->gitRepository->createRelease(new Version('1.1.0'));
        verify($this->executor, 'execute')->received(
                'git -C ' . __DIR__ . ' tag -a v1.1.0 -m "tag release v1.1.0" && git push --tags'
        );
    }

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createReleaseUsesDefaultKeyWhenNoKeyIdSpecified()
    {
        $this->executor->returns(['outputOf' => []]);
        $this->gitRepository->createRelease(new Version('1.1.0'), Key::default());
        verify($this->executor, 'execute')->received(
                'git -C ' . __DIR__ . ' tag -s -a v1.1.0 -m "tag release v1.1.0" && git push --tags'
        );
    }

    /**
     * @test
     * @group  issue_11
     * @since  2.0.0
     */
    public function createReleaseUsesGivenKeyWhenKeyIdSpecified()
    {
        $this->executor->returns(['outputOf' => []]);
        $this->gitRepository->createRelease(new Version('1.1.0'), new Key('abc123'));
        verify($this->executor, 'execute')->received(
                'git -C ' . __DIR__ . ' tag -u abc123 -a v1.1.0 -m "tag release v1.1.0" && git push --tags'
        );
    }
}
