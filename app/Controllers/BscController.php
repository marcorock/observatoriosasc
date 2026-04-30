<?php

namespace App\Controllers;

use App\Core\Template as Views;
use App\Models\BscModel;
use App\Models\DashboardConfigModel;

class BscController extends Views
{
    public function index()
    {
        $all = $this->dashboardPayload();

        // https://www.youtube.com/watch?v=oIFzqCZ53cg
        // https://www.youtube.com/watch?v=s9aJMZiRZXQ

        return $this->render("bsc/index.html", [
            'name' => "BSC - Planejamento estratégico e acompanhamento de indicadores",
            'description' => "BALANCED SCORECARD",
            "dados" => $all,
            "periodo" => $all['periodo'],
            "is_admin" => $this->isDashboardAdmin()
        ]);
    }

    public function dashboardData()
    {
        $this->json($this->dashboardPayload());
    }

    public function setLocalPeriod()
    {
        $period = $this->periodFromRequest();
        ensureFormTokenSession();

        $_SESSION['dashboard_local_period'] = $period;

        $this->json($this->dashboardPayload());
    }

    public function clearLocalPeriod()
    {
        ensureFormTokenSession();
        unset($_SESSION['dashboard_local_period']);

        $this->json($this->dashboardPayload());
    }

    public function setGlobalPeriod()
    {
        if (!$this->isDashboardAdmin()) {
            http_response_code(403);
            $this->json(['error' => 'Somente administradores podem alterar o filtro global.']);
        }

        $period = $this->periodFromRequest();
        $result = (new DashboardConfigModel())->updateGlobalPeriod($period['data_inicio'], $period['data_fim']);

        if (is_string($result)) {
            http_response_code(500);
            $this->json(['error' => $result]);
        }

        $this->json($this->dashboardPayload());
    }

    public function create()
    {
        return $this->render("bsc/create.html", [
            'name' => "Novo Registro BSC",
            'description' => "Cadastrar novo acompanhamento",
            'dados' => []
        ]);
    }

    public function records()
    {
        $model = new BscModel();
        $dados = $model->readAll();

        return $this->render("bsc/list.html", [
            'name' => "Registros BSC",
            'description' => "Tabela completa com exportação e responsividade",
            'erro' => is_string($dados) ? $dados : null,
            'dados' => is_array($dados) ? $dados : []
        ]);
    }

    public function show($id)
    {
        $model = new BscModel();
        $dados = $model->readById((int) $id);

        if (is_string($dados)) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        return $this->render("bsc/show.html", [
            'name' => "Visualizar Registro BSC",
            'description' => "Detalhes do acompanhamento",
            'dados' => $dados
        ]);
    }

    public function store()
    {
        if (!validateFormToken('bsc_create')) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        $model = new BscModel();

        $result = $model->create($_POST);

        if ($result === true) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        return $this->render("bsc/create.html", [
            'name' => "Novo Registro BSC",
            'description' => "Cadastrar novo acompanhamento",
            'erro' => $result,
            'dados' => $_POST
        ]);
    }

    public function edit($id)
    {
        $model = new BscModel();

        $dados = $model->readById((int) $id);

        if (is_string($dados)) {
            header("Location: " . url('bsc'));
            exit;
        }

        return $this->render("bsc/edit.html", [
            'name' => "Editar Registro BSC",
            'description' => "Editar acompanhamento",
            'dados' => $dados
        ]);
    }

    public function update($id)
    {
        if (!validateFormToken('bsc_edit_' . (int) $id)) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        $model = new BscModel();

        $result = $model->updateById((int) $id, $_POST);

        if ($result === true) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        $dados = (object) array_merge($_POST, [
            'id' => (int) $id
        ]);

        return $this->render("bsc/edit.html", [
            'name' => "Editar Registro BSC",
            'description' => "Editar acompanhamento",
            'erro' => $result,
            'dados' => $dados
        ]);
    }

    public function delete($id)
    {
        if (!validateFormToken('bsc_delete_' . (int) $id)) {
            header("Location: " . url('bsc/registros'));
            exit;
        }

        $model = new BscModel();

        $model->deleteById((int) $id);

        header("Location: " . url('bsc/registros'));
        exit;
    }

    private function dashboardPayload(): array
    {
        $period = $this->resolvePeriod();
        $model = new BscModel();
        $allData = $model->readAll($period['data_inicio'], $period['data_fim']);

        return [
            'situacao' => $model->countColumn('situacao', $period['data_inicio'], $period['data_fim']),
            'eixo' => $model->countColumn('eixo', $period['data_inicio'], $period['data_fim']),
            'registros' => is_array($allData) ? $allData : [],
            'total_geral' => is_array($allData) ? count($allData) : 0,
            'periodo' => $period,
            'erro' => is_string($allData) ? $allData : null,
        ];
    }

    private function resolvePeriod(): array
    {
        ensureFormTokenSession();

        $global = (new DashboardConfigModel())->getGlobalPeriod();
        $local = $_SESSION['dashboard_local_period'] ?? null;

        if (is_array($local)) {
            return [
                'data_inicio' => $this->normalizeDate($local['data_inicio'] ?? null),
                'data_fim' => $this->normalizeDate($local['data_fim'] ?? null),
                'tipo' => 'local',
                'global' => $global,
            ];
        }

        return [
            'data_inicio' => $this->normalizeDate($global['data_inicio'] ?? null),
            'data_fim' => $this->normalizeDate($global['data_fim'] ?? null),
            'tipo' => ($global['data_inicio'] ?? null) || ($global['data_fim'] ?? null) ? 'global' : 'none',
            'global' => $global,
        ];
    }

    private function periodFromRequest(): array
    {
        return [
            'data_inicio' => $this->normalizeDate($_POST['data_inicio'] ?? null),
            'data_fim' => $this->normalizeDate($_POST['data_fim'] ?? null),
        ];
    }

    private function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }

    private function isDashboardAdmin(): bool
    {
        return strtolower((string) ($_ENV['DASHBOARD_USER_PROFILE'] ?? 'user')) === 'admin';
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
