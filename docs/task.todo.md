# Task TODO

Atualizado em 2026-07-24. O contexto completo de retomada está em
`docs/continuity.md`.

## Medição do progresso

- Total: 14 etapas.
- Concluídas: 10.
- Restantes: 4.
- Progresso: 71,4%.
- Checklist interativo:
  `https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`

## Próximo incremento

- [x] 1. Decidir o destino da alteração local em `app/Database/DataConect.php`
  - confirmar se host `database` e senha `root` são defaults oficiais do Docker;
  - manter a mudança separada da refatoração do PPA;
  - não expor credenciais reais em documentação ou commits.
- [x] 2. Consolidar o checkpoint documental no Git
  - revisar os arquivos Markdown liberados pelo `.gitignore`;
  - validar formatação sem misturar a mudança funcional de conexão.
Preparação das etapas 3 a 6 de `buildFamilySnapshotRmaPayload()`:

  - criar `PpaFamilySnapshotRmaPayloadBuilder`;
  - cobrir base, atualizações, série RMA, CRAS e mês;
  - validar equivalência antes de remover o método legado;
  - atualizar documentação local e Notion no mesmo incremento.

## Sequência seguinte

- [x] 3. Caracterizar o payload de fotografia familiar.
- [x] 4. Criar o teste de equivalência da fotografia familiar.
- [x] 5. Criar `PpaFamilySnapshotRmaPayloadBuilder`.
- [x] 6. Integrar o builder e remover o legado de fotografia.
- [x] 7. Caracterizar o payload de atualização cadastral.
- [x] 8. Criar o teste de equivalência da atualização cadastral.
- [x] 9. Criar `PpaCadUpdateRmaPayloadBuilder`.
- [x] 10. Integrar o builder e remover o legado cadastral.
- [ ] 11. Revisar responsabilidades restantes no `PpaController`.
- [ ] 12. Coletar métricas controladas dos dashboards representativos.
- [ ] 13. Produzir decisão sobre filtros, conexões e cache com base nas métricas.
- [ ] 14. Homologar e preparar o pull request da branch `update-repository`.

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
- [x] Manter nove testes locais, com 141 assertions na verificação de 2026-07-24.

## Homologações pendentes

- [ ] Fluxos administrativos do PPA.
- [ ] Catálogo e dashboards públicos em desktop e mobile.
- [ ] Login, logout e CRUD de usuários administrativos.
- [ ] Proteção das rotas administrativas.
- [ ] Resultados consolidados exibidos no catálogo.

## Backlog fora da frente atual

- [ ] Corrigir `cancelado` x `Suspenso` no BSC.
- [ ] Evoluir CadÚnico.
- [ ] Definir o próximo módulo entre SASC-SA, OSC e POPWEB.
- [ ] Revisar arquivos legados e duplicados sem remoção automática.

## Checklist documental obrigatório

Para cada mudança:

- [ ] atualizar `docs/continuity.md`;
- [ ] atualizar `docs/changelog.md`;
- [ ] atualizar o documento técnico específico;
- [ ] registrar testes e métricas;
- [ ] atualizar este TODO;
- [ ] atualizar as páginas relacionadas no Notion;
- [ ] registrar o próximo incremento.
