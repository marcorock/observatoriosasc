<?php

namespace App\Controllers;

use App\Core\Template as Views;
use App\Models\BscModel;

class BscController extends Views
{
    public function index()
    {
        $model = new BscModel();
        $allData = $model->readAll();

        $all = [
            'situacao' => $model->countColumn('situacao'),
            'eixo' => $model->countColumn('eixo'),
            'registros' => is_array($allData) ? $allData : [],
            'total_geral' => is_array($allData) ? count($allData) : 0
        ];

        // https://www.youtube.com/watch?v=oIFzqCZ53cg
        // https://www.youtube.com/watch?v=s9aJMZiRZXQ

        return $this->render("bsc/index.html", [
            'name' => "BSC - Planejamento estratégico e acompanhamento de indicadores",
            'description' => "BALANCED SCORECARD",
            "dados" => $all
        ]);
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
}
