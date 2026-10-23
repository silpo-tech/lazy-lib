<?php

declare(strict_types=1);

namespace Doctrine\Common\Collections;

/**
 * Stub override for PHPStan only.
 *
 * doctrine/collections does not mark isInitialized() as impure, so PHPStan's
 * "remembering returned values" optimization assumes repeated calls on the
 * same object keep returning the first observed result. Since the backing
 * $initialized flag is mutated internally by initialize()/doInitialize(),
 * this produces false positive "will always evaluate to false/true" errors.
 *
 * @see https://phpstan.org/blog/remembering-and-forgetting-returned-values
 *
 * @template TKey of array-key
 * @template T
 */
abstract class AbstractLazyCollection
{
    /**
     * @phpstan-impure
     */
    public function isInitialized(): bool
    {
    }
}
