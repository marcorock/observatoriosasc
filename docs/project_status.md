# Status do Projeto

Atualizado em 2026-07-28. Para retomar o trabalho, começar por
`docs/continuity.md`.

## Visão geral

O Observatório SASC é uma aplicação PHP MVC com Twig, PDO e integrações com
bancos externos. Os módulos BSC e PPA possuem fluxos funcionais; CadÚnico ainda
é uma entrada inicial. O foco atual é concluir a refatoração incremental e
mensurável do PPA.

## Estado por módulo

| Módulo | Estado | Próxima necessidade |
| --- | --- | --- |
| Home e navegação | Fundo e cards preparados para temas claro e escuro | Homologação visual |
| Administração | Login, sessão e CRUD cobertos por testes locais | Homologar indicadores e vínculos |
| BSC | Dashboard e CRUD funcionais | Resolver `cancelado` x `Suspenso` |
| PPA público | Catálogo, dashboards, filtros e cache-first funcionais | Ampliar smoke tests HTTP |
| PPA administrativo | Fontes, consultas, indicadores e vínculos funcionais | Homologação operacional |
| CadÚnico | Tela inicial | Definir fluxo funcional mínimo |
| SASC-SA, OSC e POPWEB | Planejados | Levantar requisitos e fontes |

## Estado de performance do PPA

- Catálogo sem consultas externas.
- Limite aplicado no SQL externo.
- Monitoramento opt-in sem credenciais ou SQL completo nos logs.
- Métricas locais carregadas em lote.
- Sincronização CLI explícita e idempotente.
- Nove indicadores quantitativos consolidados localmente.
- Um indicador classificado como visão geral.
- Serviços e builders extraídos com cobertura mínima de testes.
- Baseline pós-refatoração coletada com três amostras por fluxo representativo.
- Cache local em arquivo implementado, testado e integrado aos dashboards;
  Redis permanece fora do escopo.
- Comando manual para um indicador implementado e validado, sem escrita no
  banco.
- Leitura cache-first integrada com fallback externo e sem renovação pública.
- Progresso familiar e atualização cadastral homologados sem filtros, com
  equivalência das prévias e zero consultas externas em hits.
- Catálogo, HTML, JSON e filtros representativos homologados com HTTP 200 e zero
  consultas externas nos hits.
- Renovação corrigida para sempre consultar a fonte; lote sequencial e exemplo
  de agendamento com trava disponíveis.

## Último marco concluído

- Data: 2026-07-24
- Commit: `405e648`
- Tarefa: concluir a entrega da refatoração do PPA na branch de integração
- Resultado: plano de 14 etapas concluído, cache homologado, atualização em
  lote disponível e PR draft #2 preparada
- Próximo incremento: smoke tests HTTP dos fluxos públicos do PPA

## Validação técnica

Em 2026-07-28, os vinte e quatro testes locais passaram com 314 assertions. O
`composer.json` e os arquivos PHP centrais da integração do cache também foram
validados.

## Configuração local

Os defaults de `app/Database/DataConect.php` permanecem `localhost` e senha
vazia, coerentes com `.env.example`, README e CLI. Ambientes Docker ou remotos
devem configurar suas credenciais no `.env`.

## Bloqueios e riscos

- A PR draft #2 reúne a refatoração e aguarda revisão humana.
- O Notion estava defasado em relação ao código até a atualização de 2026-07-24.
- O BSC mantém a divergência `cancelado` x `Suspenso`.
- Existe uma baseline operacional curta, mas ainda não há teste de carga ou
  concorrência.
- Somente indicadores previamente sincronizados usam cache; os demais
  continuam consultando as fontes externas.
- Os testes atuais cobrem serviços, builders, rotas HTTP representativas,
  autenticação e os CRUDs administrativos principais; ainda falta homologação
  visual completa.
- Alguns models ainda executam `CREATE TABLE IF NOT EXISTS` durante o runtime.

## Próximo passo recomendado

Homologar visualmente o CRUD descartável de indicadores e vínculos. Depois,
revisar a PR #2 em pequenos incrementos.
