# Módulo PPA

Atualizado em 2026-07-28. O ponto de retomada está em
`docs/continuity.md`.

## Catálogo

Fluxo após a primeira extração incremental:

```text
GET /ppa
→ PpaController::index()
→ PpaIndicatorModel::readPublicCatalog()
→ PpaController::renderCatalog()
→ PpaCatalogService::build()
→ PpaResultModel::readLatestPublishedByIndicatorIds()
→ ppa/catalog.html
```

`PpaController` permanece responsável pela rota e renderização. A composição
das métricas, status e resumo pertence a `PpaCatalogService`.

O serviço:

- carrega todos os resultados locais em uma consulta em lote;
- não executa consultas externas;
- usa o cadastro do indicador como fallback;
- preserva a fórmula `realizado / meta × 100`;
- exclui indicadores de visão geral da média de execução;
- entrega ao template os mesmos campos utilizados anteriormente.

## Compatibilidade

Esta extração não altera:

- rota `/ppa`;
- template Twig;
- nomes dos campos enviados à view;
- fórmulas e faixas de status;
- fallback sem resultado;
- sincronização CLI;
- dashboards detalhados e endpoints AJAX.

O indicador `PPA-ERRADICAR-POBREZA` continua classificado como visão geral.

## Consultas vinculadas

`PpaLinkedQueryService` resolve a consulta e a fonte cadastradas, executa o SQL
com o limite solicitado e mantém o mesmo retorno consumido pelos dashboards.
Os builders de payload ficam em serviços dedicados. O controller apenas
orquestra a seleção dos vínculos, a execução das consultas e a composição da
resposta HTTP.

## Resolução dos dashboards

`PpaDashboardResolver` classifica o tipo de painel pelos campos de resultado e
localiza os vínculos usados por cada dashboard. A ordem de precedência permanece:

1. fotografia da base com atualização e série mensal;
2. progresso de famílias com base e acompanhamento;
3. progresso mensal por unidade;
4. consulta simples como fallback.

## Payload de consulta simples

`PpaSingleQueryPayloadBuilder` agrega famílias e pessoas por CRAS, região e
bairro, preservando aliases, filtros, ordenação e o formato entregue ao
dashboard `single_query`. A implementação duplicada foi removida do controller;
os payloads dos painéis RMA continuam nele.

## Payload mensal por unidade

`PpaMonthlyUnitPayloadBuilder` consolida a série mensal, unidades, acumulados,
participação, meta e filtros do dashboard `monthly_unit_progress`. A
implementação duplicada e o filtro exclusivo desse payload foram removidos do
controller após a validação de equivalência.

## Payload de progresso familiar

`PpaFamilyRmaPayloadBuilder` consolida base territorial, meta, acompanhamento
mensal e progresso por CRAS para os indicadores familiares regulares. O builder
regular duplicado foi removido; os builders especializados de atualização
cadastral permanecem no controller nesta etapa.

## Payloads especializados

O payload de fotografia familiar já foi extraído para
`PpaFamilySnapshotRmaPayloadBuilder`, integrado ao controller e teve sua
implementação legada removida.

O payload especializado restante foi extraído para
`PpaCadUpdateRmaPayloadBuilder`, integrado ao controller e teve a implementação
legada removida.

### Contrato atual da atualização cadastral

Caracterizado em 2026-07-24 antes da criação do teste.

Esse fluxo é selecionado explicitamente por `isCadUpdateRmaIndicator()` para o
indicador `PPA-CRAS-ATUALIZACAO-C3`, antes da resolução genérica do tipo de
dashboard. Ele usa dois vínculos:

- `base_familias_*`;
- `serie_mensal_unidade_*` ou `familias_acompanhadas_*`.

A consulta de base usa limite 500 e a série RMA usa limite 5000.

Entradas do payload:

- `baseRows`: `cras`, `regiao`, `total_familias_pbf`,
  `ref_cad_referencia` ou `ref_cad`;
- `rmaRows`: `mes_referencia`, `cras`, `unidade` ou `nome_unidade`,
  `total_inseridos` ou `total_familias_acompanhadas`;
- `indicator`: `indice_futuro`, interpretado como percentual e com fallback
  de 85%;
- `filters`: `cras` e `mes_referencia`.

Regras de filtro:

- `cras` filtra base e RMA depois das consultas externas;
- `mes_referencia` filtra somente o RMA;
- valores vazios de filtro viram `null`;
- aliases territoriais seguem `normalizeCrasLabel()`.

Cálculos:

- `baseTotal`: soma de `total_familias_pbf` da base filtrada;
- `metaTotal = baseTotal × indice_futuro / 100`;
- realizado total: soma acumulada da série RMA filtrada;
- percentual total: `realizado acumulado / metaTotal × 100`;
- série mensal: total, acumulado e percentuais mensal e acumulado sobre a meta;
- consolidado por CRAS: base, meta proporcional, soma RMA e percentual;
- percentual do período: meses distintos com leitura, limitado a 12, dividido
  por 12.

Diferenças importantes em relação à fotografia familiar:

- utiliza somente duas fontes, não três;
- o realizado vem da série RMA, não de uma fotografia separada de famílias
  atualizadas;
- a base aceita apenas `total_familias_pbf`, sem fallback para
  `total_familias`;
- a referência vem apenas da base;
- o ano vem apenas da primeira linha RMA válida;
- CRAS presente somente no RMA é incluído com base zero.

Contrato de saída:

- `total_geral`;
- `meta_familias`;
- `familias_acompanhadas_total`;
- `percentual_alcancado_total`;
- `percentual_periodo`;
- `meses_periodo`;
- `referencia`;
- `ano_apuracao`;
- `grafico_mensal`;
- `grafico_cras`;
- `grafico_meta`, atualmente vazio;
- `tabela_mensal`;
- `tabela_cras`;
- `filtros_ativos`.

Ordenação:

- meses em ordem crescente de `mes_referencia`;
- tabela e gráfico de CRAS pelo realizado em ordem decrescente, usando o nome
  como desempate.

O teste `PpaCadUpdateRmaLegacyPayloadTest.php` cobre aliases, fallbacks das
colunas RMA, filtros, mês vazio, CRAS somente no RMA, valores zero, meta padrão,
referência, ano, ordenação e contrato completo. Ele executa o método privado
legado por reflexão e registra 28 assertions legadas.

`PpaCadUpdateRmaPayloadBuilder` foi criado em 2026-07-24 sem alterar o
controller. Quatro comparações adicionais de payload completo confirmam
equivalência com o legado, totalizando 32 assertions no teste de transição e
145 assertions na suíte.

Depois da integração, o teste foi renomeado para
`PpaCadUpdateRmaPayloadBuilderTest.php` e passou a validar diretamente o
serviço com 28 assertions. `buildCadUpdateRmaPayload()`, `filterBaseRows()` e
`filterRmaRows()` foram removidos do controller. A suíte final da etapa passou
com 141 assertions.

### Contrato atual da fotografia familiar

Caracterizado em 2026-07-24 antes da extração.

O tipo `family_snapshot_rma_progress` é selecionado quando existem três vínculos
ativos:

- `base_familias_*`;
- `familias_atualizadas_*`;
- `serie_mensal_unidade_*`.

O indicador conhecido que exercita o fluxo é
`PPA-CRAS-ATUALIZACAO-C3`. A resposta executa as consultas de base e atualizadas
com limite 500 e a série RMA com limite 5000.

Entradas do builder:

- `baseRows`: `cras`, `regiao`, `total_familias_pbf` ou `total_familias`,
  `ref_cad_referencia` ou `ref_cad`;
- `updatedRows`: `cras`, `total_familias_atualizadas` ou
  `total_familias_acompanhadas`, `ref_cad_referencia` ou `mes_referencia`;
- `rmaRows`: `cras`, `unidade` ou `nome_unidade`, `mes_referencia`,
  `total_inseridos` ou `total_familias_acompanhadas`;
- `indicator`: `indice_futuro`, interpretado como percentual e com fallback
  de 85%;
- `filters`: `cras` e `mes_referencia`.

Regras de filtro:

- `cras` filtra as três fontes depois da consulta externa;
- `mes_referencia` filtra somente a série RMA;
- valores vazios são normalizados para `null`;
- aliases territoriais seguem `normalizeCrasLabel()`.

Cálculos preservados:

- `baseTotal`: soma das famílias da base filtrada;
- `updatedTotal`: soma das famílias atualizadas filtradas;
- `metaFamilias = baseTotal × indice_futuro / 100`;
- percentual total: `updatedTotal / metaFamilias × 100`;
- série mensal: soma do RMA por mês, com acumulado e percentuais mensal e
  acumulado sobre a meta;
- consolidado CRAS: base, meta proporcional, atualizadas e percentual por CRAS;
- percentual do período: quantidade de meses com leitura limitada a 12,
  dividida por 12.

Contrato de saída:

- `total_geral`;
- `meta_familias`;
- `familias_acompanhadas_total`;
- `percentual_alcancado_total`;
- `percentual_periodo`;
- `meses_periodo`;
- `referencia`;
- `ano_apuracao`;
- `grafico_mensal`;
- `grafico_cras`;
- `grafico_meta`, atualmente vazio;
- `tabela_mensal`;
- `tabela_cras`;
- `filtros_ativos` com `cras` e `mes_referencia`.

Ordenação:

- meses em ordem crescente da chave `mes_referencia`;
- tabela de CRAS por famílias atualizadas em ordem decrescente e nome como
  desempate;
- gráfico de CRAS herda a ordem da tabela.

Casos cobertos pelo teste de equivalência legado:

- aliases de CRAS;
- fallbacks de nomes de colunas;
- filtro por CRAS nas três fontes;
- filtro por mês somente no RMA;
- mês ausente descartado da série;
- CRAS presente apenas nas atualizações;
- entradas vazias e valores zero;
- meta default de 85%;
- referência e ano com seus fallbacks;
- ordenação mensal e territorial;
- contrato completo das chaves retornadas.

O teste de transição executou o método privado legado por reflexão antes da
integração. Depois da remoção, ele foi renomeado para
`PpaFamilySnapshotRmaPayloadBuilderTest.php` e passou a validar diretamente o
serviço com 26 assertions.

`PpaFamilySnapshotRmaPayloadBuilder` foi criado em 2026-07-24 sem alterar o
controller. Ele preserva entradas, fallbacks, filtros, aliases, tipos numéricos,
cálculos e ordenação do método legado. Em seguida, foi integrado ao controller
e a duplicação foi removida. A suíte completa passou com 113 assertions.

## Testes de arquitetura e payload

Os testes atuais cobrem runtime externo, métricas do catálogo, resolução de
dashboard, consultas vinculadas, sincronização e armazenamento do cache e os
cinco builders extraídos. O catálogo público possui um smoke test de
renderização normal e erro controlado. Em 2026-07-28, treze arquivos passaram
com 191 assertions.

## Responsabilidades restantes no controller

Revisão concluída em 2026-07-24, sem extração automática de código.

Devem permanecer no `PpaController`:

- ações HTTP (`index()`, `show()` e `dashboardData()`), status HTTP e resposta
  JSON;
- leitura e normalização dos filtros da query string;
- seleção do dashboard e encaminhamento para renderização;
- configuração básica da página e chamada dos templates;
- coordenação entre vínculos, consultas e builders já extraídos.

Podem permanecer por enquanto, mas são candidatos futuros:

- `buildCatalogSyncPreview()` e a conversão do payload em métricas de catálogo,
  pois formam um caso de uso de sincronização reutilizado fora da resposta HTTP;
- `familyRmaUiConfig()` e `monthlyUnitUiConfig()`, pois são tabelas extensas de
  configuração visual e podem migrar para configuração dedicada;
- os quatro métodos `build*Response()`, caso medições ou novos consumidores
  mostrem benefício em criar um serviço de orquestração.

Foram identificados como candidatos a remoção, após confirmação por teste:

- `buildPeriodProgressPercent()`;
- `monthLabel()`;
- `normalizeIndicatorUnitLabel()` e seus helpers territoriais exclusivos.

Esses métodos não possuem chamadas no controller após a extração dos builders.
A remoção não faz parte desta revisão para manter a etapa apenas documental e
evitar combinar limpeza estrutural com a campanha de medição.

Após a medição, foi decidido adotar cache local em arquivo para as linhas
agregadas das consultas vinculadas, com sincronização fora da requisição
pública. O desenho está em `docs/performance/dashboard-file-cache.md`. Redis
fica fora do escopo atual.

`PpaQueryFileCache` implementa o armazenamento isolado, com identidade por hash,
validação, trava e escrita atômica. `PpaLinkedQueryService` consulta uma entrada
compatível antes de recorrer à fonte externa.

`PpaQueryCacheSynchronizer` e `bin/ppa-dashboard-cache.php` permitem preencher
manualmente as entradas de um indicador. O comando usa somente vínculos ativos,
não grava no banco e força consulta externa durante a renovação.

`PpaQueryCacheBatchSynchronizer` adiciona `--all`, processa indicadores públicos
sequencialmente, preserva resultados individuais e sinaliza falha parcial por
exit code.

`PpaLinkedQueryService` usa uma entrada compatível antes do executor externo.
Miss, corrupção ou mudança de SQL/limite preservam o fallback original. A
requisição pública não possui caminho de escrita ou renovação.

## Limites da próxima etapa

- não introduzir Redis;
- não alterar fórmulas;
- não alterar nomes do payload;
- não alterar rotas, templates ou comportamento AJAX;
- não mover filtros para SQL sem medição e estratégia de parâmetros;
- priorizar smoke tests HTTP antes de nova extração estrutural;
- atualizar documentação local e Notion em cada incremento.
