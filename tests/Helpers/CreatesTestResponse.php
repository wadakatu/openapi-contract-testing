<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting\Tests\Helpers;

use Illuminate\Testing\TestResponse;

trait CreatesTestResponse
{
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
