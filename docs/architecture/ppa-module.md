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

## Próximo limite de refatoração

A próxima extração deve tratar a execução e resolução das consultas vinculadas,
sem mover simultaneamente os builders de payload. Isso mantém cada commit
pequeno e reversível.
