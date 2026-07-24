# Status do Projeto

Atualizado em 2026-07-24. Para retomar o trabalho, começar por
`docs/continuity.md`.

## Visão geral

O Observatório SASC é uma aplicação PHP MVC com Twig, PDO e integrações com
bancos externos. Os módulos BSC e PPA possuem fluxos funcionais; CadÚnico ainda
é uma entrada inicial. O foco atual é concluir a refatoração incremental e
mensurável do PPA.

## Estado por módulo

| Módulo | Estado | Próxima necessidade |
| --- | --- | --- |
| Home e navegação | Funcional | Homologação visual contínua |
| Administração | Login, sessão e CRUD funcionais | Homologar fluxos sensíveis |
| BSC | Dashboard e CRUD funcionais | Resolver `cancelado` x `Suspenso` |
| PPA público | Catálogo, dashboards e filtros funcionais | Concluir extrações especializadas |
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

## Última tarefa trabalhada

- Data: 2026-07-23
- Commit: `0fcef27`
- Tarefa: remover o builder familiar regular legado depois da extração para
  `PpaFamilyRmaPayloadBuilder`
- Resultado: implementação duplicada removida e testes preservados
- Próxima extração: fotografia familiar + atualização + RMA

## Validação técnica

Em 2026-07-24, os sete testes locais passaram com 87 assertions. A branch estava
sincronizada com seu remoto e 23 commits à frente de `main`.

## Pendência local

`app/Database/DataConect.php` possui uma alteração não commitada que troca os
defaults para host `database` e senha `root`. É necessário decidir se isso é
configuração oficial do ambiente Docker ou ajuste exclusivamente local.

## Bloqueios e riscos

- Não existe PR aberto reunindo a refatoração.
- O Notion estava defasado em relação ao código até a atualização de 2026-07-24.
- O BSC mantém a divergência `cancelado` x `Suspenso`.
- Ainda não há benchmark estatístico dos dashboards; existe apenas coleta
  controlada inicial.
- Cache, Redis e filtros no SQL não devem ser adotados sem novas medições.

## Próximo passo recomendado

Resolver a alteração pendente de conexão e, em seguida, extrair
`buildFamilySnapshotRmaPayload()` com teste de equivalência antes de remover a
implementação do controller.
