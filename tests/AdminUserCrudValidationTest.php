<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\AdminController;
use App\Models\AdminModel;

final class AdminCrudRedirectCaptured extends RuntimeException
{
    public function __construct(public string $path)
    {
        parent::__construct('Redirect captured.');
    }
}

final class AdminUserCrudTestController extends AdminController
{
    protected function redirect(string $path): never
    {
        throw new AdminCrudRedirectCaptured($path);
    }
}

final class AdminUserCrudTestModel extends AdminModel
{
    public bool $cpfAlreadyExists = false;
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    public function __construct()
    {
    }

    public function cpfExists(string $cpf, ?int $ignoreId = null): bool
    {
        return $this->cpfAlreadyExists;
    }

    public function create(array $data): bool|string
    {
        $this->created[] = $data;

        return true;
    }

    public function readById(int $id): object|string
    {
        return (object) [
            'id' => $id,
            'nome' => 'Usuário Existente',
            'cpf' => '52998224725',
            'ativo' => 1,
        ];
    }

    public function updateById(int $id, array $data): bool|string
    {
        $this->updated[] = ['id' => $id, 'data' => $data];

        return true;
    }

    public function deleteById(int $id): bool|string
    {
        $this->deleted[] = $id;

        return true;
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
    } catch (AdminCrudRedirectCaptured $redirect) {
        return $redirect->path;
    }

    throw new RuntimeException('The controller did not redirect.');
};

adminEnsureSession();
$_SESSION = [
    'admin_auth' => [
        'id' => 7,
        'nome' => 'Administrador Atual',
    ],
];
$_POST = [];

$model = new AdminUserCrudTestModel();
$controller = new AdminUserCrudTestController($model);

$_POST = [
    'nome' => 'Nova Pessoa',
    'cpf' => '529.982.247-25',
    'senha' => 'senha-segura',
    'ativo' => '1',
    '_form_token' => formToken('admin_user_create'),
];
$createRedirect = $captureRedirect($controller->storeUser(...));
$created = $model->created[0] ?? [];

$assertSame('admin/usuarios', $createRedirect, 'redirects after creating a valid user');
$assertSame('Nova Pessoa', $created['nome'] ?? null, 'stores the normalized name');
$assertSame('52998224725', $created['cpf'] ?? null, 'stores only CPF digits');
$assertTrue(
    password_verify('senha-segura', (string) ($created['senha_hash'] ?? '')),
    'stores a password hash instead of the plain password'
);

$model->cpfAlreadyExists = true;
$createdCount = count($model->created);
$_POST['_form_token'] = formToken('admin_user_create');
ob_start();
$controller->storeUser();
$duplicateHtml = (string) ob_get_clean();

$assertTrue(
    str_contains($duplicateHtml, 'Já existe um usuário administrativo cadastrado com este CPF.'),
    'shows a clear duplicate CPF error'
);
$assertSame($createdCount, count($model->created), 'does not create a duplicate CPF');

$model->cpfAlreadyExists = false;
$_POST = [
    'nome' => 'Pessoa Editada',
    'cpf' => '529.982.247-25',
    'senha' => '',
    'ativo' => '1',
    '_form_token' => formToken('admin_user_edit_8'),
];
$updateRedirect = $captureRedirect(static fn () => $controller->updateUser(8));
$updated = $model->updated[0] ?? [];

$assertSame('admin/usuarios', $updateRedirect, 'redirects after updating another user');
$assertSame(8, $updated['id'] ?? null, 'updates the selected user');
$assertSame('', $updated['data']['senha_hash'] ?? null, 'keeps the current password when the field is empty');

$updatedCount = count($model->updated);
$_POST = [
    'nome' => 'Administrador Atual',
    'cpf' => '529.982.247-25',
    'senha' => '',
    'ativo' => '0',
    '_form_token' => formToken('admin_user_edit_7'),
];
ob_start();
$controller->updateUser(7);
$selfDeactivateHtml = (string) ob_get_clean();

$assertTrue(
    str_contains($selfDeactivateHtml, 'Você não pode desativar o usuário que está autenticado nesta sessão.'),
    'explains why the current user cannot be disabled'
);
$assertSame($updatedCount, count($model->updated), 'does not disable the current user');

$_POST = ['_form_token' => formToken('admin_user_delete_7')];
$selfDeleteRedirect = $captureRedirect(static fn () => $controller->deleteUser(7));

$assertSame('admin/usuarios', $selfDeleteRedirect, 'redirects after rejecting self-deletion');
$assertTrue(
    str_contains((string) ($_SESSION['admin_users_error'] ?? ''), 'não pode excluir'),
    'records a clear self-deletion error'
);
$assertSame([], $model->deleted, 'does not delete the current user');

$_POST = ['_form_token' => formToken('admin_user_delete_8')];
$deleteRedirect = $captureRedirect(static fn () => $controller->deleteUser(8));

$assertSame('admin/usuarios', $deleteRedirect, 'redirects after deleting another user');
$assertSame([8], $model->deleted, 'deletes the selected different user');

$_POST = [];
$_SESSION = [];

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (16 assertions)\n");
