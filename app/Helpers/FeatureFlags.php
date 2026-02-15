<?php

if (! function_exists('feature_enabled')) {
    function feature_enabled(string $category, string $feature): bool
    {
        return app('features')->enabled($category, $feature);
    }
}

if (! function_exists('ai_enabled')) {
    function ai_enabled(string $feature): bool
    {
        return feature_enabled('ai', $feature);
    }
}

if (! function_exists('experimental_enabled')) {
    function experimental_enabled(string $feature): bool
    {
        return feature_enabled('experimental', $feature);
    }
}
