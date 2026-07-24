# Manual do Usuario

Atualizado em 2026-07-24. O catálogo e os dashboards PPA descritos abaixo já
estão funcionais; a sincronização é uma operação técnica controlada.

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

Configuracao de fontes de dados do PPA

## Objetivo

Permitir que usuarios autenticados na area administrativa cadastrem e mantenham as origens de dados que alimentarao o modulo PPA.

## Quem Pode Usar

- admin autenticado

## Como Acessar

- `/admin/painel`
- `/admin/ppa/fontes`

## Como Usar

1. Entre em `/admin` e autentique-se.
2. No painel administrativo, clique em `Configurar fontes PPA`.
3. Use `Nova fonte` para abrir o formulario de cadastro.
4. Informe nome, tipo da fonte, status e, quando existir, banco e tabela/origem.
5. Salve o cadastro e revise a listagem.
6. Use o botao de lapis para editar uma fonte existente.
7. Use o botao de lixeira para excluir um cadastro de teste ou obsoleto.

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

## Funcionalidade

Sincronização controlada das métricas do catálogo PPA

## Objetivo

Permitir que uma pessoa técnica valide e persista o resultado consolidado de um
indicador sem executar consultas externas durante o acesso público ao catálogo.

## Quem Pode Usar

- pessoa técnica com acesso ao terminal e ao ambiente configurado

## Como Usar

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

O comando:

- processa somente um indicador;
- executa todas as consultas antes de iniciar a gravação;
- não altera `ppa_resultados`, `ppa_sincronizacoes` ou outras tabelas;
- mantém os arquivos fora do diretório público e do Git;
- informa entradas, linhas e tempo total;
- não oferece `--all` nesta etapa.

Após uma sincronização bem-sucedida, os dashboards usam automaticamente a
entrada compatível. Se o arquivo estiver ausente, corrompido ou incompatível
com SQL e limite atuais, a consulta externa é executada normalmente.

O acesso público nunca cria nem renova arquivos. Para atualizar os dados, a
pessoa técnica deve executar novamente o comando manual.
