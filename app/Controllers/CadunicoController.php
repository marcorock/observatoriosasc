<?php

namespace App\Controllers;

use App\Core\BaseController;
// use App\Models\CadunicoModel;

class CadunicoController extends BaseController
{
    protected function pageConfig(): array
    {
        return [
            'title' => 'Cad Unico',
            'description' => 'Modulo inicial do Cadastro Unico',
            'system' => 'Observatorio Socioassistencial',
            'name' => 'Cad Unico',
        ];
    }

    protected function rowsConfig(): array
    {
        return [
            'first' => 1,
            'second' => 1,
            'third' => 1,
        ];
    }

    public function index()
    {
        // $dados = (new CadunicoModel())->readAll();
        // dd($dados); comentado pois não ha dados vindo do banco, mas a estrutura de leitura esta funcionando

        return $this->renderPage('cadunico/index.html', [
            'header_menu_items' => [
                [
                    'kind' => 'link',
                    'label' => 'Home',
                    'icon' => 'bi-house-door',
                    'url' => url(''),
                    'class' => 'text-body',
                ],
                [
                    'kind' => 'theme',
                ],
                // [
                //     'kind' => 'link',
                //     'label' => 'BSC',
                //     'icon' => 'bi-bar-chart-line',
                //     'url' => url('bsc'),
                //     'class' => 'text-info',
                // ],
            ],
            // 'dados' => $dados, //Adicionado para passar os dados do banco para a view, mas comentado pois não ha dados vindo do banco
        ]);
    }
}
