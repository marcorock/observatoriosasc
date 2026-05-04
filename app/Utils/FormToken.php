<?php

if (!function_exists('ensureFormTokenSession')) {
    function ensureFormTokenSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('formToken')) {
    function formToken(string $key = 'default'): string
    {
        ensureFormTokenSession();

        $_SESSION['_form_tokens'] ??= [];
        $_SESSION['_form_tokens'][$key] = bin2hex(random_bytes(32));

        return $_SESSION['_form_tokens'][$key];
    }
}

if (!function_exists('formTokenInput')) {
    function formTokenInput(string $key = 'default'): string
    {
        $token = htmlspecialchars(formToken($key), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_form_token" value="' . $token . '">';
    }
}

if (!function_exists('validateFormToken')) {
    function validateFormToken(string $key = 'default', ?string $token = null): bool
    {
        ensureFormTokenSession();

        $token ??= (string) ($_POST['_form_token'] ?? '');
        $expected = (string) ($_SESSION['_form_tokens'][$key] ?? '');

        unset($_SESSION['_form_tokens'][$key]);

        return $token !== '' && $expected !== '' && hash_equals($expected, $token);
    }
}
