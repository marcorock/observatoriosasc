<?php

namespace App\Services;

class PpaDashboardResolver
{
    public static function dashboardType(array $links): string
    {
        $keys = array_map(
            static fn ($link): string => trim((string) ($link->campo_resultado ?? '')),
            $links
        );

        if (self::firstKeyByPrefixes($keys, ['base_familias_']) !== null
            && self::firstKeyByPrefixes($keys, ['familias_atualizadas_']) !== null
            && self::firstKeyByPrefixes($keys, ['serie_mensal_unidade_']) !== null) {
            return 'family_snapshot_rma_progress';
        }

        if (self::firstKeyByPrefixes($keys, ['base_familias_']) !== null
            && self::firstKeyByPrefixes($keys, ['familias_acompanhadas_', 'familias_atualizadas_']) !== null) {
            return 'family_rma_progress';
        }

        if (self::firstKeyByPrefixes($keys, ['serie_mensal_unidade_']) !== null) {
            return 'monthly_unit_progress';
        }

        return 'single_query';
    }

    public static function findLinkByResultKey(array $links, string $key): ?object
    {
        foreach ($links as $link) {
            if (trim((string) ($link->campo_resultado ?? '')) === $key) {
                return $link;
            }
        }

        return null;
    }

    public static function findLinkKeyByPrefixes(array $links, array $prefixes): ?string
    {
        $keys = array_map(
            static fn ($link): string => trim((string) ($link->campo_resultado ?? '')),
            $links
        );

        return self::firstKeyByPrefixes($keys, $prefixes);
    }

    public static function firstKeyByPrefixes(array $keys, array $prefixes): ?string
    {
        foreach ($keys as $key) {
            foreach ($prefixes as $prefix) {
                if ($key !== '' && str_starts_with($key, $prefix)) {
                    return $key;
                }
            }
        }

        return null;
    }
}
