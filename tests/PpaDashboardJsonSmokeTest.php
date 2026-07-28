<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaController;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;

final class PpaJsonResponseCaptured extends RuntimeException
{
    public function __construct(public array $payload)
    {
        parent::__construct('JSON response captured.');
    }
}

final class PpaDashboardJsonController extends PpaController
{
    protected function json(array $payload): void
    {
        throw new PpaJsonResponseCaptured($payload);
    }
}

final class PpaDashboardJsonIndicatorModel extends PpaIndicatorModel
{
    public function __construct(private object|string $indicator)
    {
    }

    public function readPublicBySlug(string $slug): object|string
    {
        return $this->indicator;
    }
}

final class PpaDashboardJsonQueryModel extends PpaIndicatorQueryModel
{
    public function __construct()
    {
    }

    public function readActiveLinksByIndicatorId(int $indicatorId): array|string
    {
        return [];
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

$capture = static function (PpaController $controller, string $slug): array {
    http_response_code(200);

    try {
        $controller->dashboardData($slug);
    } catch (PpaJsonResponseCaptured $response) {
        return [
            'status' => http_response_code(),
            'payload' => $response->payload,
        ];
    }

    throw new RuntimeException('The controller did not emit a JSON response.');
};

$notFound = $capture(
    new PpaDashboardJsonController(
        new PpaDashboardJsonIndicatorModel('Indicador do PPA nao encontrado.')
    ),
    'indicador-inexistente'
);
$assertSame(404, $notFound['status'], 'returns HTTP 404 for an unknown indicator');
$assertSame(
    ['error' => 'Indicador do PPA nao encontrado.'],
    $notFound['payload'],
    'returns a clear JSON error for an unknown indicator'
);

$indicator = (object) [
    'id' => 42,
    'slug' => 'indicador-sem-vinculos',
];
$withoutLinks = $capture(
    new PpaDashboardJsonController(
        new PpaDashboardJsonIndicatorModel($indicator),
        null,
        new PpaDashboardJsonQueryModel()
    ),
    $indicator->slug
);
$assertSame(404, $withoutLinks['status'], 'returns HTTP 404 when the indicator has no links');
$assertSame(
    ['error' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.'],
    $withoutLinks['payload'],
    'returns a clear JSON error when the indicator has no links'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (4 assertions)\n");
