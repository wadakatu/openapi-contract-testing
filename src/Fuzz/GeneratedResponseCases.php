<?php

declare(strict_types=1);

namespace Studio\Gesso\Fuzz;

use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

use function count;

/**
 * Non-empty iterable collection returned by {@see OpenApiResponseExplorer}.
 *
 * @implements IteratorAggregate<int, GeneratedResponseCase>
 */
final readonly class GeneratedResponseCases implements Countable, IteratorAggregate
{
    /**
     * @param list<GeneratedResponseCase> $cases
     * @param null|Closure(): void $beforeEach Explorer hook run before each callback in {@see each()}.
     */
    public function __construct(public array $cases, private ?Closure $beforeEach = null)
    {
        if ($cases === []) {
            throw new InvalidArgumentException(
                'GeneratedResponseCases must contain at least one GeneratedResponseCase; an empty SDK exercise would assert nothing.',
            );
        }
    }

    public function count(): int
    {
        return count($this->cases);
    }

    /**
     * @param callable(GeneratedResponseCase): mixed $callback
     */
    public function each(callable $callback): self
    {
        foreach ($this->cases as $case) {
            if ($this->beforeEach !== null) {
                ($this->beforeEach)();
            }
            $callback($case);
        }

        return $this;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cases);
    }
}
