<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';

use App\Controllers\AdminController;
use App\Controllers\BscController;
use App\Controllers\ExternalDatabaseAdminController;
use App\Controllers\PpaAdminController;

final class AdminAuthenticationRequired extends RuntimeException
{
    public function __construct(public string $redirectTo)
    {
        parent::__construct('Authentication required.');
    }
}

function adminRequireAuth(string $redirectTo = 'admin'): void
{
    throw new AdminAuthenticationRequired($redirectTo);
}

$failures = [];
$assertProtected = static function (callable $action, string $label) use (&$failures): void {
    try {
        $action();
        $failures[] = $label . "\nExpected the action to require authentication.";
    } catch (AdminAuthenticationRequired $required) {
        if ($required->redirectTo !== 'admin') {
            $failures[] = sprintf(
                "%s\nExpected redirect: 'admin'\nActual redirect:   %s",
                $label,
                var_export($required->redirectTo, true)
            );
        }
    }
};

$admin = new AdminController();
$externalDatabases = new ExternalDatabaseAdminController();
$ppa = new PpaAdminController();
$bsc = new BscController();

$assertProtected($admin->panel(...), 'protects the administrative panel');
$assertProtected($admin->users(...), 'protects administrative users');
$assertProtected($externalDatabases->sources(...), 'protects external data sources');
$assertProtected($externalDatabases->queries(...), 'protects external queries');
$assertProtected($ppa->dashboard(...), 'protects the PPA administrative panel');
$assertProtected($ppa->indicators(...), 'protects PPA indicators');
$assertProtected($ppa->links(...), 'protects PPA query links');
$assertProtected($bsc->records(...), 'protects the complete BSC records');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (8 assertions)\n");
