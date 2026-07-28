<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/Helpers.php';
require_once __DIR__ . '/../app/Utils/FormToken.php';
require_once __DIR__ . '/../app/Utils/AdminAuth.php';

use App\Controllers\PpaAdminController;
use App\Core\Template;
use App\Services\PpaAdminSynchronizationService;

final class PpaAdminSynchronizationRedirect extends RuntimeException
{
    public function __construct(public string $path)
    {
        parent::__construct('Redirect captured.');
    }
}

final class PpaAdminSynchronizationTestController extends PpaAdminController
{
    protected function redirect(string $path): never
    {
        throw new PpaAdminSynchronizationRedirect($path);
    }
}

final class PpaAdminSynchronizationTemplate extends Template
{
    public function renderDashboard(): string
    {
        return $this->render('admin/ppa/index.html', [
            'feedback' => null,
            'indicadores_total' => 2,
            'vinculos_total' => 1,
            'indicators' => [
                (object) [
                    'id' => 7,
                    'codigo_indicador' => 'PPA-TESTE',
                    'nome' => 'Indicador de teste',
                    'ultima_sincronizacao_em' => '2026-07-28 14:30:00',
                    'sincronizacao_status' => 'Atualizado',
                ],
                (object) [
                    'id' => 8,
                    'codigo_indicador' => 'PPA-PENDENTE',
                    'nome' => 'Indicador pendente',
                    'ultima_sincronizacao_em' => null,
                    'sincronizacao_status' => 'Pendente',
                ],
            ],
            'system' => 'Observatório',
            'name' => 'PPA',
            'description' => 'Teste',
            'header_title' => 'Observatório',
            'header_subtitle' => 'Teste',
            'header_menu_items' => [],
        ]);
    }
}

$failures = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = $message . sprintf(
            "\nExpected: %s\nActual:   %s",
            var_export($expected, true),
            var_export($actual, true)
        );
    }
};
$captureRedirect = static function (callable $action): string {
    try {
        $action();
    } catch (PpaAdminSynchronizationRedirect $redirect) {
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

$synchronizedIds = [];
$successService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (int $id): object => (object) [
        'id' => $id,
        'codigo_indicador' => 'PPA-TESTE',
        'ativo' => 1,
    ],
    linksLoader: static fn (): array => [(object) ['id' => 2]],
    cacheSynchronizer: static fn (): array => ['success' => true, 'entries_written' => 2],
    catalogPreview: static fn (): array => ['success' => true],
    resultPersister: static function () use (&$synchronizedIds): array {
        $synchronizedIds[] = 7;
        return ['inserted' => true, 'message' => 'Resultado gravado.'];
    }
);
$controller = new PpaAdminSynchronizationTestController(
    synchronizationService: $successService
);

$_POST = [
    'indicador_id' => '7',
    '_form_token' => formToken('ppa_indicator_synchronize'),
];
$redirect = $captureRedirect($controller->synchronizeIndicator(...));
$feedback = $_SESSION['ppa_admin_feedback'] ?? [];

$assertSame('admin/ppa', $redirect, 'deve retornar ao painel depois da sincronização');
$assertSame([7], $synchronizedIds, 'deve executar a sincronização do indicador selecionado');
$assertSame('success', $feedback['type'] ?? null, 'deve mostrar feedback de sucesso');
$assertSame(
    true,
    str_contains((string) ($feedback['message'] ?? ''), '2 entrada(s)'),
    'deve informar quantas entradas do dashboard foram atualizadas'
);

$serviceCalledWithInvalidToken = false;
$invalidTokenService = new PpaAdminSynchronizationService(
    indicatorLoader: static function () use (&$serviceCalledWithInvalidToken): object {
        $serviceCalledWithInvalidToken = true;
        return (object) [];
    }
);
$invalidTokenController = new PpaAdminSynchronizationTestController(
    synchronizationService: $invalidTokenService
);
$_POST = [
    'indicador_id' => '7',
    '_form_token' => 'token-invalido',
];
$invalidRedirect = $captureRedirect($invalidTokenController->synchronizeIndicator(...));

$assertSame('admin/ppa', $invalidRedirect, 'token inválido deve retornar ao painel');
$assertSame(false, $serviceCalledWithInvalidToken, 'token inválido não deve iniciar consultas externas');
$assertSame(
    'danger',
    $_SESSION['ppa_admin_feedback']['type'] ?? null,
    'token inválido deve mostrar feedback de erro'
);

$failureService = new PpaAdminSynchronizationService(
    indicatorLoader: static fn (): object => (object) [
        'id' => 7,
        'codigo_indicador' => 'PPA-TESTE',
        'ativo' => 1,
    ],
    linksLoader: static fn (): array => [(object) ['id' => 2]],
    cacheSynchronizer: static fn (): array => [
        'success' => false,
        'error' => 'Fonte externa indisponível.',
    ]
);
$failureController = new PpaAdminSynchronizationTestController(
    synchronizationService: $failureService
);
$_POST = [
    'indicador_id' => '7',
    '_form_token' => formToken('ppa_indicator_synchronize'),
];
$failureRedirect = $captureRedirect($failureController->synchronizeIndicator(...));

$assertSame('admin/ppa', $failureRedirect, 'falha externa deve retornar ao painel');
$assertSame(
    'danger',
    $_SESSION['ppa_admin_feedback']['type'] ?? null,
    'falha externa deve mostrar feedback de erro'
);
$assertSame(
    'Fonte externa indisponível.',
    $_SESSION['ppa_admin_feedback']['message'] ?? null,
    'deve mostrar a mensagem controlada da sincronização'
);

$view = file_get_contents(__DIR__ . '/../app/Views/admin/ppa/index.html');
$routes = file_get_contents(__DIR__ . '/../routes/web.php');

$assertSame(
    true,
    is_string($view) && str_contains($view, 'method="post"'),
    'o formulário de sincronização deve usar POST'
);
$assertSame(
    true,
    is_string($view) && str_contains($view, "form_token_input('ppa_indicator_synchronize')"),
    'o formulário deve incluir o token de segurança'
);
$assertSame(
    true,
    is_string($view) && str_contains($view, 'window.confirm('),
    'a interface deve pedir confirmação antes de sincronizar'
);
$assertSame(
    true,
    is_string($view) && str_contains($view, 'Sincronizando...'),
    'o botão deve mostrar que a operação está em andamento'
);
$assertSame(
    true,
    is_string($routes) && str_contains(
        $routes,
        "Route::post('/admin/ppa/sincronizar', [PpaAdminController::class, 'synchronizeIndicator']);"
    ),
    'a sincronização deve estar exposta somente por uma rota POST'
);
$renderedDashboard = (new PpaAdminSynchronizationTemplate())->renderDashboard();
$assertSame(
    true,
    str_contains($renderedDashboard, 'name="_form_token"'),
    'o template completo deve compilar e renderizar o token de segurança'
);
$assertSame(
    true,
    str_contains($renderedDashboard, 'Última atualização'),
    'a tabela deve identificar a coluna da última atualização'
);
$assertSame(
    true,
    str_contains($renderedDashboard, '28/07/2026 14:30'),
    'a tabela deve formatar a data da última sincronização concluída'
);
$assertSame(
    true,
    str_contains($renderedDashboard, 'Nunca sincronizado'),
    'a tabela deve identificar indicadores ainda pendentes'
);
$assertSame(
    2,
    substr_count($renderedDashboard, 'class="ppa-synchronization-form d-inline"'),
    'cada indicador deve possuir seu próprio formulário de sincronização'
);
$assertSame(
    2,
    substr_count($renderedDashboard, 'class="ppa-synchronization-button btn btn-sm btn-primary"'),
    'cada indicador deve possuir seu próprio botão'
);

$_POST = [];
$_SESSION = [];

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (21 assertions)\n");
