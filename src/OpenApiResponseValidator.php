<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting;

use const JSON_THROW_ON_ERROR;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

use function array_keys;
use function implode;
use function json_decode;
use function json_encode;
use function str_contains;
use function strtolower;

final class OpenApiResponseValidator
{
    public function validate(
        string $specName,
        string $method,
        string $requestPath,
        int $statusCode,
        mixed $responseBody,
    ): OpenApiValidationResult {
        $spec = OpenApiSpecLoader::load($specName);

        $version = OpenApiVersion::fromSpec($spec);

        /** @var string[] $specPaths */
        $specPaths = array_keys($spec['paths'] ?? []);
        $matcher = new OpenApiPathMatcher($specPaths, OpenApiSpecLoader::getStripPrefixes());
        $matchedPath = $matcher->match($requestPath);

        if ($matchedPath === null) {
            return OpenApiValidationResult::failure([
                "No matching path found in '{$specName}' spec for: {$requestPath}",
            ]);
        }

        $lowerMethod = strtolower($method);
        $pathSpec = $spec['paths'][$matchedPath] ?? [];

        if (!isset($pathSpec[$lowerMethod])) {
            return OpenApiValidationResult::failure([
                "Method {$method} not defined for path {$matchedPath} in '{$specName}' spec.",
            ]);
        }

        $statusCodeStr = (string) $statusCode;
        $responses = $pathSpec[$lowerMethod]['responses'] ?? [];

        if (!isset($responses[$statusCodeStr])) {
            return OpenApiValidationResult::failure([
                "Status code {$statusCode} not defined for {$method} {$matchedPath} in '{$specName}' spec.",
            ]);
        }

        $responseSpec = $responses[$statusCodeStr];

        // If no content is defined for this response, skip body validation (e.g. 204 No Content)
        if (!isset($responseSpec['content'])) {
            return OpenApiValidationResult::success($matchedPath);
        }

        /** @var array<string, array<string, mixed>> $content */
        $content = $responseSpec['content'];
        $schema = $this->findJsonSchema($content);

        if ($schema === null) {
            /** @var string[] $definedTypes */
            $definedTypes = array_keys($content);

            return OpenApiValidationResult::failure([
                "No JSON-compatible content type found for {$method} {$matchedPath} (status {$statusCode}) in '{$specName}' spec. Defined content types: " . implode(', ', $definedTypes),
            ]);
        }

        if ($responseBody === null) {
            return OpenApiValidationResult::failure([
                "Response body is empty but {$method} {$matchedPath} (status {$statusCode}) defines a JSON schema in '{$specName}' spec.",
            ]);
        }
        $jsonSchema = OpenApiSchemaConverter::convert($schema, $version);

        // opis/json-schema requires an object, so encode then decode
        $schemaObject = json_decode(
            (string) json_encode($jsonSchema, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR,
        );

        $dataObject = json_decode(
            (string) json_encode($responseBody, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR,
        );

        $validator = new Validator();
        $result = $validator->validate($dataObject, $schemaObject);

        if ($result->isValid()) {
            return OpenApiValidationResult::success($matchedPath);
        }

        $formatter = new ErrorFormatter();
        $formattedErrors = $formatter->format($result->error());

        $errors = [];
        foreach ($formattedErrors as $path => $messages) {
            foreach ($messages as $message) {
                $errors[] = "[{$path}] {$message}";
            }
        }

        return OpenApiValidationResult::failure($errors);
    }

    /**
     * @param array<string, array<string, mixed>> $content
     *
     * @return null|array<string, mixed>
     */
    private function findJsonSchema(array $content): ?array
    {
        foreach ($content as $contentType => $mediaType) {
            if (str_contains(strtolower($contentType), 'json') && isset($mediaType['schema'])) {
                /** @var array<string, mixed> */
                return $mediaType['schema'];
            }
        }

        return null;
    }
}
