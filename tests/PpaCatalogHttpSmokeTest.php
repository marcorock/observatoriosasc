<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaController;
use App\Models\PpaIndicatorModel;
use App\Services\PpaCatalogService;

final class PpaCatalogSmokeIndicatorModel extends PpaIndicatorModel
{
    public function __construct(private array|string $catalog)
    {
    }

    public function readPublicCatalog(): array|string
    {
        return $this->catalog;
    }
}

final class PpaCatalogSmokeService extends PpaCatalogService
{
    public function __construct()
    {
    }

    public function build(array $indicators): array
    {
        return [
            'indicators' => $indicators,
            'summary' => [
                'total_indicadores' => count($indicators),
                'indicadores_ativos' => count($indicators),
                'meta_atingida' => 0,
                'media_execucao' => null,
            ],
        ];
    }
}

$failures = [];
$assertContains = static function (string $expected, string $actual, string $label) use (&$failures): void {
    if (!str_contains($actual, $expected)) {
        $failures[] = sprintf(
            "%s\nExpected HTML to contain: %s",
            $label,
            var_export($expected, true)
        );
    }
};

$controller = new PpaController(
    new PpaCatalogSmokeIndicatorModel([]),
    new PpaCatalogSmokeService()
);
$html = $controller->index();

$assertContains('PPA - Indicadores', $html, 'renders the public PPA catalog');
$assertContains('0 indicadores', $html, 'renders the empty catalog summary');
$assertContains('Selecione um indicador do Plano Plurianual', $html, 'renders the catalog description');

$errorController = new PpaController(
    new PpaCatalogSmokeIndicatorModel('Falha controlada de leitura.'),
    new PpaCatalogSmokeService()
);
$errorHtml = $errorController->index();

$assertContains('Falha controlada de leitura.', $errorHtml, 'renders a controlled catalog read error');
$assertContains('0 indicadores', $errorHtml, 'keeps the catalog available after a read error');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (5 assertions)\n");
