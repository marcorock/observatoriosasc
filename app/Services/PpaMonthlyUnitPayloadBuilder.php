<?php

namespace App\Services;

class PpaMonthlyUnitPayloadBuilder
{
    public static function build(array $rows, object $indicator, array $filters): array
    {
        $metaAnual = (float) ($indicator->indice_futuro ?? 0);
        $filterUnidade = self::normalizeNullableFilter($filters['unidade'] ?? null);
        $filterMes = self::normalizeDateFilter($filters['mes_referencia'] ?? null);
        $totalUnidadesComLeitura = self::countUnitsWithReading($rows, $indicator);
        $metaPorUnidade = $totalUnidadesComLeitura > 0
            ? $metaAnual / $totalUnidadesComLeitura
            : $metaAnual;
        $metaAplicada = $filterUnidade !== null ? $metaPorUnidade : $metaAnual;
        $rows = self::filterRows($rows, $indicator, $filterUnidade, $filterMes);
        $anoApuracao = null;
        $mensal = [];
        $unidades = [];

        foreach ($rows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $unidade = self::normalizeIndicatorUnitLabel(
                $indicator,
                (string) ($row['unidade'] ?? $row['nome_unidade'] ?? $row['id_creas'] ?? $row['id_cras'] ?? 'Nao informado')
            );
            $totalInseridos = (int) ($row['total_inseridos'] ?? $row['total_casos'] ?? 0);

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);
            $mensal[$mesReferencia] ??= [
                'mes_referencia' => $mesReferencia,
                'mes_label' => self::monthLabel($mesReferencia),
                'total_inseridos' => 0,
            ];
            $mensal[$mesReferencia]['total_inseridos'] += $totalInseridos;
            $unidades[$unidade] ??= ['unidade' => $unidade, 'total_inseridos' => 0];
            $unidades[$unidade]['total_inseridos'] += $totalInseridos;
        }

        ksort($mensal);
        $totalInseridos = 0;
        $tabelaMensal = [];
        $graficoMensal = [];

        foreach (array_values($mensal) as $row) {
            $mesTotal = (int) $row['total_inseridos'];
            $totalInseridos += $mesTotal;
            $tabelaMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes_label' => $row['mes_label'],
                'total_inseridos' => $mesTotal,
                'acumulado' => $totalInseridos,
                'percentual_mes' => $metaAplicada > 0 ? ($mesTotal / $metaAplicada) * 100 : 0,
                'percentual_acumulado' => $metaAplicada > 0 ? ($totalInseridos / $metaAplicada) * 100 : 0,
            ];
            $graficoMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes' => $row['mes_label'],
                'total' => $mesTotal,
            ];
        }

        $tabelaUnidades = array_values(array_map(
            static function ($row) use ($totalInseridos, $metaPorUnidade): array {
                return [
                    'unidade' => $row['unidade'],
                    'total_inseridos' => (int) $row['total_inseridos'],
                    'participacao' => $totalInseridos > 0 ? ($row['total_inseridos'] / $totalInseridos) * 100 : 0,
                    'meta_anual' => $metaPorUnidade,
                    'percentual_meta' => $metaPorUnidade > 0 ? ($row['total_inseridos'] / $metaPorUnidade) * 100 : 0,
                ];
            },
            $unidades
        ));
        usort(
            $tabelaUnidades,
            static fn ($a, $b) => ($b['total_inseridos'] <=> $a['total_inseridos'])
                ?: strcasecmp($a['unidade'], $b['unidade'])
        );
        $graficoUnidades = array_map(static fn ($row): array => [
            'unidade' => $row['unidade'],
            'total' => $row['total_inseridos'],
        ], $tabelaUnidades);
        usort($graficoUnidades, static fn ($a, $b) => strcasecmp($a['unidade'], $b['unidade']));

        return [
            'total_unidades' => count($tabelaUnidades),
            'meta_anual' => $metaAplicada,
            'total_inseridos' => $totalInseridos,
            'percentual_alcancado_total' => $metaAplicada > 0 ? ($totalInseridos / $metaAplicada) * 100 : 0,
            'percentual_periodo' => (min(12, count($graficoMensal)) / 12) * 100,
            'meses_periodo' => count($graficoMensal),
            'ano_apuracao' => $anoApuracao,
            'grafico_mensal' => $graficoMensal,
            'grafico_unidades' => $graficoUnidades,
            'tabela_mensal' => $tabelaMensal,
            'tabela_unidades' => $tabelaUnidades,
            'filtros_ativos' => [
                'unidade' => $filterUnidade,
                'mes_referencia' => $filterMes,
            ],
        ];
    }

    public static function empty(): array
    {
        return [
            'total_unidades' => 0,
            'meta_anual' => 0,
            'total_inseridos' => 0,
            'percentual_alcancado_total' => 0,
            'ano_apuracao' => null,
            'grafico_mensal' => [],
            'grafico_unidades' => [],
            'tabela_mensal' => [],
            'tabela_unidades' => [],
            'filtros_ativos' => ['unidade' => null, 'mes_referencia' => null],
        ];
    }

    private static function filterRows(
        array $rows,
        object $indicator,
        ?string $filterUnidade,
        ?string $filterMes
    ): array {
        return array_values(array_filter(
            $rows,
            static function ($row) use ($indicator, $filterUnidade, $filterMes): bool {
                $unidade = self::normalizeIndicatorUnitLabel(
                    $indicator,
                    (string) ($row['unidade'] ?? $row['nome_unidade'] ?? $row['id_creas'] ?? $row['id_cras'] ?? '')
                );
                $mes = trim((string) ($row['mes_referencia'] ?? ''));

                return !($filterUnidade !== null && $unidade !== $filterUnidade)
                    && !($filterMes !== null && $mes !== $filterMes);
            }
        ));
    }

    private static function countUnitsWithReading(array $rows, object $indicator): int
    {
        $units = [];

        foreach ($rows as $row) {
            $month = trim((string) ($row['mes_referencia'] ?? ''));
            $rawUnit = trim((string) (
                $row['unidade']
                ?? $row['nome_unidade']
                ?? $row['id_creas']
                ?? $row['id_cras']
                ?? ''
            ));

            if ($month === '' || $rawUnit === '') {
                continue;
            }

            $unit = self::normalizeIndicatorUnitLabel($indicator, $rawUnit);
            $units[mb_strtolower($unit)] = true;
        }

        return count($units);
    }

    private static function normalizeIndicatorUnitLabel(object $indicator, string $value): string
    {
        $value = self::cleanLabel($value, 'Nao informado');
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        if (!str_starts_with($code, 'PPA-CRAS-')) {
            return $value;
        }

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

    private static function normalizeNullableFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? self::cleanLabel($value, '') : null;
    }

    private static function normalizeDateFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
    }

    private static function monthLabel(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return $date;
        }

        $months = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

        return ($months[(int) date('n', $timestamp)] ?? date('m', $timestamp)) . '/' . date('Y', $timestamp);
    }
}
