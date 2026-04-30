<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader as Loader;
use Twig\Error\LoaderError as Error;
use Twig\Extension\DebugExtension as Debug;
use Twig\TwigFunction;

abstract class Template 
{
    private Environment $template;

    public function __construct()
    {
        try {
            $loader = new Loader(__DIR__."/../../app/Views/");
            
            $this->template = new Environment($loader,[
                'debug' => true,
                // "cache" => "/../../storage/cache/",
                "auto_reload" => true
            ]);

            // Debug do Twig
            $this->template->addExtension(new Debug());

            /**
             * 🔥 Função global url() para usar no Twig
             * Exemplo: {{ url('bsc/store') }}
             */
            $this->template->addFunction(
                new TwigFunction('url', function ($path = '') {
                    return url($path);
                })
            );

            $this->template->addFunction(
                new TwigFunction('form_token_input', function ($key = 'default') {
                    return formTokenInput($key);
                }, ['is_safe' => ['html']])
            );

        } catch (Error $th) {
           die("Este diretório não existe ou não foi implementado");
        }
    }

    protected function render(string $template, array $data=[])
    {
        try {
            return $this->template->render($template,$data);
        } catch (Error $th) {
           die("O template {$template}, não existe ou não foi implementado");
        }
    }
}
