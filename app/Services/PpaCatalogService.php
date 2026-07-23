<?php

namespace App\Services;

use App\Models\PpaResultModel;

class PpaCatalogService
{
    private ?PpaResultModel $resultModel;

    public function __construct(?PpaResultModel $resultModel = null)
    {
        $this->resultModel = $resultModel;
    }

    public function build(array $indicators): array
    {
        $resultModel = $this->resultModel ??= new PpaResultModel();
        $resultsByIndicator = $resultModel->readLatestPublishedByIndicatorIds(
            array_map(static fn ($indicator): int => (int) ($indicator->id ?? 0), $indicators)
        );
        $decorated = $this->decorate($indicators, $resultsByIndicator);

        return [
            'indicators' => $decorated,
            'summary' => self::buildSummary($decorated),
        ];
    }

    public static function indicatorMetrics(object $indicator, ?object $result = null): array
    {
        $meta = self::firstNumeric([
            $result->valor_meta_quantitativa ?? null,
            $result->indice_futuro ?? null,
            $indicator->indice_futuro ?? null,
        ]);
        $realized = self::firstNumeric([
            $result->valor_resultado ?? null,
            $result->indice_recente ?? null,
            $indicator->indice_recente ?? null,
        ]);

        return [
            'meta' => $meta,
            'realizado' => $realized,
            'percentual' => ($meta !== null && $meta > 0 && $realized !== null)
                ? ($realized / $meta) * 100
                : null,
        ];
    }

    public static function indicatorStatus(object $indicator): string
    {
        if (($indicator->metricas_tipo ?? '') === 'visao_geral') {
            return 'Visão geral';
        }

        $meta = $indicator->meta_valor ?? null;
        $realized = $indicator->realizado_valor ?? null;
        $percentage = $indicator->percentual_atingido ?? null;

        if ($meta === null || $meta <= 0 || $realized === null) {
            return 'Sem leitura';
        }

        if ($percentage >= 100) {
            return 'Meta atingida';
        }

        if ($percentage >= 70) {
            return 'Em progresso';
        }

        return 'Em atenção';
    }

    public static function isOverviewIndicator(object $indicator): bool
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        return $code === 'PPA-ERRADICAR-POBREZA';
    }

    public static function buildSummary(array $indicators): array
    {
        $percentages = [];
        $targetsReached = 0;

        foreach ($indicators as $indicator) {
            if (($indicator->percentual_atingido ?? null) !== null) {
                $percentages[] = (float) $indicator->percentual_atingido;
            }

            if (($indicator->status_painel ?? '') === 'Meta atingida') {
                $targetsReached++;
            }
        }

        return [
            'total_indicadores' => count($indicators),
            'indicadores_ativos' => count(array_filter(
                $indicators,
                static fn ($indicator): bool => (int) ($indicator->ativo ?? 0) === 1
            )),
            'meta_atingida' => $targetsReached,
            'media_execucao' => $percentages !== []
                ? array_sum($percentages) / count($percentages)
                : null,
        ];
    }

    private function decorate(array $indicators, array $resultsByIndicator): array
    {
        return array_map(static function ($indicator) use ($resultsByIndicator) {
            $result = $resultsByIndicator[(int) ($indicator->id ?? 0)] ?? null;
            $metrics = self::indicatorMetrics($indicator, $result);

            $indicator->meta_valor = $metrics['meta'];
            $indicator->realizado_valor = $metrics['realizado'];
            $indicator->percentual_atingido = $metrics['percentual'];
            $indicator->metricas_tipo = self::isOverviewIndicator($indicator)
                ? 'visao_geral'
                : 'meta';
            $indicator->status_painel = self::indicatorStatus($indicator);
            $indicator->status_painel_classe = self::statusClass($indicator->status_painel);
            $indicator->metricas_origem = $result !== null ? 'resultado_local' : 'cadastro_indicador';
            $indicator->metricas_atualizadas_em = $result->validated_at ?? $result->created_at ?? null;

            return $indicator;
        }, $indicators);
    }

    private static function firstNumeric(array $values): ?float
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private static function statusClass(string $status): string
    {
        return match ($status) {
            'Meta atingida' => 'success',
            'Em progresso' => 'warning',
            'Em atenção' => 'danger',
            default => 'secondary',
        };
    }
}
