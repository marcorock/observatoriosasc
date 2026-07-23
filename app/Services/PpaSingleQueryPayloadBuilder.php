<?php

namespace App\Services;

class PpaSingleQueryPayloadBuilder
{
    public static function build(array $rows, array $filters = []): array
    {
        $filterCras = self::normalizeNullableFilter($filters['cras'] ?? null);
        $filterRegiao = self::normalizeNullableFilter($filters['regiao'] ?? null);
        $totalFamilias = 0;
        $totalPessoas = 0;
        $familiasPorCras = [];
        $familiasPorRegiao = [];
        $tabelaCras = [];
        $tabelaBairro = [];
        $referencia = null;

        foreach ($rows as $row) {
            $cras = self::normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $bairro = self::cleanLabel((string) ($row['bairro'] ?? 'Não informado'), 'Não informado');
            $regiao = self::cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $familias = (int) ($row['total_familias'] ?? 0);
            $pessoas = (int) ($row['total_pessoas'] ?? 0);
            $refCad = trim((string) ($row['ref_cad'] ?? ''));

            if (($filterCras !== null && $cras !== $filterCras)
                || ($filterRegiao !== null && $regiao !== $filterRegiao)) {
                continue;
            }

            $totalFamilias += $familias;
            $totalPessoas += $pessoas;
            $referencia ??= $refCad !== '' ? $refCad : null;
            $familiasPorCras[$cras] ??= ['cras' => $cras, 'total' => 0];
            $familiasPorCras[$cras]['total'] += $familias;
            $familiasPorRegiao[$regiao] ??= ['regiao' => $regiao, 'total' => 0];
            $familiasPorRegiao[$regiao]['total'] += $familias;
            $tabelaCras[$cras] ??= [
                'cras' => $cras,
                'regiao' => $regiao,
                'total_familias' => 0,
                'total_pessoas' => 0,
            ];
            $tabelaCras[$cras]['total_familias'] += $familias;
            $tabelaCras[$cras]['total_pessoas'] += $pessoas;

            $bairroKey = mb_strtolower($bairro) . '|' . mb_strtolower($cras) . '|' . mb_strtolower($regiao);
            $tabelaBairro[$bairroKey] ??= [
                'bairro' => $bairro,
                'cras' => $cras,
                'regiao' => $regiao,
                'total_familias' => 0,
                'total_pessoas' => 0,
            ];
            $tabelaBairro[$bairroKey]['total_familias'] += $familias;
            $tabelaBairro[$bairroKey]['total_pessoas'] += $pessoas;
        }

        $tabelaCras = array_values($tabelaCras);
        usort($tabelaCras, static fn ($a, $b) => strcasecmp($a['cras'], $b['cras']));
        $tabelaBairro = array_values($tabelaBairro);
        usort($tabelaBairro, static function ($a, $b) {
            return strcasecmp($a['cras'], $b['cras'])
                ?: strcasecmp($a['bairro'], $b['bairro'])
                ?: strcasecmp($a['regiao'], $b['regiao']);
        });
        $familiasPorCras = array_values($familiasPorCras);
        usort($familiasPorCras, static fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcasecmp($a['cras'], $b['cras']));
        $familiasPorRegiao = array_values($familiasPorRegiao);
        usort($familiasPorRegiao, static fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcasecmp($a['regiao'], $b['regiao']));

        return [
            'total_geral' => $totalFamilias,
            'total_pessoas' => $totalPessoas,
            'cras_total' => count($familiasPorCras),
            'bairro_total' => count($tabelaBairro),
            'referencia' => $referencia,
            'situacao' => [],
            'eixo' => [],
            'familias_por_cras' => $familiasPorCras,
            'familias_por_regiao' => $familiasPorRegiao,
            'tabela_cras' => $tabelaCras,
            'tabela_bairro' => $tabelaBairro,
            'filtros_ativos' => ['cras' => $filterCras, 'regiao' => $filterRegiao],
            'registros' => $rows,
        ];
    }

    public static function empty(array $filters = []): array
    {
        return [
            'total_geral' => 0,
            'total_pessoas' => 0,
            'cras_total' => 0,
            'bairro_total' => 0,
            'referencia' => null,
            'situacao' => [],
            'eixo' => [],
            'familias_por_cras' => [],
            'familias_por_regiao' => [],
            'tabela_cras' => [],
            'tabela_bairro' => [],
            'filtros_ativos' => [
                'cras' => self::normalizeNullableFilter($filters['cras'] ?? null),
                'regiao' => self::normalizeNullableFilter($filters['regiao'] ?? null),
            ],
            'registros' => [],
        ];
    }

    private static function cleanLabel(string $value, string $fallback): string
    {
        $value = trim(trim($value), " \t\n\r\0\x0B'\"");

        return $value !== '' ? $value : $fallback;
    }

    private static function normalizeCrasLabel(string $value): string
    {
        $value = self::cleanLabel($value, 'Não informado');
        $aliases = [
            'CRAS MARIANA 2' => 'CRAS MARIANA',
            'UNIDADE PERNAMBUCANO' => 'CRAS PARQUE SANTA RITA',
            'UNIDADE SAO FRANCISCO XAVIER' => 'CRAS ALTO DA PONTE',
        ];

        return $aliases[mb_strtoupper($value)] ?? $value;
    }

    private static function normalizeNullableFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? self::cleanLabel($value, '') : null;
    }
}
