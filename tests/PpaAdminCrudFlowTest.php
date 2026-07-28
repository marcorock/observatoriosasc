<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaAdminController;
use App\Models\ExternalQueryModel;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;

final class PpaAdminRedirectCaptured extends RuntimeException
{
    public function __construct(public string $path)
    {
        parent::__construct('Redirect captured.');
    }
}

final class PpaAdminCrudTestController extends PpaAdminController
{
    protected function redirect(string $path): never
    {
        throw new PpaAdminRedirectCaptured($path);
    }
}

final class PpaAdminCrudIndicatorModel extends PpaIndicatorModel
{
    public array $created = [];
    public array $updated = [];
    public array $deleted = [];

    public function __construct()
    {
    }

    public function readActiveOptions(): array
    {
        return [(object) ['id' => 5, 'nome' => 'Indicador em memória']];
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

final class PpaAdminCrudLinkModel extends PpaIndicatorQueryModel
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

final class PpaAdminCrudExternalQueryModel extends ExternalQueryModel
{
    public function __construct()
    {
    }

    public function readActiveOptions(): array
    {
        return [(object) ['id' => 9, 'nome' => 'Consulta em memória']];
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
    } catch (PpaAdminRedirectCaptured $redirect) {
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

$indicatorModel = new PpaAdminCrudIndicatorModel();
$linkModel = new PpaAdminCrudLinkModel();
$controller = new PpaAdminCrudTestController(
    $indicatorModel,
    $linkModel,
    new PpaAdminCrudExternalQueryModel()
);

$_POST = [
    'codigo_indicador' => '  PPA-TESTE-UI  ',
    'numero_programa' => '12',
    'nome' => '  Indicador em memória  ',
    'unidade_medida' => 'famílias',
    'indice_recente' => '10',
    'indice_futuro' => '20',
    'tipo_apuracao' => 'manual',
    'periodicidade' => 'anual',
    'objetivo' => 'Teste sem persistência.',
    'ativo' => '1',
    '_form_token' => formToken('ppa_indicator_create'),
];
$indicatorCreateRedirect = $captureRedirect($controller->storeIndicator(...));
$createdIndicator = $indicatorModel->created[0] ?? [];

$assertSame('admin/ppa/indicadores', $indicatorCreateRedirect, 'redirects after creating an indicator');
$assertSame('PPA-TESTE-UI', $createdIndicator['codigo_indicador'] ?? null, 'trims the indicator code');
$assertSame('Indicador em memória', $createdIndicator['nome'] ?? null, 'trims the indicator name');
$assertSame('12', $createdIndicator['numero_programa'] ?? null, 'forwards the program number');

$_POST['nome'] = 'Indicador editado';
$_POST['_form_token'] = formToken('ppa_indicator_edit_5');
$indicatorUpdateRedirect = $captureRedirect(static fn () => $controller->updateIndicator(5));
$updatedIndicator = $indicatorModel->updated[0] ?? [];

$assertSame('admin/ppa/indicadores', $indicatorUpdateRedirect, 'redirects after updating an indicator');
$assertSame(5, $updatedIndicator['id'] ?? null, 'updates the selected indicator');
$assertSame('Indicador editado', $updatedIndicator['data']['nome'] ?? null, 'forwards edited indicator values');

$_POST = ['_form_token' => formToken('ppa_indicator_delete_5')];
$indicatorDeleteRedirect = $captureRedirect(static fn () => $controller->deleteIndicator(5));
$assertSame('admin/ppa/indicadores', $indicatorDeleteRedirect, 'redirects after deleting an indicator');
$assertSame([5], $indicatorModel->deleted, 'deletes the selected indicator');

$_POST = [
    'indicador_id' => '5',
    'external_query_id' => '9',
    'papel' => 'principal',
    'campo_resultado' => ' total_familias ',
    'observacao' => ' Vínculo em memória. ',
    'ativo' => '1',
    '_form_token' => formToken('ppa_link_create'),
];
$linkCreateRedirect = $captureRedirect($controller->storeLink(...));
$createdLink = $linkModel->created[0] ?? [];

$assertSame('admin/ppa/vinculos', $linkCreateRedirect, 'redirects after creating a link');
$assertSame(5, $createdLink['indicador_id'] ?? null, 'normalizes the linked indicator id');
$assertSame(9, $createdLink['external_query_id'] ?? null, 'normalizes the linked query id');
$assertSame('total_familias', $createdLink['campo_resultado'] ?? null, 'trims the result field');

$_POST['papel'] = 'apoio';
$_POST['_form_token'] = formToken('ppa_link_edit_11');
$linkUpdateRedirect = $captureRedirect(static fn () => $controller->updateLink(11));
$updatedLink = $linkModel->updated[0] ?? [];

$assertSame('admin/ppa/vinculos', $linkUpdateRedirect, 'redirects after updating a link');
$assertSame(11, $updatedLink['id'] ?? null, 'updates the selected link');
$assertSame('apoio', $updatedLink['data']['papel'] ?? null, 'forwards edited link values');

$_POST = ['_form_token' => formToken('ppa_link_delete_11')];
$linkDeleteRedirect = $captureRedirect(static fn () => $controller->deleteLink(11));
$assertSame('admin/ppa/vinculos', $linkDeleteRedirect, 'redirects after deleting a link');
$assertSame([11], $linkModel->deleted, 'deletes the selected link');

$_POST = [];
$_SESSION = [];

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (18 assertions)\n");
