<?php

namespace App\Services;

use App\Models\ExternalDataSourceModel;
use App\Models\ExternalDatabaseRuntime;
use App\Models\ExternalQueryModel;
use Closure;

class PpaLinkedQueryService
{
    private ?ExternalQueryModel $queryModel;
    private ?ExternalDataSourceModel $sourceModel;
    private ?Closure $queryRunner;

    public function __construct(
        ?ExternalQueryModel $queryModel = null,
        ?ExternalDataSourceModel $sourceModel = null,
        ?Closure $queryRunner = null
    ) {
        $this->queryModel = $queryModel;
        $this->sourceModel = $sourceModel;
        $this->queryRunner = $queryRunner;
    }

    /**
     * Executa a consulta registrada em um vínculo de indicador.
     *
     * @return array|string Dados da consulta ou a mensagem de erro já esperada pelos dashboards.
     */
    public function run(object $link, int $limit): array|string
    {
        $queryModel = $this->queryModel ??= new ExternalQueryModel();
        $query = $queryModel->readById((int) ($link->external_query_id ?? 0));

        if (is_string($query)) {
            return $query;
        }

        $sourceModel = $this->sourceModel ??= new ExternalDataSourceModel();
        $source = $sourceModel->readById((int) ($query->source_id ?? 0));

        if (is_string($source)) {
            return $source;
        }

        $runner = $this->queryRunner
            ?? static fn (object $source, object $query, int $limit, array $context): array =>
                ExternalDatabaseRuntime::runRegisteredQuery($source, $query, $limit, $context);
        $preview = $runner($source, $query, $limit, [
            'indicator_id' => $link->indicador_id ?? null,
            'indicator_code' => $link->codigo_indicador ?? null,
        ]);

        if (!($preview['success'] ?? false)) {
            return (string) ($preview['message'] ?? 'Falha ao executar a consulta externa.');
        }

        return [
            'rows' => $preview['rows'] ?? [],
            'query' => $query,
            'source' => $source,
        ];
    }
}
