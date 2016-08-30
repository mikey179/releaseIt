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
 * @since  2.0.0
 */
class Key
{
    private $keyId;

    /**
     * creates a reference to the default key
     *
     * @return  self
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * constructor
     *
     * If no key is passed it should be treated as the default key. It is up to
     * the repository to figure out what `default key` actually means.
     *
     * @param  string  $keyId  optional  id of key to use
     */
    public function __construct(string $keyId = null)
    {
        $this->keyId = $keyId;
    }

    /**
     * whether this is a reference to the default key or not
     *
     * @return  bool
     */
    public function isDefault(): bool
    {
        return null === $this->keyId;
    }

    public function __toString(): string
    {
        return (string) $this->keyId;
    }
}
