<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\ExternalQueryModel;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;

class PpaAdminController extends Template
{
    public function dashboard()
    {
        adminRequireAuth('admin');
        adminEnsureSession();

        $indicatorCount = (new PpaIndicatorModel())->readAll();
        $linkCount = (new PpaIndicatorQueryModel())->readAll();
        $feedback = $_SESSION['ppa_admin_feedback'] ?? null;
        unset($_SESSION['ppa_admin_feedback']);

        echo $this->render('admin/ppa/index.html', array_merge([
            'feedback' => $feedback,
            'indicadores_total' => is_array($indicatorCount) ? count($indicatorCount) : 0,
            'vinculos_total' => is_array($linkCount) ? count($linkCount) : 0,
        ], $this->pageDefaults('Modulo PPA', 'Cadastro de indicadores e vinculos com consultas externas')));
    }

    public function indicators()
    {
        adminRequireAuth('admin');
        adminEnsureSession();

        $dados = (new PpaIndicatorModel())->readAll();
        $feedback = $_SESSION['ppa_admin_feedback'] ?? null;
        unset($_SESSION['ppa_admin_feedback']);

        echo $this->render('admin/ppa/indicadores/index.html', array_merge([
            'erro' => is_string($dados) ? $dados : null,
            'dados' => is_array($dados) ? $dados : [],
            'feedback' => $feedback,
        ], $this->pageDefaults('Indicadores PPA', 'Cadastro base dos indicadores do modulo PPA', [
            [
                'kind' => 'link',
                'label' => 'Painel PPA',
                'icon' => 'bi-kanban',
                'url' => url('admin/ppa'),
                'class' => 'text-primary',
            ],
            [
                'kind' => 'link',
                'label' => 'Novo Indicador',
                'icon' => 'bi-plus-square',
                'url' => url('admin/ppa/indicadores/novo'),
                'class' => 'text-success',
            ],
            [
                'kind' => 'link',
                'label' => 'Vinculos',
                'icon' => 'bi-link-45deg',
                'url' => url('admin/ppa/vinculos'),
                'class' => 'text-info',
            ],
        ])));
    }

    public function createIndicator()
    {
        adminRequireAuth('admin');

        echo $this->render('admin/ppa/indicadores/create.html', array_merge([
            'erro' => null,
            'dados' => $this->defaultIndicatorData(),
        ], $this->pageDefaults('Novo Indicador PPA', 'Cadastro de indicador generico do modulo PPA')));
    }

    public function storeIndicator()
    {
        adminRequireAuth('admin');

        if (!validateFormToken('ppa_indicator_create')) {
            header('Location: ' . url('admin/ppa/indicadores/novo'));
            exit;
        }

        $data = $this->indicatorDataFromRequest();
        $result = (new PpaIndicatorModel())->create($data);

        if ($result !== true) {
            echo $this->render('admin/ppa/indicadores/create.html', array_merge([
                'erro' => $result,
                'dados' => (object) $data,
            ], $this->pageDefaults('Novo Indicador PPA', 'Cadastro de indicador generico do modulo PPA')));
            return;
        }

        $this->setFeedback('success', 'Indicador do PPA cadastrado com sucesso.');
        header('Location: ' . url('admin/ppa/indicadores'));
        exit;
    }

    public function editIndicator($id)
    {
        adminRequireAuth('admin');

        $dados = (new PpaIndicatorModel())->readById((int) $id);

        if (is_string($dados)) {
            header('Location: ' . url('admin/ppa/indicadores'));
            exit;
        }

        echo $this->render('admin/ppa/indicadores/edit.html', array_merge([
            'erro' => null,
            'dados' => $dados,
        ], $this->pageDefaults('Editar Indicador PPA', 'Atualizacao do cadastro base do indicador')));
    }

    public function updateIndicator($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('ppa_indicator_edit_' . $id)) {
            header('Location: ' . url('admin/ppa/indicadores/editar/' . $id));
            exit;
        }

        $data = $this->indicatorDataFromRequest();
        $result = (new PpaIndicatorModel())->updateById($id, $data);

        if ($result !== true) {
            echo $this->render('admin/ppa/indicadores/edit.html', array_merge([
                'erro' => $result,
                'dados' => (object) array_merge($data, ['id' => $id]),
            ], $this->pageDefaults('Editar Indicador PPA', 'Atualizacao do cadastro base do indicador')));
            return;
        }

        $this->setFeedback('success', 'Indicador do PPA atualizado com sucesso.');
        header('Location: ' . url('admin/ppa/indicadores'));
        exit;
    }

    public function deleteIndicator($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('ppa_indicator_delete_' . $id)) {
            header('Location: ' . url('admin/ppa/indicadores'));
            exit;
        }

        $result = (new PpaIndicatorModel())->deleteById($id);

        $this->setFeedback($result === true ? 'success' : 'danger', $result === true ? 'Indicador do PPA excluido com sucesso.' : $result);

        header('Location: ' . url('admin/ppa/indicadores'));
        exit;
    }

    public function links()
    {
        adminRequireAuth('admin');
        adminEnsureSession();

        $dados = (new PpaIndicatorQueryModel())->readAll();
        $feedback = $_SESSION['ppa_admin_feedback'] ?? null;
        unset($_SESSION['ppa_admin_feedback']);

        echo $this->render('admin/ppa/vinculos/index.html', array_merge([
            'erro' => is_string($dados) ? $dados : null,
            'dados' => is_array($dados) ? $dados : [],
            'feedback' => $feedback,
        ], $this->pageDefaults('Vinculos PPA x Consultas', 'Relacionamento entre indicadores do PPA e consultas externas', [
            [
                'kind' => 'link',
                'label' => 'Painel PPA',
                'icon' => 'bi-kanban',
                'url' => url('admin/ppa'),
                'class' => 'text-primary',
            ],
            [
                'kind' => 'link',
                'label' => 'Indicadores',
                'icon' => 'bi-list-task',
                'url' => url('admin/ppa/indicadores'),
                'class' => 'text-body',
            ],
            [
                'kind' => 'link',
                'label' => 'Novo Vinculo',
                'icon' => 'bi-link-45deg',
                'url' => url('admin/ppa/vinculos/novo'),
                'class' => 'text-success',
            ],
        ])));
    }

    public function createLink()
    {
        adminRequireAuth('admin');

        echo $this->render('admin/ppa/vinculos/create.html', array_merge([
            'erro' => null,
            'dados' => $this->defaultLinkData(),
            'indicators' => (new PpaIndicatorModel())->readActiveOptions(),
            'queries' => (new ExternalQueryModel())->readActiveOptions(),
        ], $this->pageDefaults('Novo Vinculo PPA', 'Associe um indicador do PPA a uma consulta externa')));
    }

    public function storeLink()
    {
        adminRequireAuth('admin');

        if (!validateFormToken('ppa_link_create')) {
            header('Location: ' . url('admin/ppa/vinculos/novo'));
            exit;
        }

        $data = $this->linkDataFromRequest();
        $result = (new PpaIndicatorQueryModel())->create($data);

        if ($result !== true) {
            echo $this->render('admin/ppa/vinculos/create.html', array_merge([
                'erro' => $result,
                'dados' => (object) $data,
                'indicators' => (new PpaIndicatorModel())->readActiveOptions(),
                'queries' => (new ExternalQueryModel())->readActiveOptions(),
            ], $this->pageDefaults('Novo Vinculo PPA', 'Associe um indicador do PPA a uma consulta externa')));
            return;
        }

        $this->setFeedback('success', 'Vinculo cadastrado com sucesso.');
        header('Location: ' . url('admin/ppa/vinculos'));
        exit;
    }

    public function editLink($id)
    {
        adminRequireAuth('admin');

        $dados = (new PpaIndicatorQueryModel())->readById((int) $id);

        if (is_string($dados)) {
            header('Location: ' . url('admin/ppa/vinculos'));
            exit;
        }

        echo $this->render('admin/ppa/vinculos/edit.html', array_merge([
            'erro' => null,
            'dados' => $dados,
            'indicators' => (new PpaIndicatorModel())->readActiveOptions(),
            'queries' => (new ExternalQueryModel())->readActiveOptions(),
        ], $this->pageDefaults('Editar Vinculo PPA', 'Atualize o relacionamento entre indicador e consulta externa')));
    }

    public function updateLink($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('ppa_link_edit_' . $id)) {
            header('Location: ' . url('admin/ppa/vinculos/editar/' . $id));
            exit;
        }

        $data = $this->linkDataFromRequest();
        $result = (new PpaIndicatorQueryModel())->updateById($id, $data);

        if ($result !== true) {
            echo $this->render('admin/ppa/vinculos/edit.html', array_merge([
                'erro' => $result,
                'dados' => (object) array_merge($data, ['id' => $id]),
                'indicators' => (new PpaIndicatorModel())->readActiveOptions(),
                'queries' => (new ExternalQueryModel())->readActiveOptions(),
            ], $this->pageDefaults('Editar Vinculo PPA', 'Atualize o relacionamento entre indicador e consulta externa')));
            return;
        }

        $this->setFeedback('success', 'Vinculo atualizado com sucesso.');
        header('Location: ' . url('admin/ppa/vinculos'));
        exit;
    }

    public function deleteLink($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('ppa_link_delete_' . $id)) {
            header('Location: ' . url('admin/ppa/vinculos'));
            exit;
        }

        $result = (new PpaIndicatorQueryModel())->deleteById($id);

        $this->setFeedback($result === true ? 'success' : 'danger', $result === true ? 'Vinculo excluido com sucesso.' : $result);

        header('Location: ' . url('admin/ppa/vinculos'));
        exit;
    }

    private function pageDefaults(string $title, string $subtitle, array $menuItems = []): array
    {
        return [
            'name' => $title,
            'description' => $subtitle,
            'header_title' => 'Observatorio Socioassistencial - SJC',
            'header_subtitle' => $subtitle,
            'header_menu_items' => array_merge([
                [
                    'kind' => 'link',
                    'label' => 'Home',
                    'icon' => 'bi-house-door',
                    'url' => url(''),
                    'class' => 'text-body',
                ],
                [
                    'kind' => 'link',
                    'label' => 'Painel',
                    'icon' => 'bi-speedometer2',
                    'url' => url('admin/painel'),
                    'class' => 'text-primary',
                ],
                [
                    'kind' => 'theme',
                ],
            ], $menuItems),
        ];
    }

    private function defaultIndicatorData(): object
    {
        return (object) [
            'codigo_indicador' => '',
            'numero_programa' => '',
            'nome' => '',
            'unidade_medida' => '',
            'indice_recente' => '',
            'indice_futuro' => '',
            'tipo_apuracao' => 'manual',
            'periodicidade' => 'anual',
            'objetivo' => '',
            'justificativa' => '',
            'publico_alvo' => '',
            'ods_codigo' => '',
            'ods_descricao' => '',
            'meta_ods' => '',
            'memoria_calculo' => '',
            'fonte_dados' => '',
            'criterio_utilizado' => '',
            'forma_calculo' => '',
            'resultado_esperado' => '',
            'ativo' => 1,
        ];
    }

    private function defaultLinkData(): object
    {
        return (object) [
            'indicador_id' => '',
            'external_query_id' => '',
            'papel' => 'principal',
            'campo_resultado' => '',
            'observacao' => '',
            'ativo' => 1,
        ];
    }

    private function indicatorDataFromRequest(): array
    {
        return [
            'codigo_indicador' => trim((string) ($_POST['codigo_indicador'] ?? '')),
            'numero_programa' => trim((string) ($_POST['numero_programa'] ?? '')),
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'unidade_medida' => trim((string) ($_POST['unidade_medida'] ?? '')),
            'indice_recente' => trim((string) ($_POST['indice_recente'] ?? '')),
            'indice_futuro' => trim((string) ($_POST['indice_futuro'] ?? '')),
            'tipo_apuracao' => trim((string) ($_POST['tipo_apuracao'] ?? 'manual')),
            'periodicidade' => trim((string) ($_POST['periodicidade'] ?? 'anual')),
            'objetivo' => trim((string) ($_POST['objetivo'] ?? '')),
            'justificativa' => trim((string) ($_POST['justificativa'] ?? '')),
            'publico_alvo' => trim((string) ($_POST['publico_alvo'] ?? '')),
            'ods_codigo' => trim((string) ($_POST['ods_codigo'] ?? '')),
            'ods_descricao' => trim((string) ($_POST['ods_descricao'] ?? '')),
            'meta_ods' => trim((string) ($_POST['meta_ods'] ?? '')),
            'memoria_calculo' => trim((string) ($_POST['memoria_calculo'] ?? '')),
            'fonte_dados' => trim((string) ($_POST['fonte_dados'] ?? '')),
            'criterio_utilizado' => trim((string) ($_POST['criterio_utilizado'] ?? '')),
            'forma_calculo' => trim((string) ($_POST['forma_calculo'] ?? '')),
            'resultado_esperado' => trim((string) ($_POST['resultado_esperado'] ?? '')),
            'ativo' => (string) ($_POST['ativo'] ?? '1'),
        ];
    }

    private function linkDataFromRequest(): array
    {
        return [
            'indicador_id' => (int) ($_POST['indicador_id'] ?? 0),
            'external_query_id' => (int) ($_POST['external_query_id'] ?? 0),
            'papel' => trim((string) ($_POST['papel'] ?? 'principal')),
            'campo_resultado' => trim((string) ($_POST['campo_resultado'] ?? '')),
            'observacao' => trim((string) ($_POST['observacao'] ?? '')),
            'ativo' => (string) ($_POST['ativo'] ?? '1'),
        ];
    }

    private function setFeedback(string $type, string $message): void
    {
        adminEnsureSession();
        $_SESSION['ppa_admin_feedback'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
