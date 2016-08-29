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
namespace bovigo\releaseit\composer;
use bovigo\releaseit\Series;
use Hampel\Json\Json;
use Hampel\Json\JsonException;
/**
 * Represents a composer package.
 */
class Package
{
    /**
     * package config
     *
     * @type  array
     */
    private $config;

    /**
     * constructor
     *
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * reads a package from its composer.json config
     *
     * @param   string  $filename
     * @return  Package
     * @throws  InvalidPackage
     */
    public static function fromFile(string $filename): self
    {
        if (!file_exists($filename)) {
            throw new InvalidPackage(
                    'No composer.json found - are you sure this is a composer package?'
            );
        }

        try{
            return new self(Json::decode(file_get_contents($filename), true));
        } catch (JsonException $je) {
            throw new InvalidPackage($je->getMessage(), $je);
        }
    }

    /**
     * returns branch alias for given branch
     *
     * If for given branch no alias is defined return value is null.
     *
     * @param   string  $branch
     * @return  string
     */
    public function getBranchAlias(string $branch)
    {
        if (isset($this->config['extra']['branch-alias'][$branch])) {
            return $this->config['extra']['branch-alias'][$branch];
        }

        return null;
    }

    /**
     * returns series for given branch
     *
     * If no branch alias is defined for given branch return value is null.
     *
     * @param   string  $branch
     * @return  Series
     */
    public function getSeries(string $branch)
    {
        $branchAlias = $this->getBranchAlias($branch);
        if (null === $branchAlias) {
            return null;
        }

        return new Series(str_replace('.x-dev', '', $branchAlias));
    }
}
