<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\ExternalDatabaseAdminController;
use App\Models\ExternalDataSourceModel;
use App\Models\ExternalQueryModel;

final class ExternalAdminRedirectCaptured extends RuntimeException
{
    public function __construct(public string $path)
    {
        parent::__construct('Redirect captured.');
    }
}

final class ExternalAdminCrudTestController extends ExternalDatabaseAdminController
{
    protected function redirect(string $path): never
    {
        throw new ExternalAdminRedirectCaptured($path);
    }
}

final class ExternalAdminCrudSourceModel extends ExternalDataSourceModel
{
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    public function __construct()
    {
    }

    public function readActiveOptions(): array
    {
        return [(object) ['id' => 3, 'nome' => 'Fonte em memória']];
    }

    public function create(array $data): bool|string
    {
        $this->created[] = $data;

        return true;
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

final class ExternalAdminCrudQueryModel extends ExternalQueryModel
{
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    public function __construct()
    {
    }

    public function create(array $data): bool|string
    {
        $this->created[] = $data;

        return true;
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
$captureRedirect = static function (callable $action): string {
    try {
        $action();
    } catch (ExternalAdminRedirectCaptured $redirect) {
        return $redirect->path;
    }

    throw new RuntimeException('The controller did not redirect.');
};

adminEnsureSession();
$_SESSION = [
    'admin_auth' => [
        'id' => 7,
        'nome' => 'Administrador de Teste',
    ],
];
$_POST = [];

$sourceModel = new ExternalAdminCrudSourceModel();
$queryModel = new ExternalAdminCrudQueryModel();
$controller = new ExternalAdminCrudTestController($sourceModel, $queryModel);

$_POST = [
    'nome' => 'Fonte em memória',
    'host' => 'database.internal',
    'porta' => '3307',
    'database_name' => 'dados_sociais',
    'username' => 'leitura',
    'password' => 'senha-temporaria',
    'charset' => 'utf8mb4',
    'descricao' => 'Não será persistida.',
    'ativo' => '1',
    '_form_token' => formToken('external_source_create'),
];
$sourceCreateRedirect = $captureRedirect($controller->storeSource(...));
$createdSource = $sourceModel->created[0] ?? [];

$assertSame('admin/bases-externas', $sourceCreateRedirect, 'redirects after creating a source');
$assertSame(3307, $createdSource['porta'] ?? null, 'normalizes the source port');
$assertSame('senha-temporaria', $createdSource['password'] ?? null, 'forwards the password to the encrypting model');

$_POST = [
    'nome' => 'Fonte editada',
    'host' => 'database.internal',
    'porta' => '3306',
    'database_name' => 'dados_sociais',
    'username' => 'leitura',
    'password' => '',
    'charset' => 'utf8mb4',
    'descricao' => '',
    'ativo' => '0',
    '_form_token' => formToken('external_source_edit_5'),
];
$sourceUpdateRedirect = $captureRedirect(static fn () => $controller->updateSource(5));
$updatedSource = $sourceModel->updated[0] ?? [];

$assertSame('admin/bases-externas', $sourceUpdateRedirect, 'redirects after updating a source');
$assertSame(5, $updatedSource['id'] ?? null, 'updates the selected source');
$assertSame('', $updatedSource['data']['password'] ?? null, 'allows the model to preserve an empty edit password');

$_POST = ['_form_token' => formToken('external_source_delete_5')];
$sourceDeleteRedirect = $captureRedirect(static fn () => $controller->deleteSource(5));
$assertSame('admin/bases-externas', $sourceDeleteRedirect, 'redirects after deleting a source');
$assertSame([5], $sourceModel->deleted, 'deletes the selected source');

$_POST = [
    'source_id' => '3',
    'nome' => 'Resumo em memória',
    'descricao' => 'Não será persistida.',
    'sql_query' => 'SELECT cras, COUNT(*) AS total FROM familias GROUP BY cras',
    'ativo' => '1',
    '_form_token' => formToken('external_query_create'),
];
$queryCreateRedirect = $captureRedirect($controller->storeQuery(...));
$createdQuery = $queryModel->created[0] ?? [];

$assertSame('admin/bases-externas/consultas', $queryCreateRedirect, 'redirects after creating a query');
$assertSame(3, $createdQuery['source_id'] ?? null, 'normalizes the selected source id');
$assertSame(
    'SELECT cras, COUNT(*) AS total FROM familias GROUP BY cras',
    $createdQuery['sql_query'] ?? null,
    'forwards the query SQL to the validating model'
);

$_POST['nome'] = 'Resumo editado';
$_POST['_form_token'] = formToken('external_query_edit_9');
$queryUpdateRedirect = $captureRedirect(static fn () => $controller->updateQuery(9));
$updatedQuery = $queryModel->updated[0] ?? [];

$assertSame('admin/bases-externas/consultas', $queryUpdateRedirect, 'redirects after updating a query');
$assertSame(9, $updatedQuery['id'] ?? null, 'updates the selected query');
$assertSame('Resumo editado', $updatedQuery['data']['nome'] ?? null, 'forwards edited query values');

$_POST = ['_form_token' => formToken('external_query_delete_9')];
$queryDeleteRedirect = $captureRedirect(static fn () => $controller->deleteQuery(9));
$assertSame('admin/bases-externas/consultas', $queryDeleteRedirect, 'redirects after deleting a query');
$assertSame([9], $queryModel->deleted, 'deletes the selected query');

$_POST = [];
$_SESSION = [];

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (16 assertions)\n");
