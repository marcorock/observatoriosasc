<?php

namespace App\Core;

abstract class BaseController extends Template
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Define as configurações principais da página.
     */
    abstract protected function pageConfig(): array;

    /**
     * Define a quantidade de colunas por linha do layout.
     */
    abstract protected function rowsConfig(): array;

    /**
     * Mescla a configuração da página com os dados da view.
     */
    protected function renderPage(
        string $template,
        array $data = [],
        array $pageConfigOverride = [],
        array $rowsConfigOverride = []
    )
    {
        $config = array_merge([
            'title' => 'Observatório',
            'description' => '',
            'system' => 'Observatório',
            'name' => 'Observatório',
        ], $this->pageConfig(), $pageConfigOverride);

        $rows = array_merge([
            'first' => 3,
            'second' => 3,
            'third' => 3,
        ], $this->rowsConfig(), $rowsConfigOverride);

        return $this->render($template, array_merge([
            'config' => $config,
            'title' => $config['title'],
            'description' => $config['description'],
            'system' => $config['system'],
            'name' => $config['name'],
            'colunms_row' => $rows,
        ], $data));
    }

    /**
     * Renderiza a view com sobrescrita de layout e, opcionalmente, da página.
     */
    protected function renderWithRows(
        string $template,
        array $data = [],
        array $rowsConfigOverride = [],
        array $pageConfigOverride = []
    )
    {
        return $this->renderPage($template, $data, $pageConfigOverride, $rowsConfigOverride);
    }

    /**
     * Renderiza a view com sobrescrita apenas das configurações da página.
     */
    protected function renderWithConfig(
        string $template,
        array $data = [],
        array $pageConfigOverride = []
    )
    {
        return $this->renderPage($template, $data, $pageConfigOverride);
    }
}
