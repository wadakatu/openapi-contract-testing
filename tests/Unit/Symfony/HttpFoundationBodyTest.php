<?php

declare(strict_types=1);

namespace Studio\Gesso\Tests\Unit\Symfony;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Studio\Gesso\Symfony\HttpFoundationBody;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class HttpFoundationBodyTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function provideJson_types_are_preserved_for_every_json_content_typeCases(): iterable
    {
        yield 'JSON' => ['application/json'];
        yield 'suffix and parameters' => ['Application/Problem+Json; charset=utf-8'];
        yield 'absent header' => [''];
    }

    #[Test]
    #[DataProvider('provideJson_types_are_preserved_for_every_json_content_typeCases')]
    public function json_types_are_preserved_for_every_json_content_type(string $contentType): void
    {
        $body = HttpFoundationBody::json('{"value":{}}', $contentType);
        $this->assertTrue($body->preservesJsonTypes);
        $this->assertInstanceOf(stdClass::class, $body->value);
        $this->assertInstanceOf(stdClass::class, $body->value->value);
        $this->assertSame([], HttpFoundationBody::json('[]', $contentType)->value);
        $this->assertTrue(HttpFoundationBody::json('null', $contentType)->present);
        $this->assertFalse(HttpFoundationBody::json('', $contentType)->present);
    }

    #[Test]
    public function invalid_json_escapes_as_json_exception_for_the_adapter_to_handle(): void
    {
        $this->expectException(JsonException::class);
        HttpFoundationBody::request(Request::create('/', 'POST', content: '{'), 'application/json');
    }

    #[Test]
    public function opaque_request_presence_includes_parameters_and_uploaded_files(): void
    {
        $requests = [
            Request::create('/', 'POST', ['value' => 'parsed']),
            Request::create('/', 'POST', files: ['file' => new UploadedFile(__FILE__, 'body.xml', 'application/xml', test: true)]),
        ];
        foreach ($requests as $request) {
            $body = HttpFoundationBody::request($request, 'application/xml');
            $this->assertTrue($body->present);
            $this->assertNull($body->value);
            $this->assertFalse($body->preservesJsonTypes);
        }
        $this->assertFalse(HttpFoundationBody::request(Request::create('/', 'POST'), 'application/xml')->present);
    }

    #[Test]
    public function opaque_response_extraction_keeps_its_existing_negotiation_policy(): void
    {
        $this->assertFalse(HttpFoundationBody::json('<a/>', 'application/xml')->present);
    }
}
