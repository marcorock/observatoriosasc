# Continuidade do Projeto

Atualizado em 2026-08-06.

Este é o ponto de entrada obrigatório para retomar o desenvolvimento. O estado
executável deve ser confirmado pelo código e pelo Git; os demais documentos
detalham arquitetura, operação e histórico.

## Contexto de trabalho

- Repositório: `marcorock/observatoriosasc`
- Branch ativa: `fix-ui-user`
- Último checkpoint funcional: `2ef840c`
- Situação: cinco correções de visualização isoladas em commits locais, ainda
  sem publicação
- Pull request: #2 integrada com 116 commits; a branch
  `integration/update-repository` foi removida após o merge
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

Homologar as correções de ordenação dos gráficos por unidade, da disposição dos
percentuais, da identificação numérica dos painéis, da meta proporcional por
unidade e do detalhamento de atendimentos do indicador 158.

## Correções visuais de 2026-08-06

- Os gráficos de barras por CRAS, CREAS ou outra unidade recebem seus dados em
  ordem alfabética; as tabelas mantêm sua ordenação anterior.
- Cards que exibem os dois percentuais apresentam `% PERÍODO` à esquerda e
  `% ALCANÇADO` à direita.
- O cabeçalho de cada painel individual inclui o número cadastrado no formato
  `PPA - {número} - {nome}`; indicadores sem número mantêm o formato anterior.
- Nos dashboards mensais por unidade, a meta geral permanece inalterada sem
  filtro. Ao selecionar uma unidade, a meta é dividida igualmente pela
  quantidade de unidades com leitura, contadas antes dos filtros de unidade e
  mês; o percentual alcançado usa essa meta proporcional.
- O card `ATENDIMENTOS REALIZADOS` do indicador 158 separa `TÉCNICO`, calculado
  como `C1 - (C2 + C3)`, e `NÍVEL MÉDIO`, calculado como `C2 + C3`. Ambos usam
  as mesmas linhas filtradas por unidade e mês.
- O conteúdo dos cards divididos é centralizado nos eixos vertical e horizontal,
  mantendo divisores, tipografia e estilos existentes.
- Rótulos configuráveis, cores, classes e interações foram preservados.
- A suíte completa passou no container com UID 1000: 30 arquivos e 325
  assertions. O teste de permissões também passou fora do contexto `root`.
- A consulta local do indicador 158 foi atualizada, seu cache foi renovado com
  54 linhas e os campos `total_inseridos`, `total_c2` e `total_c3` foram
  confirmados. No consolidado validado, `C1 = TÉCNICO + NÍVEL MÉDIO`.

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

Na verificação de 2026-07-28, os vinte e seis arquivos de teste passaram:

| Teste | Assertions |
| --- | ---: |
| `AdminAuthenticationFlowTest.php` | 11 |
| `AdminRouteProtectionTest.php` | 9 |
| `AdminUserCrudValidationTest.php` | 16 |
| `ExternalDatabaseRuntimeTest.php` | 9 |
| `ExternalAdminCrudFlowTest.php` | 16 |
| `ExternalAdminPayloadValidationTest.php` | 18 |
| `HomeThemeMarkupTest.php` | 7 |
| `NavigationLoaderMarkupTest.php` | 8 |
| `PpaCatalogHttpSmokeTest.php` | 5 |
| `PpaCatalogMetricsTest.php` | 26 |
| `PpaCronInstallerScriptTest.php` | 14 |
| `PpaAdminCrudFlowTest.php` | 18 |
| `PpaAdminSynchronizationFlowTest.php` | 22 |
| `PpaAdminSynchronizationServiceTest.php` | 12 |
| `PpaCadUpdateRmaPayloadBuilderTest.php` | 28 |
| `PpaDashboardResolverTest.php` | 10 |
| `PpaDashboardHtmlSmokeTest.php` | 6 |
| `PpaDashboardJsonSmokeTest.php` | 4 |
| `PpaDashboardFilterCacheSmokeTest.php` | 11 |
| `PpaFamilyRmaPayloadBuilderTest.php` | 11 |
| `PpaFamilySnapshotRmaPayloadBuilderTest.php` | 26 |
| `PpaLinkedQueryServiceTest.php` | 17 |
| `PpaMonthlyUnitPayloadBuilderTest.php` | 11 |
| `PpaQueryCacheSynchronizerTest.php` | 10 |
| `PpaQueryCacheBatchSynchronizerTest.php` | 10 |
| `PpaQueryFileCacheTest.php` | 18 |
| `PpaScheduledSynchronizationBatchServiceTest.php` | 11 |
| `PpaScheduledSynchronizationCommandTest.php` | 10 |
| `PpaSingleQueryPayloadBuilderTest.php` | 12 |
| **Total** | **386** |

## Sequência concluída

O plano possui 14 etapas mensuráveis, organizadas em cinco fases. O checklist
interativo está no Notion:

`https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`

Progresso atual: 14 de 14 etapas, ou 100%.

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
14. Concluído — homologar e preparar a entrega no GitHub.

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

## Histórico das extrações e otimizações

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
com validação, trava, escrita atômica e 16 assertions. Posteriormente, ele foi
integrado ao runtime por `PpaLinkedQueryService`.

O comando manual `bin/ppa-dashboard-cache.php` e
`PpaQueryCacheSynchronizer` foram criados no commit `4df0c63`. A validação real
de `PPA-CREAS-MULHERES-F1` gravou uma entrada com 13 linhas em 65,729 ms. O
arquivo é local e ignorado pelo Git.

`PpaLinkedQueryService` passou a usar cache compatível no commit `9baaa3d`.
Ausência, corrupção ou incompatibilidade mantêm a consulta externa; requisições
públicas nunca gravam cache. A prévia cache-first do indicador mensal preservou
meta 555 e realizado 73, respondeu em 46,381 ms e registrou zero consultas
externas.

Progresso familiar e atualização cadastral também foram sincronizados e
comparados antes/depois. As prévias foram idênticas, os tempos cache-first foram
30,548 ms e 31,560 ms e cada fluxo passou de duas consultas externas para zero.
Existem cinco entradas locais válidas no ambiente de homologação.

Catálogo, HTML e JSON dos três indicadores sincronizados responderam HTTP 200.
Filtros representativos de unidade/CRAS e mês retornaram um mês e um território,
sem erro e sem consulta externa. Os endpoints filtrados responderam entre
36,721 ms e 44,228 ms.

O comando de atualização foi corrigido no commit `fa905b0` para sempre ignorar
cache existente e consultar a fonte. A renovação mensal registrou uma consulta
externa. O lote sequencial `--all` foi publicado em `359a269`, com continuação
após falhas individuais e exit code de falha parcial. O exemplo de agendamento
usa `flock` para impedir sobreposição.

O checklist original foi concluído. A antiga branch de integração conectou os
históricos independentes preservando a árvore de `update-repository`.

Pull request integrada:

`https://github.com/marcorock/observatoriosasc/pull/2`

A PR #2 foi integrada em `main` com 116 commits em 2026-07-28.

## Próximo incremento

Homologar visualmente o novo card do indicador 158 em desktop e mobile,
incluindo filtros de mês e CRAS.

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
