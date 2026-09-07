<?php

declare(strict_types=1);

namespace Studio\Gesso\Validation\Support;

use Closure;
use RuntimeException;

/**
 * An opaque body whose presence must only be inspected if the resolved
 * contract requires it. A failed inspection is unknown, never absent.
 *
 * @internal Separate adapter-to-validator metadata; never store this probe in
 *           DecodedBody::value or treat it as a present decoded JSON value.
 */
final readonly class DeferredBodyPresence
{
    /** @param Closure(): bool $inspect throws RuntimeException when presence cannot be determined safely */
    public function __construct(private Closure $inspect) {}

    /** @throws RuntimeException */
    public function isPresent(): bool
    {
        return ($this->inspect)();
    }
}
