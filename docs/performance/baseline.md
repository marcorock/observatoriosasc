# Baseline de performance do PPA

Data da auditoria inicial: 2026-07-23.
Última revisão documental: 2026-07-24.

## Estado inicial

Esta baseline é estrutural. Os tempos reais ainda precisam ser coletados no
ambiente em funcionamento com a instrumentação opt-in.

| Fluxo | Consultas externas por request | Conexões externas por request |
| --- | ---: | ---: |
| Catálogo `/ppa` | 0 | 0 |
| Dashboard de consulta simples | 1 | 1 |
| Dashboard mensal por unidade | 1 | 1 |
| Dashboard família + RMA | 2 | 2 |
| Dashboard atualização cadastral + RMA | 2 | 2 |
| Dashboard snapshot familiar + RMA | 3 | 3 |

Os filtros AJAX repetem as consultas externas do respectivo dashboard. O
carregamento inicial usa os dados incluídos no HTML e não faz uma segunda
requisição automática.

## Métricas monitoradas

Com `EXTERNAL_QUERY_PERFORMANCE_LOG=true`, coletar para cada tipo de dashboard:

- tempo total da página;
- tempo de conexão externa;
- tempo de execução e leitura da consulta;
- quantidade de linhas retornadas;
- limite solicitado;
- variação e pico de memória;
- sucesso ou estágio da falha.

A tabela estrutural acima registra a quantidade esperada de operações. As
campanhas controladas estão registradas a seguir.

## Coleta controlada

Coleta realizada em 2026-07-23, com uma única requisição por fluxo e o
monitoramento desativado imediatamente após a medição.

| Fluxo | HTTP | Tempo total | Conexão externa | Consulta externa | Linhas | Resposta |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Catálogo `/ppa` | 200 | 74,7 ms | — | — | — | 81.743 bytes |
| `/ppa/erradicar-a-pobreza` | 200 | 790,1 ms | 13,9 ms | 702,8 ms | 342 | 386.536 bytes |

Uma segunda verificação do catálogo, depois de desativar a instrumentação,
respondeu HTTP 200 em 82,6 ms.

Esses valores são uma amostra inicial, não um benchmark estatístico. A consulta
externa representa aproximadamente 89% do tempo total observado no dashboard
simples.

## Estado após os incrementos

- o catálogo permanece com zero consultas e conexões externas;
- nove indicadores quantitativos possuem resultados locais validados;
- o indicador de visão geral não dispara sincronização;
- serviços e builders foram extraídos sem alteração intencional de payload;
- nove testes passaram com 141 assertions em 2026-07-24.

## Campanha pós-refatoração

Coleta realizada em 2026-07-24, com três execuções consecutivas por fluxo,
monitoramento ativado somente nos processos CLI e nenhuma persistência. Os
tempos totais incluem leitura do cadastro local, consultas externas e builder.

| Fluxo | Amostras | Consultas | Mediana total | Média total | Intervalo |
| --- | ---: | ---: | ---: | ---: | ---: |
| Consulta simples | 3 | 1 | 740,2 ms | 828,8 ms | 703,9–1.042,3 ms |
| Mensal por unidade | 3 | 1 | 71,6 ms | 70,5 ms | 57,3–82,6 ms |
| Progresso familiar | 3 | 2 | 618,7 ms | 611,3 ms | 592,8–622,5 ms |
| Atualização cadastral ativa | 3 | 2 | 4.041,3 ms | 4.158,0 ms | 3.994,6–4.438,2 ms |
| Fotografia familiar simulada | 3 | 3 | 12.997,9 ms | 13.265,3 ms | 12.932,9–13.865,1 ms |

### Detalhamento das consultas externas

Valores médios das três amostras.

| Fluxo/consulta | Conexão | Consulta | Linhas | Limite |
| --- | ---: | ---: | ---: | ---: |
| Consulta simples | 14,8 ms | 802,7 ms | 342 | 5.000 |
| Mensal por unidade | 17,9 ms | 14,7 ms | 13 | 5.000 |
| Progresso familiar — base PBF | 16,2 ms | 528,0 ms | 14 | 500 |
| Progresso familiar — RMA | 17,8 ms | 16,2 ms | 44 | 5.000 |
| Atualização cadastral — base | 15,4 ms | 4.077,7 ms | 15 | 500 |
| Atualização cadastral — RMA | 17,5 ms | 13,2 ms | 44 | 5.000 |
| Fotografia — base | 16,3 ms | 4.008,3 ms | 15 | 500 |
| Fotografia — atualizadas | 18,9 ms | 9.180,5 ms | 11 | 500 |
| Fotografia — RMA | 17,1 ms | 12,4 ms | 44 | 5.000 |

O logger registrou variação de memória de zero bytes em todas as consultas e
pico de 4 MiB. O zero é compatível com a granularidade de alocação do PHP e não
significa ausência absoluta de alocações.

### Condições e limitações

- O indicador de consulta simples é uma visão geral e não pode ser sincronizado.
  A medição chamou o mesmo construtor privado de resposta usado pelo dashboard.
- A fotografia familiar possui um terceiro vínculo cadastrado, porém inativo.
  Ela foi simulada em modo somente leitura com os três vínculos e o mesmo
  orquestrador e builder; não representa uma rota atualmente ativa.
- Uma execução exploratória anterior à série controlada do progresso familiar
  levou 17.188,6 ms. As três execuções seguintes ficaram entre 592,8 e
  622,5 ms, indicando forte influência de cache da fonte ou do banco.
- A campanha é uma baseline operacional curta, não um benchmark de carga ou
  concorrência.
- O monitoramento permaneceu desativado na configuração persistente.

## Leitura inicial

- conexão externa custa cerca de 15–19 ms por consulta e não é o principal
  gargalo;
- consultas RMA retornam rapidamente;
- a consulta simples ainda concentra aproximadamente 97% do tempo total médio
  na execução externa;
- a base cadastral da atualização representa aproximadamente 98% do tempo do
  fluxo ativo;
- na fotografia, as duas consultas CECAD somam cerca de 13,2 s; o builder e o
  RMA têm participação pequena;
- otimizar ou evitar o processamento das consultas CECAD deve ser avaliado
  antes de cache de aplicação ou novas extrações do controller.

## Decisão posterior à campanha

Foi escolhido um cache local em arquivo, atualizado por comando manual e
posteriormente por agendamento. Redis não será adotado neste momento. A decisão,
os controles e a ordem de implementação estão em
`docs/performance/dashboard-file-cache.md`.

A revisão do SQL CECAD continua recomendada como frente independente. Pushdown
de filtros e reutilização de conexões ficam com prioridade posterior.

## Primeira validação cache-first

Em 2026-07-24, o indicador mensal `PPA-CREAS-MULHERES-F1` reutilizou a entrada
manual com 13 linhas:

- meta e realizado preservados em 555 e 73;
- tempo total de 46,381 ms;
- zero consultas externas registradas;
- mediana anterior sem cache: 71,6 ms.

O ganho absoluto é pequeno nesse fluxo rápido. A homologação seguinte deve
priorizar atualização cadastral e progresso familiar, onde a baseline externa
é maior.

### Homologação dos fluxos mais caros

Ainda em 2026-07-24:

- progresso familiar: prévia equivalente, de 11.508,229 ms e duas consultas
  externas para 30,548 ms e zero consultas;
- atualização cadastral: prévia equivalente, de 4.069,384 ms e duas consultas
  externas para 31,560 ms e zero consultas.

Os tempos sem cache continuam sujeitos à variação da fonte. A equivalência foi
determinada pelo conteúdo completo da prévia, excluindo apenas o tempo.

### Endpoints filtrados

Na homologação HTTP cache-first:

- mensal por unidade: 37,702 ms;
- progresso familiar: 44,228 ms;
- atualização cadastral: 36,721 ms.

As três respostas filtradas retornaram HTTP 200, um mês e um território, sem
erro e sem consultas externas.
