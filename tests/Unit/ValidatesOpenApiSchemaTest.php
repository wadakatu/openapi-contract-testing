<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting\Tests\Unit;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Studio\OpenApiContractTesting\HttpMethod;
use Studio\OpenApiContractTesting\Laravel\ValidatesOpenApiSchema;
use Studio\OpenApiContractTesting\OpenApiCoverageTracker;
use Studio\OpenApiContractTesting\OpenApiSpecLoader;

class ValidatesOpenApiSchemaTest extends TestCase
{
    use ValidatesOpenApiSchema;

    protected function setUp(): void
    {
        parent::setUp();
        OpenApiSpecLoader::reset();
        OpenApiSpecLoader::configure(__DIR__ . '/../fixtures/specs');
        OpenApiCoverageTracker::reset();
        $this->openApiSpec = 'petstore-3.0';
    }

    protected function tearDown(): void
    {
        OpenApiSpecLoader::reset();
        OpenApiCoverageTracker::reset();
        parent::tearDown();
    }

    #[Test]
    public function empty_body_204_response_passes_validation(): void
    {
        $response = $this->makeTestResponse('', 204);

        $this->assertResponseMatchesOpenApiSchema(
            $response,
            HttpMethod::DELETE,
            '/v1/pets/123',
        );
    }

    #[Test]
    public function empty_body_200_without_content_schema_passes_validation(): void
    {
        $response = $this->makeTestResponse('', 200);

        $this->assertResponseMatchesOpenApiSchema(
            $response,
            HttpMethod::GET,
            '/v1/health',
        );
    }

    private function makeTestResponse(string $content, int $statusCode): TestResponse
    {
        $baseResponse = new class ($content, $statusCode) {
            public function __construct(
                private readonly string $content,
                private readonly int $statusCode,
            ) {}

            public function getContent(): string
            {
                return $this->content;
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }
        };

        return new TestResponse($baseResponse);
    }
}
