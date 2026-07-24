# Roadmap do Projeto

Atualizado em 2026-07-24. A ordem operacional vigente também está registrada em
`docs/continuity.md`.

## Marco atual — estabilização do PPA

O marco possui 14 etapas acompanhadas no checklist do Notion:
`https://app.notion.com/p/3a70c14a2c49813489dfc18b8478734d`.
O progresso inicial registrado é 0 de 14 etapas.

### Concluído

- Instrumentar consultas externas de forma opt-in.
- Aplicar `LIMIT` no SQL externo.
- Remover consultas externas do catálogo.
- Ler métricas consolidadas localmente e em lote.
- Criar sincronização CLI com prévia, commit e idempotência.
- Consolidar os nove indicadores quantitativos públicos.
- Classificar o indicador de visão geral.
- Extrair catálogo, consultas vinculadas e resolução de dashboards.
- Extrair builders de consulta simples, mensal por unidade e progresso familiar.
- Criar sete testes locais, com 87 assertions na última execução.

### Sequência imediata

1. Resolver os defaults não commitados de `DataConect.php`.
2. Consolidar a documentação no Git.
3. Caracterizar, testar, extrair e integrar o builder de fotografia familiar.
4. Caracterizar, testar, extrair e integrar o builder cadastral.
5. Revisar o controller e medir os dashboards.
6. Registrar a decisão técnica e preparar a entrega.
7. Atualizar documentação local e Notion em cada incremento.

### Depois das extrações

1. Medir novamente os dashboards representativos.
2. Identificar filtros e agregações com maior descarte em PHP.
3. Avaliar prepared statements ou placeholders controlados para pushdown.
4. Avaliar reutilização de conexões por request.
5. Comparar cache em banco, arquivo, APCu ou Redis com base nas medições.
6. Preparar PR para revisão e homologação.

## Backlog paralelo

- Corrigir a divergência `cancelado` x `Suspenso` no BSC.
- Homologar os CRUDs administrativos do PPA.
- Homologar login, logout e gerenciamento de usuários.
- Evoluir CadÚnico para um fluxo funcional mínimo.
- Revisar arquivos legados e duplicados sem remoção automática.
- Mapear requisitos de SASC-SA, OSC e POPWEB.

## Critérios para introduzir cache

Cache não faz parte da sequência imediata. A decisão depende de consultas
repetidas comprovadas, ganho mensurável, tolerância à defasagem, estratégia de
invalidação, topologia de servidores, fallback e custo operacional.

Redis exige aprovação explícita.

## Critério de conclusão de uma etapa

Uma etapa só termina quando código, testes, métricas aplicáveis, documentação
local e Notion descrevem o mesmo estado e existe apenas um próximo incremento
claramente registrado.
