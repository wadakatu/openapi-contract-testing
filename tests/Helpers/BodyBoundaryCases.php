<?php

declare(strict_types=1);

namespace Studio\Gesso\Tests\Helpers;

/** Shared wire payloads: every adapter must make the same type/presence decisions. */
final class BodyBoundaryCases
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function json(): iterable
    {
        yield 'object accepts object' => ['/object', '{}', true];
        yield 'object rejects empty array' => ['/object', '[]', false];
        yield 'object rejects null' => ['/object', 'null', false];
        yield 'object rejects scalar' => ['/object', '0', false];
        yield 'object rejects absent body' => ['/object', '', false];
        yield 'object preserves numeric keys' => ['/object', '{"0":"value"}', true];
        yield 'array accepts empty array' => ['/array', '[]', true];
        yield 'array accepts populated array' => ['/array', '["value"]', true];
        yield 'array rejects empty object' => ['/array', '{}', false];
        yield 'nullable object accepts null' => ['/nullable-object', 'null', true];
        yield 'nullable object accepts object' => ['/nullable-object', '{}', true];
        yield 'nullable object rejects empty array' => ['/nullable-object', '[]', false];
        yield 'nullable object rejects absent body' => ['/nullable-object', '', false];
        yield 'nested object accepts empty object' => ['/nested', '{"value":{}}', true];
        yield 'nested object rejects empty array' => ['/nested', '{"value":[]}', false];
        yield 'malformed JSON fails' => ['/object', '{', false];
    }

    /** @return iterable<string, array{string, bool}> */
    public static function opaque(): iterable
    {
        yield 'nonempty XML' => ['<a/>', true];
        yield 'zero is present' => ['0', true];
        yield 'empty is absent' => ['', false];
    }
}
