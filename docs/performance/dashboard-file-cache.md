# Decisão de cache em arquivo para dashboards PPA

Decisão registrada em 2026-07-24. O armazenamento isolado foi implementado no
commit `b66a4c0` e o comando manual para um indicador no commit `4df0c63`.
A leitura cache-first foi integrada no commit `9baaa3d`.

## Estado da implementação

`PpaQueryFileCache` já oferece:

- diretório padrão fora de `public`;
- identidade por versão, fonte, consulta, hash do SQL e limite;
- envelope JSON validado na leitura;
- trava compartilhada para leitura e exclusiva para escrita;
- arquivo temporário no mesmo diretório e substituição por `rename()`;
- preservação do arquivo anterior quando a nova entrada é inválida;
- rejeição segura de arquivo ausente, corrompido ou incompatível.

O diretório `storage/cache/` foi incluído no `.gitignore`.

`PpaQueryFileCacheTest.php` cobre 16 assertions. Na verificação de 2026-07-24,
o armazenamento e o sincronizador possuem 26 assertions. Com os testes de hit
e fallback no serviço de consultas, os onze testes do projeto passaram com 174
assertions.

`PpaQueryCacheSynchronizer`:

- recebe apenas os vínculos ativos do indicador selecionado;
- executa todas as consultas antes de iniciar as gravações;
- usa limite 500 para bases e fotografias e 5.000 para as demais séries;
- não grava nenhuma entrada quando uma consulta falha antes da etapa de escrita;
- relata chaves, IDs, limites, linhas e horário das entradas gravadas.

O comando disponível é:

```bash
php bin/ppa-dashboard-cache.php <slug-ou-codigo>
```

Ele aceita somente um indicador. `--all` ainda não existe.

Validação real em 2026-07-24:

```text
indicador: PPA-CREAS-MULHERES-F1
entradas gravadas: 1
linhas: 13
tempo total: 65,729 ms
```

Essa validação criou somente arquivos locais ignorados pelo Git e não alterou
o banco de dados.

## Leitura cache-first implementada

`PpaLinkedQueryService` agora:

1. resolve consulta e fonte como antes;
2. procura uma entrada com fonte, consulta, hash do SQL e limite compatíveis;
3. em hit, retorna as linhas armazenadas e não chama o executor externo;
4. em ausência, corrupção ou incompatibilidade, executa a consulta externa
   original;
5. nunca grava ou renova cache durante a requisição.

O retorno interno informa `cache.hit`, horário de geração e quantidade de
linhas para observabilidade, sem alterar os payloads entregues às views.

Validação real do hit em 2026-07-24:

```text
indicador: PPA-CREAS-MULHERES-F1
meta: 555
realizado: 73
tempo total cache-first: 46,381 ms
consultas externas registradas: 0
```

## Decisão

Adotar, em incremento posterior, um cache local em arquivo para as linhas
agregadas das consultas vinculadas aos dashboards. A atualização será explícita
por comando CLI e poderá ser agendada pelo sistema operacional.

Redis fica fora do escopo. Ele só deve ser reconsiderado se houver múltiplas
instâncias da aplicação, volume concorrente comprovado ou necessidade de
invalidação distribuída.

## Por que armazenar linhas e não o payload final

Os builders atuais recebem as linhas das fontes e aplicam filtros de CRAS,
região, unidade e mês. Armazenar essas linhas agregadas permite:

- reutilizar exatamente os mesmos builders e regras;
- atender filtros interativos sem nova consulta externa;
- evitar um arquivo para cada combinação de filtros;
- preservar gráficos, tabelas, aliases, cálculos e ordenação;
- invalidar pelo vínculo/consulta, não pela tela.

Os arquivos não devem armazenar SQL, credenciais ou senhas. Somente metadados
técnicos mínimos e as linhas agregadas já retornadas ao dashboard.

## Fluxo proposto

```text
Sincronização manual ou programada
→ resolve indicador, vínculos, consultas e fontes
→ executa cada consulta externa
→ valida o retorno completo
→ grava arquivo temporário
→ renomeia atomicamente para o arquivo definitivo

Requisição pública
→ resolve o vínculo
→ tenta ler e validar o arquivo
→ entrega as linhas ao builder atual
→ em ausência ou corrupção, usa a consulta externa existente
→ nunca atualiza o cache durante a requisição pública
```

O último arquivo válido deve ser preservado quando uma sincronização falhar.

## Identidade e invalidação

Cada entrada deve ser identificada por:

- ID da fonte;
- ID da consulta;
- hash SHA-256 do SQL normalizado;
- limite solicitado;
- versão do formato do cache.

Assim, alterações de SQL, fonte, limite ou formato deixam de reutilizar
automaticamente um arquivo incompatível.

Envelope sugerido:

```json
{
  "schema_version": 1,
  "generated_at": "2026-07-24T12:00:00-03:00",
  "source_id": 1,
  "query_id": 16,
  "query_hash": "sha256",
  "limit": 500,
  "row_count": 15,
  "rows": []
}
```

## Armazenamento e segurança

Diretório sugerido:

```text
storage/cache/ppa/queries/
```

Requisitos:

- ficar fora de `public`;
- não ser versionado;
- permitir escrita somente ao usuário da aplicação e ao usuário do agendador;
- usar escrita em arquivo temporário e `rename()` atômico;
- usar trava por entrada durante a atualização;
- validar JSON, versão, identidade e contagem antes da leitura;
- não apagar o último arquivo válido antes de concluir o substituto.

## Atualização

Interface CLI sugerida:

```bash
php bin/ppa-dashboard-cache.php <slug-ou-codigo>
php bin/ppa-dashboard-cache.php --all
```

O primeiro incremento deve oferecer apenas execução manual. O agendamento deve
reutilizar exatamente esse comando e ser configurado fora do repositório, por
cron ou mecanismo equivalente.

Frequência inicial sugerida:

- consultas CECAD: uma vez ao dia ou após a atualização oficial da fonte;
- consultas RMA: uma vez ao dia durante o período de alimentação;
- execução manual disponível para atualização extraordinária.

A frequência definitiva deve seguir a periodicidade real das fontes, não um TTL
arbitrariamente curto.

## Leitura, ausência e dados antigos

Primeira política recomendada:

- cache válido: usar o arquivo;
- cache ausente, incompatível ou corrompido: executar a consulta externa atual;
- sincronização com falha: manter o arquivo anterior;
- arquivo antigo: continuar disponível e expor `generated_at` para
  observabilidade, sem renovação automática na requisição.

Depois da homologação, pode-se decidir se determinados indicadores devem falhar
de forma controlada em vez de consultar a fonte ao vivo. Isso não faz parte do
primeiro incremento.

## Ganho esperado

Com cache aquecido, as consultas externas deixam de participar da requisição.
O maior benefício esperado está em:

- atualização cadastral, atualmente com mediana de 4.041,3 ms;
- fotografia familiar simulada, atualmente com mediana de 12.997,9 ms;
- consulta simples, atualmente com mediana de 740,2 ms.

O dashboard mensal por unidade, com mediana de 71,6 ms, terá ganho absoluto
menor.

## Riscos e controles

| Risco | Controle |
| --- | --- |
| Dados desatualizados | registrar e exibir `generated_at`; agendamento alinhado à fonte |
| Arquivo parcial | temporário + `rename()` atômico |
| Corrupção | validação de envelope, JSON e contagem |
| Concorrência na atualização | trava por chave |
| SQL alterado usando arquivo antigo | hash do SQL na identidade |
| Falha da fonte durante sincronização | preservar o último arquivo válido |
| Disco cheio ou sem permissão | erro explícito no CLI; requisição mantém fallback atual |
| Várias instâncias com discos diferentes | não suportado inicialmente; reconsiderar armazenamento compartilhado |

## Alternativas avaliadas

### Revisão do SQL CECAD

Continua recomendada e pode reduzir custo de sincronização e fallback. Não
precisa bloquear o cache em arquivo, mas deve ser tratada separadamente com
plano de execução e equivalência dos resultados.

### Pushdown de filtros

Pode ajudar consultas filtradas, porém exige parâmetros seguros no executor e
talvez não reduza os agrupamentos caros. Fica como candidato posterior.

### Reutilização de conexões

Tem benefício pequeno diante dos 4–13 segundos das consultas CECAD. Baixa
prioridade.

### Redis

Não aprovado neste momento. O custo operacional não se justifica para uma
aplicação de instância única sem métricas de concorrência.

## Ordem de implementação recomendada

1. Concluído — criar armazenamento de arquivo com validação, trava e escrita
   atômica.
2. Concluído — criar testes unitários de hit, ausência, corrupção e
   substituição.
3. Concluído — criar comando manual para um indicador.
4. Concluído — integrar leitura cache-first com fallback externo, sem renovação
   pública.
5. Próximo — medir novamente e homologar filtros e equivalência.
6. Adicionar `--all` e documentar exemplo de agendamento.
7. Revisar os planos das consultas CECAD como frente independente.
