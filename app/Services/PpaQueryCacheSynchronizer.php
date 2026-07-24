<?php

namespace App\Services;

use Closure;

final class PpaQueryCacheSynchronizer
{
    private ?Closure $linkRunner;

    public function __construct(
        private ?PpaQueryFileCache $cache = null,
        ?Closure $linkRunner = null
    ) {
        $this->linkRunner = $linkRunner;
    }

    public function synchronize(object $indicator, array $links): array
    {
        $indicatorId = (int) ($indicator->id ?? 0);
        $indicatorCode = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        if ($indicatorId <= 0 || $indicatorCode === '') {
            return [
                'success' => false,
                'error' => 'O indicador informado e invalido.',
            ];
        }

        if ($links === []) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'indicator_code' => $indicatorCode,
                'error' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
            ];
        }

        $entries = [];

        foreach ($links as $link) {
            $limit = self::limitForLink($link);
            $result = $this->runLink($link, $limit);

            if (is_string($result)) {
                return [
                    'success' => false,
                    'indicator_id' => $indicatorId,
                    'indicator_code' => $indicatorCode,
                    'result_key' => trim((string) ($link->campo_resultado ?? '')),
                    'error' => $result,
                ];
            }

            $entries[] = [
                'link' => $link,
                'limit' => $limit,
                'rows' => $result['rows'] ?? [],
                'query' => $result['query'],
                'source' => $result['source'],
            ];
        }

        $cache = $this->cache ??= new PpaQueryFileCache();
        $written = [];

        try {
            foreach ($entries as $entry) {
                $payload = $cache->write(
                    $entry['source'],
                    $entry['query'],
                    $entry['limit'],
                    $entry['rows']
                );
                $written[] = [
                    'result_key' => trim((string) ($entry['link']->campo_resultado ?? '')),
                    'query_id' => $payload['query_id'],
                    'source_id' => $payload['source_id'],
                    'limit' => $payload['limit'],
                    'row_count' => $payload['row_count'],
                    'generated_at' => $payload['generated_at'],
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'indicator_id' => $indicatorId,
                'indicator_code' => $indicatorCode,
                'entries_written' => count($written),
                'error' => 'Nao foi possivel gravar todos os arquivos de cache do indicador.',
            ];
        }

        return [
            'success' => true,
            'indicator_id' => $indicatorId,
            'indicator_code' => $indicatorCode,
            'entries_written' => count($written),
            'entries' => $written,
        ];
    }

    public static function limitForLink(object $link): int
    {
        $resultKey = trim((string) ($link->campo_resultado ?? ''));

        return str_starts_with($resultKey, 'base_familias_')
            || str_starts_with($resultKey, 'familias_atualizadas_')
                ? 500
                : 5000;
    }

    private function runLink(object $link, int $limit): array|string
    {
        if ($this->linkRunner !== null) {
            return ($this->linkRunner)($link, $limit);
        }

        return (new PpaLinkedQueryService())->run($link, $limit);
    }
}
