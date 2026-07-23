# Baseline de performance do PPA

Data da auditoria inicial: 2026-07-23.

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

## Métricas pendentes

Com `EXTERNAL_QUERY_PERFORMANCE_LOG=true`, coletar para cada tipo de dashboard:

- tempo total da página;
- tempo de conexão externa;
- tempo de execução e leitura da consulta;
- quantidade de linhas retornadas;
- limite solicitado;
- variação e pico de memória;
- sucesso ou estágio da falha.

A tabela estrutural acima registra apenas a quantidade esperada de operações; os
primeiros tempos controlados estão registrados a seguir.

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
