<?php

declare(strict_types=1);

namespace Wadakatu\OpenApiContractTesting\Laravel;

use Illuminate\Testing\TestResponse;
use Wadakatu\OpenApiContractTesting\HttpMethod;
use Wadakatu\OpenApiContractTesting\OpenApiCoverageTracker;
use Wadakatu\OpenApiContractTesting\OpenApiResponseValidator;

trait ValidatesOpenApiSchema
{
    protected string $openApiSpec = 'front';

    protected function assertResponseMatchesOpenApiSchema(
        TestResponse $response,
        ?HttpMethod $method = null,
        ?string $path = null,
    ): void {
        $request = app('request');
        $resolvedMethod = $method?->value ?? $request->getMethod();
        $resolvedPath = $path ?? $request->getPathInfo();

        $validator = new OpenApiResponseValidator();
        $result = $validator->validate(
            $this->openApiSpec,
            $resolvedMethod,
            $resolvedPath,
            $response->getStatusCode(),
            $response->json(),
        );

        if ($result->matchedPath() !== null) {
            OpenApiCoverageTracker::record(
                $this->openApiSpec,
                $resolvedMethod,
                $result->matchedPath(),
            );
        }

        $this->assertTrue(
            $result->isValid(),
            "OpenAPI schema validation failed for {$resolvedMethod} {$resolvedPath} (spec: {$this->openApiSpec}):\n"
            . $result->errorMessage(),
        );
    }
}
