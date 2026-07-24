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
| PPA público | Catálogo, dashboards, filtros e builders funcionais | Implementar cache em arquivo incremental |
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
- Cache local em arquivo aprovado como próxima otimização; Redis fora do escopo.

## Última tarefa trabalhada

- Data: 2026-07-23
- Commit: `0fcef27`
- Tarefa: remover o builder familiar regular legado depois da extração para
  `PpaFamilyRmaPayloadBuilder`
- Resultado: implementação duplicada removida e testes preservados
- Próxima extração: fotografia familiar + atualização + RMA

## Validação técnica

Em 2026-07-24, os nove testes locais passaram com 141 assertions.

## Configuração local

Os defaults de `app/Database/DataConect.php` permanecem `localhost` e senha
vazia, coerentes com `.env.example`, README e CLI. Ambientes Docker ou remotos
devem configurar suas credenciais no `.env`.

## Bloqueios e riscos

- Não existe PR aberto reunindo a refatoração.
- O Notion estava defasado em relação ao código até a atualização de 2026-07-24.
- O BSC mantém a divergência `cancelado` x `Suspenso`.
- Existe uma baseline operacional curta, mas ainda não há teste de carga ou
  concorrência.
- O cache em arquivo ainda não está implementado; até lá, dashboards detalhados
  continuam consultando as fontes externas.

## Próximo passo recomendado

Implementar primeiro o armazenamento seguro do cache em arquivo, com testes,
sem ainda integrar sua leitura aos dashboards.
