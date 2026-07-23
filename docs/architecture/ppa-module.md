# Módulo PPA

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
dashboard `single_query`. Os payloads dos painéis RMA continuam no controller.
