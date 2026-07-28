<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\AdminController;
use App\Models\AdminModel;

final class AdminRedirectCaptured extends RuntimeException
{
    public function __construct(public string $path)
    {
        parent::__construct('Redirect captured.');
    }
}

final class AdminAuthenticationTestController extends AdminController
{
    protected function redirect(string $path): never
    {
        throw new AdminRedirectCaptured($path);
    }
}

final class AdminAuthenticationTestModel extends AdminModel
{
    public function __construct(private ?object $admin)
    {
    }

    public function findActiveByCpf(string $cpf): ?object
    {
        return $this->admin;
    }
}

$failures = [];
$assertSame = static function ($expected, $actual, string $label) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = sprintf(
            "%s\nExpected: %s\nActual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        );
    }
};
$assertTrue = static function (bool $actual, string $label) use (&$failures): void {
    if (!$actual) {
        $failures[] = $label . "\nExpected true, got false.";
    }
};
$captureRedirect = static function (callable $action): string {
    try {
        $action();
    } catch (AdminRedirectCaptured $redirect) {
        return $redirect->path;
    }

    throw new RuntimeException('The controller did not redirect.');
};

adminEnsureSession();
$_SESSION = [];
$_POST = [];

$cpf = '52998224725';
$password = 'senha-de-teste';
$admin = (object) [
    'id' => 7,
    'nome' => 'Administrador de Teste',
    'cpf' => $cpf,
    'senha_hash' => password_hash($password, PASSWORD_DEFAULT),
    'ativo' => 1,
];
$controller = new AdminAuthenticationTestController(
    new AdminAuthenticationTestModel($admin)
);

$_POST = [
    'cpf' => '529.982.247-25',
    'senha' => $password,
    '_form_token' => formToken('admin_login'),
];
$loginRedirect = $captureRedirect($controller->authenticate(...));

$assertSame('admin/painel', $loginRedirect, 'redirects a valid login to the panel');
$assertSame(7, $_SESSION['admin_auth']['id'] ?? null, 'stores the administrator id');
$assertSame('Administrador de Teste', $_SESSION['admin_auth']['nome'] ?? null, 'stores the administrator name');
$assertSame('529.982.247-25', $_SESSION['admin_auth']['cpf'] ?? null, 'stores the formatted CPF');
$assertTrue(
    !empty($_SESSION['admin_auth']['ultimo_login_em']),
    'records the login time'
);

unset($_SESSION['admin_auth']);
$_POST = [
    'cpf' => $cpf,
    'senha' => 'senha-incorreta',
    '_form_token' => formToken('admin_login'),
];
ob_start();
$controller->authenticate();
$invalidLoginHtml = (string) ob_get_clean();

$assertTrue(
    str_contains($invalidLoginHtml, 'CPF ou senha inválidos.'),
    'shows a clear error for invalid credentials'
);
$assertSame(false, isset($_SESSION['admin_auth']), 'does not create a session for invalid credentials');

$_SESSION['admin_auth'] = [
    'id' => 7,
    'nome' => 'Administrador de Teste',
];
$_POST = ['_form_token' => 'token-invalido'];
$invalidLogoutRedirect = $captureRedirect($controller->logout(...));

$assertSame('admin/painel', $invalidLogoutRedirect, 'rejects logout without a valid form token');
$assertSame(true, isset($_SESSION['admin_auth']), 'keeps the session after a rejected logout');

$_POST = ['_form_token' => formToken('admin_logout')];
$logoutRedirect = $captureRedirect($controller->logout(...));

$assertSame('/', $logoutRedirect, 'redirects a valid logout to home');
$assertSame(false, isset($_SESSION['admin_auth']), 'removes the administrative session on logout');

$_POST = [];
$_SESSION = [];

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (11 assertions)\n");
