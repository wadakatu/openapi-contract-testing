<?php

declare(strict_types=1);

namespace Studio\Gesso\Tests\Unit;

use const JSON_THROW_ON_ERROR;

use JsonException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Studio\Gesso\Coverage\OpenApiCoverageTracker;
use Studio\Gesso\DecodedBody;
use Studio\Gesso\OpenApiRequestValidator;
use Studio\Gesso\OpenApiResponseValidator;
use Studio\Gesso\Spec\OpenApiSpecLoader;
use Studio\Gesso\Tests\Helpers\BodyBoundaryCases;
use Studio\Gesso\Validation\Strict\StrictRequiredTracker;

use function json_decode;

final class BodyBoundaryCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        OpenApiSpecLoader::reset();
        OpenApiSpecLoader::configure(__DIR__ . '/../fixtures/specs');
        OpenApiCoverageTracker::reset();
    }

    protected function tearDown(): void
    {
        OpenApiSpecLoader::reset();
        OpenApiCoverageTracker::reset();
    }

    #[Test]
    public function legacy_empty_array_remains_compatible_with_object_schemas(): void
    {
        foreach ([[], DecodedBody::present([])] as $body) {
            $this->assertBody('/object', $body, true);
            $this->assertBody('/nullable-object', $body, true);
            $this->assertBody('/array', $body, true);
        }
        $this->assertBody('/nullable-object', null, false);
        $this->assertBody('/nullable-object', DecodedBody::present(null), true);
    }

    #[Test]
    #[DataProviderExternal(BodyBoundaryCases::class, 'json')]
    public function explicit_json_values_preserve_wire_body_boundaries(string $path, string $wire, bool $valid): void
    {
        // Parsing failures belong to adapters, not to the decoded-value API.
        if ($wire === '{') {
            $this->expectException(JsonException::class);
        }
        $body = $wire === '' ? DecodedBody::absent() : DecodedBody::fromJsonValue(json_decode($wire, false, flags: JSON_THROW_ON_ERROR));
        $this->assertBody($path, $body, $valid);
    }

    private function assertBody(string $path, mixed $body, bool $valid): void
    {
        $request = (new OpenApiRequestValidator())->validate('body-boundaries', 'POST', $path, [], [], $body, 'application/json');
        $response = (new OpenApiResponseValidator(new StrictRequiredTracker()))->validate('body-boundaries', 'POST', $path, 200, $body, 'application/json');

        $this->assertSame($valid, $request->isValid(), $request->errorMessage());
        $this->assertSame($valid, $response->isValid(), $response->errorMessage());
    }
}
