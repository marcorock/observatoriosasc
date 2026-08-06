# Task TODO

Atualizado em 2026-08-06. O contexto completo de retomada está em
`docs/continuity.md`.

## Correções visuais em homologação

- [x] Ordenar alfabeticamente os gráficos de barras por CRAS, CREAS e demais
  unidades sem alterar os cálculos.
- [x] Exibir `% PERÍODO` antes de `% ALCANÇADO` nos cards combinados.
- [x] Adicionar cobertura para a ordenação dos payloads e da marcação dos cards.
- [ ] Executar a suíte em ambiente com PHP e homologar os dashboards em desktop
  e mobile.

## Medição do progresso

- Total: 14 etapas.
- Concluídas: 14.
- Restantes: 0.
- Progresso: 100%.
- Checklist interativo:
  `https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`

## Plano histórico concluído

- [x] 1. Decidir o destino da alteração local em `app/Database/DataConect.php`
  - confirmar se host `database` e senha `root` são defaults oficiais do Docker;
  - manter a mudança separada da refatoração do PPA;
  - não expor credenciais reais em documentação ou commits.
- [x] 2. Consolidar o checkpoint documental no Git
  - revisar os arquivos Markdown liberados pelo `.gitignore`;
  - validar formatação sem misturar a mudança funcional de conexão.

## Sequência concluída

- [x] 3. Caracterizar o payload de fotografia familiar.
- [x] 4. Criar o teste de equivalência da fotografia familiar.
- [x] 5. Criar `PpaFamilySnapshotRmaPayloadBuilder`.
- [x] 6. Integrar o builder e remover o legado de fotografia.
- [x] 7. Caracterizar o payload de atualização cadastral.
- [x] 8. Criar o teste de equivalência da atualização cadastral.
- [x] 9. Criar `PpaCadUpdateRmaPayloadBuilder`.
- [x] 10. Integrar o builder e remover o legado cadastral.
- [x] 11. Revisar responsabilidades restantes no `PpaController`.
- [x] 12. Coletar métricas controladas dos dashboards representativos.
- [x] 13. Produzir decisão sobre filtros, conexões e cache com base nas métricas.
- [x] 14. Homologar e preparar o pull request da branch `update-repository`.

## Concluído na frente de performance do PPA

- [x] Mapear catálogo, dashboard detalhado e endpoint JSON.
- [x] Instrumentar consultas externas com log opt-in.
- [x] Aplicar limite no SQL antes do `fetchAll()`.
- [x] Remover consultas externas do catálogo `/ppa`.
- [x] Carregar métricas locais em lote.
- [x] Criar prévia CLI sem gravação.
- [x] Criar persistência explícita e idempotente.
- [x] Sincronizar nove indicadores quantitativos.
- [x] Classificar o indicador de visão geral.
- [x] Extrair `PpaCatalogService`.
- [x] Extrair `PpaLinkedQueryService`.
- [x] Extrair `PpaDashboardResolver`.
- [x] Extrair `PpaSingleQueryPayloadBuilder`.
- [x] Extrair `PpaMonthlyUnitPayloadBuilder`.
- [x] Extrair `PpaFamilyRmaPayloadBuilder`.
- [x] Extrair `PpaFamilySnapshotRmaPayloadBuilder`.
- [x] Extrair `PpaCadUpdateRmaPayloadBuilder`.
- [x] Classificar as responsabilidades restantes no `PpaController` sem nova
  extração prematura.
- [x] Manter vinte e nove testes locais, com 386 assertions na verificação de 2026-07-28.

## Homologações pendentes

- [x] Implementar armazenamento de cache em arquivo com validação, trava e
  escrita atômica.
- [x] Criar comando manual de sincronização do cache para um indicador.
- [x] Disponibilizar sincronização completa de um indicador no painel
  administrativo, com confirmação, feedback e proteção por token.
- [x] Exibir situação, última atualização e ação individual em uma tabela de
  sincronização.
- [x] Implementar sincronização automática completa em lote com trava e resumo.
- [x] Preparar receita diária para 04:15 em `America/Sao_Paulo`.
- [x] Criar instalador validado e instruções para o sysadmin.
- [ ] Instalar a receita no agendador do servidor real e monitorar a primeira
  execução.
- [x] Integrar leitura cache-first com fallback externo.
- [x] Homologar equivalência e tempos cache-first do progresso familiar e da
  atualização cadastral.
- [x] Homologar filtros e endpoints HTTP representativos com cache.
- [x] Adicionar atualização em lote e documentar agendamento somente após
  homologar o fluxo manual.
- [x] Criar branch de integração conectando os históricos independentes.
- [x] Criar a pull request draft
  `https://github.com/marcorock/observatoriosasc/pull/2`.
- [x] Integrar a PR #2 em `main` e remover
  `integration/update-repository`.

## Próxima frente — proteção da integração

- [x] Reconciliar documentação canônica com `main`.
- [x] Remover briefing de auditoria concluído e `.docx` duplicado, preservando
  o plano histórico em Markdown.
- [x] Criar smoke test para `/ppa`, incluindo erro controlado de leitura.
- [x] Completar smoke tests para `/ppa/{slug}`:
  - [x] slug inexistente;
  - [x] indicador válido sem vínculos ativos.
- [x] Criar smoke tests de erro para `/ppa/{slug}/data`:
  - [x] slug inexistente;
  - [x] indicador sem vínculos.
- [x] Cobrir filtros representativos e cache hit/miss nos testes HTTP:
  - [x] filtro por CRAS e região com cache hit;
  - [x] cache miss com fallback externo controlado.
- [x] Fluxos administrativos do PPA:
  - [x] criação, edição e exclusão de indicadores com model em memória;
  - [x] criação, edição e exclusão de vínculos com models em memória.
- [x] CRUDs de fontes e consultas externas:
  - [x] validação de payload, SQL SELECT e senha criptografada;
  - [x] fluxo de criação, edição e exclusão com models em memória.
- [ ] Catálogo e dashboards públicos em desktop e mobile.
- [x] Login, logout e CRUD de usuários administrativos:
  - [x] login válido e credenciais inválidas;
  - [x] logout válido e token inválido;
  - [x] validações de criação, edição e exclusão.
- [x] Proteção das rotas administrativas contra acesso anônimo.
- [ ] Resultados consolidados exibidos no catálogo.

## Backlog fora da frente atual

- [x] Exibir feedback de carregamento na navegação interna.
- [x] Preparar fundo e cards da Home para os temas claro e escuro.
- [ ] Corrigir `cancelado` x `Suspenso` no BSC.
- [ ] Evoluir CadÚnico.
- [ ] Definir o próximo módulo entre SASC-SA, OSC e POPWEB.
- [ ] Revisar arquivos legados e duplicados sem remoção automática.

## Checklist documental obrigatório

Para cada mudança:

- [x] atualizar `docs/continuity.md`;
- [x] atualizar `docs/changelog.md`;
- [x] atualizar o documento técnico específico;
- [x] registrar testes e métricas;
- [x] atualizar este TODO;
- [ ] atualizar as páginas relacionadas no Notion;
- [x] registrar o próximo incremento.
