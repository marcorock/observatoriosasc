# Continuidade do Projeto

Atualizado em 2026-07-24.

Este é o ponto de entrada obrigatório para retomar o desenvolvimento. O estado
executável deve ser confirmado pelo código e pelo Git; os demais documentos
detalham arquitetura, operação e histórico.

## Contexto de trabalho

- Repositório: `marcorock/observatoriosasc`
- Branch ativa: `update-repository`
- Último checkpoint publicado antes desta revisão: `f2cf6e8`
- Situação: sincronizada com `origin/update-repository` e 23 commits à frente
  de `main` na última verificação
- Pull request da branch: inexistente na última verificação
- Issues abertas: nenhuma na última verificação
- Defaults de conexão: mantidos como `localhost` e senha vazia no código; cada
  ambiente deve configurar valores diferentes em seu `.env`

Antes de continuar, executar:

```bash
git status --short --branch
git log --oneline --decorate -12
for test_file in tests/*Test.php; do php "$test_file"; done
```

## Objetivo atual

Melhorar a performance e a manutenibilidade do módulo PPA de forma incremental,
sem alterar rotas, payloads, templates, cálculos ou regras de negócio.

## Estado funcional do PPA

- O catálogo `/ppa` usa métricas locais e não executa consultas externas.
- O catálogo carrega os resultados em lote a partir de `ppa_resultados`.
- O runtime externo aplica o limite no SQL antes de carregar os registros.
- O monitoramento de consultas externas é opt-in.
- Existe sincronização CLI com prévia e persistência explícita.
- Nove indicadores quantitativos públicos possuem resultados locais validados.
- `PPA-ERRADICAR-POBREZA` é uma visão geral e não participa dos cálculos de
  alcance de meta.
- O carregamento HTML inicial não dispara uma segunda consulta AJAX automática.

## Extrações concluídas

- `PpaCatalogService`
- `PpaLinkedQueryService`
- `PpaDashboardResolver`
- `PpaSingleQueryPayloadBuilder`
- `PpaMonthlyUnitPayloadBuilder`
- `PpaFamilyRmaPayloadBuilder`
- `PpaFamilySnapshotRmaPayloadBuilder`
- `PpaCadUpdateRmaPayloadBuilder`

O controller mantém a orquestração HTTP, filtros de entrada, renderização e
configurações visuais. A revisão da etapa 11 não encontrou benefício suficiente
para uma nova extração antes da campanha de medição.

## Testes

Na última verificação, os onze arquivos de teste passaram:

| Teste | Assertions |
| --- | ---: |
| `ExternalDatabaseRuntimeTest.php` | 9 |
| `PpaCatalogMetricsTest.php` | 26 |
| `PpaCadUpdateRmaPayloadBuilderTest.php` | 28 |
| `PpaDashboardResolverTest.php` | 10 |
| `PpaFamilyRmaPayloadBuilderTest.php` | 11 |
| `PpaFamilySnapshotRmaPayloadBuilderTest.php` | 26 |
| `PpaLinkedQueryServiceTest.php` | 8 |
| `PpaMonthlyUnitPayloadBuilderTest.php` | 11 |
| `PpaQueryCacheSynchronizerTest.php` | 10 |
| `PpaQueryFileCacheTest.php` | 16 |
| `PpaSingleQueryPayloadBuilderTest.php` | 12 |
| **Total** | **167** |

## Próxima sequência aprovada

O plano possui 14 etapas mensuráveis, organizadas em cinco fases. O checklist
interativo está no Notion:

`https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`

Progresso atual: 13 de 14 etapas, ou 92,9%.

1. Concluído — decidir os defaults de conexão em `DataConect.php`.
2. Concluído — consolidar o checkpoint documental no Git.
3. Concluído — caracterizar o payload de fotografia familiar.
4. Concluído — criar seu teste de equivalência.
5. Concluído — criar `PpaFamilySnapshotRmaPayloadBuilder`.
6. Concluído — integrar o builder e remover o legado de fotografia.
7. Concluído — caracterizar o payload de atualização cadastral.
8. Concluído — criar seu teste de equivalência.
9. Concluído — criar `PpaCadUpdateRmaPayloadBuilder`.
10. Concluído — integrar o builder e remover o legado cadastral.
11. Concluído — revisar as responsabilidades restantes no `PpaController`.
12. Concluído — executar nova campanha de medição.
13. Concluído — produzir decisão técnica baseada nas métricas.
14. Homologar e preparar a entrega no GitHub.

Cada etapa vale um ponto. O percentual é calculado por
`etapas concluídas / 14 × 100`.

## Decisão sobre a conexão local

Não existe configuração Docker Compose versionada no repositório. O
`.env.example`, o README e o bootstrap CLI usam `localhost` e senha vazia como
defaults de desenvolvimento. Por isso, `DataConect.php` foi mantido exatamente
como estava no Git. Ambientes Docker ou remotos devem declarar host e senha no
`.env`, que não é versionado.

Em 2026-07-24, `core/app.php` foi corrigido para carregar explicitamente as
variáveis `DB_*` no caso padrão. Antes, o `default` atribuía `NULL` e fazia a
conexão ignorar o `.env`. A correção foi validada por sintaxe PHP e pelos sete
testes, e publicada no commit `e17ee07`.

## Contrato caracterizado para a próxima extração

O fluxo `family_snapshot_rma_progress` usado por
`PPA-CRAS-ATUALIZACAO-C3` depende de base familiar, fotografia de famílias
atualizadas e série RMA. Entradas, filtros, aliases, cálculos, ordenação e
campos de saída estão registrados em `docs/architecture/ppa-module.md`.

O teste foi criado em `tests/PpaFamilySnapshotRmaLegacyPayloadTest.php` e chama
o método privado legado por reflexão, sem construir models ou abrir conexões.
Ele possui 26 assertions e fixa o contrato que o novo builder deverá reproduzir.

`PpaFamilySnapshotRmaPayloadBuilder` foi inicialmente criado sem integração ao
controller. Quatro comparações de payload completo confirmaram equivalência com
o legado, inclusive a preservação de `0.0` para percentual de período sem meses.

O controller passou a usar o builder e o método legado foi removido. O teste foi
renomeado para `PpaFamilySnapshotRmaPayloadBuilderTest.php` e agora valida
diretamente o serviço com 26 assertions.

O contrato foi caracterizado em `docs/architecture/ppa-module.md`. O fluxo é
forçado para `PPA-CRAS-ATUALIZACAO-C3`, usa base CECAD e série RMA, e calcula o
realizado pelo acumulado do RMA. Ele não deve ser confundido com a fotografia
familiar de três fontes.

O teste `PpaCadUpdateRmaLegacyPayloadTest.php` fixa o comportamento legado com
28 assertions, sem abrir conexões ou instanciar models.

`PpaCadUpdateRmaPayloadBuilder` foi criado sem integração ao controller. Quatro
comparações de payload completo confirmam equivalência com o legado.

O controller passou a usar o builder cadastral. O método legado e os helpers de
filtro que ficaram sem consumidores foram removidos. O teste foi renomeado para
`PpaCadUpdateRmaPayloadBuilderTest.php` e valida diretamente o serviço com 28
assertions.

As responsabilidades restantes foram classificadas. Ações HTTP, filtros,
seleção de dashboard, renderização e coordenação permanecem no controller.
Sincronização de catálogo, configuração visual e orquestração das respostas são
candidatos futuros, condicionados a benefício mensurável. Helpers sem chamadas
foram registrados para uma limpeza posterior isolada.

A campanha pós-refatoração registrou três amostras por fluxo. As medianas foram
740,2 ms para consulta simples, 71,6 ms para mensal por unidade, 618,7 ms para
progresso familiar, 4.041,3 ms para atualização cadastral ativa e 12.997,9 ms
para a simulação somente leitura da fotografia familiar. O detalhamento,
limitações e métricas por consulta estão em `docs/performance/baseline.md`.

Decisão técnica: implementar em incremento próprio um cache local em arquivo
para as linhas agregadas das consultas vinculadas. A atualização será manual
por CLI e poderá ser programada posteriormente; requisições públicas apenas
leem o cache e mantêm fallback para a consulta externa. Redis não será adotado
neste momento.

O desenho, riscos, invalidação e ordem de implementação estão em
`docs/performance/dashboard-file-cache.md`. A revisão do SQL CECAD continua
recomendada como frente independente.

O armazenamento isolado `PpaQueryFileCache` foi criado no commit `b66a4c0`,
com validação, trava, escrita atômica e 16 assertions. Ele ainda não possui
consumidores e não altera os dashboards.

O comando manual `bin/ppa-dashboard-cache.php` e
`PpaQueryCacheSynchronizer` foram criados no commit `4df0c63`. A validação real
de `PPA-CREAS-MULHERES-F1` gravou uma entrada com 13 linhas em 65,729 ms. O
arquivo é local, ignorado pelo Git, e ainda não é lido pelo runtime.

Próxima etapa do checklist original: homologar e preparar a entrega no GitHub.
Antes da homologação final, o próximo incremento é integrar a leitura
cache-first ao serviço de consultas, com fallback externo e sem renovação em
requisições públicas.

## Regra documental para cada incremento

Nenhum incremento é considerado concluído sem:

1. atualizar este documento com o novo ponto de retomada;
2. registrar a mudança em `docs/changelog.md`;
3. atualizar o documento técnico específico;
4. registrar testes e métricas executados;
5. atualizar `docs/task.todo.md`;
6. reconciliar as páginas correspondentes no Notion;
7. registrar apenas um próximo passo incremental.

## Índice documental

- `docs/continuity.md`: retomada rápida e sequência vigente.
- `docs/project_status.md`: retrato dos módulos e bloqueios.
- `docs/task.todo.md`: tarefas concluídas, atuais e futuras.
- `docs/roadmap.md`: ordem das entregas.
- `docs/changelog.md`: histórico cronológico.
- `docs/documentacao_tecnica.md`: referência geral do sistema.
- `docs/architecture/ppa-module.md`: arquitetura atual do PPA.
- `docs/performance/baseline.md`: baseline e medições.
- `docs/performance/query-monitoring.md`: operação do monitoramento.
- `docs/performance/catalog-metrics.md`: catálogo local e sincronizações.
- `docs/performance/dashboard-file-cache.md`: decisão e desenho do cache dos
  dashboards.
- `docs/manual_usuario.md`: uso das telas e comandos disponíveis.
- `docs/plano_inicial_modulo_ppa.md`: plano histórico; não usar como status.
- `docs/Plano Inicial do Módulo PPA - Observatório SASC.docx`: artefato
  histórico; a versão Markdown e os documentos atuais prevalecem.
