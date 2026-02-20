<?php

declare(strict_types=1);

namespace Studio\OpenApiContractTesting\Laravel;

/**
 * Namespace-level config() mock for unit testing.
 *
 * PHP resolves unqualified function calls by checking the current namespace first,
 * so this takes priority over the global \config() from illuminate/support
 * when called from within the Studio\OpenApiContractTesting\Laravel namespace.
 */
function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__openapi_testing_config'][$key] ?? $default;
}
