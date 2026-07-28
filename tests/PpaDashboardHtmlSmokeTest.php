<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaController;
use App\Models\PpaIndicatorModel;
use App\Services\PpaCatalogService;

final class PpaDashboardSmokeIndicatorModel extends PpaIndicatorModel
{
    public function __construct()
    {
    }

    public function readPublicBySlug(string $slug): object|string
    {
        return 'Indicador do PPA nao encontrado.';
    }

    public function readPublicCatalog(): array|string
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
$assertContains = static function (string $expected, string $label) use ($html, &$failures): void {
    if (!str_contains($html, $expected)) {
        $failures[] = sprintf(
            "%s\nExpected HTML to contain: %s",
            $label,
            var_export($expected, true)
        );
    }
};

$assertContains('PPA - Indicadores', 'falls back to the PPA catalog');
$assertContains('Indicador do PPA nao encontrado.', 'renders the controlled not-found message');
$assertContains('0 indicadores', 'keeps the catalog summary available');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (3 assertions)\n");
