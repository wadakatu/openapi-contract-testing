<?php

declare(strict_types=1);

namespace Studio\Gesso\Tests\Unit\Internal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Studio\Gesso\Internal\OpenApiDocumentShapeNormalizer;

final class OpenApiDocumentShapeNormalizerTest extends TestCase
{
    #[Test]
    public function body_examples_are_literal_but_structural_and_schema_nodes_stay_normalized(): void
    {
        $empty = new stdClass();
        $body = ['content' => ['application/json' => [
            'example' => ['object' => $empty, 'array' => [], '$ref' => 'literal-not-a-reference'],
            'examples' => ['empty' => ['value' => $empty]],
            'schema' => ['type' => 'object', 'properties' => ['example' => $empty]],
        ]]];
        $document = OpenApiDocumentShapeNormalizer::normalizeResolvedDocument([
            'paths' => ['/body' => ['post' => ['requestBody' => $body, 'responses' => ['200' => $body]]]],
            'components' => ['requestBodies' => ['Body' => $body], 'responses' => ['Body' => $body], 'examples' => ['empty' => ['value' => $empty]]],
        ]);
        foreach ([
            $document['paths']['/body']['post']['requestBody'],
            $document['paths']['/body']['post']['responses']['200'],
            $document['components']['requestBodies']['Body'],
            $document['components']['responses']['Body'],
        ] as $normalized) {
            $media = $normalized['content']['application/json'];
            $this->assertSame($empty, $media['example']['object']);
            $this->assertSame([], $media['example']['array']);
            $this->assertSame('literal-not-a-reference', $media['example']['$ref']);
            $this->assertSame($empty, $media['examples']['empty']['value']);
            $this->assertSame([], $media['schema']['properties']['example']);
        }
        $this->assertSame($empty, $document['components']['examples']['empty']['value']);
    }

    #[Test]
    public function preserves_empty_object_security_container_for_validation(): void
    {
        $document = OpenApiDocumentShapeNormalizer::normalizeResolvedDocument([
            'security' => new stdClass(),
            'paths' => [
                '/pets' => [
                    'get' => ['security' => new stdClass()],
                ],
            ],
        ]);

        $this->assertInstanceOf(stdClass::class, $document['security']);
        $this->assertInstanceOf(stdClass::class, $document['paths']['/pets']['get']['security']);
    }

    #[Test]
    public function preserves_empty_objects_only_in_structural_security_requirement_lists(): void
    {
        $document = OpenApiDocumentShapeNormalizer::normalizeResolvedDocument([
            'security' => [new stdClass()],
            'paths' => [
                '/pets' => [
                    'get' => [
                        'security' => [new stdClass()],
                        'callbacks' => [
                            'updated' => [
                                '{$request.body#/callbackUrl}' => [
                                    'post' => ['security' => [new stdClass()]],
                                ],
                            ],
                        ],
                    ],
                    'additionalOperations' => [
                        'COPY' => ['security' => [new stdClass()]],
                    ],
                ],
            ],
            'webhooks' => [
                'petUpdated' => [
                    'post' => ['security' => [new stdClass()]],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Payload' => [
                        'type' => 'object',
                        'required' => ['security'],
                        'properties' => ['security' => new stdClass()],
                    ],
                ],
                'pathItems' => [
                    'Pets' => ['get' => ['security' => [new stdClass()]]],
                ],
                'callbacks' => [
                    'Updated' => [
                        '{$request.body#/callbackUrl}' => [
                            'post' => ['security' => [new stdClass()]],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(stdClass::class, $document['security'][0]);
        $this->assertInstanceOf(stdClass::class, $document['paths']['/pets']['get']['security'][0]);
        $this->assertInstanceOf(
            stdClass::class,
            $document['paths']['/pets']['get']['callbacks']['updated']['{$request.body#/callbackUrl}']['post']['security'][0],
        );
        $this->assertInstanceOf(
            stdClass::class,
            $document['paths']['/pets']['additionalOperations']['COPY']['security'][0],
        );
        $this->assertInstanceOf(stdClass::class, $document['webhooks']['petUpdated']['post']['security'][0]);
        $this->assertInstanceOf(
            stdClass::class,
            $document['components']['pathItems']['Pets']['get']['security'][0],
        );
        $this->assertInstanceOf(
            stdClass::class,
            $document['components']['callbacks']['Updated']['{$request.body#/callbackUrl}']['post']['security'][0],
        );
        $this->assertSame([], $document['components']['schemas']['Payload']['properties']['security']);
    }
}
