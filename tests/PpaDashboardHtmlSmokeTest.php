<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaController;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Services\PpaCatalogService;

final class PpaDashboardSmokeIndicatorModel extends PpaIndicatorModel
{
    public function __construct(
        private object|string $indicator = 'Indicador do PPA nao encontrado.'
    )
    {
    }

    public function readPublicBySlug(string $slug): object|string
    {
        return $this->indicator;
    }

    public function readPublicCatalog(): array|string
    {
        return [];
    }
}

final class PpaDashboardSmokeQueryModel extends PpaIndicatorQueryModel
{
    public function __construct()
    {
    }

    public function readActiveLinksByIndicatorId(int $indicatorId): array|string
    {
        return [];
    }
}

final class PpaDashboardSmokeCatalogService extends PpaCatalogService
{
    public function __construct()
    {
    }

    public function build(array $indicators): array
    {
        return [
            'indicators' => [],
            'summary' => [
                'total_indicadores' => 0,
                'indicadores_ativos' => 0,
                'meta_atingida' => 0,
                'media_execucao' => null,
            ],
        ];
    }
}

$controller = new PpaController(
    new PpaDashboardSmokeIndicatorModel(),
    new PpaDashboardSmokeCatalogService()
);
$html = $controller->show('indicador-inexistente');

$failures = [];
$assertContains = static function (
    string $expected,
    string $actual,
    string $label
) use (&$failures): void {
    if (!str_contains($actual, $expected)) {
        $failures[] = sprintf(
            "%s\nExpected HTML to contain: %s",
            $label,
            var_export($expected, true)
        );
    }
};

$assertContains('PPA - Indicadores', $html, 'falls back to the PPA catalog');
$assertContains('Indicador do PPA nao encontrado.', $html, 'renders the controlled not-found message');
$assertContains('0 indicadores', $html, 'keeps the catalog summary available');

$indicator = (object) [
    'id' => 42,
    'slug' => 'indicador-sem-vinculos',
    'codigo_indicador' => 'PPA-TESTE-SEM-VINCULOS',
    'nome' => 'Indicador sem vínculos',
    'objetivo' => 'Validar o estado inicial do dashboard.',
    'unidade_medida' => 'familias',
];
$preparationController = new PpaController(
    new PpaDashboardSmokeIndicatorModel($indicator),
    new PpaDashboardSmokeCatalogService(),
    new PpaDashboardSmokeQueryModel()
);
$preparationHtml = $preparationController->show($indicator->slug);

$assertContains('Indicador sem vínculos', $preparationHtml, 'renders the selected indicator');
$assertContains('Em Preparacao', $preparationHtml, 'renders the preparation status');
$assertContains(
    'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
    $preparationHtml,
    'explains why the dashboard has no data'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (6 assertions)\n");
