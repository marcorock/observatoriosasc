<?php

namespace App\Core;

use Pecee\SimpleRouter\SimpleRouter;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException as NotFoundException;



class Router extends SimpleRouter
{
    /**
     * Inicializa as rotas da aplicação e processa a requisição atual.
     * 
     * @return void
     */
    public static function start(): void
    {
        // Arquivos de Rotas
        require_once __DIR__."/../../routes/web.php";
        
        // Iniciando o Roteador
        try {
            parent::enableMultiRouteRendering(false);
            parent::start();
        } catch (NotFoundException $th) {
            die("Error: {$th->getCode()}, {$th->getMessage()}");
        }
    }
}