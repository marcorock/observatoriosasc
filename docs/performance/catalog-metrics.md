# Métricas locais do catálogo PPA

## Decisão incremental

O catálogo `/ppa` não executa consultas externas. Ele tenta carregar, em uma
única consulta local, o registro mais recente de cada indicador em
`ppa_resultados` com status `validado` ou `publicado`.

Não foi criada uma tabela nova porque o schema existente já prevê:

- `ppa_resultados`, para resultados consolidados e históricos;
- `ppa_resultado_variaveis`, para valores auxiliares;
- `ppa_sincronizacoes`, para registrar execuções e falhas.

## Prioridade dos valores

Para a meta:

1. `ppa_resultados.valor_meta_quantitativa`;
2. `ppa_resultados.indice_futuro`;
3. `ppa_indicadores.indice_futuro`.

Para o realizado:

1. `ppa_resultados.valor_resultado`;
2. `ppa_resultados.indice_recente`;
3. `ppa_indicadores.indice_recente`.

O percentual continua sendo:

```text
realizado / meta × 100
```

Valores zero são leituras válidas. A ausência de resultado mantém o estado
`Sem leitura`.

## Segurança operacional

- não há gravação durante o acesso público;
- não há `CREATE TABLE IF NOT EXISTS`;
- não há consulta externa no catálogo;
- se `ppa_resultados` não existir ou estiver indisponível, o catálogo usa o
  fallback de `ppa_indicadores`;
- o payload e os templates existentes permanecem compatíveis.

## Próximo incremento

Implementar uma sincronização explícita e controlada para um indicador por vez.
Ela deverá executar as consultas já usadas pelo dashboard, consolidar o
resultado e gravar em `ppa_resultados`, registrando a execução em
`ppa_sincronizacoes`.

Essa sincronização não deve ser disparada automaticamente por uma requisição
pública.
