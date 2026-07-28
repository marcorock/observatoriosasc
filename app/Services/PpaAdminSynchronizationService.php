<?php

namespace App\Services;

use App\Controllers\PpaController;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Models\PpaResultModel;
use Closure;

final class PpaAdminSynchronizationService
{
    public function __construct(
        private ?Closure $indicatorLoader = null,
        private ?Closure $linksLoader = null,
        private ?Closure $cacheSynchronizer = null,
        private ?Closure $catalogPreview = null,
        private ?Closure $resultPersister = null,
        private string $executionType = 'manual'
    ) {
        $this->executionType = $executionType === 'automatico' ? 'automatico' : 'manual';
    }

    public function synchronizeIndicator(int $indicatorId): array
    {
        if ($indicatorId <= 0) {
            return [
                'success' => false,
                'error' => 'Selecione um indicador valido para sincronizar.',
            ];
        }

        $indicator = $this->loadIndicator($indicatorId);

        if (is_string($indicator)) {
            return [
                'success' => false,
                'error' => $indicator,
            ];
        }

        if ((int) ($indicator->ativo ?? 0) !== 1) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'error' => 'Somente indicadores ativos podem ser sincronizados.',
            ];
        }

        $links = $this->loadLinks($indicatorId);

        if (is_string($links) || $links === []) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'error' => is_string($links)
                    ? $links
                    : 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
            ];
        }

        $cacheResult = $this->synchronizeCache($indicator, $links);

        if (($cacheResult['success'] ?? false) !== true) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'cache' => $cacheResult,
                'error' => (string) ($cacheResult['error'] ?? 'Nao foi possivel atualizar o dashboard.'),
            ];
        }

        $preview = $this->buildCatalogPreview((string) ($indicator->codigo_indicador ?? ''));

        if (($preview['success'] ?? false) !== true) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'cache' => $cacheResult,
                'catalog' => $preview,
                'error' => (string) ($preview['error'] ?? 'O dashboard foi atualizado, mas o resumo do catalogo falhou.'),
            ];
        }

        try {
            $persistence = $this->persistResult($preview);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'cache' => $cacheResult,
                'catalog' => $preview,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'indicator_id' => $indicatorId,
            'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
            'entries_written' => (int) ($cacheResult['entries_written'] ?? 0),
            'result_inserted' => (bool) ($persistence['inserted'] ?? false),
            'persistence_message' => (string) ($persistence['message'] ?? ''),
        ];
    }

    private function loadIndicator(int $indicatorId): object|string
    {
        if ($this->indicatorLoader !== null) {
            return ($this->indicatorLoader)($indicatorId);
        }

        return (new PpaIndicatorModel())->readById($indicatorId);
    }

    private function loadLinks(int $indicatorId): array|string
    {
        if ($this->linksLoader !== null) {
            return ($this->linksLoader)($indicatorId);
        }

        return (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId($indicatorId);
    }

    private function synchronizeCache(object $indicator, array $links): array
    {
        if ($this->cacheSynchronizer !== null) {
            return ($this->cacheSynchronizer)($indicator, $links);
        }

        return (new PpaQueryCacheSynchronizer())->synchronize($indicator, $links);
    }

    private function buildCatalogPreview(string $indicatorCode): array
    {
        if ($this->catalogPreview !== null) {
            return ($this->catalogPreview)($indicatorCode);
        }

        return (new PpaController())->buildCatalogSyncPreview($indicatorCode);
    }

    private function persistResult(array $preview): array
    {
        if ($this->resultPersister !== null) {
            return ($this->resultPersister)($preview, $this->executionType);
        }

        return (new PpaResultModel())->storeValidatedPreview($preview, $this->executionType);
    }
}
