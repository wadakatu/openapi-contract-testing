<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting\Laravel;

/**
 * Namespace-level config() mock for unit testing.
 *
 * This library does not depend on laravel/framework, so the global \config()
 * helper is unavailable during unit tests. This namespaced function acts as a
 * lightweight substitute.
 *
 * PHP resolves unqualified function calls by checking the current namespace first,
 * then falling back to the global namespace. Because the ValidatesOpenApiSchema trait
 * lives in Studio\OpenApiContractTesting\Laravel and calls config() without a leading
 * backslash, this function takes priority over any global \config() that might exist
 * at runtime.
 *
 * IMPORTANT: This relies on config() being called as an unqualified function
 * in ValidatesOpenApiSchema.php (i.e., no "use function config" import).
 * Adding such an import would bypass namespace resolution and break this mock.
 *
 * Test values are read from $GLOBALS['__openapi_testing_config'].
 */
function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__openapi_testing_config'][$key] ?? $default;
}
