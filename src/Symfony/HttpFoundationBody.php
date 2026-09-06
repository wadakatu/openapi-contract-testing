<?php

declare(strict_types=1);

namespace Studio\Gesso\Symfony;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Studio\Gesso\DecodedBody;
use Studio\Gesso\Validation\Support\ContentTypeMatcher;
use Studio\Gesso\Validation\Support\FormBodyDecoder;
use Symfony\Component\HttpFoundation\Request;

use function json_decode;
use function sprintf;
use function strtolower;

/**
 * Shared body extraction for Laravel and Symfony. JSON failures deliberately
 * escape to the adapter so its assertion and baseline policy remain in charge.
 *
 * @internal Not part of the package's public API. Do not use from user code.
 */
final class HttpFoundationBody
{
    /** @throws JsonException */
    public static function request(Request $request, string $contentType): DecodedBody
    {
        $content = $request->getContent();
        $normalizedType = ContentTypeMatcher::normalizeMediaType($contentType);

        if ($normalizedType === '' || ContentTypeMatcher::isJsonContentType($normalizedType)) {
            return self::decodeJson($content);
        }

        $fields = HttpFoundationFormBody::fields($request);
        if (FormBodyDecoder::isFormMediaType($normalizedType)) {
            if ($fields !== null) {
                return DecodedBody::present($fields);
            }

            return $content === '' ? DecodedBody::absent() : DecodedBody::present($content);
        }

        // HttpFoundation may only retain parsed parameters / files, not raw
        // bytes. Opaque bodies need a presence bit, not a guessed JSON value.
        return $content !== '' || $fields !== null
            ? DecodedBody::present(null)
            : DecodedBody::absent();
    }

    /**
     * Decode only JSON; response content negotiation handles non-JSON types.
     *
     * @throws JsonException
     */
    public static function json(string $content, string $contentType): DecodedBody
    {
        if ($content === '' || ($contentType !== '' && !ContentTypeMatcher::isJsonContentType(
            ContentTypeMatcher::normalizeMediaType($contentType),
        ))) {
            return DecodedBody::absent();
        }

        return self::decodeJson($content);
    }

    public static function parseFailure(JsonException $exception, string $contentType, string $subject): string
    {
        return sprintf(
            '%s body could not be parsed as JSON: %s%s',
            $subject,
            $exception->getMessage(),
            $contentType === '' ? sprintf(' (no Content-Type header was present on the %s)', strtolower($subject)) : '',
        );
    }

    /** @throws JsonException */
    private static function decodeJson(string $content): DecodedBody
    {
        return $content === ''
            ? DecodedBody::absent()
            : DecodedBody::fromJsonValue(json_decode($content, false, flags: JSON_THROW_ON_ERROR));
    }
}
