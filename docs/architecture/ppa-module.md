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
