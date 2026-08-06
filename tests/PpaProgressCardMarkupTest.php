<?php

$failures = [];
$assertPeriodBeforeReached = static function (string $path, string $periodId, string $reachedId) use (&$failures): void {
    $view = file_get_contents($path);
    $periodPosition = strpos($view, $periodId);
    $reachedPosition = strpos($view, $reachedId);

    if ($periodPosition === false || $reachedPosition === false || $periodPosition >= $reachedPosition) {
        $failures[] = basename($path) . ' must show period progress before reached progress';
    }
};

$viewsDirectory = __DIR__ . '/../app/Views/ppa';
$assertPeriodBeforeReached(
    $viewsDirectory . '/detail_family_rma.html',
    'ppaCardPeriodoValue',
    'ppaCardPercentualValue'
);

$unitView = file_get_contents($viewsDirectory . '/detail_unit_rma.html');
foreach (['ppaUnitCardTechnicalValue', 'ppaUnitCardMiddleLevelValue'] as $attendanceValueId) {
    if (!str_contains($unitView, $attendanceValueId)) {
        $failures[] = 'detail_unit_rma.html must render ' . $attendanceValueId;
    }
}

$familyView = file_get_contents($viewsDirectory . '/detail_family_rma.html');
foreach ([
    'ppaCardMetaPpaValue',
    'ppaCardMetaPactoValue',
    'ppaCardPercentualPpaValue',
    'ppaCardPercentualPactoValue',
] as $dualTargetValueId) {
    if (!str_contains($familyView, $dualTargetValueId)) {
        $failures[] = 'detail_family_rma.html must render ' . $dualTargetValueId;
    }
}
$assertPeriodBeforeReached(
    $viewsDirectory . '/detail_unit_rma.html',
    'ppaUnitCardPeriodoValue',
    'ppaUnitCardPercentualValue'
);

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (8 assertions)\n");
