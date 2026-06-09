<?php

namespace App\Core;

use App\Utils\ViewClasses;

use Twig\Environment;
use Twig\Loader\FilesystemLoader as Loader;
use Twig\Error\LoaderError as Error;
use Twig\Extension\DebugExtension as Debug;
use Twig\TwigFunction;
use Twig\Lexer;

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
            $lexar = new Lexer($this->template, [
                $this->ViewClasses()
            ]);
            $this->template->setLexer($lexar);

            $this->template->addFunction(
                new TwigFunction('form_token_input', function ($key = 'default') {
                    return formTokenInput($key);
                }, ['is_safe' => ['html']])
            );

            $this->template->addFunction(
                new TwigFunction('admin_is_authenticated', function () {
                    return function_exists('adminIsAuthenticated') ? adminIsAuthenticated() : false;
                })
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

    // Funções auxiliares para o front-end
    private function ViewClasses(): void
    {
        [
            $this->template->addFunction(new TwigFunction('url', function (string $url) {
                return ViewClasses::url($url);
            })),
            $this->template->addFunction(new TwigFunction('slug', function (string $frase) {
                return ViewClasses::slug($frase);
            })),
            $this->template->addFunction(new TwigFunction('conteTempo', function ($data) {
                return ViewClasses::countTime($data);
            })),
            $this->template->addFunction(new TwigFunction('csrf_field', function () {
                return ViewClasses::csrf_field();
            })),
            $this->template->addFunction(new TwigFunction('saudacao', function () {
                return ViewClasses::greeting();
            })),
            $this->template->addFunction(new TwigFunction('calcDiasAfastado', function ($daybegin, $dayEnd = null, $previewsDayEnd = null) {
                return ViewClasses::calcDaysAway($daybegin, $dayEnd, $previewsDayEnd);
            })),
            $this->template->addFunction(new TwigFunction('resumo', function (string $text, int $limite) {
                return ViewClasses::resume($text, $limite);
            }))
        ];
    }
}
