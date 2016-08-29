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
    predicate\isInstanceOf,
    predicate\isSameAs
};
use function bovigo\callmap\{verify, throws};
/**
 * Test for bovigo\releaseit\repository\SvnRepository.
 */
class SvnRepositoryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  SvnRepository
     */
    private $svnRepository;
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
        $this->executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/trunk']
        ]);
        $this->svnRepository = new SvnRepository($this->executor);
        $this->root          = vfsStream::setup();
    }

    /**
     * @test
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoFails()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'execute' => throws(new \RuntimeException('error'))
        ]);
        expect(function() use ($executor) { new SvnRepository($executor); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while checking svn info');
    }

    /**
     * @test
     */
    public function createInstanceThrowsRepositoryErrorWhenSvnInfoDoesNotContainSvnUrl()
    {
        $executor = NewInstance::of(Executor::class)->returns(['outputOf' => []]);
        expect(function() use ($executor) { new SvnRepository($executor); })
                ->throws(RepositoryError::class)
                ->withMessage(
                        'Could not retrieve svn tag url, can not create release for this svn repository'
                );
    }

    /**
     * @test
     */
    public function createInstanceThrowsRepositoryErrorWhenTagUrlCanNotBeDerivedFromSvnUrl()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo']
        ]);
        expect(function() use ($executor) { new SvnRepository($executor); })
                ->throws(RepositoryError::class)
                ->withMessage(
                        'Can not extract tag url from current svn checkout url http://svn.example.org/svn/foo'
                );
    }

    /**
     * @test
     */
    public function canCreateInstanceFromBranch()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/branches/v1.1.x']
        ]);
        expect(function() use ($executor) { new SvnRepository($executor); })
                ->doesNotThrow();
    }

    /**
     * @test
     */
    public function isDirtyThrowsRepositoryErrorWhenSvnStatusFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->svnRepository->isDirty(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while checking svn status');
    }

    /**
     * @test
     */
    public function isDirtyReturnsTrueWhenCheckoutContainsUncomittedChanges()
    {
        $this->executor->returns(['outputOf' => ['A  readme.md']]);
        assertTrue($this->svnRepository->isDirty());
    }

    /**
     * @test
     */
    public function isDirtyReturnsFalseWhenCheckoutContainsNoUncomittedChanges()
    {
        $this->executor->returns(['outputOf' => []]);
        assertFalse($this->svnRepository->isDirty());
    }

    /**
     * @test
     */
    public function readStatusReturnsInputStreamToReadResultFrom()
    {
        $inputStream = NewInstance::of(InputStream::class);
        $this->executor->returns(['executeAsync' => $inputStream]);
        assert($this->svnRepository->readStatus(), isSameAs($inputStream));
    }

    /**
     * @test
     */
    public function branchReturnsTrunkWhenWorkspaceIsTrunkCheckout()
    {
        assert($this->svnRepository->branch(), equals('trunk'));
    }

    /**
     * @test
     */
    public function branchReturnsBranchNameWhenWorkspaceIsBranchCheckout()
    {
        $executor = NewInstance::of(Executor::class)->returns([
                'outputOf' => ['URL: http://svn.example.org/svn/foo/branches/cool-new-feature']
        ]);
        $svnRepository = new SvnRepository($executor);
        assert($svnRepository->branch(), equals('cool-new-feature'));
    }

    /**
     * @test
     */
    public function lastReleasesThrowsRepositoryErrorWhenSvnListFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->svnRepository->lastReleases(); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while retrieving last releases');
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithVWhenNoSeriesGiven()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0/', 'v1.0.1/']]);
        $this->svnRepository->lastReleases();
        verify($this->executor, 'execute')->receivedOn(
                2,
                'svn list http://svn.example.org/svn/foo/tags | grep "v" | sort -r | head -5'
        );
    }

    /**
     * @test
     */
    public function lastReleasesReturnsListOfLastReleases()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0/', 'v1.0.1/']]);
        assert(
                $this->svnRepository->lastReleases(new Series('1.0'), 2),
                equals(['v1.0.0', 'v1.0.1'])
        );
    }

    /**
     * @test
     */
    public function lastReleasesGrepsForTagsStartingWithGivenSeries()
    {
        $this->executor->returns(['outputOf' => ['v1.0.0/', 'v1.0.1/']]);
        $this->svnRepository->lastReleases(new Series('1.0'), 2);
        verify($this->executor, 'execute')->receivedOn(
                2,
                'svn list http://svn.example.org/svn/foo/tags | grep "v1.0" | sort -r | head -2'
        );
    }

    /**
     * @test
     */
    public function createReleaseThrowsRepositoryErrorWhenSvnCpFails()
    {
        $this->executor->returns([
                    'outputOf' => throws(new \RuntimeException('error'))
        ]);
        expect(function() { $this->svnRepository->createRelease(new Version('1.1.0')); })
                ->throws(RepositoryError::class)
                ->withMessage('Failure while creating release');
    }

    /**
     * @test
     */
    public function createReleaseReturnsOutputFromCreatingTag()
    {
        $svnCpOutput = ['Committed revision 303.'];
        $this->executor->returns(['outputOf' => $svnCpOutput]);
        assert(
                $this->svnRepository->createRelease(new Version('1.1.0')),
                equals($svnCpOutput)
        );
        verify($this->executor, 'outputOf')->receivedOn(
                2,
                'svn cp . http://svn.example.org/svn/foo/tags/v1.1.0 -m "tag release v1.1.0"'
        );
    }
}
