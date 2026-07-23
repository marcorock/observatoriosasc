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

Não há números de tempo nesta baseline porque a auditoria não executou consultas
contra as bases externas de produção.
