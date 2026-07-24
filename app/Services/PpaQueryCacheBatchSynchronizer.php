<?php

namespace App\Services;

use App\Models\PpaIndicatorQueryModel;
use Closure;

final class PpaQueryCacheBatchSynchronizer
{
    private ?Closure $linksLoader;
    private ?Closure $indicatorSynchronizer;

    public function __construct(
        ?Closure $linksLoader = null,
        ?Closure $indicatorSynchronizer = null
    ) {
        $this->linksLoader = $linksLoader;
        $this->indicatorSynchronizer = $indicatorSynchronizer;
    }

    public function synchronize(array $indicators): array
    {
        $results = [];
        $succeeded = 0;
        $failed = 0;
        $entriesWritten = 0;

        foreach ($indicators as $indicator) {
            $indicatorId = (int) ($indicator->id ?? 0);
            $indicatorCode = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

            try {
                $links = $this->loadLinks($indicatorId);

                if (is_string($links)) {
                    $result = [
                        'success' => false,
                        'indicator_id' => $indicatorId,
                        'indicator_code' => $indicatorCode,
                        'error' => $links,
                    ];
                } else {
                    $result = $this->synchronizeIndicator($indicator, $links);
                }
            } catch (\Throwable) {
                $result = [
                    'success' => false,
                    'indicator_id' => $indicatorId,
                    'indicator_code' => $indicatorCode,
                    'error' => 'Falha inesperada ao atualizar o cache deste indicador.',
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
            'indicators_succeeded' => $succeeded,
            'indicators_failed' => $failed,
            'entries_written' => $entriesWritten,
            'results' => $results,
        ];
    }

    private function loadLinks(int $indicatorId): array|string
    {
        if ($this->linksLoader !== null) {
            return ($this->linksLoader)($indicatorId);
        }

        return (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId($indicatorId);
    }

    private function synchronizeIndicator(object $indicator, array $links): array
    {
        if ($this->indicatorSynchronizer !== null) {
            return ($this->indicatorSynchronizer)($indicator, $links);
        }

        return (new PpaQueryCacheSynchronizer())->synchronize($indicator, $links);
    }
}
