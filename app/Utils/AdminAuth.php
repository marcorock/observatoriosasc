<?php

if (!function_exists('adminEnsureSession')) {
    function adminEnsureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('adminIsAuthenticated')) {
    function adminIsAuthenticated(): bool
    {
        adminEnsureSession();

        return isset($_SESSION['admin_auth']['id']);
    }
}

if (!function_exists('adminRequireAuth')) {
    function adminRequireAuth(string $redirectTo = 'admin'): void
    {
        if (!adminIsAuthenticated()) {
            header('Location: ' . url($redirectTo));
            exit;
        }
    }
}
