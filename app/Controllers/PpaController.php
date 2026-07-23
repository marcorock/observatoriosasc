<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ExternalDataSourceModel;
use App\Models\ExternalDatabaseRuntime;
use App\Models\ExternalQueryModel;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Models\PpaResultModel;

class PpaController extends BaseController
{
    /**
     * Configuração básica da página PPA.
     * Define o título, descrição e nome do sistema para o módulo do Plano Plurianual.
     * 
     * @return array Array com configurações da página (title, description, system, name)
     */
    protected function pageConfig(): array
    {
        return [
            'title' => 'PPA',
            'description' => 'Plano Plurianual',
            'system' => 'Observatorio Socioassistencial',
            'name' => 'PPA',
        ];
    }

    /**
     * Configuração de linhas para o layout do PPA.
     * Define a distribuição de colunas para exibição de conteúdo.
     * 
     * @return array Array com configuração de linhas (first, second, third)
     */
    protected function rowsConfig(): array
    {
        return [
            'first' => 1,
            'second' => 1,
            'third' => 1,
        ];
    }

    /**
     * Exibe o catálogo de todos os indicadores do PPA.
     * Lista todos os indicadores públicos disponíveis no módulo do Plano Plurianual.
     * Aplicável a TODOS os indicadores do PPA (página de listagem geral).
     * 
     * @return mixed Renderização da página com catálogo de indicadores
     */
    public function index()
    {
        $indicators = (new PpaIndicatorModel())->readPublicCatalog();

        return $this->renderCatalog(is_array($indicators) ? $indicators : [], is_string($indicators) ? $indicators : null);
    }

    /**
     * Exibe o dashboard detalhado de um indicador específico do PPA.
     * 
     * Resolve o tipo de dashboard a ser exibido baseado no tipo de indicador:
     * - "family_rma_progress": Para indicadores que usam base de famílias PBF com acompanhamento RMA
     * - "single_query": Para consultas simples com dados de CRAS e regionais
     * 
     * Indicadores suportados:
     * - Indicadores com vinculação de consultas que possuem campos 'base_familias_pbf' e 'familias_acompanhadas_b2_mensal' (RMA)
     * - Indicadores com consultas simples vinculadas (CRAS, região, bairro)
     * 
     * @param string $slug Slug único do indicador do PPA
     * @return mixed Renderização do dashboard específico ou catálogo de fallback se indicador não encontrado
     */
    public function show(string $slug)
    {
        $indicator = (new PpaIndicatorModel())->readPublicBySlug($slug);

        if (is_string($indicator)) {
            $indicators = (new PpaIndicatorModel())->readPublicCatalog();

            return $this->renderCatalog(
                is_array($indicators) ? $indicators : [],
                'Indicador do PPA nao encontrado.'
            );
        }

        $links = (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId((int) $indicator->id);

        if (is_string($links) || $links === []) {
            return $this->renderSingleQueryDashboard([
                'dados' => $this->emptySingleQueryPayload(),
                'status' => 'Em Preparacao',
                'mensagem' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
                'filters' => [
                    'cras' => null,
                    'regiao' => null,
                ],
                'meta' => [
                    'link' => null,
                    'query' => null,
                    'source' => null,
                ],
            ], $indicator);
        }

        if ($this->isCadUpdateRmaIndicator($indicator)) {
            return $this->renderCadUpdateRmaDashboard($indicator, $links);
        }

        return match ($this->resolveDashboardType($links)) {
            'family_snapshot_rma_progress' => $this->renderFamilySnapshotRmaProgressDashboard($indicator, $links),
            'family_rma_progress' => $this->renderFamilyRmaProgressDashboard($indicator, $links),
            'monthly_unit_progress' => $this->renderMonthlyUnitProgressDashboard($indicator, $links),
            default => $this->renderSingleQueryFromLinks($indicator, $links),
        };
    }

    /**
     * Endpoint JSON que retorna os dados do dashboard de um indicador específico.
     * 
     * Este método é chamado via AJAX para atualizar os dados do dashboard quando os filtros interativos
     * (CRAS, Região, Mês de Referência) são alterados pelo usuário.
     * 
     * Responde com dados JSON estruturados contendo:
     * - Para indicadores RMA: dados de progresso de famílias acompanhadas vs meta
     * - Para consultas simples: dados tabulares de CRAS, região e bairro
     * 
     * @param string $slug Slug único do indicador do PPA
     * @return void Retorna resposta JSON e encerra execução
     */
    public function dashboardData(string $slug): void
    {
        $indicator = (new PpaIndicatorModel())->readPublicBySlug($slug);

        if (is_string($indicator)) {
            http_response_code(404);
            $this->json([
                'error' => 'Indicador do PPA nao encontrado.',
            ]);
        }

        $links = (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId((int) $indicator->id);

        if (is_string($links) || $links === []) {
            http_response_code(404);
            $this->json([
                'error' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
            ]);
        }

        $filters = $this->interactiveFiltersFromRequest();

        if ($this->isCadUpdateRmaIndicator($indicator)) {
            $payload = $this->buildCadUpdateRmaResponse($indicator, $links, $filters);

            if (!empty($payload['error'])) {
                http_response_code(422);
            }

            $this->json($payload);
        }

        $type = $this->resolveDashboardType($links);

        $payload = match ($type) {
            'family_snapshot_rma_progress' => $this->buildFamilySnapshotRmaProgressResponse($indicator, $links, $filters),
            'family_rma_progress' => $this->buildFamilyRmaProgressResponse($indicator, $links, $filters),
            'monthly_unit_progress' => $this->buildMonthlyUnitProgressResponse($indicator, $links, $filters),
            default => $this->buildSingleQueryResponse($indicator, $links, $filters),
        };

        if (!empty($payload['error'])) {
            http_response_code(422);
        }

        $this->json($payload);
    }

    public function buildCatalogSyncPreview(string $slug): array
    {
        $indicator = (new PpaIndicatorModel())->readPublicBySlug($slug);

        if (is_string($indicator)) {
            return [
                'success' => false,
                'error' => $indicator,
            ];
        }

        $links = (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId((int) $indicator->id);

        if (is_string($links) || $links === []) {
            return [
                'success' => false,
                'error' => is_string($links)
                    ? $links
                    : 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
            ];
        }

        if ($this->isCadUpdateRmaIndicator($indicator)) {
            $payload = $this->buildCadUpdateRmaResponse($indicator, $links, [
                'cras' => null,
                'mes_referencia' => null,
            ]);
        } else {
            $type = $this->resolveDashboardType($links);
            $payload = match ($type) {
                'family_snapshot_rma_progress' => $this->buildFamilySnapshotRmaProgressResponse(
                    $indicator,
                    $links,
                    ['cras' => null, 'mes_referencia' => null]
                ),
                'family_rma_progress' => $this->buildFamilyRmaProgressResponse(
                    $indicator,
                    $links,
                    ['cras' => null, 'mes_referencia' => null]
                ),
                'monthly_unit_progress' => $this->buildMonthlyUnitProgressResponse(
                    $indicator,
                    $links,
                    ['unidade' => null, 'mes_referencia' => null]
                ),
                default => $this->buildSingleQueryResponse(
                    $indicator,
                    $links,
                    ['cras' => null, 'regiao' => null]
                ),
            };
        }

        if (!empty($payload['error'])) {
            return [
                'success' => false,
                'indicator_id' => (int) $indicator->id,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'error' => (string) $payload['error'],
            ];
        }

        $metrics = $this->catalogMetricsFromDashboardPayload($indicator, $payload);

        if ($metrics === null) {
            return [
                'success' => false,
                'indicator_id' => (int) $indicator->id,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'dashboard_type' => (string) ($payload['type'] ?? ''),
                'error' => 'Este tipo de dashboard ainda nao possui consolidacao segura para o catalogo.',
            ];
        }

        return [
            'success' => true,
            'dry_run' => true,
            'indicator_id' => (int) $indicator->id,
            'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
            'indicator_slug' => (string) ($indicator->slug ?? $slug),
            'dashboard_type' => (string) ($payload['type'] ?? ''),
            'metrics' => $metrics,
        ];
    }

    /**
     * Determina o tipo de dashboard a ser renderizado baseado nas consultas vinculadas ao indicador.
     * 
     * Analisa os campos de resultado das consultas para identificar:
     * - "family_rma_progress": Se houver consultas com campos 'base_familias_pbf' E 'familias_acompanhadas_b2_mensal'
     *   Indicadores: Aqueles que trabalham com base CECAD e acompanhamento RMA de famílias
     * - "single_query": Para todos os outros casos (padrão)
     *   Indicadores: Consultas simples de dados de CRAS, região, bairro
     * 
     * @param array $links Array de objetos com dados de vínculos de consultas (deve conter campo 'campo_resultado')
     * @return string Tipo de dashboard: 'family_rma_progress' ou 'single_query'
     */
    private function resolveDashboardType(array $links): string
    {
        $keys = [];

        foreach ($links as $link) {
            $keys[] = trim((string) ($link->campo_resultado ?? ''));
        }

        if ($this->findFirstKeyByPrefixes($keys, ['base_familias_']) !== null
            && $this->findFirstKeyByPrefixes($keys, ['familias_atualizadas_']) !== null
            && $this->findFirstKeyByPrefixes($keys, ['serie_mensal_unidade_']) !== null) {
            return 'family_snapshot_rma_progress';
        }

        if ($this->findFirstKeyByPrefixes($keys, ['base_familias_']) !== null
            && $this->findFirstKeyByPrefixes($keys, ['familias_acompanhadas_', 'familias_atualizadas_']) !== null) {
            return 'family_rma_progress';
        }

        if ($this->findFirstKeyByPrefixes($keys, ['serie_mensal_unidade_']) !== null) {
            return 'monthly_unit_progress';
        }

        return 'single_query';
    }

    /**
     * Renderiza o dashboard de consulta simples para um indicador.
     * 
     * Executa a consulta vinculada e monta a resposta com dados filtrados e estruturados
     * para exibição em tabelas e gráficos.
     * Aplicável para indicadores com consultas simples (não RMA).
     * 
     * @param object $indicator Objeto do indicador do PPA com id, nome, slug, objetivo
     * @param array $links Array de vínculos de consultas do indicador
     * @return mixed Renderização da página HTML com dados do dashboard
     */
    private function renderSingleQueryFromLinks(object $indicator, array $links)
    {
        $payload = $this->buildSingleQueryResponse($indicator, $links, [
            'cras' => null,
            'regiao' => null,
        ]);

        return $this->renderSingleQueryDashboard($payload, $indicator);
    }

    /**
     * Renderiza a página HTML do dashboard de consulta simples.
     * 
     * Monta a página final com layouts, dados consolidados e filtros interativos.
     * Usado para indicadores que possuem consultas simples sem lógica especial de RMA.
     * 
     * @param array $payload Array contendo dados, status, mensagens e metadados do dashboard
     * @param object $indicator Objeto do indicador com informações para exibição na página
     * @return mixed Renderização da página HTML
     */
    private function renderSingleQueryDashboard(
        array $payload,
        object $indicator
    ) {
        return $this->renderPage(
            'ppa/detail.html',
            [
                'dados' => $payload['dados'],
                'status' => $payload['status'],
                'mensagem' => $payload['mensagem'],
                'indicator' => $indicator,
                'link' => $payload['meta']['link'] ?? null,
                'query' => $payload['meta']['query'] ?? null,
                'source' => $payload['meta']['source'] ?? null,
                'interactive_filters' => $payload['filters'],
                'interactive_endpoint' => url('ppa/' . ($indicator->slug ?? '') . '/data'),
            ],
            $this->pageConfigForIndicator($indicator)
        );
    }

    /**
     * Constrói a resposta estruturada para o dashboard de consulta simples.
     * 
     * Executa a consulta vinculada e organiza os dados em estruturas para:
     * - Gráficos de famílias por CRAS e região
     * - Tabelas detalhadas de CRAS e bairros
     * - Totalizações de famílias e pessoas
     * - Aplicação de filtros por CRAS e região (quando fornecidos)
     * 
     * Indicadores suportados: Todos aqueles com vinculação de consulta simples
     * Exemplos: Indicadores CRAS, cadastros por região, dados de atendimento
     * 
     * @param object $indicator Objeto do indicador do PPA
     * @param array $links Array de vínculos de consultas do indicador
     * @param array $filters Array de filtros (cras, regiao) para aplicar aos dados
     * @return array Array estruturado com dados, status, mensagens, filtros e metadados
     */
    private function buildSingleQueryResponse(object $indicator, array $links, array $filters): array
    {
        $primaryLink = $links[0] ?? null;

        if ($primaryLink === null) {
            return [
                'type' => 'single_query',
                'status' => 'Em Preparacao',
                'mensagem' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
                'dados' => $this->emptySingleQueryPayload($filters),
                'filters' => [
                    'cras' => $this->normalizeNullableFilter($filters['cras'] ?? null),
                    'regiao' => $this->normalizeNullableFilter($filters['regiao'] ?? null),
                ],
                'error' => 'Nenhum vinculo ativo foi encontrado para este indicador do PPA.',
                'meta' => [
                    'link' => null,
                    'query' => null,
                    'source' => null,
                ],
            ];
        }

        $result = $this->runLinkQuery($primaryLink, 5000);

        if (is_string($result)) {
            return [
                'type' => 'single_query',
                'status' => 'Em Preparacao',
                'mensagem' => $result,
                'dados' => $this->emptySingleQueryPayload($filters),
                'filters' => [
                    'cras' => $this->normalizeNullableFilter($filters['cras'] ?? null),
                    'regiao' => $this->normalizeNullableFilter($filters['regiao'] ?? null),
                ],
                'error' => $result,
                'meta' => [
                    'link' => $primaryLink,
                    'query' => null,
                    'source' => null,
                ],
            ];
        }

        return [
            'type' => 'single_query',
            'status' => 'Ativo',
            'mensagem' => 'Dados consolidados a partir do vinculo ativo configurado no modulo PPA.',
            'dados' => $this->buildSingleQueryPayload($result['rows'] ?? [], $filters),
            'filters' => [
                'cras' => $this->normalizeNullableFilter($filters['cras'] ?? null),
                'regiao' => $this->normalizeNullableFilter($filters['regiao'] ?? null),
            ],
            'error' => null,
            'meta' => [
                'link' => $primaryLink,
                'query' => $result['query'] ?? null,
                'source' => $result['source'] ?? null,
            ],
        ];
    }

    /**
     * Renderiza o dashboard de progresso de famílias RMA do PPA.
     * 
     * Especializado para indicadores que acompanham famílias CECAD vs acompanhamento RMA.
     * Exibe gráficos de:
     * - Progresso mensal de famílias acompanhadas vs meta
     * - Distribuição por CRAS
     * - Meta vs Realizado em percentual
     * 
     * Indicadores: Aqueles vinculados com consultas de base CECAD (base_familias_pbf) e 
     * acompanhamento RMA mensal (familias_acompanhadas_b2_mensal)
     * 
     * @param object $indicator Objeto do indicador do PPA com informações de meta (indice_futuro)
     * @param array $links Array de vínculos de consultas (deve incluir base e RMA)
     * @return mixed Renderização da página HTML com dashboard RMA
     */
    private function renderFamilyRmaProgressDashboard(object $indicator, array $links)
    {
        $payload = $this->buildFamilyRmaProgressResponse($indicator, $links, [
            'cras' => null,
            'mes_referencia' => null,
        ]);

        return $this->renderPage(
            'ppa/detail_family_rma.html',
            [
                'dados' => $payload['dados'],
                'status' => $payload['status'],
                'mensagem' => $payload['mensagem'],
                'indicator' => $indicator,
                'base_link' => $payload['meta']['base_link'] ?? null,
                'rma_link' => $payload['meta']['rma_link'] ?? null,
                'interactive_filters' => $payload['filters'],
                'interactive_endpoint' => url('ppa/' . ($indicator->slug ?? '') . '/data'),
                'dashboard_ui' => $payload['ui'] ?? $this->familyRmaUiConfig($indicator),
            ],
            $this->pageConfigForIndicator($indicator)
        );
    }

    private function renderCadUpdateRmaDashboard(object $indicator, array $links)
    {
        $payload = $this->buildCadUpdateRmaResponse($indicator, $links, [
            'cras' => null,
            'mes_referencia' => null,
        ]);

        return $this->renderPage(
            'ppa/detail_family_rma.html',
            [
                'dados' => $payload['dados'],
                'status' => $payload['status'],
                'mensagem' => $payload['mensagem'],
                'indicator' => $indicator,
                'base_link' => $payload['meta']['base_link'] ?? null,
                'rma_link' => $payload['meta']['rma_link'] ?? null,
                'interactive_filters' => $payload['filters'],
                'interactive_endpoint' => url('ppa/' . ($indicator->slug ?? '') . '/data'),
                'dashboard_ui' => $payload['ui'] ?? $this->familyRmaUiConfig($indicator),
            ],
            $this->pageConfigForIndicator($indicator)
        );
    }

    private function renderFamilySnapshotRmaProgressDashboard(object $indicator, array $links)
    {
        $payload = $this->buildFamilySnapshotRmaProgressResponse($indicator, $links, [
            'cras' => null,
            'mes_referencia' => null,
        ]);

        return $this->renderPage(
            'ppa/detail_family_rma.html',
            [
                'dados' => $payload['dados'],
                'status' => $payload['status'],
                'mensagem' => $payload['mensagem'],
                'indicator' => $indicator,
                'base_link' => $payload['meta']['base_link'] ?? null,
                'rma_link' => $payload['meta']['rma_link'] ?? null,
                'interactive_filters' => $payload['filters'],
                'interactive_endpoint' => url('ppa/' . ($indicator->slug ?? '') . '/data'),
                'dashboard_ui' => $payload['ui'] ?? $this->familyRmaUiConfig($indicator),
            ],
            $this->pageConfigForIndicator($indicator)
        );
    }

    private function renderMonthlyUnitProgressDashboard(object $indicator, array $links)
    {
        $payload = $this->buildMonthlyUnitProgressResponse($indicator, $links, [
            'unidade' => null,
            'mes_referencia' => null,
        ]);

        return $this->renderPage(
            'ppa/detail_unit_rma.html',
            [
                'dados' => $payload['dados'],
                'status' => $payload['status'],
                'mensagem' => $payload['mensagem'],
                'indicator' => $indicator,
                'link' => $payload['meta']['link'] ?? null,
                'interactive_filters' => $payload['filters'],
                'interactive_endpoint' => url('ppa/' . ($indicator->slug ?? '') . '/data'),
                'dashboard_ui' => $payload['ui'] ?? $this->monthlyUnitUiConfig($indicator),
            ],
            $this->pageConfigForIndicator($indicator)
        );
    }

    /**
     * Constrói a resposta estruturada para o dashboard de progresso de famílias RMA.
     * 
     * Integra dados de duas consultas:
     * 1. Base CECAD: Famílias inscritas no PBF (base_familias_pbf)
     * 2. RMA: Famílias acompanhadas mensalmente (familias_acompanhadas_b2_mensal)
     * 
     * Calcula:
     * - Meta de acompanhamento (10% da base por padrão, via indice_futuro do indicador)
     * - Progresso mensal acumulado
     * - Percentual de alcance por CRAS
     * - Gráficos de evolução mensal e distribuição por CRAS
     * 
     * Indicadores: Aqueles que monitoram acompanhamento de famílias PBF em CRAS
     * Exemplos: Indicadores de cobertura RMA, acompanhamento de famílias vulneráveis
     * 
     * @param object $indicator Indicador com indice_futuro (meta em percentual)
     * @param array $links Vínculos contendo consultas de base e RMA
     * @param array $filters Filtros (cras, mes_referencia) para dados interativos
     * @return array Resposta estruturada com gráficos, tabelas e metadados
     */
    private function buildFamilyRmaProgressResponse(object $indicator, array $links, array $filters): array
    {
        $baseKey = $this->findLinkKeyByPrefixes($links, ['base_familias_']);
        $rmaKey = $this->findLinkKeyByPrefixes($links, ['familias_acompanhadas_', 'familias_atualizadas_']);
        $baseLink = $baseKey !== null ? $this->findLinkByResultKey($links, $baseKey) : null;
        $rmaLink = $rmaKey !== null ? $this->findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $rmaLink === null) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => 'Vinculos incompletos.',
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $baseResult = $this->runLinkQuery($baseLink, 500);

        if (is_string($baseResult)) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => $baseResult,
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => $baseResult,
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $rmaResult = $this->runLinkQuery($rmaLink, 5000);

        if (is_string($rmaResult)) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => $rmaResult,
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => $rmaResult,
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $payload = $this->buildFamilyRmaPayload($baseResult['rows'] ?? [], $rmaResult['rows'] ?? [], $indicator, $filters);

        return [
            'type' => 'family_rma_progress',
            'status' => 'Ativo',
            'mensagem' => $ui['message_active'] ?? 'Base CECAD de referencia cruzada com RMA CRAS mes a mes.',
            'dados' => $payload,
            'filters' => $filters,
            'error' => null,
            'ui' => $ui,
            'meta' => [
                'base_link' => $baseLink,
                'rma_link' => $rmaLink,
                'base_query' => $baseResult['query'] ?? null,
                'base_source' => $baseResult['source'] ?? null,
                'rma_query' => $rmaResult['query'] ?? null,
                'rma_source' => $rmaResult['source'] ?? null,
            ],
        ];
    }

    private function buildFamilySnapshotRmaProgressResponse(object $indicator, array $links, array $filters): array
    {
        $baseKey = $this->findLinkKeyByPrefixes($links, ['base_familias_']);
        $updatedKey = $this->findLinkKeyByPrefixes($links, ['familias_atualizadas_']);
        $rmaKey = $this->findLinkKeyByPrefixes($links, ['serie_mensal_unidade_']);
        $baseLink = $baseKey !== null ? $this->findLinkByResultKey($links, $baseKey) : null;
        $updatedLink = $updatedKey !== null ? $this->findLinkByResultKey($links, $updatedKey) : null;
        $rmaLink = $rmaKey !== null ? $this->findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $updatedLink === null || $rmaLink === null) {
            return [
                'type' => 'family_snapshot_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => 'Vinculos incompletos.',
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'updated_link' => $updatedLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $baseResult = $this->runLinkQuery($baseLink, 500);
        $updatedResult = $this->runLinkQuery($updatedLink, 500);
        $rmaResult = $this->runLinkQuery($rmaLink, 5000);

        foreach ([$baseResult, $updatedResult, $rmaResult] as $result) {
            if (is_string($result)) {
                return [
                    'type' => 'family_snapshot_rma_progress',
                    'status' => 'Em Preparacao',
                    'mensagem' => $result,
                    'dados' => $this->emptyFamilyRmaPayload(),
                    'filters' => $filters,
                    'error' => $result,
                    'ui' => $ui,
                    'meta' => [
                        'base_link' => $baseLink,
                        'updated_link' => $updatedLink,
                        'rma_link' => $rmaLink,
                    ],
                ];
            }
        }

        return [
            'type' => 'family_snapshot_rma_progress',
            'status' => 'Ativo',
            'mensagem' => $ui['message_active'] ?? 'Base CECAD de referência com taxa de atualização cadastral consolidada.',
            'dados' => $this->buildFamilySnapshotRmaPayload(
                $baseResult['rows'] ?? [],
                $updatedResult['rows'] ?? [],
                $rmaResult['rows'] ?? [],
                $indicator,
                $filters
            ),
            'filters' => $filters,
            'error' => null,
            'ui' => $ui,
            'meta' => [
                'base_link' => $baseLink,
                'updated_link' => $updatedLink,
                'rma_link' => $rmaLink,
                'base_query' => $baseResult['query'] ?? null,
                'updated_query' => $updatedResult['query'] ?? null,
                'rma_query' => $rmaResult['query'] ?? null,
                'base_source' => $baseResult['source'] ?? null,
                'updated_source' => $updatedResult['source'] ?? null,
                'rma_source' => $rmaResult['source'] ?? null,
            ],
        ];
    }

    private function buildCadUpdateRmaResponse(object $indicator, array $links, array $filters): array
    {
        $baseKey = $this->findLinkKeyByPrefixes($links, ['base_familias_']);
        $rmaKey = $this->findLinkKeyByPrefixes($links, ['serie_mensal_unidade_', 'familias_acompanhadas_']);
        $baseLink = $baseKey !== null ? $this->findLinkByResultKey($links, $baseKey) : null;
        $rmaLink = $rmaKey !== null ? $this->findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $rmaLink === null) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => 'Vinculos incompletos.',
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $baseResult = $this->runLinkQuery($baseLink, 500);

        if (is_string($baseResult)) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => $baseResult,
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => $baseResult,
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $rmaResult = $this->runLinkQuery($rmaLink, 5000);

        if (is_string($rmaResult)) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => $rmaResult,
                'dados' => $this->emptyFamilyRmaPayload(),
                'filters' => $filters,
                'error' => $rmaResult,
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        return [
            'type' => 'family_rma_progress',
            'status' => 'Ativo',
            'mensagem' => $ui['message_active'] ?? 'Base CECAD de referencia cruzada com RMA CRAS C.3.',
            'dados' => $this->buildCadUpdateRmaPayload($baseResult['rows'] ?? [], $rmaResult['rows'] ?? [], $indicator, $filters),
            'filters' => $filters,
            'error' => null,
            'ui' => $ui,
            'meta' => [
                'base_link' => $baseLink,
                'rma_link' => $rmaLink,
                'base_query' => $baseResult['query'] ?? null,
                'base_source' => $baseResult['source'] ?? null,
                'rma_query' => $rmaResult['query'] ?? null,
                'rma_source' => $rmaResult['source'] ?? null,
            ],
        ];
    }

    private function buildMonthlyUnitProgressResponse(object $indicator, array $links, array $filters): array
    {
        $seriesKey = $this->findLinkKeyByPrefixes($links, ['serie_mensal_unidade_']);
        $seriesLink = $seriesKey !== null ? $this->findLinkByResultKey($links, $seriesKey) : null;
        $ui = $this->monthlyUnitUiConfig($indicator);

        if ($seriesLink === null) {
            return [
                'type' => 'monthly_unit_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => $this->emptyMonthlyUnitPayload(),
                'filters' => $filters,
                'error' => 'Vinculo mensal por unidade nao encontrado.',
                'ui' => $ui,
                'meta' => [
                    'link' => null,
                ],
            ];
        }

        $result = $this->runLinkQuery($seriesLink, 5000);

        if (is_string($result)) {
            return [
                'type' => 'monthly_unit_progress',
                'status' => 'Em Preparacao',
                'mensagem' => $result,
                'dados' => $this->emptyMonthlyUnitPayload(),
                'filters' => $filters,
                'error' => $result,
                'ui' => $ui,
                'meta' => [
                    'link' => $seriesLink,
                ],
            ];
        }

        return [
            'type' => 'monthly_unit_progress',
            'status' => 'Ativo',
            'mensagem' => $ui['message_active'] ?? 'Leitura mensal da base RMA consolidada por unidade.',
            'dados' => $this->buildMonthlyUnitPayload($result['rows'] ?? [], $indicator, $filters),
            'filters' => [
                'unidade' => $this->normalizeNullableFilter($filters['unidade'] ?? null),
                'mes_referencia' => $this->normalizeDateFilter($filters['mes_referencia'] ?? null),
            ],
            'error' => null,
            'ui' => $ui,
            'meta' => [
                'link' => $seriesLink,
                'query' => $result['query'] ?? null,
                'source' => $result['source'] ?? null,
            ],
        ];
    }

    /**
     * Executa uma consulta vinculada a um indicador contra uma fonte de dados externa.
     * 
     * Resolve a consulta SQL e a fonte de dados registradas, executa a query com limite
     * de linhas e retorna os resultados ou uma mensagem de erro.
     * 
     * @param object $link Objeto do vínculo contendo external_query_id
     * @param int $limit Número máximo de linhas a retornar (ex: 500 para base, 5000 para RMA)
     * @return array|string Array com 'rows', 'query', 'source' se sucesso; String com mensagem de erro se falha
     */
    private function runLinkQuery(object $link, int $limit): array|string
    {
        $query = (new ExternalQueryModel())->readById((int) $link->external_query_id);

        if (is_string($query)) {
            return $query;
        }

        $source = (new ExternalDataSourceModel())->readById((int) $query->source_id);

        if (is_string($source)) {
            return $source;
        }

        $preview = ExternalDatabaseRuntime::runRegisteredQuery($source, $query, $limit, [
            'indicator_id' => $link->indicador_id ?? null,
            'indicator_code' => $link->codigo_indicador ?? null,
        ]);

        if (!$preview['success']) {
            return $preview['message'];
        }

        return [
            'rows' => $preview['rows'] ?? [],
            'query' => $query,
            'source' => $source,
        ];
    }

    /**
     * Constrói o payload de dados para um indicador de consulta simples.
     * 
     * Processa linhas de resultado e organiza em estruturas para visualização:
     * - Totalizações gerais (famílias, pessoas)
     * - Agrupamentos por CRAS e região (para gráficos)
     * - Tabelas detalhadas por CRAS e bairro
     * - Aplicação de filtros por CRAS e região (se fornecidos)
     * 
     * Normaliza dados: CRAS com aliases, bairros, regiões
     * Extrai referência de cadastro (quando disponível)
     * 
     * Indicadores: Todos com consultas simples (estrutura de rows com cras, bairro, regiao, total_familias, total_pessoas)
     * 
     * @param array $rows Linhas de resultado da consulta com colunas: cras, bairro, regiao, total_familias, total_pessoas, ref_cad
     * @param array $filters Filtros opcionais (cras, regiao) para filtrar dados
     * @return array Estrutura com totais, listas agrupadas, tabelas e metadados
     */
    private function buildSingleQueryPayload(array $rows, array $filters = []): array
    {
        $filterCras = $this->normalizeNullableFilter($filters['cras'] ?? null);
        $filterRegiao = $this->normalizeNullableFilter($filters['regiao'] ?? null);
        $totalFamilias = 0;
        $totalPessoas = 0;
        $familiasPorCras = [];
        $familiasPorRegiao = [];
        $tabelaCras = [];
        $tabelaBairro = [];
        $referencia = null;

        foreach ($rows as $row) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $bairro = $this->cleanLabel((string) ($row['bairro'] ?? 'Não informado'), 'Não informado');
            $regiao = $this->cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $familias = (int) ($row['total_familias'] ?? 0);
            $pessoas = (int) ($row['total_pessoas'] ?? 0);
            $refCad = trim((string) ($row['ref_cad'] ?? ''));

            if ($filterCras !== null && $cras !== $filterCras) {
                continue;
            }

            if ($filterRegiao !== null && $regiao !== $filterRegiao) {
                continue;
            }

            $totalFamilias += $familias;
            $totalPessoas += $pessoas;

            if ($refCad !== '' && $referencia === null) {
                $referencia = $refCad;
            }

            if (!isset($familiasPorCras[$cras])) {
                $familiasPorCras[$cras] = ['cras' => $cras, 'total' => 0];
            }
            $familiasPorCras[$cras]['total'] += $familias;

            if (!isset($familiasPorRegiao[$regiao])) {
                $familiasPorRegiao[$regiao] = ['regiao' => $regiao, 'total' => 0];
            }
            $familiasPorRegiao[$regiao]['total'] += $familias;

            if (!isset($tabelaCras[$cras])) {
                $tabelaCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => $regiao,
                    'total_familias' => 0,
                    'total_pessoas' => 0,
                ];
            }

            $tabelaCras[$cras]['total_familias'] += $familias;
            $tabelaCras[$cras]['total_pessoas'] += $pessoas;

            $bairroKey = mb_strtolower($bairro) . '|' . mb_strtolower($cras) . '|' . mb_strtolower($regiao);

            if (!isset($tabelaBairro[$bairroKey])) {
                $tabelaBairro[$bairroKey] = [
                    'bairro' => $bairro,
                    'cras' => $cras,
                    'regiao' => $regiao,
                    'total_familias' => 0,
                    'total_pessoas' => 0,
                ];
            }

            $tabelaBairro[$bairroKey]['total_familias'] += $familias;
            $tabelaBairro[$bairroKey]['total_pessoas'] += $pessoas;
        }

        $tabelaCras = array_values($tabelaCras);
        usort($tabelaCras, fn ($a, $b) => strcasecmp($a['cras'], $b['cras']));

        $tabelaBairro = array_values($tabelaBairro);
        usort($tabelaBairro, function ($a, $b) {
            $compareCras = strcasecmp($a['cras'], $b['cras']);

            if ($compareCras !== 0) {
                return $compareCras;
            }

            $compareBairro = strcasecmp($a['bairro'], $b['bairro']);

            if ($compareBairro !== 0) {
                return $compareBairro;
            }

            return strcasecmp($a['regiao'], $b['regiao']);
        });

        $familiasPorCras = array_values($familiasPorCras);
        usort($familiasPorCras, fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcasecmp($a['cras'], $b['cras']));

        $familiasPorRegiao = array_values($familiasPorRegiao);
        usort($familiasPorRegiao, fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcasecmp($a['regiao'], $b['regiao']));

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
            'filtros_ativos' => [
                'cras' => $filterCras,
                'regiao' => $filterRegiao,
            ],
            'registros' => $rows,
        ];
    }

    /**
     * Retorna uma estrutura vazia de payload para indicadores de consulta simples.
     * 
     * Usado quando não há dados disponíveis para um indicador (status "Em Preparacao").
     * Mantém a mesma estrutura de buildSingleQueryPayload mas com valores zerados/vazios.
     * 
     * Indicadores: Todos aqueles de consulta simples que ainda não possuem dados
     * 
     * @param array $filters Filtros (cras, regiao) que permanecerão na estrutura vazia
     * @return array Estrutura vazia com zeros e arrays vazios
     */
    private function emptySingleQueryPayload(array $filters = []): array
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
                'cras' => $this->normalizeNullableFilter($filters['cras'] ?? null),
                'regiao' => $this->normalizeNullableFilter($filters['regiao'] ?? null),
            ],
            'registros' => [],
        ];
    }

    /**
     * Constrói o payload de dados para indicadores de progresso de famílias RMA.
     * 
     * Integra duas consultas:
     * 1. Base CECAD: Total de famílias inscritas no PBF por CRAS
     * 2. RMA: Famílias acompanhadas mensalmente por CRAS
     * 
     * Calcula:
     * - Meta de acompanhamento (indice_futuro em % da base)
     * - Acumulado mensal de famílias acompanhadas
     * - Percentual de alcance por CRAS e geral
     * - Gráficos: progresso mensal, distribuição por CRAS, comparativo meta vs realizado
     * 
     * Aplicável aos indicadores RMA vinculados com base_familias_pbf e familias_acompanhadas_b2_mensal.
     * Exemplos: Cobertura RMA, acompanhamento de famílias vulneráveis
     * 
     * @param array $baseRows Linhas da base CECAD com colunas: cras, regiao, total_familias_pbf, ref_cad_referencia
     * @param array $rmaRows Linhas do RMA com colunas: mes_referencia, cras, nome_unidade, total_familias_acompanhadas
     * @param object $indicator Indicador com indice_futuro (meta em %)
     * @param array $filters Filtros (cras, mes_referencia) para dados interativos
     * @return array Estrutura com gráficos, tabelas mensal/CRAS e metadados
     */
    private function buildFamilyRmaPayload(array $baseRows, array $rmaRows, object $indicator, array $filters): array
    {
        $referenciaBase = null;
        $anoApuracao = null;
        $baseTotal = 0;
        $metaPercentual = ((float) ($indicator->indice_futuro ?? 10)) / 100;
        $filterCras = $this->normalizeNullableFilter($filters['cras'] ?? null);
        $filterMes = $this->normalizeNullableFilter($filters['mes_referencia'] ?? null);

        $baseRows = $this->filterBaseRows($baseRows, $filterCras);
        $rmaRows = $this->filterRmaRows($rmaRows, $filterCras, $filterMes);

        $basePorCras = [];
        $rmaPorMes = [];
        $rmaPorCras = [];

        foreach ($baseRows as $row) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $regiao = $this->cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $totalFamilias = (int) ($row['total_familias_pbf'] ?? 0);
            $refCad = trim((string) ($row['ref_cad_referencia'] ?? $row['ref_cad'] ?? ''));

            $baseTotal += $totalFamilias;

            if ($referenciaBase === null && $refCad !== '') {
                $referenciaBase = $refCad;
            }

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => $regiao,
                    'base_familias_pbf' => 0,
                ];
            }

            $basePorCras[$cras]['base_familias_pbf'] += $totalFamilias;
        }

        foreach ($rmaRows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? $row['nome_unidade'] ?? 'Não informado'));
            $acompanhadas = (int) ($row['total_familias_acompanhadas'] ?? 0);

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);

            if (!isset($rmaPorMes[$mesReferencia])) {
                $rmaPorMes[$mesReferencia] = [
                    'mes_referencia' => $mesReferencia,
                    'mes_label' => $this->monthLabel($mesReferencia),
                    'total_familias_acompanhadas' => 0,
                ];
            }

            $rmaPorMes[$mesReferencia]['total_familias_acompanhadas'] += $acompanhadas;

            if (!isset($rmaPorCras[$cras])) {
                $rmaPorCras[$cras] = [
                    'cras' => $cras,
                    'familias_acompanhadas' => 0,
                ];
            }

            $rmaPorCras[$cras]['familias_acompanhadas'] += $acompanhadas;

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => 'Nao informada',
                    'base_familias_pbf' => 0,
                ];
            }
        }

        ksort($rmaPorMes);

        $metaFamilias = $baseTotal * $metaPercentual;
        $acumulado = 0;
        $tabelaMensal = [];
        $graficoMensal = [];

        foreach (array_values($rmaPorMes) as $row) {
            $mesTotal = (int) $row['total_familias_acompanhadas'];
            $acumulado += $mesTotal;

            $percentualMes = $metaFamilias > 0 ? ($mesTotal / $metaFamilias) * 100 : 0;
            $percentualAcumulado = $metaFamilias > 0 ? ($acumulado / $metaFamilias) * 100 : 0;

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
            $baseFamilias = (int) $row['base_familias_pbf'];
            $acompanhadas = (int) ($rmaPorCras[$cras]['familias_acompanhadas'] ?? 0);
            $metaCras = $baseFamilias * $metaPercentual;

            $tabelaCras[] = [
                'cras' => $cras,
                'regiao' => $row['regiao'],
                'base_familias_pbf' => $baseFamilias,
                'meta_familias' => $metaCras,
                'familias_acompanhadas' => $acompanhadas,
                'percentual_alcancado' => $metaCras > 0 ? ($acompanhadas / $metaCras) * 100 : 0,
            ];
        }

        usort($tabelaCras, fn ($a, $b) => strcasecmp($a['cras'], $b['cras']));

        $graficoCras = array_map(fn ($row) => [
            'cras' => $row['cras'],
            'total' => (int) $row['familias_acompanhadas'],
        ], $tabelaCras);
        usort($graficoCras, fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcasecmp($a['cras'], $b['cras']));

        $realizadoTotal = $acumulado;
        $percentualAlcancado = $baseTotal > 0 ? ($realizadoTotal / $metaFamilias) * 100 : 0;
        $referenciaMeta = [
            ['categoria' => 'Meta 10%', 'total' => (float) round($metaFamilias, 2)],
            ['categoria' => 'Realizado', 'total' => (float) round($realizadoTotal, 2)],
        ];

        return [
            'total_geral' => $baseTotal,
            'meta_familias' => $metaFamilias,
            'familias_acompanhadas_total' => $realizadoTotal,
            'percentual_alcancado_total' => $percentualAlcancado,
            'percentual_periodo' => $this->buildPeriodProgressPercent(count($graficoMensal)),
            'meses_periodo' => count($graficoMensal),
            'referencia' => $referenciaBase,
            'ano_apuracao' => $anoApuracao,
            'grafico_mensal' => $graficoMensal,
            'grafico_cras' => $graficoCras,
            'grafico_meta' => $referenciaMeta,
            'tabela_mensal' => $tabelaMensal,
            'tabela_cras' => $tabelaCras,
            'filtros_ativos' => [
                'cras' => $filterCras,
                'mes_referencia' => $filterMes,
            ],
        ];
    }

    /**
     * Retorna uma estrutura vazia de payload para indicadores de progresso RMA.
     * 
     * Usado quando há erros na execução das consultas ou faltam vínculos necessários.
     * Mantém a mesma estrutura de buildFamilyRmaPayload mas com valores zerados/vazios.
     * 
     * Indicadores: Aqueles de tipo family_rma_progress que ainda não possuem dados válidos
     * 
     * @return array Estrutura vazia com zeros e arrays vazios para gráficos/tabelas
     */
    private function emptyFamilyRmaPayload(): array
    {
        return [
            'total_geral' => 0,
            'meta_familias' => 0,
            'familias_acompanhadas_total' => 0,
            'percentual_alcancado_total' => 0,
            'referencia' => null,
            'ano_apuracao' => null,
            'grafico_mensal' => [],
            'grafico_cras' => [],
            'grafico_meta' => [],
            'tabela_mensal' => [],
            'tabela_cras' => [],
            'filtros_ativos' => [
                'cras' => null,
                'mes_referencia' => null,
            ],
        ];
    }

    private function buildFamilySnapshotRmaPayload(array $baseRows, array $updatedRows, array $rmaRows, object $indicator, array $filters): array
    {
        $referenciaBase = null;
        $anoApuracao = null;
        $baseTotal = 0;
        $updatedTotal = 0;
        $metaPercentual = ((float) ($indicator->indice_futuro ?? 85)) / 100;
        $filterCras = $this->normalizeNullableFilter($filters['cras'] ?? null);
        $filterMes = $this->normalizeNullableFilter($filters['mes_referencia'] ?? null);

        $baseRows = $this->filterBaseRows($baseRows, $filterCras);
        $updatedRows = $this->filterBaseRows($updatedRows, $filterCras);
        $rmaRows = $this->filterRmaRows($rmaRows, $filterCras, $filterMes);

        $basePorCras = [];
        $updatedPorCras = [];
        $rmaPorMes = [];

        foreach ($baseRows as $row) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $regiao = $this->cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $totalFamilias = (int) ($row['total_familias_pbf'] ?? $row['total_familias'] ?? 0);
            $refCad = trim((string) ($row['ref_cad_referencia'] ?? $row['ref_cad'] ?? ''));

            $baseTotal += $totalFamilias;

            if ($referenciaBase === null && $refCad !== '') {
                $referenciaBase = $refCad;
            }

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => $regiao,
                    'base_familias_pbf' => 0,
                ];
            }

            $basePorCras[$cras]['base_familias_pbf'] += $totalFamilias;
        }

        foreach ($updatedRows as $row) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $totalAtualizadas = (int) ($row['total_familias_atualizadas'] ?? $row['total_familias_acompanhadas'] ?? 0);
            $refCad = trim((string) ($row['ref_cad_referencia'] ?? $row['mes_referencia'] ?? ''));

            $updatedTotal += $totalAtualizadas;

            if ($referenciaBase === null && $refCad !== '') {
                $referenciaBase = $refCad;
            }

            if (!isset($updatedPorCras[$cras])) {
                $updatedPorCras[$cras] = 0;
            }

            $updatedPorCras[$cras] += $totalAtualizadas;

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => 'Nao informada',
                    'base_familias_pbf' => 0,
                ];
            }
        }

        foreach ($rmaRows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $totalMensal = (int) ($row['total_inseridos'] ?? $row['total_familias_acompanhadas'] ?? 0);

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);

            if (!isset($rmaPorMes[$mesReferencia])) {
                $rmaPorMes[$mesReferencia] = [
                    'mes_referencia' => $mesReferencia,
                    'mes_label' => $this->monthLabel($mesReferencia),
                    'total_familias_acompanhadas' => 0,
                ];
            }

            $rmaPorMes[$mesReferencia]['total_familias_acompanhadas'] += $totalMensal;
        }

        ksort($rmaPorMes);

        $metaFamilias = $baseTotal * $metaPercentual;
        $acumuladoMensal = 0;
        $tabelaMensal = [];
        $graficoMensal = [];

        foreach (array_values($rmaPorMes) as $row) {
            $mesTotal = (int) $row['total_familias_acompanhadas'];
            $acumuladoMensal += $mesTotal;

            $percentualMes = $metaFamilias > 0 ? ($mesTotal / $metaFamilias) * 100 : 0;
            $percentualAcumulado = $metaFamilias > 0 ? ($acumuladoMensal / $metaFamilias) * 100 : 0;

            $tabelaMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes_label' => $row['mes_label'],
                'familias_acompanhadas' => $mesTotal,
                'acumulado' => $acumuladoMensal,
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
            $baseFamilias = (int) ($row['base_familias_pbf'] ?? 0);
            $metaCras = $baseFamilias * $metaPercentual;
            $atualizadas = (int) ($updatedPorCras[$cras] ?? 0);
            $percentualCras = $metaCras > 0 ? ($atualizadas / $metaCras) * 100 : 0;

            $tabelaCras[] = [
                'cras' => $cras,
                'regiao' => $row['regiao'] ?? 'Nao informada',
                'base_familias_pbf' => $baseFamilias,
                'meta_familias' => $metaCras,
                'familias_acompanhadas' => $atualizadas,
                'percentual_alcancado' => $percentualCras,
            ];
        }

        usort($tabelaCras, fn ($a, $b) => ($b['familias_acompanhadas'] <=> $a['familias_acompanhadas']) ?: strcasecmp($a['cras'], $b['cras']));

        $graficoCras = array_map(fn ($row) => [
            'cras' => $row['cras'],
            'total' => (int) $row['familias_acompanhadas'],
        ], $tabelaCras);

        return [
            'total_geral' => $baseTotal,
            'meta_familias' => $metaFamilias,
            'familias_acompanhadas_total' => $updatedTotal,
            'percentual_alcancado_total' => $metaFamilias > 0 ? ($updatedTotal / $metaFamilias) * 100 : 0,
            'percentual_periodo' => $this->buildPeriodProgressPercent(count($graficoMensal)),
            'meses_periodo' => count($graficoMensal),
            'referencia' => $referenciaBase,
            'ano_apuracao' => $anoApuracao ?? ($referenciaBase !== null ? substr($referenciaBase, 0, 4) : null),
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

    private function buildCadUpdateRmaPayload(array $baseRows, array $rmaRows, object $indicator, array $filters): array
    {
        $referenciaBase = null;
        $anoApuracao = null;
        $baseTotal = 0;
        $metaPercentual = (float) ($indicator->indice_futuro ?? 85);
        $metaFator = $metaPercentual / 100;
        $filterCras = $this->normalizeNullableFilter($filters['cras'] ?? null);
        $filterMes = $this->normalizeNullableFilter($filters['mes_referencia'] ?? null);

        $baseRows = $this->filterBaseRows($baseRows, $filterCras);
        $rmaRows = $this->filterRmaRows($rmaRows, $filterCras, $filterMes);

        $basePorCras = [];
        $rmaPorMes = [];
        $rmaPorCras = [];

        foreach ($baseRows as $row) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? 'Não informado'));
            $regiao = $this->cleanLabel((string) ($row['regiao'] ?? 'Não informada'), 'Não informada');
            $totalFamilias = (int) ($row['total_familias_pbf'] ?? 0);
            $refCad = trim((string) ($row['ref_cad_referencia'] ?? $row['ref_cad'] ?? ''));

            $baseTotal += $totalFamilias;

            if ($referenciaBase === null && $refCad !== '') {
                $referenciaBase = $refCad;
            }

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => $regiao,
                    'base_familias_pbf' => 0,
                ];
            }

            $basePorCras[$cras]['base_familias_pbf'] += $totalFamilias;
        }

        foreach ($rmaRows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? $row['unidade'] ?? $row['nome_unidade'] ?? 'Não informado'));
            $atualizadas = (int) ($row['total_inseridos'] ?? $row['total_familias_acompanhadas'] ?? 0);

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);

            if (!isset($rmaPorMes[$mesReferencia])) {
                $rmaPorMes[$mesReferencia] = [
                    'mes_referencia' => $mesReferencia,
                    'mes_label' => $this->monthLabel($mesReferencia),
                    'total_familias_acompanhadas' => 0,
                ];
            }

            $rmaPorMes[$mesReferencia]['total_familias_acompanhadas'] += $atualizadas;

            if (!isset($rmaPorCras[$cras])) {
                $rmaPorCras[$cras] = [
                    'cras' => $cras,
                    'familias_acompanhadas' => 0,
                ];
            }

            $rmaPorCras[$cras]['familias_acompanhadas'] += $atualizadas;

            if (!isset($basePorCras[$cras])) {
                $basePorCras[$cras] = [
                    'cras' => $cras,
                    'regiao' => 'Nao informada',
                    'base_familias_pbf' => 0,
                ];
            }
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
            $acompanhadas = (int) (($rmaPorCras[$cras]['familias_acompanhadas'] ?? 0));
            $metaCras = $baseCras * $metaFator;
            $percentualCras = $metaCras > 0 ? ($acompanhadas / $metaCras) * 100 : 0;

            $tabelaCras[] = [
                'cras' => $cras,
                'regiao' => $row['regiao'] ?? 'Nao informada',
                'base_familias_pbf' => $baseCras,
                'meta_familias' => $metaCras,
                'familias_acompanhadas' => $acompanhadas,
                'percentual_alcancado' => $percentualCras,
            ];
        }

        usort($tabelaCras, fn ($a, $b) => ($b['familias_acompanhadas'] <=> $a['familias_acompanhadas']) ?: strcasecmp($a['cras'], $b['cras']));

        $graficoCras = array_map(fn ($row) => [
            'cras' => $row['cras'],
            'total' => $row['familias_acompanhadas'],
        ], $tabelaCras);

        return [
            'total_geral' => $baseTotal,
            'meta_familias' => $metaTotal,
            'familias_acompanhadas_total' => $acumulado,
            'percentual_alcancado_total' => $metaTotal > 0 ? ($acumulado / $metaTotal) * 100 : 0,
            'percentual_periodo' => $this->buildPeriodProgressPercent(count($graficoMensal)),
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

    private function buildMonthlyUnitPayload(array $rows, object $indicator, array $filters): array
    {
        $metaAnual = (float) ($indicator->indice_futuro ?? 0);
        $filterUnidade = $this->normalizeNullableFilter($filters['unidade'] ?? null);
        $filterMes = $this->normalizeDateFilter($filters['mes_referencia'] ?? null);
        $rows = $this->filterUnitRows($rows, $indicator, $filterUnidade, $filterMes);

        $anoApuracao = null;
        $mensal = [];
        $unidades = [];

        foreach ($rows as $row) {
            $mesReferencia = trim((string) ($row['mes_referencia'] ?? ''));
            $unidade = $this->normalizeIndicatorUnitLabel(
                $indicator,
                (string) ($row['unidade'] ?? $row['nome_unidade'] ?? $row['id_creas'] ?? $row['id_cras'] ?? 'Nao informado')
            );
            $totalInseridos = (int) ($row['total_inseridos'] ?? $row['total_casos'] ?? 0);

            if ($mesReferencia === '') {
                continue;
            }

            $anoApuracao ??= substr($mesReferencia, 0, 4);

            if (!isset($mensal[$mesReferencia])) {
                $mensal[$mesReferencia] = [
                    'mes_referencia' => $mesReferencia,
                    'mes_label' => $this->monthLabel($mesReferencia),
                    'total_inseridos' => 0,
                ];
            }

            $mensal[$mesReferencia]['total_inseridos'] += $totalInseridos;

            if (!isset($unidades[$unidade])) {
                $unidades[$unidade] = [
                    'unidade' => $unidade,
                    'total_inseridos' => 0,
                ];
            }

            $unidades[$unidade]['total_inseridos'] += $totalInseridos;
        }

        ksort($mensal);

        $totalInseridos = 0;
        $tabelaMensal = [];
        $graficoMensal = [];

        foreach (array_values($mensal) as $row) {
            $mesTotal = (int) $row['total_inseridos'];
            $totalInseridos += $mesTotal;

            $percentualMes = $metaAnual > 0 ? ($mesTotal / $metaAnual) * 100 : 0;
            $percentualAcumulado = $metaAnual > 0 ? ($totalInseridos / $metaAnual) * 100 : 0;

            $tabelaMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes_label' => $row['mes_label'],
                'total_inseridos' => $mesTotal,
                'acumulado' => $totalInseridos,
                'percentual_mes' => $percentualMes,
                'percentual_acumulado' => $percentualAcumulado,
            ];

            $graficoMensal[] = [
                'mes_referencia' => $row['mes_referencia'],
                'mes' => $row['mes_label'],
                'total' => $mesTotal,
            ];
        }

        $tabelaUnidades = array_values(array_map(function ($row) use (&$totalInseridos, $metaAnual) {
            $participacao = $totalInseridos > 0 ? ($row['total_inseridos'] / $totalInseridos) * 100 : 0;
            $percentualMeta = $metaAnual > 0 ? ($row['total_inseridos'] / $metaAnual) * 100 : 0;

            return [
                'unidade' => $row['unidade'],
                'total_inseridos' => (int) $row['total_inseridos'],
                'participacao' => $participacao,
                'meta_anual' => $metaAnual,
                'percentual_meta' => $percentualMeta,
            ];
        }, $unidades));

        usort($tabelaUnidades, fn ($a, $b) => ($b['total_inseridos'] <=> $a['total_inseridos']) ?: strcasecmp($a['unidade'], $b['unidade']));

        $graficoUnidades = array_map(fn ($row) => [
            'unidade' => $row['unidade'],
            'total' => $row['total_inseridos'],
        ], $tabelaUnidades);

        return [
            'total_unidades' => count($tabelaUnidades),
            'meta_anual' => $metaAnual,
            'total_inseridos' => $totalInseridos,
            'percentual_alcancado_total' => $metaAnual > 0 ? ($totalInseridos / $metaAnual) * 100 : 0,
            'percentual_periodo' => $this->buildPeriodProgressPercent(count($graficoMensal)),
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

    private function emptyMonthlyUnitPayload(): array
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
            'filtros_ativos' => [
                'unidade' => null,
                'mes_referencia' => null,
            ],
        ];
    }

    /**
     * Filtra linhas da base CECAD por CRAS (quando filtro é fornecido).
     * 
     * @param array $rows Linhas da base com coluna 'cras'
     * @param ?string $filterCras CRAS para filtro (null = sem filtro)
     * @return array Linhas filtradas ou todas se filterCras for null
     */
    private function filterBaseRows(array $rows, ?string $filterCras): array
    {
        if ($filterCras === null) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($filterCras) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? ''));
            return $cras === $filterCras;
        }));
    }

    /**
     * Filtra linhas do RMA por CRAS e/ou Mês de Referência (quando filtros são fornecidos).
     * 
     * @param array $rows Linhas do RMA com colunas: cras (ou nome_unidade), mes_referencia
     * @param ?string $filterCras CRAS para filtro (null = sem filtro)
     * @param ?string $filterMes Mês (YYYY-MM) para filtro (null = sem filtro)
     * @return array Linhas filtradas conforme os critérios fornecidos
     */
    private function filterRmaRows(array $rows, ?string $filterCras, ?string $filterMes): array
    {
        return array_values(array_filter($rows, function ($row) use ($filterCras, $filterMes) {
            $cras = $this->normalizeCrasLabel((string) ($row['cras'] ?? $row['unidade'] ?? $row['nome_unidade'] ?? ''));
            $mes = trim((string) ($row['mes_referencia'] ?? ''));

            if ($filterCras !== null && $cras !== $filterCras) {
                return false;
            }

            if ($filterMes !== null && $mes !== $filterMes) {
                return false;
            }

            return true;
        }));
    }

    private function filterUnitRows(array $rows, object $indicator, ?string $filterUnidade, ?string $filterMes): array
    {
        return array_values(array_filter($rows, function ($row) use ($indicator, $filterUnidade, $filterMes) {
            $unidade = $this->normalizeIndicatorUnitLabel(
                $indicator,
                (string) ($row['unidade'] ?? $row['nome_unidade'] ?? $row['id_creas'] ?? $row['id_cras'] ?? '')
            );
            $mes = trim((string) ($row['mes_referencia'] ?? ''));

            if ($filterUnidade !== null && $unidade !== $filterUnidade) {
                return false;
            }

            if ($filterMes !== null && $mes !== $filterMes) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Encontra um vínculo de consulta pelo seu campo de resultado.
     * 
     * Procura no array de vínculos por aquele que possui um campo específico no resultado.
     * Usado para localizar vínculos específicos (ex: base_familias_pbf, familias_acompanhadas_b2_mensal)
     * 
     * @param array $links Array de vínculos com propriedade 'campo_resultado'
     * @param string $key Chave do campo resultado a procurar
     * @return ?object Vínculo encontrado ou null se não existe
     */
    private function findLinkByResultKey(array $links, string $key): ?object
    {
        foreach ($links as $link) {
            if (trim((string) ($link->campo_resultado ?? '')) === $key) {
                return $link;
            }
        }

        return null;
    }

    private function findLinkKeyByPrefixes(array $links, array $prefixes): ?string
    {
        $keys = [];

        foreach ($links as $link) {
            $keys[] = trim((string) ($link->campo_resultado ?? ''));
        }

        return $this->findFirstKeyByPrefixes($keys, $prefixes);
    }

    private function findFirstKeyByPrefixes(array $keys, array $prefixes): ?string
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

    private function familyRmaUiConfig(object $indicator): array
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        $config = match ($code) {
            'PPA-CRAS-ATUALIZACAO-C3' => [
                'base_label' => 'Base famílias 1/2 SM',
                'meta_label' => 'Meta 85%',
                'acompanhadas_label' => 'Famílias atualizadas',
                'percentual_label' => '% Alcançado',
                'base_foot' => 'Base CECAD de famílias com renda per capita de até 1/2 salário mínimo.',
                'meta_foot' => 'Meta percentual definida para o indicador',
                'acompanhadas_foot' => 'Total acumulado de famílias encaminhadas para atualização no RMA C.3',
                'percentual_foot' => 'Percentual atingido',
                'mensal_title' => 'Atualização mês a mês',
                'cras_title' => 'Atualização por CRAS',
                'mensal_table_title' => 'Série mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS C.3 com acumulado anual do período',
                'cras_table_title' => 'Consolidado por CRAS',
                'cras_table_subtitle' => 'Base até 1/2 salário mínimo, atualizações acumuladas e taxa por CRAS',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Base CECAD de referência cruzada com RMA CRAS C.3.',
            ],
            'PPA-ACOMPANHAR-FAMILIAS-PBF' => [
                'base_label' => 'Base famílias PBF',
                'meta_label' => 'Meta 10%',
                'acompanhadas_label' => 'Famílias acompanhadas',
                'percentual_label' => '% Alcançado',
                'base_foot' => 'Base CECAD de referência cruzada com RMA CRAS B.2.',
                'meta_foot' => 'Meta calculada sobre a base de referência',
                'acompanhadas_foot' => 'Total acumulado de novos acompanhamentos no RMA',
                'percentual_foot' => 'Percentual atingido',
                'mensal_title' => 'Acompanhamento mês a mês',
                'cras_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS B.2 com acumulado anual do periodo',
                'cras_table_title' => 'Consolidado por CRAS',
                'cras_table_subtitle' => 'Base PBF, meta territorial e acompanhamento acumulado',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Base CECAD de referência cruzada com RMA CRAS B.2.',
            ],
            'PPA-ACOMPANHAR-FAMILIAS-MEIO-SM-PAIF' => [
                'base_label' => 'Base famílias 1/2 SM',
                'meta_label' => 'Meta 10%',
                'acompanhadas_label' => 'Famílias acompanhadas',
                'percentual_label' => '% Alcançado',
                'base_foot' => 'Base CECAD de referência cruzada com RMA CRAS A.2.',
                'meta_foot' => 'Meta calculada sobre a base de referência',
                'acompanhadas_foot' => 'Total acumulado de novos acompanhamentos no RMA',
                'percentual_foot' => 'Percentual atingido',
                'mensal_title' => 'Acompanhamento mês a mês',
                'cras_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS A.2 com acumulado anual do periodo',
                'cras_table_title' => 'Consolidado por CRAS',
                'cras_table_subtitle' => 'Base até 1/2 salário mínimo, meta territorial e acompanhamento acumulado',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Base CECAD de referência cruzada com RMA CRAS A.2.',
            ],
            'PPA-ACOMPANHAR-BPC-PAIF' => [
                'base_label' => 'Base famílias BPC',
                'meta_label' => 'Meta 10%',
                'acompanhadas_label' => 'Famílias acompanhadas',
                'percentual_label' => '% Alcançado',
                'base_foot' => 'Base CECAD de referência cruzada com RMA CRAS B.4.',
                'meta_foot' => 'Meta calculada sobre a base de referência',
                'acompanhadas_foot' => 'Total acumulado de novos acompanhamentos no RMA',
                'percentual_foot' => 'Percentual atingido',
                'mensal_title' => 'Acompanhamento mês a mês',
                'cras_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS B.4 com acumulado anual do periodo',
                'cras_table_title' => 'Consolidado por CRAS',
                'cras_table_subtitle' => 'Base de famílias com BPC, meta territorial e acompanhamento acumulado',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Base CECAD de referência cruzada com RMA CRAS B.4.',
            ],
            default => [
                'base_label' => 'Base famílias PBF',
                'meta_label' => 'Meta 10%',
                'acompanhadas_label' => 'Famílias acompanhadas',
                'percentual_label' => '% Alcançado',
                'base_foot' => 'Base CECAD de referência cruzada com RMA CRAS mes a mes.',
                'meta_foot' => 'Meta calculada sobre a base de referência',
                'acompanhadas_foot' => 'Total acumulado de novos acompanhamentos no RMA',
                'percentual_foot' => 'Percentual atingido sobre a base de familias PBF',
                'mensal_title' => 'Acompanhamento mês a mês',
                'cras_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS com acumulado anual do periodo',
                'cras_table_title' => 'Consolidado por CRAS',
                'cras_table_subtitle' => 'Base PBF, meta territorial e acompanhamento acumulado',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Base CECAD de referência cruzada com RMA CRAS mes a mes.',
            ],
        };

        if ($this->shouldShowPeriodProgress($indicator)) {
            $config['show_period_progress'] = true;
            $config['period_label'] = '% Período';
            $config['period_foot'] = 'Percentual do período';
        }

        $config['mensal_percent_label'] ??= '% Mês';
        $config['mensal_accum_label'] ??= '% Acum.';
        $config['cras_meta_label'] ??= $config['meta_label'] ?? 'Meta';
        $config['cras_progress_label'] ??= '% Alc.';

        return $config;
    }

    private function monthlyUnitUiConfig(object $indicator): array
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        $config = match ($code) {
            'PPA-CRAS-ATUALIZACAO-C3' => [
                'total_label' => 'CRAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Cadastros atualizados',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CRAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de familias encaminhadas para atualizacao cadastral',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS C.3 com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CRAS',
                'unidade_table_subtitle' => 'Distribuicao das familias encaminhadas para atualizacao cadastral por unidade ao longo do ano',
                'filter_unit_label' => 'CRAS',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CRAS',
                'message_active' => 'Leitura do RMA CRAS C.3 consolidada mes a mes.',
            ],
            'PPA-CRAS-ATENDIMENTOS-C1' => [
                'total_label' => 'CRAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Atendimentos realizados',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CRAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de atendimentos individualizados realizados',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CRAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CRAS C.1 com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CRAS',
                'unidade_table_subtitle' => 'Distribuicao dos atendimentos particularizados por unidade ao longo do ano',
                'filter_unit_label' => 'CRAS',
                'filter_hint' => 'Clique em um CRAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CRAS',
                'message_active' => 'Leitura do RMA CRAS C.1 consolidada mes a mes.',
            ],
            'PPA-CREAS-CRIANCAS-ADOLESCENTES-C1A5' => [
                'total_label' => 'CREAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Casos inseridos',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CREAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de novos casos de criancas e adolescentes inseridos no PAEFI',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CREAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CREAS bloco C (C.1 a C.5) com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CREAS',
                'unidade_table_subtitle' => 'Distribuicao dos novos casos de criancas e adolescentes por unidade ao longo do ano',
                'filter_unit_label' => 'CREAS',
                'filter_hint' => 'Clique em um CREAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CREAS',
                'message_active' => 'Leitura do RMA CREAS bloco C (C.1 a C.5) consolidada mes a mes.',
            ],
            'PPA-CREAS-MSE-J4' => [
                'total_label' => 'CREAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Adolescentes inseridos',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CREAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de novos adolescentes inseridos em acompanhamento socioeducativo',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CREAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CREAS J.4 com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CREAS',
                'unidade_table_subtitle' => 'Distribuicao de novos casos de medidas socioeducativas por unidade ao longo do ano',
                'filter_unit_label' => 'CREAS',
                'filter_hint' => 'Clique em um CREAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CREAS',
                'message_active' => 'Leitura do RMA CREAS J.4 consolidada mes a mes.',
            ],
            'PPA-CREAS-PAEFI-A2' => [
                'total_label' => 'CREAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Novos atendidos',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CREAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de novos casos inseridos no acompanhamento do PAEFI',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CREAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CREAS A.2 com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CREAS',
                'unidade_table_subtitle' => 'Distribuicao de novos casos do PAEFI por unidade ao longo do ano',
                'filter_unit_label' => 'CREAS',
                'filter_hint' => 'Clique em um CREAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CREAS',
                'message_active' => 'Leitura do RMA CREAS A.2 consolidada mes a mes.',
            ],
            'PPA-CREAS-MULHERES-F1' => [
                'total_label' => 'CREAS com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Mulheres inseridas',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA CREAS com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado de mulheres adultas inseridas no acompanhamento',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por CREAS',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura do RMA CREAS F.1 com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por CREAS',
                'unidade_table_subtitle' => 'Distribuicao de novos casos por unidade ao longo do ano',
                'filter_unit_label' => 'CREAS',
                'filter_hint' => 'Clique em um CREAS ou mês para filtrar.',
                'year_badge_label' => 'RMA CREAS',
                'message_active' => 'Leitura do RMA CREAS F.1 consolidada mes a mes.',
            ],
            default => [
                'total_label' => 'Unidades com leitura',
                'meta_label' => 'Meta anual',
                'acompanhadas_label' => 'Inseridos',
                'percentual_label' => '% Alcançado',
                'total_foot' => 'Unidades do RMA com leitura no ano de apuracao',
                'meta_foot' => 'Meta anual cadastrada para o indicador',
                'acompanhadas_foot' => 'Total acumulado do indicador no periodo',
                'percentual_foot' => 'Percentual atingido sobre a meta anual do indicador',
                'mensal_title' => 'Acompanhamento mês a mês',
                'unidade_title' => 'Acompanhamento por unidade',
                'mensal_table_title' => 'Serie mensal do indicador',
                'mensal_table_subtitle' => 'Leitura mensal com acumulado anual do periodo',
                'unidade_table_title' => 'Consolidado por unidade',
                'unidade_table_subtitle' => 'Distribuicao de registros por unidade ao longo do ano',
                'filter_unit_label' => 'Unidade',
                'filter_hint' => 'Clique em uma unidade ou mês para filtrar.',
                'year_badge_label' => 'RMA',
                'message_active' => 'Leitura mensal da base consolidada por unidade.',
            ],
        };

        if ($this->shouldShowPeriodProgress($indicator)) {
            $config['show_period_progress'] = true;
            $config['period_label'] = '% Período';
            $config['period_foot'] = 'Percentual do período';
        }

        $config['mensal_percent_label'] ??= '% Mês';
        $config['mensal_accum_label'] ??= '% Acum.';
        $config['unit_meta_label'] ??= $config['meta_label'] ?? 'Meta';
        $config['unit_progress_label'] ??= '% Meta';

        return $config;
    }

    /**
     * Monta configurações de página para um indicador específico.
     * 
     * Extrai o nome e objetivo do indicador para personalizar o título e descrição
     * da página no navegador e cabeçalho.
     * 
     * @param object $indicator Indicador com propriedades 'nome' e 'objetivo'
     * @return array Array com 'name' (título da página) e 'description' (subtítulo)
     */
    private function pageConfigForIndicator(object $indicator): array
    {
        $indicatorName = trim((string) ($indicator->nome ?? ''));
        $pageName = $indicatorName !== '' ? 'PPA - ' . $indicatorName : 'PPA';
        $subtitle = trim((string) ($indicator->objetivo ?? 'Plano Plurianual'));

        return [
            'name' => $pageName,
            'description' => $subtitle !== '' ? $subtitle : 'Plano Plurianual',
        ];
    }

    /**
     * Renderiza a página de catálogo com lista de todos os indicadores do PPA.
     * 
     * Decora os indicadores com métricas (meta, realizado, percentual, status)
     * e cria um resumo geral do catálogo.
     * 
     * Aplicável a TODOS os indicadores do PPA (página de listagem principal).
     * 
     * @param array $indicators Array de indicadores públicos do PPA
     * @param ?string $message Mensagem opcional a exibir no catálogo (ex: erro ao carregar indicador)
     * @return mixed Renderização da página HTML com catálogo decorado
     */
    private function renderCatalog(array $indicators, ?string $message = null)
    {
        $resultsByIndicator = (new PpaResultModel())->readLatestPublishedByIndicatorIds(
            array_map(static fn ($indicator): int => (int) ($indicator->id ?? 0), $indicators)
        );
        $catalogIndicators = $this->decorateCatalogIndicators($indicators, $resultsByIndicator);

        return $this->renderPage('ppa/catalog.html', [
            'indicators' => $catalogIndicators,
            'summary' => $this->buildCatalogSummary($catalogIndicators),
            'mensagem' => $message,
        ], [
            'name' => 'PPA - Indicadores',
            'description' => 'Selecione um indicador do Plano Plurianual',
        ]);
    }

    /**
     * Decora indicadores com métricas para exibição no catálogo.
     * 
     * Calcula para cada indicador:
     * - Meta (indice_futuro ou 10% da base para RMA)
     * - Realizado (indice_recente ou total de famílias acompanhadas)
     * - Percentual atingido
     * - Status do painel (Meta atingida, Em progresso, Em atenção, Sem leitura)
     * - Classe CSS para o status
     * 
     * Aplicável a TODOS os indicadores do PPA.
     * 
     * @param array $indicators Array de indicadores públicos
     * @return array Indicadores decorados com propriedades adicionais para exibição
     */
    private function decorateCatalogIndicators(array $indicators, array $resultsByIndicator = []): array
    {
        return array_map(function ($indicator) use ($resultsByIndicator) {
            $result = $resultsByIndicator[(int) ($indicator->id ?? 0)] ?? null;
            $metrics = $this->catalogIndicatorMetrics($indicator, $result);
            $meta = $metrics['meta'];
            $realizado = $metrics['realizado'];
            $percentual = $metrics['percentual'];

            $indicator->meta_valor = $meta;
            $indicator->realizado_valor = $realizado;
            $indicator->percentual_atingido = $percentual;
            $indicator->status_painel = $this->catalogIndicatorStatus($indicator);
            $indicator->status_painel_classe = $this->catalogIndicatorStatusClass($indicator->status_painel);
            $indicator->metricas_origem = $result !== null ? 'resultado_local' : 'cadastro_indicador';
            $indicator->metricas_atualizadas_em = $result->validated_at ?? $result->created_at ?? null;

            return $indicator;
        }, $indicators);
    }

    /**
     * Calcula as métricas de um indicador para exibição no catálogo.
     * 
     * Usa somente os valores persistidos no cadastro do indicador. O catálogo não
     * executa consultas externas; os dados em tempo real ficam restritos ao
     * dashboard detalhado de cada indicador.
     * 
     * Aplicável a TODOS os indicadores do PPA.
     * 
     * @param object $indicator Indicador com indice_futuro e indice_recente
     * @return array Array com 'meta', 'realizado', 'percentual' (ou null se sem dados)
     */
    private function catalogIndicatorMetrics(object $indicator, ?object $result = null): array
    {
        $meta = $this->firstNumericMetric([
            $result->valor_meta_quantitativa ?? null,
            $result->indice_futuro ?? null,
            $indicator->indice_futuro ?? null,
        ]);
        $realizado = $this->firstNumericMetric([
            $result->valor_resultado ?? null,
            $result->indice_recente ?? null,
            $indicator->indice_recente ?? null,
        ]);
        $percentual = ($meta !== null && $meta > 0 && $realizado !== null)
            ? ($realizado / $meta) * 100
            : null;

        return [
            'meta' => $meta,
            'realizado' => $realizado,
            'percentual' => $percentual,
        ];
    }

    private function firstNumericMetric(array $values): ?float
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private function catalogMetricsFromDashboardPayload(object $indicator, array $payload): ?array
    {
        $type = (string) ($payload['type'] ?? '');
        $data = is_array($payload['dados'] ?? null) ? $payload['dados'] : [];

        if (in_array($type, ['family_rma_progress', 'family_snapshot_rma_progress'], true)) {
            $meta = $this->firstNumericMetric([$data['meta_familias'] ?? null]);
            $realizado = $this->firstNumericMetric([$data['familias_acompanhadas_total'] ?? null]);
        } elseif ($type === 'monthly_unit_progress') {
            $meta = $this->firstNumericMetric([
                $data['meta_anual'] ?? null,
                $indicator->indice_futuro ?? null,
            ]);
            $realizado = $this->firstNumericMetric([$data['total_inseridos'] ?? null]);
        } else {
            return null;
        }

        if ($meta === null || $meta <= 0 || $realizado === null) {
            return null;
        }

        return [
            'ano_referencia' => $this->normalizeReferenceYear(
                $data['ano_apuracao'] ?? null,
                $data['referencia'] ?? null
            ),
            'competencia' => null,
            'valor_meta_quantitativa' => $meta,
            'valor_resultado' => $realizado,
            'percentual_atingido' => ($realizado / $meta) * 100,
            'unidade_medida' => (string) ($indicator->unidade_medida ?? ''),
            'data_referencia' => $data['referencia'] ?? null,
        ];
    }

    private function normalizeReferenceYear($year, $reference): int
    {
        $normalizedYear = (int) $year;

        if ($normalizedYear >= 2000 && $normalizedYear <= 2100) {
            return $normalizedYear;
        }

        if (is_string($reference) && preg_match('/^(20\d{2})/', $reference, $matches)) {
            return (int) $matches[1];
        }

        return (int) date('Y');
    }

    /**
     * Cria um resumo geral do catálogo de indicadores.
     * 
     * Calcula:
     * - Total de indicadores
     * - Indicadores ativos
     * - Quantidade com meta atingida
     * - Média de execução (percentual médio entre todos)
     * 
     * Aplicável a TODOS os indicadores do PPA (resumo geral).
     * 
     * @param array $indicators Array de indicadores já decorados com métricas
     * @return array Array com 'total_indicadores', 'indicadores_ativos', 'meta_atingida', 'media_execucao'
     */
    private function buildCatalogSummary(array $indicators): array
    {
        $total = count($indicators);
        $ativos = count(array_filter($indicators, fn ($indicator) => (int) ($indicator->ativo ?? 0) === 1));
        $metaAtingida = 0;
        $percentuais = [];

        foreach ($indicators as $indicator) {
            if (($indicator->percentual_atingido ?? null) !== null) {
                $percentuais[] = (float) $indicator->percentual_atingido;
            }

            if (($indicator->status_painel ?? '') === 'Meta atingida') {
                $metaAtingida++;
            }
        }

        return [
            'total_indicadores' => $total,
            'indicadores_ativos' => $ativos,
            'meta_atingida' => $metaAtingida,
            'media_execucao' => $percentuais !== [] ? array_sum($percentuais) / count($percentuais) : null,
        ];
    }

    /**
     * Determina o status de um indicador baseado em seu desempenho.
     * 
     * Retorna:
     * - "Sem leitura": Se faltam dados (meta inválida ou realizado nulo)
     * - "Meta atingida": Se percentual >= 100%
     * - "Em progresso": Se percentual >= 70%
     * - "Em atenção": Se percentual < 70%
     * 
     * Aplicável a TODOS os indicadores do PPA.
     * 
     * @param object $indicator Indicador com meta_valor, realizado_valor, percentual_atingido
     * @return string Status do indicador para exibição no catálogo
     */
    private function catalogIndicatorStatus(object $indicator): string
    {
        $meta = $indicator->meta_valor ?? null;
        $realizado = $indicator->realizado_valor ?? null;
        $percentual = $indicator->percentual_atingido ?? null;

        if ($meta === null || $meta <= 0 || $realizado === null) {
            return 'Sem leitura';
        }

        if ($percentual >= 100) {
            return 'Meta atingida';
        }

        if ($percentual >= 70) {
            return 'Em progresso';
        }

        return 'Em atenção';
    }

    /**
     * Retorna a classe CSS (Bootstrap) correspondente ao status de um indicador.
     * 
     * Mapeia status para cores/estilos:
     * - "Meta atingida" => 'success' (verde)
     * - "Em progresso" => 'warning' (amarelo)
     * - "Em atenção" => 'danger' (vermelho)
     * - Outros => 'secondary' (cinza)
     * 
     * @param string $status Status do indicador (resultado de catalogIndicatorStatus)
     * @return string Classe CSS Bootstrap para estilização
     */
    private function catalogIndicatorStatusClass(string $status): string
    {
        return match ($status) {
            'Meta atingida' => 'success',
            'Em progresso' => 'warning',
            'Em atenção' => 'danger',
            default => 'secondary',
        };
    }

    private function shouldShowPeriodProgress(object $indicator): bool
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        return in_array($code, [
            'PPA-CRAS-ATUALIZACAO-C3',
            'PPA-ACOMPANHAR-BPC-PAIF',
            'PPA-ACOMPANHAR-FAMILIAS-MEIO-SM-PAIF',
            'PPA-ACOMPANHAR-FAMILIAS-PBF',
            'PPA-CREAS-PAEFI-A2',
            'PPA-CRAS-ATENDIMENTOS-C1',
        ], true);
    }

    private function buildPeriodProgressPercent(int $monthsCount): float
    {
        $monthsCount = max(0, min(12, $monthsCount));

        return ($monthsCount / 12) * 100;
    }

    private function isCadUpdateRmaIndicator(object $indicator): bool
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        return $code === 'PPA-CRAS-ATUALIZACAO-C3';
    }

    /**
     * Converte uma data em formato legível "Mês/Ano" para exibição.
     * 
     * Exemplo: "2024-06" ou "2024-06-15" => "Jun/2024"
     * 
     * Usado nos gráficos e tabelas RMA para exibir meses de referência.
     * 
     * @param string $date Data em qualquer formato reconhecido pelo strtotime
     * @return string Formato "Mês/Ano" com mês abreviado (Jan, Fev, etc)
     */
    private function monthLabel(string $date): string
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

        $month = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);

        return ($months[$month] ?? date('m', $timestamp)) . '/' . $year;
    }

    /**
     * Limpa um rótulo/label removendo espaços e caracteres especiais extras.
     * 
     * Remove espaços nas extremidades e caracteres como aspas e apóstrofos.
     * Retorna um fallback se o valor ficar vazio após limpeza.
     * 
     * @param string $value Valor a limpar
     * @param string $fallback Valor a retornar se ficar vazio (padrão: 'Nao informado')
     * @return string Valor limpo ou fallback
     */
    private function cleanLabel(string $value, string $fallback = 'Nao informado'): string
    {
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B'\"");

        return $value !== '' ? $value : $fallback;
    }

    /**
     * Normaliza nomes de CRAS com limpeza e aplicação de aliases.
     * 
     * Processa:
     * 1. Limpeza do rótulo
     * 2. Conversão para maiúsculas
     * 3. Aplicação de aliases para unificar nomes duplicados ou alternativos
     * 
     * Exemplos de aliases:
     * - 'CRAS MARIANA 2' => 'CRAS MARIANA'
     * - 'UNIDADE PERNAMBUCANO' => 'CRAS PARQUE SANTA RITA'
     * - 'UNIDADE SAO FRANCISCO XAVIER' => 'CRAS ALTO DA PONTE'
     * 
     * Usado em toda agregação de dados por CRAS.
     * 
     * @param string $value Nome do CRAS (pode vir com variações ou erros)
     * @return string Nome normalizado
     */
    private function normalizeCrasLabel(string $value): string
    {
        $value = $this->cleanLabel($value, 'Não informado');
        $upper = mb_strtoupper($value);

        $aliases = [
            'CRAS MARIANA 2' => 'CRAS MARIANA',
            'UNIDADE PERNAMBUCANO' => 'CRAS PARQUE SANTA RITA',
            'UNIDADE SAO FRANCISCO XAVIER' => 'CRAS ALTO DA PONTE',

        ];

        return $aliases[$upper] ?? $value;
    }

    private function normalizeUnitLabel(string $value): string
    {
        return $this->cleanLabel($value, 'Nao informado');
    }

    private function normalizeIndicatorUnitLabel(object $indicator, string $value): string
    {
        $code = trim((string) ($indicator->codigo_indicador ?? $indicator->codigo ?? ''));

        if (str_starts_with($code, 'PPA-CRAS-')) {
            return $this->normalizeCrasLabel($value);
        }

        return $this->normalizeUnitLabel($value);
    }

    /**
     * Extrai e normaliza filtros interativos da URL (query string).
     * 
     * Lê parâmetros GET:
     * - 'cras': CRAS para filtro de dados
     * - 'regiao': Região para filtro de dados
     * - 'mes_referencia': Mês (YYYY-MM) para filtro em dashboards RMA
     * 
     * Normaliza e valida cada filtro antes de retornar.
     * 
     * Usado quando dashboard é chamado via AJAX com filtros do usuário.
     * 
     * @return array Array com chaves cras, regiao, mes_referencia (null se não fornecido/inválido)
     */
    private function interactiveFiltersFromRequest(): array
    {
        return [
            'cras' => $this->normalizeNullableFilter($_GET['cras'] ?? null),
            'regiao' => $this->normalizeNullableFilter($_GET['regiao'] ?? null),
            'unidade' => $this->normalizeNullableFilter($_GET['unidade'] ?? null),
            'mes_referencia' => $this->normalizeDateFilter($_GET['mes_referencia'] ?? null),
        ];
    }

    /**
     * Normaliza um filtro que pode ser nulo.
     * 
     * Limpa espaços e caracteres especiais. Retorna null se vazio após limpeza.
     * 
     * Usado para validação de filtros CRAS e Região.
     * 
     * @param ?string $value Valor do filtro (pode ser null ou string)
     * @return ?string Valor normalizado ou null
     */
    private function normalizeNullableFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $this->cleanLabel($value, '') : null;
    }

    /**
     * Normaliza e valida um filtro de data.
     * 
     * Aceita apenas datas em formato YYYY-MM-DD.
     * Valida estrutura da data antes de retornar.
     * 
     * Usado para validar filtro de mês_referencia em dashboards RMA.
     * 
     * @param ?string $value Data em formato YYYY-MM-DD (pode ser null)
     * @return ?string Data validada ou null se inválida/vazia
     */
    private function normalizeDateFilter(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $value);

        return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * Retorna uma resposta JSON codificada com suporte a caracteres acentuados.
     * 
     * Define header Content-Type e codifica o array em JSON sem escapar caracteres unicode.
     * Encerra a execução após enviar a resposta.
     * 
     * Usado para retornar dados do dashboard via AJAX (dashboardData).
     * 
     * @param array $payload Array de dados a codificar e enviar como JSON
     * @return void Encerra a execução
     */
    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
