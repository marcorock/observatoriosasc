<?php

namespace App\Services;

class PpaCadUpdateRmaPayloadBuilder
{
    public static function build(array $baseRows, array $rmaRows, object $indicator, array $filters): array
    {
        $referenciaBase = null;
        $anoApuracao = null;
        $baseTotal = 0;
        $metaPercentual = (float) ($indicator->indice_futuro ?? 85);
        $metaFator = $metaPercentual / 100;
        $filterCras = self::normalizeFilter($filters['cras'] ?? null);
        $filterMes = self::normalizeFilter($filters['mes_referencia'] ?? null);

        $baseRows = self::filterBaseRows($baseRows, $filterCras);
        $rmaRows = self::filterRmaRows($rmaRows, $filterCras, $filterMes);

        $basePorCras = [];
        $rmaPorMes = [];
        $rmaPorCras = [];

        foreach ($baseRows as $row) {
            $cras = self::normalizeCras((string) ($row['cras'] ?? 'Não informado'));
            $regiao = self::cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $totalFamilias = (int) ($row['total_familias_pbf'] ?? 0);
            $refCad = trim((string) ($row['ref_cad_referencia'] ?? $row['ref_cad'] ?? ''));

            $baseTotal += $totalFamilias;
            $referenciaBase ??= $refCad !== '' ? $refCad : null;
            $basePorCras[$cras] ??= [
                'cras' => $cras,
                'regiao' => $regiao,
                'base_familias_pbf' => 0,
            ];
            $basePorCras[$cras]['base_familias_pbf'] += $totalFamilias;
        }

        foreach ($rmaRows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $cras = self::normalizeCras((string) (
                $row['cras']
                ?? $row['unidade']
                ?? $row['nome_unidade']
                ?? 'Não informado'
            ));
            $atualizadas = (int) (
                $row['total_inseridos']
                ?? $row['total_familias_acompanhadas']
                ?? 0
            );

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);
            $rmaPorMes[$mesReferencia] ??= [
                'mes_referencia' => $mesReferencia,
                'mes_label' => self::monthLabel($mesReferencia),
                'total_familias_acompanhadas' => 0,
            ];
            $rmaPorMes[$mesReferencia]['total_familias_acompanhadas'] += $atualizadas;

            $rmaPorCras[$cras] ??= [
                'cras' => $cras,
                'familias_acompanhadas' => 0,
            ];
            $rmaPorCras[$cras]['familias_acompanhadas'] += $atualizadas;

            $basePorCras[$cras] ??= [
                'cras' => $cras,
                'regiao' => 'Nao informada',
                'base_familias_pbf' => 0,
            ];
        }

        ksort($rmaPorMes);
        $metaTotal = $baseTotal * $metaFator;
        $acumulado = 0;
        $tabelaMensal = [];
        $graficoMensal = [];

        foreach (array_values($rmaPorMes) as $row) {
            $mesTotal = (int) $row['total_familias_acompanhadas'];
            $acumulado += $mesTotal;
            $percentualMes = $metaTotal > 0 ? ($mesTotal / $metaTotal) * 100 : 0;
            $percentualAcumulado = $metaTotal > 0 ? ($acumulado / $metaTotal) * 100 : 0;

            $tabelaMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes_label' => $row['mes_label'],
                'familias_acompanhadas' => $mesTotal,
                'acumulado' => $acumulado,
                'percentual_mes' => $percentualMes,
                'percentual_acumulado' => $percentualAcumulado,
            ];
            $graficoMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes' => $row['mes_label'],
                'total' => $mesTotal,
            ];
        }

        $tabelaCras = [];
        foreach ($basePorCras as $cras => $row) {
            $baseCras = (int) ($row['base_familias_pbf'] ?? 0);
            $acompanhadas = (int) ($rmaPorCras[$cras]['familias_acompanhadas'] ?? 0);
            $metaCras = $baseCras * $metaFator;

            $tabelaCras[] = [
                'cras' => $cras,
                'regiao' => $row['regiao'] ?? 'Nao informada',
                'base_familias_pbf' => $baseCras,
                'meta_familias' => $metaCras,
                'familias_acompanhadas' => $acompanhadas,
                'percentual_alcancado' => $metaCras > 0 ? ($acompanhadas / $metaCras) * 100 : 0,
            ];
        }

        usort(
            $tabelaCras,
            static fn ($a, $b) => ($b['familias_acompanhadas'] <=> $a['familias_acompanhadas'])
                ?: strcasecmp($a['cras'], $b['cras'])
        );

        $graficoCras = array_map(static fn ($row): array => [
            'cras' => $row['cras'],
            'total' => $row['familias_acompanhadas'],
        ], $tabelaCras);
        usort($graficoCras, static fn ($a, $b) => strcasecmp($a['cras'], $b['cras']));

        return [
            'total_geral' => $baseTotal,
            'meta_familias' => $metaTotal,
            'familias_acompanhadas_total' => $acumulado,
            'percentual_alcancado_total' => $metaTotal > 0 ? ($acumulado / $metaTotal) * 100 : 0,
            'percentual_periodo' => self::periodProgressPercent(count($graficoMensal)),
            'meses_periodo' => count($graficoMensal),
            'referencia' => $referenciaBase,
            'ano_apuracao' => $anoApuracao,
            'grafico_mensal' => $graficoMensal,
            'grafico_cras' => $graficoCras,
            'grafico_meta' => [],
            'tabela_mensal' => $tabelaMensal,
            'tabela_cras' => $tabelaCras,
            'filtros_ativos' => [
                'cras' => $filterCras,
                'mes_referencia' => $filterMes,
            ],
        ];
    }

    private static function filterBaseRows(array $rows, ?string $filterCras): array
    {
        if ($filterCras === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn ($row): bool => self::normalizeCras((string) ($row['cras'] ?? '')) === $filterCras
        ));
    }

    private static function filterRmaRows(array $rows, ?string $filterCras, ?string $filterMes): array
    {
        return array_values(array_filter($rows, static function ($row) use ($filterCras, $filterMes): bool {
            $cras = self::normalizeCras((string) (
                $row['cras']
                ?? $row['unidade']
                ?? $row['nome_unidade']
                ?? ''
            ));
            $mes = trim((string) ($row['mes_referencia'] ?? ''));

            return !($filterCras !== null && $cras !== $filterCras)
                && !($filterMes !== null && $mes !== $filterMes);
        }));
    }

    private static function normalizeFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? self::cleanLabel($value, '') : null;
    }

    private static function normalizeCras(string $value): string
    {
        $value = self::cleanLabel($value, 'Não informado');
        $aliases = [
            'CRAS MARIANA 2' => 'CRAS MARIANA',
            'UNIDADE PERNAMBUCANO' => 'CRAS PARQUE SANTA RITA',
            'UNIDADE SAO FRANCISCO XAVIER' => 'CRAS ALTO DA PONTE',
        ];

        return $aliases[mb_strtoupper($value)] ?? $value;
    }

    private static function cleanLabel(string $value, string $fallback): string
    {
        $value = trim(trim($value), " \t\n\r\0\x0B'\"");

        return $value !== '' ? $value : $fallback;
    }

    private static function monthLabel(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $months = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return ($months[(int) date('n', $timestamp)] ?? date('m', $timestamp))
            . '/'
            . date('Y', $timestamp);
    }

    private static function periodProgressPercent(int $monthsCount): float
    {
        $monthsCount = max(0, min(12, $monthsCount));

        return ($monthsCount / 12) * 100;
    }
}
