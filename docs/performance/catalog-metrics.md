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

## Prévia sem gravação

Antes de implementar persistência, a consolidação pode ser validada por CLI:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo>
```

O comando:

- executa o mesmo pipeline do dashboard;
- aceita somente um indicador;
- não grava em `ppa_resultados` ou `ppa_sincronizacoes`;
- retorna erro para tipos de dashboard ainda não mapeados;
- imprime meta, realizado, percentual, referência e tempo total.

Indicador piloto validado:

```text
PPA-CRAS-ATUALIZACAO-C3
meta quantitativa: 49185,25
realizado: 11480
percentual atingido: 23,34033068857025
referência: 2026-05-08
```

## Persistência explícita

Depois de revisar a prévia, a gravação é solicitada explicitamente:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo> --commit
```

A operação:

1. valida novamente todos os campos consolidados;
2. inicia uma transação;
3. cria uma execução em `ppa_sincronizacoes`;
4. procura um resultado validado/publicado idêntico;
5. insere em `ppa_resultados` somente quando houve mudança;
6. conclui a sincronização;
7. confirma a transação.

Qualquer exceção durante a persistência executa rollback. A mensagem bruta do
banco não é exibida pelo comando.

## Primeira sincronização controlada

O indicador `PPA-CRAS-ATUALIZACAO-C3` foi sincronizado em 2026-07-23:

```text
resultado inserido: sim
status: validado
meta quantitativa: 49185,2500
valor realizado: 11480,0000
ano de referência: 2026
```

Uma segunda execução com os mesmos valores foi reconhecida como idêntica. Ela
foi registrada no histórico de sincronizações com zero inserções e não duplicou
`ppa_resultados`.

Após a sincronização, `/ppa` permaneceu HTTP 200 e passou a mostrar a meta e o
realizado do indicador usando somente a leitura local.

## Segunda sincronização controlada

O indicador `PPA-ACOMPANHAR-BPC-PAIF` foi sincronizado individualmente depois
de a prévia CLI ser comparada com o endpoint JSON do dashboard:

```text
base total: 13228
meta quantitativa: 1322,8000
valor realizado: 481,0000
percentual atingido: 36,36226186876323
referência: 2026-05-08
```

O resultado foi gravado com status `validado`, a sincronização foi concluída
com uma inserção e o catálogo permaneceu HTTP 200. Uma repetição para testar
idempotência não foi executada, pois esse comportamento já havia sido validado
com o indicador piloto e geraria carga externa desnecessária.

## Terceira sincronização controlada

O indicador `PPA-ACOMPANHAR-FAMILIAS-MEIO-SM-PAIF` também foi comparado com seu
endpoint JSON antes da persistência:

```text
base total: 57865
meta quantitativa: 5786,5000
valor realizado: 1833,0000
percentual atingido: 31,677179642270804
referência: 2026-05-08
```

O resultado foi gravado com status `validado`, sem repetição do comando, e o
catálogo permaneceu HTTP 200.

## Quarta sincronização controlada

O indicador `PPA-ACOMPANHAR-FAMILIAS-PBF` foi validado pelo mesmo procedimento:

```text
base total: 35571
meta quantitativa: 3557,1000
valor realizado: 1069,0000
percentual atingido: 30,05257091450901
referência: 2026-05-08
```

A prévia coincidiu com o endpoint JSON. O resultado foi gravado uma única vez
com status `validado`, e o catálogo permaneceu HTTP 200.
