<?php

declare(strict_types=1);

namespace Studio\Gesso;

use const JSON_THROW_ON_ERROR;

use JsonException;

use function json_decode;

/**
 * Envelope for a request / response body, carrying the absent-vs-present
 * distinction alongside its decoded value when available.
 *
 * A decoded body is one of four shapes — a JSON object/array, a JSON scalar,
 * the literal JSON `null`, or no body at all. PHP's `json_decode()` collapses
 * the last two: a body of the four bytes `null` and an absent body both
 * decode to PHP `null`. Passing the decoded value around as a bare `mixed`
 * therefore loses the "was a body present?" bit — the gap issue #246 first
 * patched with an internal marker enum and issue #248 closes properly here.
 *
 * `present` records whether the wire carried a body; `value` is the decoded
 * value when the adapter can decode it (always `null` when `present` is
 * false). A literal-null JSON body is `present === true` with `value === null`
 * — exactly the state a bare `null` could not express. Adapters also use that
 * shape for an opaque non-JSON body whose presence is known but whose value is
 * intentionally not decoded.
 * `preservesJsonTypes` records object-preserving JSON decoding; only legacy
 * values without this provenance may use the empty-array-to-object shim.
 *
 * The framework adapters build this envelope; the body validators consume it.
 * The public `OpenApiResponseValidator::validate()` /
 * `OpenApiRequestValidator::validate()` still accept a `mixed` body for
 * backward compatibility and normalize it through {@see self::fromLegacy()}.
 */
final readonly class DecodedBody
{
    /**
     * @param mixed $value the decoded JSON body value — a `stdClass`, `array`,
     *                     `string`, `int`, `float`, `bool`, or `null`. Framework
     *                     adapters decode JSON objects as `stdClass` (issue
     *                     #559) so a wire-level `{}` is distinguishable from
     *                     `[]`; a legacy assoc-array caller may still hand this
     *                     an `array` for an object body. Always `null` when
     *                     `$present` is false. Typed `mixed` rather than a
     *                     union because the public validators accept a bare
     *                     legacy body of any shape via {@see self::fromLegacy()}.
     * @param bool $preservesJsonTypes true only for object-preserving JSON values
     */
    private function __construct(
        public bool $present,
        public mixed $value,
        /** @internal Decoder provenance, not consumer configuration. */
        public bool $preservesJsonTypes = false,
    ) {}

    /**
     * No body was carried on the wire.
     */
    public static function absent(): self
    {
        return new self(false, null);
    }

    /**
     * A body was carried on the wire; `$value` is its decoded value (which may
     * itself be `null` for a literal JSON `null` or an opaque non-JSON body).
     *
     * This legacy factory enables empty-array-to-object compatibility. Do not
     * use it for object-preserving JSON decodes: an actual `[]` could silently
     * pass an object schema. Use {@see self::fromJsonValue()} instead.
     */
    public static function present(mixed $value): self
    {
        return new self(true, $value);
    }

    /**
     * A present JSON value decoded with `json_decode($json, false)`.
     *
     * Objects must remain objects, including nested ones. This provenance
     * disables the legacy empty-array-to-object compatibility coercion:
     * `[]` means a JSON array, never an ambiguous decoded `{}`. A literal
     * JSON `null` remains present. This method does not parse JSON text.
     */
    public static function fromJsonValue(mixed $value): self
    {
        return new self(true, $value, true);
    }

    /**
     * @internal Shared adapter decoding, including empty-vs-literal-null handling.
     *
     * @throws JsonException
     */
    public static function decodeJson(string $content): self
    {
        return $content === ''
            ? self::absent()
            : self::fromJsonValue(json_decode($content, false, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Normalize a legacy `mixed` body argument into a {@see DecodedBody}.
     *
     * An existing {@see DecodedBody} passes through unchanged. Otherwise the
     * historical convention is preserved: a plain PHP `null` means "no body
     * was present", any other value means "this body was present". This keeps
     * the `mixed` body parameter of the public validators backward compatible
     * — callers that never pass `null` for "present" lose nothing, and the
     * marker that previously expressed "present null" was internal-only.
     */
    public static function fromLegacy(mixed $body): self
    {
        if ($body instanceof self) {
            return $body;
        }

        return $body === null ? self::absent() : self::present($body);
    }
}
