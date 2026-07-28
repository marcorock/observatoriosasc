<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\ExternalDataSourceModel;
use App\Models\ExternalDatabaseRuntime;
use App\Models\ExternalQueryModel;

class ExternalDatabaseAdminController extends Template
{
    private ?ExternalDataSourceModel $sourceModel;
    private ?ExternalQueryModel $queryModel;

    public function __construct(
        ?ExternalDataSourceModel $sourceModel = null,
        ?ExternalQueryModel $queryModel = null
    ) {
        parent::__construct();
        $this->sourceModel = $sourceModel;
        $this->queryModel = $queryModel;
    }

    public function sources()
    {
        adminRequireAuth('admin');
        adminEnsureSession();

        $dados = $this->sourceModel()->readAll();
        $feedback = $_SESSION['external_db_feedback'] ?? null;
        unset($_SESSION['external_db_feedback']);

        echo $this->render('admin/external_databases/sources/index.html', array_merge([
            'admin' => $this->adminSession(),
            'erro' => is_string($dados) ? $dados : null,
            'dados' => is_array($dados) ? $dados : [],
            'feedback' => $feedback,
        ], $this->pageDefaults('Bases de Dados Externas', 'Cadastro e teste de conexoes MySQL/MariaDB externas', [
            [
                'kind' => 'link',
                'label' => 'Nova Conexao',
                'icon' => 'bi-database-add',
                'url' => url('admin/bases-externas/nova'),
                'class' => 'text-success',
            ],
            [
                'kind' => 'link',
                'label' => 'Consultas',
                'icon' => 'bi-braces-asterisk',
                'url' => url('admin/bases-externas/consultas'),
                'class' => 'text-info',
            ],
        ])));
    }

    public function createSource()
    {
        adminRequireAuth('admin');

        echo $this->render('admin/external_databases/sources/create.html', array_merge([
            'erro' => null,
            'dados' => $this->defaultSourceData(),
        ], $this->pageDefaults('Nova Base Externa', 'Cadastro de conexao externa MySQL/MariaDB')));
    }

    public function storeSource()
    {
        adminRequireAuth('admin');

        if (!validateFormToken('external_source_create')) {
            $this->redirect('admin/bases-externas/nova');
        }

        $data = $this->sourceDataFromRequest(true);
        $result = $this->sourceModel()->create($data);

        if ($result !== true) {
            echo $this->render('admin/external_databases/sources/create.html', array_merge([
                'erro' => $result,
                'dados' => (object) $data,
            ], $this->pageDefaults('Nova Base Externa', 'Cadastro de conexao externa MySQL/MariaDB')));
            return;
        }

        $this->redirect('admin/bases-externas');
    }

    public function editSource($id)
    {
        adminRequireAuth('admin');

        $dados = $this->sourceModel()->readById((int) $id);

        if (is_string($dados)) {
            $this->redirect('admin/bases-externas');
        }

        echo $this->render('admin/external_databases/sources/edit.html', array_merge([
            'erro' => null,
            'dados' => (object) [
                'id' => (int) $dados->id,
                'nome' => (string) $dados->nome,
                'host' => (string) $dados->host,
                'porta' => (int) $dados->porta,
                'database_name' => (string) $dados->database_name,
                'username' => (string) $dados->username,
                'charset' => (string) $dados->charset,
                'descricao' => (string) ($dados->descricao ?? ''),
                'ativo' => (int) $dados->ativo,
            ],
        ], $this->pageDefaults('Editar Base Externa', 'Atualizacao da conexao externa cadastrada')));
    }

    public function updateSource($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_source_edit_' . $id)) {
            $this->redirect('admin/bases-externas/editar/' . $id);
        }

        $data = $this->sourceDataFromRequest(false);
        $result = $this->sourceModel()->updateById($id, $data);

        if ($result !== true) {
            echo $this->render('admin/external_databases/sources/edit.html', array_merge([
                'erro' => $result,
                'dados' => (object) array_merge($data, ['id' => $id]),
            ], $this->pageDefaults('Editar Base Externa', 'Atualizacao da conexao externa cadastrada')));
            return;
        }

        $this->redirect('admin/bases-externas');
    }

    public function deleteSource($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_source_delete_' . $id)) {
            $this->redirect('admin/bases-externas');
        }

        $result = $this->sourceModel()->deleteById($id);

        if ($result !== true) {
            $this->setFeedback('danger', $result);
        }

        $this->redirect('admin/bases-externas');
    }

    public function testSource($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_source_test_' . $id)) {
            $this->redirect('admin/bases-externas');
        }

        $source = $this->sourceModel()->readById($id);

        if (is_string($source)) {
            $this->setFeedback('danger', $source);
            $this->redirect('admin/bases-externas');
        }

        $result = ExternalDatabaseRuntime::testConnection($source);
        $this->setFeedback($result['success'] ? 'success' : 'danger', $result['message']);
        $this->redirect('admin/bases-externas');
    }

    public function queries()
    {
        adminRequireAuth('admin');
        adminEnsureSession();

        $dados = $this->queryModel()->readAll();
        $feedback = $_SESSION['external_db_feedback'] ?? null;
        unset($_SESSION['external_db_feedback']);

        echo $this->render('admin/external_databases/queries/index.html', array_merge([
            'admin' => $this->adminSession(),
            'erro' => is_string($dados) ? $dados : null,
            'dados' => is_array($dados) ? $dados : [],
            'feedback' => $feedback,
        ], $this->pageDefaults('Consultas Externas', 'Consultas SELECT consolidadas para dashboards', [
            [
                'kind' => 'link',
                'label' => 'Bases Externas',
                'icon' => 'bi-hdd-network',
                'url' => url('admin/bases-externas'),
                'class' => 'text-primary',
            ],
            [
                'kind' => 'link',
                'label' => 'Nova Consulta',
                'icon' => 'bi-plus-square',
                'url' => url('admin/bases-externas/consultas/nova'),
                'class' => 'text-success',
            ],
        ])));
    }

    public function createQuery()
    {
        adminRequireAuth('admin');
        $sources = $this->sourceModel()->readActiveOptions();

        echo $this->render('admin/external_databases/queries/create.html', array_merge([
            'erro' => null,
            'dados' => $this->defaultQueryData(),
            'sources' => $sources,
        ], $this->pageDefaults('Nova Consulta Externa', 'Cadastro de consulta SELECT vinculada a uma base externa')));
    }

    public function storeQuery()
    {
        adminRequireAuth('admin');

        if (!validateFormToken('external_query_create')) {
            $this->redirect('admin/bases-externas/consultas/nova');
        }

        $data = $this->queryDataFromRequest();
        $sources = $this->sourceModel()->readActiveOptions();
        $result = $this->queryModel()->create($data);

        if ($result !== true) {
            echo $this->render('admin/external_databases/queries/create.html', array_merge([
                'erro' => $result,
                'dados' => (object) $data,
                'sources' => $sources,
            ], $this->pageDefaults('Nova Consulta Externa', 'Cadastro de consulta SELECT vinculada a uma base externa')));
            return;
        }

        $this->redirect('admin/bases-externas/consultas');
    }

    public function editQuery($id)
    {
        adminRequireAuth('admin');
        $sources = $this->sourceModel()->readActiveOptions();
        $dados = $this->queryModel()->readById((int) $id);

        if (is_string($dados)) {
            $this->redirect('admin/bases-externas/consultas');
        }

        echo $this->render('admin/external_databases/queries/edit.html', array_merge([
            'erro' => null,
            'dados' => $dados,
            'sources' => $sources,
        ], $this->pageDefaults('Editar Consulta Externa', 'Atualizacao da consulta consolidada cadastrada')));
    }

    public function updateQuery($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_query_edit_' . $id)) {
            $this->redirect('admin/bases-externas/consultas/editar/' . $id);
        }

        $data = $this->queryDataFromRequest();
        $sources = $this->sourceModel()->readActiveOptions();
        $result = $this->queryModel()->updateById($id, $data);

        if ($result !== true) {
            echo $this->render('admin/external_databases/queries/edit.html', array_merge([
                'erro' => $result,
                'dados' => (object) array_merge($data, ['id' => $id]),
                'sources' => $sources,
            ], $this->pageDefaults('Editar Consulta Externa', 'Atualizacao da consulta consolidada cadastrada')));
            return;
        }

        $this->redirect('admin/bases-externas/consultas');
    }

    public function deleteQuery($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_query_delete_' . $id)) {
            $this->redirect('admin/bases-externas/consultas');
        }

        $result = $this->queryModel()->deleteById($id);

        if ($result !== true) {
            $this->setFeedback('danger', $result);
        }

        $this->redirect('admin/bases-externas/consultas');
    }

    public function testQuery($id)
    {
        adminRequireAuth('admin');
        $id = (int) $id;

        if (!validateFormToken('external_query_test_' . $id)) {
            $this->redirect('admin/bases-externas/consultas');
        }

        $query = $this->queryModel()->readById($id);

        if (is_string($query)) {
            $this->setFeedback('danger', $query);
            $this->redirect('admin/bases-externas/consultas');
        }

        $source = $this->sourceModel()->readById((int) $query->source_id);

        if (is_string($source)) {
            $this->setFeedback('danger', $source);
            $this->redirect('admin/bases-externas/consultas');
        }

        $preview = ExternalDatabaseRuntime::runRegisteredQuery($source, $query);

        echo $this->render('admin/external_databases/queries/test.html', array_merge([
            'query' => $query,
            'source' => $source,
            'preview' => $preview,
        ], $this->pageDefaults('Teste de Consulta Externa', 'Validacao manual do retorno consolidado da consulta')));
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

    private function adminSession(): object
    {
        return (object) ($_SESSION['admin_auth'] ?? []);
    }

    private function defaultSourceData(): object
    {
        return (object) [
            'nome' => '',
            'host' => '',
            'porta' => 3306,
            'database_name' => '',
            'username' => '',
            'charset' => 'utf8mb4',
            'descricao' => '',
            'ativo' => 1,
        ];
    }

    private function defaultQueryData(): object
    {
        return (object) [
            'source_id' => '',
            'nome' => '',
            'descricao' => '',
            'sql_query' => "SELECT\n    coluna,\n    COUNT(*) AS total\nFROM tabela\nGROUP BY coluna",
            'ativo' => 1,
        ];
    }

    private function sourceDataFromRequest(bool $includePassword): array
    {
        return [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'host' => trim((string) ($_POST['host'] ?? '')),
            'porta' => (int) ($_POST['porta'] ?? 3306),
            'database_name' => trim((string) ($_POST['database_name'] ?? '')),
            'username' => trim((string) ($_POST['username'] ?? '')),
            'password' => $includePassword || isset($_POST['password']) ? (string) ($_POST['password'] ?? '') : '',
            'charset' => trim((string) ($_POST['charset'] ?? 'utf8mb4')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'ativo' => (string) ($_POST['ativo'] ?? '1'),
        ];
    }

    private function queryDataFromRequest(): array
    {
        return [
            'source_id' => (int) ($_POST['source_id'] ?? 0),
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'sql_query' => trim((string) ($_POST['sql_query'] ?? '')),
            'ativo' => (string) ($_POST['ativo'] ?? '1'),
        ];
    }

    private function setFeedback(string $type, string $message): void
    {
        adminEnsureSession();
        $_SESSION['external_db_feedback'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    private function sourceModel(): ExternalDataSourceModel
    {
        return $this->sourceModel ??= new ExternalDataSourceModel();
    }

    private function queryModel(): ExternalQueryModel
    {
        return $this->queryModel ??= new ExternalQueryModel();
    }
}
