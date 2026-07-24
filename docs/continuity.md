# Continuidade do Projeto

Atualizado em 2026-07-24.

Este é o ponto de entrada obrigatório para retomar o desenvolvimento. O estado
executável deve ser confirmado pelo código e pelo Git; os demais documentos
detalham arquitetura, operação e histórico.

## Contexto de trabalho

- Repositório: `marcorock/observatoriosasc`
- Branch ativa: `update-repository`
- Último commit documentado: `0fcef27`
- Situação: sincronizada com `origin/update-repository` e 23 commits à frente
  de `main` na última verificação
- Pull request da branch: inexistente na última verificação
- Issues abertas: nenhuma na última verificação
- Alteração local não commitada: defaults de conexão em
  `app/Database/DataConect.php` para host `database` e senha `root`

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

O controller ainda contém os builders especializados de fotografia familiar e
atualização cadastral, além de configurações visuais e normalizações.

## Testes

Na última verificação, os sete arquivos de teste passaram:

| Teste | Assertions |
| --- | ---: |
| `ExternalDatabaseRuntimeTest.php` | 9 |
| `PpaCatalogMetricsTest.php` | 26 |
| `PpaDashboardResolverTest.php` | 10 |
| `PpaFamilyRmaPayloadBuilderTest.php` | 11 |
| `PpaLinkedQueryServiceTest.php` | 8 |
| `PpaMonthlyUnitPayloadBuilderTest.php` | 11 |
| `PpaSingleQueryPayloadBuilderTest.php` | 12 |
| **Total** | **87** |

## Próxima sequência aprovada

O plano possui 14 etapas mensuráveis, organizadas em cinco fases. O checklist
interativo está no Notion:

`https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`

Progresso inicial: 0 de 14 etapas, ou 0%.

1. Decidir os defaults de conexão em `DataConect.php`.
2. Consolidar o checkpoint documental no Git.
3. Caracterizar o payload de fotografia familiar.
4. Criar seu teste de equivalência.
5. Criar `PpaFamilySnapshotRmaPayloadBuilder`.
6. Integrar o builder e remover o legado de fotografia.
7. Caracterizar o payload de atualização cadastral.
8. Criar seu teste de equivalência.
9. Criar `PpaCadUpdateRmaPayloadBuilder`.
10. Integrar o builder e remover o legado cadastral.
11. Revisar as responsabilidades restantes no `PpaController`.
12. Executar nova campanha de medição.
13. Produzir decisão técnica baseada nas métricas.
14. Homologar e preparar a entrega no GitHub.

Cada etapa vale um ponto. O percentual é calculado por
`etapas concluídas / 14 × 100`.

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
- `docs/manual_usuario.md`: uso das telas e comandos disponíveis.
- `docs/plano_inicial_modulo_ppa.md`: plano histórico; não usar como status.
- `docs/Plano Inicial do Módulo PPA - Observatório SASC.docx`: artefato
  histórico; a versão Markdown e os documentos atuais prevalecem.
