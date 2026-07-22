<?php

use Pecee\SimpleRouter\SimpleRouter as Route;

/** ###############################################################
 * ? Configuração do Timezone e checagem de erros padrão do sistema
 *  ###############################################################
 */
date_default_timezone_set("America/Sao_Paulo");

// Capturando a rota atual
if (!function_exists('captureRoute')) {
    function captureRoute(): string
    {
        $path = Route::request()->getUrl()->getPath();
        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);

        return ($lastSegment === false || $lastSegment === '') ? '/' : $lastSegment;
    }
}

// Roteamento entre sistemas por página.
switch (captureRoute()) {
    case 'sascsa':
        $host = $_ENV['SASCSA_DB_HOST'];
        $port = $_ENV['SASCSA_DB_PORT'];
        $name = $_ENV['SASCSA_DB_NAME'];
        $user = $_ENV['SASCSA_DB_USER'];
        $pass = $_ENV['SASCSA_DB_PASS'];
        $charset = $_ENV['SASCSA_DB_CHARSET'];
        break;
    case 'osc':
        $host = $_ENV['OSC_DB_HOST'];
        $port = $_ENV['OSC_DB_PORT'];
        $name = $_ENV['OSC_DB_NAME'];
        $user = $_ENV['OSC_DB_USER'];
        $pass = $_ENV['OSC_DB_PASS'];
        $charset = $_ENV['OSC_DB_CHARSET'];
        break;
    case 'cadunico':
        $host = $_ENV['CADUNICO_DB_HOST'];
        $port = $_ENV['CADUNICO_DB_PORT'];
        $name = $_ENV['CADUNICO_DB_NAME'];
        $user = $_ENV['CADUNICO_DB_USER'];
        $pass = $_ENV['CADUNICO_DB_PASS'];
        $charset = $_ENV['CADUNICO_DB_CHARSET'];
        break;
    default:
        $host = NULL;
        $port = NULL;
        $name = NULL;
        $user = NULL;
        $pass = NULL;
        $charset = NULL;
}

/**
 *  Dados para conexão com o banco de dados
 */
define("DB_HOST", $host ?? null );
define("DB_PORT", $port ?? NULL );
define("DB_NAME", $name ?? NULL );
define("DB_USER", $user ?? NULL );
define("DB_PASS", $pass ?? NULL );
define("DB_CHARSET", $charset ?? NULL );
define("TESTE", "Olá mundo");
