<?php

declare(strict_types=1);

namespace Studio\Gesso\Tests\Unit\Validation\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Studio\Gesso\Validation\Support\BodyStructureInspector;

use function iterator_to_array;

final class BodyStructureInspectorTest extends TestCase
{
    #[Test]
    public function missing_null_and_empty_request_nodes_keep_runtime_compatibility(): void
    {
        foreach ([[], ['requestBody' => null], ['requestBody' => []], ['requestBody' => ['content' => null]], ['requestBody' => ['content' => []]]] as $operation) {
            $this->assertSame([], iterator_to_array(BodyStructureInspector::request($operation)));
        }
    }

    #[Test]
    public function encoding_is_checked_only_on_requests(): void
    {
        $content = ['application/json' => ['schema' => [], 'encoding' => 'oops']];
        $this->assertSame([], iterator_to_array(BodyStructureInspector::content($content, 'responses[200].content')));
        $this->assertSame([
            ['location' => 'requestBody.content["application/json"].encoding', 'node' => 'oops'],
        ], iterator_to_array(BodyStructureInspector::request(['requestBody' => ['content' => $content]])));
    }
}
