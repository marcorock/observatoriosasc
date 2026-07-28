# Manual do Usuario

Atualizado em 2026-07-28. O catálogo e os dashboards PPA descritos abaixo já
estão funcionais; um indicador pode ser sincronizado pelo painel administrativo
ou por comandos técnicos controlados.

## Funcionalidade

Menu principal, dashboard BSC, acesso inicial ao Cad Unico e login administrativo

## Objetivo

Permitir entrar no sistema por uma tela inicial de selecao de modulos e acessar o dashboard BSC ou o modulo inicial do Cad Unico.

## Quem Pode Usar

- admin
- user

Observacao: apenas perfil configurado como `admin` pode salvar o filtro global do dashboard.

## Como Acessar

- Menu principal: `/`
- Dashboard BSC: `/bsc`
- Cad Unico: `/cadunico`
- Login administrativo: `/admin`
- Painel administrativo: `/admin/painel`
- Usuários administrativos: `/admin/usuarios`
- Registros: `/bsc/registros` (rota protegida)
- Novo registro: `/bsc/create`

## Como Usar

1. Acesse a rota `/` para abrir o menu principal.
2. Escolha o modulo desejado clicando diretamente no botao com o nome do tema.
3. Em qualquer tela, use o menu hamburguer no canto direito do header para acessar as opcoes da pagina.
4. O primeiro item do menu e sempre `Home`, para voltar ao menu principal.
5. No BSC, use o item `Filtro de período` do menu para definir um periodo de consulta.
6. Se desejar apenas uma consulta temporaria, aplique o filtro local.
7. Se voce for administrador e quiser manter o periodo para o dashboard, use a opcao de salvar periodo global.
8. Para alternar entre visualizacao em tabela e grafico no dashboard, use o item correspondente no menu da tela.
9. Para consultar todos os registros do BSC, entre por `/admin`, autentique-se e use o atalho `BSC - Registros` no painel administrativo.
10. Na tela de registros, use a busca, a paginacao e as exportacoes para Excel ou PDF.
11. Para cadastrar um novo item, clique em `Novo registro`, preencha os campos obrigatorios e salve.
12. Para editar ou visualizar um item, use os botoes de acao da tabela.
13. Para excluir, use o botao de lixeira e confirme a operacao.

## Resultado Esperado

- O menu principal deve mostrar os modulos disponiveis de forma centralizada.
- O dashboard deve exibir os totais e graficos conforme o periodo selecionado.
- A tabela de registros deve listar os acompanhamentos cadastrados.
- Novos registros devem aparecer na listagem apos o salvamento.
- Edicoes e exclusoes devem refletir na listagem e no dashboard.

## Observacoes

- Os campos obrigatorios no cadastro sao `Estrategia`, `Eixo` e `Situacao`.
- O campo `Data de referencia` influencia os filtros de periodo do dashboard.
- O nome exato do quarto status ainda esta em revisao tecnica entre `Cancelado` e `Suspenso`.
- Ao abrir uma página interna, o sistema exibe `Carregando...` até a próxima
  tela ficar disponível. Cliques repetidos são bloqueados durante a navegação.

## Funcionalidade

Login administrativo

## Objetivo

Permitir autenticacao administrativa por CPF e senha em uma rota nao exposta no menu principal.

## Quem Pode Usar

- admin

## Como Acessar

- `/admin`

## Como Usar

1. Abra diretamente a rota `/admin`.
2. Informe o CPF com mascara `000.000.000-00`.
3. Informe a senha cadastrada.
4. Clique em `Entrar`.
5. Apos o acesso, use `Sair` para encerrar a sessao.

## Resultado Esperado

- O sistema deve autenticar o administrador e redirecionar para `/admin/painel`.
- A rota `/bsc/registros` deve redirecionar para `/admin` quando acessada sem autenticacao.
- O logout deve encerrar a sessao e voltar para `/admin`.
- O painel administrativo deve manter o mesmo header padrao visual do restante do sistema.

## Observacoes

- A rota nao fica visivel no menu publico do sistema.
- E necessario existir um usuario previamente cadastrado na tabela `admin_users`.
- A listagem completa do BSC passa a depender da autenticacao administrativa.

## Funcionalidade

CRUD de usuarios administrativos

## Objetivo

Permitir cadastrar, editar, inativar e excluir acessos administrativos pela propria area reservada.

## Quem Pode Usar

- admin autenticado

## Como Acessar

- `/admin/painel`
- `/admin/usuarios`

## Como Usar

1. Entre em `/admin` e autentique-se.
2. No painel administrativo, clique em `Gerenciar usuários`.
3. Use `Novo usuário` para cadastrar um novo acesso.
4. Preencha nome, CPF, status e senha inicial.
5. Na listagem, use o botão de lápis para editar um usuário existente.
6. Se quiser manter a senha atual na edição, deixe o campo de senha em branco.
7. Use o botão de exclusão apenas para usuários diferentes do que está autenticado.

## Resultado Esperado

- Novos usuários devem ser cadastrados com CPF único e senha válida.
- Usuários existentes devem poder ser atualizados sem quebrar a sessão atual.
- O sistema deve impedir a exclusão do próprio usuário autenticado.

## Funcionalidade

Configuração de fontes e consultas externas

## Objetivo

Permitir que usuários autenticados cadastrem, testem e mantenham as conexões e
consultas que alimentam o módulo PPA.

## Quem Pode Usar

- admin autenticado

## Como Acessar

- `/admin/painel`
- `/admin/bases-externas`
- `/admin/bases-externas/consultas`

## Como Usar

1. Entre em `/admin` e autentique-se.
2. No painel administrativo, abra `Bases externas`.
3. Use `Nova fonte` para cadastrar a conexão e teste-a antes de vinculá-la.
4. Abra `Consultas` para cadastrar ou revisar o SQL permitido.
5. Teste a consulta e confirme o limite e as colunas retornadas.
6. Use a área `PPA > Vínculos` para relacionar indicador e consulta.
7. Exclua apenas cadastros sem dependências e depois de validar os consumidores.

## Resultado Esperado

- A listagem deve mostrar as fontes cadastradas do modulo PPA.
- O cadastro deve gravar registros na tabela `ppa_fontes_dados`.
- O acesso deve ficar restrito a usuarios autenticados no painel administrativo.

## Funcionalidade

Painel administrativo do modulo PPA

## Objetivo

Centralizar a administracao do modulo PPA em um controller proprio, com CRUDs para as entradas manuais e configuracoes estruturais do modulo.

## Quem Pode Usar

- admin autenticado

## Como Acessar

- `/admin/painel`
- `/admin/ppa`

## Como Usar

1. Entre em `/admin` e autentique-se.
2. No painel administrativo, clique em `Gerenciar módulo PPA`.
3. No painel do PPA, escolha o cadastro desejado: fontes, indicadores, relacoes, variaveis ou resultados.
4. Use os atalhos internos do proprio painel PPA para criar, revisar, editar ou excluir registros.

## Resultado Esperado

- O modulo PPA passa a ter uma area administrativa propria.
- Os cadastros manuais centrais ficam prontos antes da primeira tela publica/funcional do indicador.

## Funcionalidade

Dashboard pública inicial do PPA

## Objetivo

Disponibilizar a primeira tela pública do módulo PPA para servir como ponto de entrada dos indicadores que serão acompanhados.

## Quem Pode Usar

- admin
- user

## Como Acessar

- `/`
- `/ppa`

## Como Usar

1. Acesse a rota `/`.
2. Clique no botão `PPA`, posicionado entre `BSC` e `Cad Unico`.
3. Ou abra diretamente a rota `/ppa`.
4. Consulte os cards gerais e a lista inicial dos indicadores disponíveis.

## Resultado Esperado

- A home deve exibir o botão `PPA` como segundo item da esquerda para a direita.
- A rota `/ppa` deve carregar sem autenticação.
- A tela deve funcionar como dashboard geral inicial do módulo.

## Funcionalidade

Página pública individual do indicador do PPA

## Objetivo

Permitir abrir um indicador específico a partir do dashboard do PPA para consultar seus dados principais e o histórico publicado.

## Quem Pode Usar

- admin
- user

## Como Acessar

- `/ppa`
- `/ppa/indicadores/{id}`

## Como Usar

1. Acesse `/ppa`.
2. Na seção `Indicadores disponíveis`, clique em `Abrir`.
3. Consulte os cards principais, as informações institucionais, a memória técnica e o histórico publicado.

## Resultado Esperado

- Cada card de indicador no dashboard deve levar para uma página pública individual.
- A página individual deve abrir sem autenticação.

## Sincronização pelo painel administrativo

### Quem pode usar

- administrador autenticado

### Como usar

1. Acesse `/admin/ppa`.
2. Na tabela `Sincronização dos indicadores`, confira a situação e a data da
   última atualização de cada linha.
3. Na linha desejada, clique em `Sincronizar`.
4. Confirme a operação e aguarde sem fechar a página.
5. Confira a mensagem de sucesso ou erro exibida no painel.
6. Abra o catálogo e o dashboard do indicador para validar os dados.

Essa ação consulta a fonte externa, renova o cache detalhado do dashboard e
depois grava o resultado consolidado do catálogo. Se a consulta externa falhar,
o último cache válido é preservado. Indicadores inativos ou sem vínculos ativos
não são sincronizados.

Uma linha com situação `Pendente` e texto `Nunca sincronizado` ainda não possui
uma sincronização concluída registrada. A data exibida representa o término da
última sincronização bem-sucedida, mesmo quando os números permaneceram iguais.

O usuário que executa o servidor web precisa ter permissão de escrita em
`storage/cache/ppa/queries`. Em instalações novas, configure o diretório para o
usuário ou grupo do servidor web sem conceder acesso público ao conteúdo.

A ação desta tela continua sendo manual. O processo automático correspondente
é descrito na seção seguinte e precisa ser ativado no agendador do servidor.

## Sincronização automática diária

O projeto possui o comando:

```bash
php bin/ppa-scheduled-sync.php --all
```

Ele processa um indicador elegível por vez, atualiza dashboard e catálogo,
ignora indicadores sem vínculo ou de visão geral e continua os demais quando
uma fonte individual falha. Uma trava impede duas execuções simultâneas.

A receita versionada está em:

```text
deploy/cron/observatoriosasc-ppa
```

Ela está preparada para executar diariamente às 04:15 no horário de São Paulo
com o usuário `www-data`. Em um servidor Linux com cron, a instalação deve ser
feita por uma pessoa com permissão administrativa:

```bash
sudo bash deploy/cron/install-ppa-sync-cron.sh \
  /var/www/projects/observatoriosasc
```

As instruções completas para o sysadmin estão em `deploy/cron/README.md`.

O comando deve executar com o mesmo usuário do PHP web, ou com um grupo que
tenha escrita nos arquivos existentes em `storage/cache/ppa/queries`. Executar
com outro usuário pode permitir criar a pasta, mas impedir a atualização das
travas e caches que já pertencem ao servidor web.

O ambiente de desenvolvimento atual não possui serviço `cron`; portanto, ter o
arquivo no projeto não significa que o horário já esteja ativo. Em hospedagens
com painel próprio, cadastre o mesmo comando e selecione o fuso
`America/Sao_Paulo`.

## Sincronização técnica pelo terminal

### Objetivo

Permitir que uma pessoa técnica execute e inspecione separadamente as mesmas
operações pelo terminal.

### Quem pode usar

- pessoa técnica com acesso ao terminal e ao ambiente configurado

### Como usar

1. Execute a prévia de apenas um indicador:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo>
```

2. Compare meta, realizado, percentual e referência com o dashboard.
3. Somente depois da validação, persista explicitamente:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo> --commit
```

4. Confirme o catálogo `/ppa` e o histórico de sincronização.

## Resultado Esperado

- a prévia não grava dados;
- o commit usa transação;
- resultados idênticos não são duplicados;
- erros não exibem credenciais ou mensagens brutas do banco;
- o catálogo continua sem consultas externas.

## Observacoes

- Nunca disparar sincronização automaticamente por uma rota pública.
- Processar um indicador por vez.
- O indicador de visão geral encerra sem consultar a fonte externa.

## Cache manual dos dashboards PPA

Para atualizar os arquivos locais das consultas ativas de um indicador:

```bash
php bin/ppa-dashboard-cache.php <slug-ou-codigo>
```

Para atualizar todos os indicadores públicos sequencialmente:

```bash
php bin/ppa-dashboard-cache.php --all
```

O comando:

- no modo individual, processa somente o indicador informado;
- executa todas as consultas antes de iniciar a gravação;
- não altera `ppa_resultados`, `ppa_sincronizacoes` ou outras tabelas;
- mantém os arquivos fora do diretório público e do Git;
- informa entradas, linhas e tempo total;
- no lote, continua os demais indicadores após uma falha e retorna exit code
  diferente de zero se o resultado for parcial.

Após uma sincronização bem-sucedida, os dashboards usam automaticamente a
entrada compatível. Se o arquivo estiver ausente, corrompido ou incompatível
com SQL e limite atuais, a consulta externa é executada normalmente.

O acesso público nunca cria nem renova arquivos. Para atualizar os dados, a
pessoa técnica deve executar novamente o comando manual.

Para agendamento, use o comando `--all` com uma trava do sistema, como `flock`,
e monitore stdout, stderr e exit code. Um exemplo completo está em
`docs/performance/dashboard-file-cache.md`.

## Adicionar um novo indicador PPA

Fluxo operacional recomendado:

1. Cadastre o indicador no painel administrativo.
2. Cadastre ou revise as consultas externas necessárias.
3. Crie os vínculos ativos e defina `campo_resultado` conforme o tipo de
   dashboard.
4. Abra o dashboard sem cache e valide cards, gráficos, tabelas e filtros.
5. Execute a prévia das métricas do catálogo:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo>
```

6. Compare meta, realizado, percentual e referência. Depois da validação,
   persista explicitamente:

```bash
php bin/ppa-sync-preview.php <slug-ou-codigo> --commit
```

7. Gere as entradas detalhadas do dashboard:

```bash
php bin/ppa-dashboard-cache.php <slug-ou-codigo>
```

8. Revalide HTML, endpoint JSON e filtros. Em cache hit, não deve existir
   consulta externa.
9. Inclua o indicador no lote programado somente depois da homologação manual.

As métricas do catálogo e o cache detalhado são independentes:

- `ppa-sync-preview.php --commit` atualiza meta e realizado exibidos no
  catálogo;
- `ppa-dashboard-cache.php` atualiza as linhas usadas em gráficos, tabelas e
  filtros.

Alterações de SQL, fonte ou limite deixam entradas anteriores incompatíveis e
fazem o dashboard usar o fallback externo até uma nova sincronização.
