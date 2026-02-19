<?php

declare(strict_types=1);

namespace Wadakatu\OpenApiContractTesting\PHPUnit;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;
use Wadakatu\OpenApiContractTesting\OpenApiCoverageTracker;
use Wadakatu\OpenApiContractTesting\OpenApiSpecLoader;

use function array_map;
use function explode;
use function getcwd;
use function round;
use function str_repeat;
use function str_starts_with;

final class OpenApiCoverageExtension implements Extension
{
    /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if ($parameters->has('spec_base_path')) {
            $basePath = $parameters->get('spec_base_path');
            if (!str_starts_with($basePath, '/')) {
                $basePath = getcwd() . '/' . $basePath;
            }

            $stripPrefixes = [];
            if ($parameters->has('strip_prefixes')) {
                $stripPrefixes = array_map('trim', explode(',', $parameters->get('strip_prefixes')));
            }

            OpenApiSpecLoader::configure($basePath, $stripPrefixes);
        }

        $specs = ['front'];
        if ($parameters->has('specs')) {
            $specs = array_map('trim', explode(',', $parameters->get('specs')));
        }

        $facade->registerSubscriber(new class ($specs) implements ExecutionFinishedSubscriber {
            /** @param string[] $specs */
            public function __construct(private readonly array $specs) {}

            /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter */
            public function notify(ExecutionFinished $event): void
            {
                $this->printReport();
            }

            private function printReport(): void
            {
                $hasCoverage = false;
                foreach ($this->specs as $spec) {
                    $covered = OpenApiCoverageTracker::getCovered();
                    if (!isset($covered[$spec]) || empty($covered[$spec])) {
                        continue;
                    }
                    $hasCoverage = true;
                }

                if (!$hasCoverage) {
                    return;
                }

                echo "\n\n";
                echo "OpenAPI Contract Test Coverage\n";
                echo str_repeat('=', 50) . "\n";

                foreach ($this->specs as $spec) {
                    try {
                        $result = OpenApiCoverageTracker::computeCoverage($spec);
                    } catch (RuntimeException) {
                        continue;
                    }

                    $percentage = $result['total'] > 0
                        ? round($result['coveredCount'] / $result['total'] * 100, 1)
                        : 0;

                    echo "\n[{$spec}] {$result['coveredCount']}/{$result['total']} endpoints ({$percentage}%)\n";
                    echo str_repeat('-', 50) . "\n";

                    if (!empty($result['covered'])) {
                        echo "Covered:\n";
                        foreach ($result['covered'] as $endpoint) {
                            echo "  ✓ {$endpoint}\n";
                        }
                    }

                    $uncoveredCount = $result['total'] - $result['coveredCount'];
                    if ($uncoveredCount > 0) {
                        echo "Uncovered: {$uncoveredCount} endpoints\n";
                    }
                }

                echo "\n";
            }
        });
    }
}
