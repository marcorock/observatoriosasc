<?php

// 
require_once __DIR__."/../vendor/autoload.php";

// Funções auxiliares globais
require_once __DIR__."/../app/Utils/Helpers.php";
require_once __DIR__."/../app/Utils/FormToken.php";

//
require_once __DIR__."/../app/Core/Router.php";


/** Gerenciador de arquivos ENV */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

/** Arquivo de configuração */
require_once __DIR__."/../core/app.php";

// 
App\Core\Router::start();
