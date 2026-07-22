<?php

namespace App\Controllers;

use App\Core\BaseController;

class IndexController extends BaseController
{
    protected function pageConfig(): array
    {
        return [
            'title' => 'Menu Principal',
            'description' => 'Selecione um modulo para acessar o observatorio',
            'system' => 'Observatorio Socioassistencial',
            'name' => 'Observatorio Socioassistencial - SJCs',
        ];
    }

    protected function rowsConfig(): array
    {
        return [
            'first' => 2,
            'second' => 2,
            'third' => 2,
        ];
    }

    public function index()
    {
        return $this->renderPage('home/index.html', [
            'header_menu_items' => [
                [
                    'kind' => 'link',
                    'label' => 'Home',
                    'icon' => 'bi-house-door',
                    'url' => url(''),
                    'class' => 'text-body',
                    'disabled' => true,
                ],
                [
                    'kind' => 'theme',
                ],
                [
                    'kind' => 'link',
                    'label' => 'BSC',
                    'icon' => 'bi-bar-chart-line',
                    'url' => url('bsc'),
                    'class' => 'text-primary',
                ],
                [
                    'kind' => 'link',
                    'label' => 'PPA',
                    'icon' => 'bi-kanban',
                    'url' => url('ppa'),
                    'class' => 'text-warning',
                ],
                [
                    'kind' => 'link',
                    'label' => 'Cad Unico',
                    'icon' => 'bi-people',
                    'url' => url('cadunico'),
                    'class' => 'text-success',
                ],
            ],
            'modules' => [
                [
                    'title' => 'BSC',
                    'url' => url('bsc'),
                    'icon' => 'bi-bar-chart-line-fill',
                    'accent' => 'linear-gradient(135deg, #0f4aa1 0%, #1f6fd1 100%)',
                    'visual' => 'icon',
                ],
                [
                    'title' => 'PPA',
                    'url' => url('ppa'),
                    'icon' => 'bi-kanban-fill',
                    'accent' => 'linear-gradient(135deg, #8b5cf6 0%, #f59e0b 100%)',
                    'visual' => 'ppa',
                ],
                [
                    'title' => 'Cad Unico',
                    'url' => url('cadunico'),
                    'icon' => 'bi-people-fill',
                    'accent' => 'linear-gradient(135deg, #0b6e4f 0%, #d6b400 100%)',
                    'visual' => 'cadunico',
                ],
            ],
        ]);
    }
}
