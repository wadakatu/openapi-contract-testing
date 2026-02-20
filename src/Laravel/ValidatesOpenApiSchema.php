<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting\Laravel;

use Illuminate\Testing\TestResponse;
use Studio\OpenApiContractTesting\HttpMethod;
use Studio\OpenApiContractTesting\OpenApiCoverageTracker;
use Studio\OpenApiContractTesting\OpenApiResponseValidator;

use function is_string;

trait ValidatesOpenApiSchema
{
    protected function openApiSpec(): string
    {
        $spec = config('openapi-contract-testing.default_spec');

        if (!is_string($spec) || $spec === '') {
            return '';
        }

        return $spec;
    }

    protected function assertResponseMatchesOpenApiSchema(
        TestResponse $response,
        ?HttpMethod $method = null,
        ?string $path = null,
    ): void {
        $specName = $this->openApiSpec();
        if ($specName === '') {
            $this->fail(
                'openApiSpec() must return a non-empty spec name, but an empty string was returned. '
                . 'Either override openApiSpec() in your test class, or set the "default_spec" key '
                . 'in config/openapi-contract-testing.php.',
            );
        }

        $resolvedMethod = $method !== null ? $method->value : app('request')->getMethod();
        $resolvedPath = $path ?? app('request')->getPathInfo();

        $content = $response->getContent();
        if ($content === false) {
            $this->fail('OpenAPI contract testing requires buffered responses, but getContent() returned false (streamed response?).');
        }

        $validator = new OpenApiResponseValidator();
        $result = $validator->validate(
            $specName,
            $resolvedMethod,
            $resolvedPath,
            $response->getStatusCode(),
            $content !== '' ? $response->json() : null,
        );

        if ($result->matchedPath() !== null) {
            OpenApiCoverageTracker::record(
                $specName,
                $resolvedMethod,
                $result->matchedPath(),
            );
        }

        $this->assertTrue(
            $result->isValid(),
            "OpenAPI schema validation failed for {$resolvedMethod} {$resolvedPath} (spec: {$specName}):\n"
            . $result->errorMessage(),
        );
    }
}
