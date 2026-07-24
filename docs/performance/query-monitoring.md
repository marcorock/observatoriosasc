# Monitoramento de consultas externas

Atualizado em 2026-07-24.

## Objetivo

Registrar métricas das consultas externas sem alterar cálculos, payloads ou
tratamento de erros do sistema.

O monitoramento é desativado por padrão e qualquer falha ao registrar uma
métrica é ignorada para não interromper a requisição.

## Ativação controlada

Adicione temporariamente ao `.env`:

```dotenv
EXTERNAL_QUERY_PERFORMANCE_LOG=true
```

As linhas são enviadas ao log de erros configurado no PHP/FPM ou servidor web e
começam com:

```text
[external-query-performance]
```

Reinicie ou recarregue o processo PHP somente se o ambiente exigir isso para
reler variáveis.

## Campos registrados

- data e hora;
- endpoint sem query string;
- identificadores do indicador, fonte e consulta;
- nome da fonte e da consulta;
- hash SHA-256 da consulta normalizada;
- tempo de conexão, consulta e total;
- linhas retornadas e limite solicitado;
- variação e pico de memória;
- sucesso ou estágio da falha.

O SQL completo, credenciais, senha criptografada, parâmetros da URL e mensagem
bruta da exceção não são registrados.

## Validação segura

1. Ative em uma única instância ou janela de manutenção observada.
2. Acesse `/ppa` e confirme que não há log de consulta externa.
3. Abra um indicador de cada tipo.
4. Aplique um filtro e confirme uma nova sequência de métricas.
5. Compare cards, gráficos e tabelas antes e depois.
6. Monitore tamanho e rotação do log do PHP.

## Desativação e rollback

Para desativar imediatamente:

```dotenv
EXTERNAL_QUERY_PERFORMANCE_LOG=false
```

Sem a variável, o comportamento também é desativado. O rollback do código
consiste em remover o logger e o quarto argumento opcional de
`runRegisteredQuery()`. Nenhuma alteração de banco de dados é necessária.

## Próxima campanha de medição

Depois da extração dos builders especializados, coletar amostras controladas de
cada tipo de dashboard e registrar os resultados em `baseline.md`. O logger deve
permanecer desativado fora da janela de medição.
