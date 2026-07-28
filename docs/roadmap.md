# Roadmap do Projeto

Atualizado em 2026-07-28. A ordem operacional vigente também está registrada em
`docs/continuity.md`.

## Marco concluído — performance e estrutura do PPA

O plano de 14 etapas acompanhado no Notion foi concluído em 2026-07-24.

Resultados consolidados:

- consultas externas instrumentadas de forma opt-in;
- `LIMIT` aplicado no SQL externo;
- catálogo sem consultas externas e com métricas locais em lote;
- sincronização CLI com prévia, persistência explícita e idempotência;
- nove indicadores quantitativos consolidados;
- indicador de visão geral excluído dos cálculos de meta;
- catálogo, resolução, consultas e cinco builders extraídos;
- baseline pós-refatoração coletada;
- cache em arquivo implementado, integrado e homologado;
- sincronização individual e em lote disponível;
- sincronização completa de um indicador disponível no painel administrativo;
- sincronização automática completa em lote, com comando e receita diária;
- instalador idempotente e instruções de entrega ao sysadmin;
- vinte e nove testes locais com 386 assertions;
- branch de integração e PR draft #2 preparadas.

## Marco atual — proteção da integração

### Sequência imediata

1. Concluído — reconciliar a documentação com o código e a branch de
   integração.
2. Concluído — catálogo, dashboard HTML e erros do endpoint JSON cobertos.
3. Concluído — filtros, cache hit e cache miss cobertos.
4. Concluído — acesso, login, logout, usuários, fontes, consultas, indicadores
   e vínculos cobertos.
5. Revisar a PR #2 e tratar observações em commits pequenos.
6. Marcar a PR como pronta e realizar merge somente após aprovação.

### Critérios de conclusão

- rotas públicas principais protegidas contra regressão;
- fluxos administrativos sensíveis homologados;
- suíte local verde;
- documentação e código descrevendo o mesmo estado;
- revisão humana da PR concluída.

## Próxima frente estrutural

Somente depois da proteção da integração:

1. remover helpers comprovadamente sem consumidores;
2. auditar arquivos legados e duplicados;
3. retirar `CREATE TABLE IF NOT EXISTS` do runtime e formalizar instalação ou
   migrations;
4. avaliar extração da sincronização de catálogo e das configurações visuais;
5. medir cache hit/miss, idade dos arquivos e concorrência de atualização;
6. avaliar filtros parametrizados no SQL apenas nos gargalos comprovados;
7. avaliar reutilização de conexão externa por fonte e request.

Redis permanece fora do escopo. Sua adoção exige múltiplas instâncias,
necessidade de invalidação distribuída ou volume concorrente comprovado.

## Backlog paralelo

- corrigir a divergência `cancelado` x `Suspenso` no BSC;
- evoluir CadÚnico para um fluxo funcional mínimo;
- definir o próximo módulo entre SASC-SA, OSC e POPWEB.

## Critério de conclusão de um incremento

Um incremento só termina quando código, testes, métricas aplicáveis,
documentação local e Notion descrevem o mesmo estado e existe apenas um próximo
passo claramente registrado.
