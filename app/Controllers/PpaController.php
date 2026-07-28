<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PpaIndicatorModel;
use App\Models\PpaIndicatorQueryModel;
use App\Services\PpaCadUpdateRmaPayloadBuilder;
use App\Services\PpaCatalogService;
use App\Services\PpaDashboardResolver;
use App\Services\PpaFamilyRmaPayloadBuilder;
use App\Services\PpaFamilySnapshotRmaPayloadBuilder;
use App\Services\PpaLinkedQueryService;
use App\Services\PpaMonthlyUnitPayloadBuilder;
use App\Services\PpaSingleQueryPayloadBuilder;

class PpaController extends BaseController
{
    private ?PpaLinkedQueryService $linkedQueryService = null;
    private ?PpaIndicatorModel $indicatorModel;
    private ?PpaCatalogService $catalogService;

    public function __construct(
        ?PpaIndicatorModel $indicatorModel = null,
        ?PpaCatalogService $catalogService = null
    ) {
        parent::__construct();
        $this->indicatorModel = $indicatorModel;
        $this->catalogService = $catalogService;
    }

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
        $indicators = $this->indicatorModel()->readPublicCatalog();

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
        $indicator = $this->indicatorModel()->readPublicBySlug($slug);

        if (is_string($indicator)) {
            $indicators = $this->indicatorModel()->readPublicCatalog();

            return $this->renderCatalog(
                is_array($indicators) ? $indicators : [],
                'Indicador do PPA nao encontrado.'
            );
        }

        $links = (new PpaIndicatorQueryModel())->readActiveLinksByIndicatorId((int) $indicator->id);

        if (is_string($links) || $links === []) {
            return $this->renderSingleQueryDashboard([
                'dados' => PpaSingleQueryPayloadBuilder::empty(),
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

        return match (PpaDashboardResolver::dashboardType($links)) {
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
        $indicator = $this->indicatorModel()->readPublicBySlug($slug);

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

        $type = PpaDashboardResolver::dashboardType($links);

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
        $indicator = $this->indicatorModel()->readPublicBySlug($slug);

        if (is_string($indicator)) {
            return [
                'success' => false,
                'error' => $indicator,
            ];
        }

        if ($this->isCatalogOverviewIndicator($indicator)) {
            return [
                'success' => false,
                'indicator_id' => (int) $indicator->id,
                'indicator_code' => (string) ($indicator->codigo_indicador ?? ''),
                'error' => 'Este indicador e uma visao geral e nao possui meta, realizado ou percentual para sincronizar.',
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
            $type = PpaDashboardResolver::dashboardType($links);
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
                'dados' => PpaSingleQueryPayloadBuilder::empty($filters),
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
                'dados' => PpaSingleQueryPayloadBuilder::empty($filters),
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
            'dados' => PpaSingleQueryPayloadBuilder::build($result['rows'] ?? [], $filters),
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
        $baseKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['base_familias_']);
        $rmaKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['familias_acompanhadas_', 'familias_atualizadas_']);
        $baseLink = $baseKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $baseKey) : null;
        $rmaLink = $rmaKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $rmaLink === null) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
                'filters' => $filters,
                'error' => $rmaResult,
                'ui' => $ui,
                'meta' => [
                    'base_link' => $baseLink,
                    'rma_link' => $rmaLink,
                ],
            ];
        }

        $payload = PpaFamilyRmaPayloadBuilder::build(
            $baseResult['rows'] ?? [],
            $rmaResult['rows'] ?? [],
            $indicator,
            $filters
        );

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
        $baseKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['base_familias_']);
        $updatedKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['familias_atualizadas_']);
        $rmaKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['serie_mensal_unidade_']);
        $baseLink = $baseKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $baseKey) : null;
        $updatedLink = $updatedKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $updatedKey) : null;
        $rmaLink = $rmaKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $updatedLink === null || $rmaLink === null) {
            return [
                'type' => 'family_snapshot_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
                    'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
            'dados' => PpaFamilySnapshotRmaPayloadBuilder::build(
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
        $baseKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['base_familias_']);
        $rmaKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['serie_mensal_unidade_', 'familias_acompanhadas_']);
        $baseLink = $baseKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $baseKey) : null;
        $rmaLink = $rmaKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $rmaKey) : null;
        $ui = $this->familyRmaUiConfig($indicator);

        if ($baseLink === null || $rmaLink === null) {
            return [
                'type' => 'family_rma_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
                'dados' => PpaFamilyRmaPayloadBuilder::empty(),
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
            'dados' => PpaCadUpdateRmaPayloadBuilder::build(
                $baseResult['rows'] ?? [],
                $rmaResult['rows'] ?? [],
                $indicator,
                $filters
            ),
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
        $seriesKey = PpaDashboardResolver::findLinkKeyByPrefixes($links, ['serie_mensal_unidade_']);
        $seriesLink = $seriesKey !== null ? PpaDashboardResolver::findLinkByResultKey($links, $seriesKey) : null;
        $ui = $this->monthlyUnitUiConfig($indicator);

        if ($seriesLink === null) {
            return [
                'type' => 'monthly_unit_progress',
                'status' => 'Em Preparacao',
                'mensagem' => 'As consultas necessarias para este indicador ainda nao foram vinculadas corretamente.',
                'dados' => PpaMonthlyUnitPayloadBuilder::empty(),
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
                'dados' => PpaMonthlyUnitPayloadBuilder::empty(),
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
            'dados' => PpaMonthlyUnitPayloadBuilder::build($result['rows'] ?? [], $indicator, $filters),
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
        $service = $this->linkedQueryService ??= new PpaLinkedQueryService();

        return $service->run($link, $limit);
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
        $catalog = $this->catalogService()->build($indicators);

        return $this->renderPage('ppa/catalog.html', [
            'indicators' => $catalog['indicators'],
            'summary' => $catalog['summary'],
            'mensagem' => $message,
        ], [
            'name' => 'PPA - Indicadores',
            'description' => 'Selecione um indicador do Plano Plurianual',
        ]);
    }

    private function indicatorModel(): PpaIndicatorModel
    {
        return $this->indicatorModel ??= new PpaIndicatorModel();
    }

    private function catalogService(): PpaCatalogService
    {
        return $this->catalogService ??= new PpaCatalogService();
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

    private function isCatalogOverviewIndicator(object $indicator): bool
    {
        return PpaCatalogService::isOverviewIndicator($indicator);
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
