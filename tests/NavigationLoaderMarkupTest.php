<?php

$failures = [];
$assertContains = static function (string $expected, string $actual, string $label) use (&$failures): void {
    if (!str_contains($actual, $expected)) {
        $failures[] = sprintf(
            "%s\nExpected content to contain: %s",
            $label,
            var_export($expected, true)
        );
    }
};

$viewsDirectory = __DIR__ . '/../app/Views';
$base = file_get_contents($viewsDirectory . '/base.twig');
$baseForm = file_get_contents($viewsDirectory . '/base_form.twig');
$loader = file_get_contents($viewsDirectory . '/a_modulos/navigation_loader.twig');

$assertContains(
    '{% include "a_modulos/navigation_loader.twig" %}',
    $base,
    'includes the navigation loader in dashboard pages'
);
$assertContains(
    '{% include "a_modulos/navigation_loader.twig" %}',
    $baseForm,
    'includes the navigation loader in form and catalog pages'
);
$assertContains('id="appNavigationLoader"', $loader, 'provides one identifiable loading overlay');
$assertContains('role="status"', $loader, 'announces loading state to assistive technology');
$assertContains("event.target.closest('a[href]')", $loader, 'observes internal navigation links');
$assertContains("link.target === '_blank'", $loader, 'ignores links opened in another tab');
$assertContains("destination.origin !== window.location.origin", $loader, 'ignores external links');
$assertContains("window.addEventListener('pageshow', hideLoader)", $loader, 'hides the overlay after back navigation');

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (8 assertions)\n");
