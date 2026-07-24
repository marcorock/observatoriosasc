# Módulo PPA

Atualizado em 2026-07-24. O ponto de retomada está em
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
Os builders de payload permanecem no controller para manter cada etapa pequena
e reversível.

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

## Payloads especializados ainda no controller

Os próximos candidatos de extração são:

- `buildFamilySnapshotRmaPayload()`, usado pela fotografia da base com
  atualização e série mensal;
- `buildCadUpdateRmaPayload()`, usado pelo fluxo especializado de atualização
  cadastral.

A ordem aprovada é extrair primeiro a fotografia familiar, criar teste de
equivalência e somente então remover o método legado. O fluxo de atualização
cadastral será tratado em incremento posterior.

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

Casos que o teste de equivalência da próxima etapa deve cobrir:

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

## Testes de arquitetura e payload

Os testes atuais cobrem runtime externo, métricas do catálogo, resolução de
dashboard, consultas vinculadas e os três builders extraídos. Em 2026-07-24,
sete arquivos passaram com 87 assertions.

## Limites da etapa atual

- não introduzir cache ou Redis;
- não alterar fórmulas;
- não alterar nomes do payload;
- não alterar rotas, templates ou comportamento AJAX;
- não mover filtros para SQL sem medição e estratégia de parâmetros;
- atualizar documentação local e Notion em cada extração.
