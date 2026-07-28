<?php

namespace App\Services;

use Closure;

final class PpaScheduledSynchronizationBatchService
{
    private ?PpaAdminSynchronizationService $synchronizationService = null;

    public function __construct(
        private ?Closure $indicatorSynchronizer = null
    ) {
    }

    public function synchronize(array $indicators): array
    {
        $results = [];
        $succeeded = 0;
        $failed = 0;
        $skipped = 0;
        $entriesWritten = 0;

        foreach ($indicators as $indicator) {
            $indicatorId = (int) ($indicator->id ?? 0);
            $indicatorCode = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));
            $skipReason = $this->skipReason($indicator);

            if ($skipReason !== null) {
                $skipped++;
                $results[] = [
                    'success' => true,
                    'skipped' => true,
                    'indicator_id' => $indicatorId,
                    'indicator_code' => $indicatorCode,
                    'message' => $skipReason,
                ];
                continue;
            }

            try {
                $result = $this->synchronizeIndicator($indicatorId);
            } catch (\Throwable) {
                $result = [
                    'success' => false,
                    'indicator_id' => $indicatorId,
                    'indicator_code' => $indicatorCode,
                    'error' => 'Falha inesperada ao sincronizar este indicador.',
                ];
            }

            if (($result['success'] ?? false) === true) {
                $succeeded++;
                $entriesWritten += (int) ($result['entries_written'] ?? 0);
            } else {
                $failed++;
            }

            $results[] = $result;
        }

        return [
            'success' => $failed === 0,
            'indicators_total' => count($indicators),
            'indicators_attempted' => $succeeded + $failed,
            'indicators_succeeded' => $succeeded,
            'indicators_failed' => $failed,
            'indicators_skipped' => $skipped,
            'entries_written' => $entriesWritten,
            'results' => $results,
        ];
    }

    private function skipReason(object $indicator): ?string
    {
        $indicatorCode = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        if ($indicatorCode === 'PPA-ERRADICAR-POBREZA') {
            return 'Indicador de visao geral sem resultado quantitativo para sincronizar.';
        }

        if ((int) ($indicator->vinculos_ativos ?? 0) <= 0) {
            return 'Indicador sem vinculos ativos.';
        }

        return null;
    }

    private function synchronizeIndicator(int $indicatorId): array
    {
        if ($this->indicatorSynchronizer !== null) {
            return ($this->indicatorSynchronizer)($indicatorId);
        }

        $service = $this->synchronizationService ??= new PpaAdminSynchronizationService(
            executionType: 'automatico'
        );

        return $service->synchronizeIndicator($indicatorId);
    }
}
