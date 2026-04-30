<?php

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $basePath = rtrim(str_replace('/public', '', $scriptDir), '/');

        if ($basePath === '' || $basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        return ($basePath ?: '') . '/' . $path;
    }
}
