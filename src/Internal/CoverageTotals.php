<?php

declare(strict_types=1);

namespace Studio\Gesso\Internal;

use Studio\Gesso\Coverage\OpenApiCoverageTracker;

use function round;

/**
 * Arithmetic shared by the coverage renderers.
 *
 * @internal Renderer implementation detail; the rendered formats are the contract.
 *
 * @phpstan-import-type CoverageResult from OpenApiCoverageTracker
 *
 * @phpstan-type CoverageSums array{
 *     endpointTotal: int,
 *     endpointFullyCovered: int,
 *     endpointPartial: int,
 *     endpointUncovered: int,
 *     endpointRequestOnly: int,
 *     responseTotal: int,
 *     responseCovered: int,
 *     responseSkipped: int,
 *     responseUncovered: int,
 * }
 */
final class CoverageTotals
{
    /**
     * Percentage with one decimal, rendered the way every report prints it
     * ("0" when nothing was measured, "50" rather than "50.0").
     */
    public static function percentage(int $covered, int $total): string
    {
        return (string) ($total > 0 ? round($covered / $total * 100, 1) : 0);
    }

    /**
     * @param array<string, CoverageResult> $results
     *
     * @return CoverageSums
     */
    public static function sum(array $results): array
    {
        $totals = [
            'endpointTotal' => 0,
            'endpointFullyCovered' => 0,
            'endpointPartial' => 0,
            'endpointUncovered' => 0,
            'endpointRequestOnly' => 0,
            'responseTotal' => 0,
            'responseCovered' => 0,
            'responseSkipped' => 0,
            'responseUncovered' => 0,
        ];

        foreach ($results as $result) {
            foreach ($totals as $key => $_) {
                $totals[$key] += $result[$key];
            }
        }

        return $totals;
    }
}
