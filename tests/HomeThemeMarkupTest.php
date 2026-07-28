<?php

declare(strict_types=1);

$viewPath = __DIR__ . '/../app/Views/home/index.html';
$view = file_get_contents($viewPath);

if ($view === false) {
    fwrite(STDERR, "FAIL: não foi possível ler a view da Home.\n");
    exit(1);
}

$assertions = 0;

$assertContains = static function (string $expected, string $message) use ($view, &$assertions): void {
    $assertions++;

    if (!str_contains($view, $expected)) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assertNotContains = static function (string $unexpected, string $message) use ($view, &$assertions): void {
    $assertions++;

    if (str_contains($view, $unexpected)) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assertContains(
    'background-color: var(--app-bg);',
    'a Home deve usar o fundo padrão do tema'
);
$assertContains(
    'color: var(--app-text);',
    'a Home deve usar a cor de texto padrão do tema'
);
$assertContains(
    'background-color: var(--card-bg);',
    'os cartões devem usar o fundo padrão dos cards'
);
$assertContains(
    'border: 1px solid var(--card-border);',
    'os cartões devem ter uma borda visível nos dois temas'
);
$assertContains(
    'transition: background-color 0.25s ease, color 0.25s ease;',
    'a troca de tema deve ser visualmente suave'
);
$assertNotContains(
    'linear-gradient(180deg, #f5f7fb 0%, #edf2f8 100%)',
    'o antigo fundo gelo não deve continuar na Home'
);
$assertNotContains(
    'background: #ffffff !important;',
    'os cartões não devem forçar fundo branco no modo escuro'
);

fwrite(STDOUT, "OK ({$assertions} assertions)\n");
