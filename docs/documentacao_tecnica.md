# Documentacao Tecnica

Atualizado em 2026-07-24. Para estado da branch, testes e sequência de retomada,
consultar `docs/continuity.md`.

## Arquitetura Geral

O projeto segue arquitetura MVC em PHP com carregamento PSR-4 via Composer. O roteamento e feito com `pecee/simple-router`, as views usam Twig, a conexao e a montagem de queries ficam centralizadas em classes proprias, e a aplicacao e servida a partir do ponto de entrada em `public`.

Estrutura principal identificada:

- `app/Controllers`: controladores HTTP
- `app/Models`: models por modulo
- `app/Views`: templates Twig e componentes visuais
- `app/Database`: conexao e query builder
- `app/Services`: serviços e builders extraídos do módulo PPA
- `app/Utils`: helpers, token de formulario e autenticacao administrativa
- `routes/web.php`: rotas web
- `core/app.php`: bootstrap complementar da aplicacao
- `database`: scripts SQL de referencia

### Seleção da conexão local

`public/index.php` carrega o `.env` antes de incluir `core/app.php`. O switch de
`core/app.php` seleciona credenciais específicas para SASC-SA, OSC e CadÚnico.
Para as demais rotas, inclusive PPA e BSC, o caso padrão repassa `DB_HOST`,
`DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` e `DB_CHARSET` do ambiente para as
constantes consumidas por `DataConect`.

Os valores de um ambiente Docker ou remoto pertencem ao `.env`; não devem ser
fixados como defaults globais em `DataConect.php`.

Dependencias identificadas em `composer.json`:

- `pecee/simple-router`
- `twig/twig`
- `twbs/bootstrap`
- `twbs/bootstrap-icons`
- `vlucas/phpdotenv`
- `mobiledetect/mobiledetectlib`

## Rotas

| Metodo | Rota | Controller | Permissao | Descricao |
|---|---|---|---|---|
| GET | `/admin` | `AdminController@login` | rota oculta | Tela de login administrativo |
| POST | `/admin/login` | `AdminController@authenticate` | rota oculta | Autentica administrador por CPF e senha |
| GET | `/admin/painel` | `AdminController@panel` | sessao admin | Tela inicial autenticada da area administrativa |
| GET | `/admin/usuarios` | `AdminController@users` | sessao admin | Lista usuarios administrativos |
| GET | `/admin/usuarios/novo` | `AdminController@createUser` | sessao admin | Formulario de cadastro de usuario administrativo |
| POST | `/admin/usuarios/store` | `AdminController@storeUser` | sessao admin com token | Persiste novo usuario administrativo |
| GET | `/admin/usuarios/editar/{id}` | `AdminController@editUser` | sessao admin | Formulario de edicao de usuario administrativo |
| POST | `/admin/usuarios/update/{id}` | `AdminController@updateUser` | sessao admin com token | Atualiza usuario administrativo |
| POST | `/admin/usuarios/delete/{id}` | `AdminController@deleteUser` | sessao admin com token | Exclui usuario administrativo |
| POST | `/admin/logout` | `AdminController@logout` | sessao admin com token | Encerra a sessao administrativa |
| GET | `/admin/ppa` | `PpaAdminController@dashboard` | sessao admin | Painel administrativo proprio do modulo PPA |
| GET | `/admin/ppa/fontes` | `PpaAdminController@dataSources` | sessao admin | Lista as fontes de dados do modulo PPA |
| GET | `/admin/ppa/fontes/novo` | `PpaAdminController@createDataSource` | sessao admin | Formulario de cadastro de fonte PPA |
| POST | `/admin/ppa/fontes/store` | `PpaAdminController@storeDataSource` | sessao admin com token | Persiste fonte de dados do PPA |
| GET | `/admin/ppa/fontes/editar/{id}` | `PpaAdminController@editDataSource` | sessao admin | Formulario de edicao de fonte PPA |
| POST | `/admin/ppa/fontes/update/{id}` | `PpaAdminController@updateDataSource` | sessao admin com token | Atualiza fonte de dados do PPA |
| POST | `/admin/ppa/fontes/delete/{id}` | `PpaAdminController@deleteDataSource` | sessao admin com token | Exclui fonte de dados do PPA |
| GET | `/admin/ppa/indicadores` | `PpaAdminController@indicators` | sessao admin | Lista os indicadores do modulo PPA |
| GET | `/admin/ppa/indicadores/novo` | `PpaAdminController@createIndicator` | sessao admin | Formulario de cadastro de indicador PPA |
| POST | `/admin/ppa/indicadores/store` | `PpaAdminController@storeIndicator` | sessao admin com token | Persiste indicador do PPA |
| GET | `/admin/ppa/indicadores/editar/{id}` | `PpaAdminController@editIndicator` | sessao admin | Formulario de edicao de indicador PPA |
| POST | `/admin/ppa/indicadores/update/{id}` | `PpaAdminController@updateIndicator` | sessao admin com token | Atualiza indicador do PPA |
| POST | `/admin/ppa/indicadores/delete/{id}` | `PpaAdminController@deleteIndicator` | sessao admin com token | Exclui indicador do PPA |
| GET | `/admin/ppa/relacoes` | `PpaAdminController@indicatorSources` | sessao admin | Lista relacoes entre indicadores e fontes do PPA |
| GET | `/admin/ppa/relacoes/novo` | `PpaAdminController@createIndicatorSource` | sessao admin | Formulario de cadastro de relacao indicador x fonte |
| POST | `/admin/ppa/relacoes/store` | `PpaAdminController@storeIndicatorSource` | sessao admin com token | Persiste relacao indicador x fonte |
| GET | `/admin/ppa/relacoes/editar/{id}` | `PpaAdminController@editIndicatorSource` | sessao admin | Formulario de edicao de relacao indicador x fonte |
| POST | `/admin/ppa/relacoes/update/{id}` | `PpaAdminController@updateIndicatorSource` | sessao admin com token | Atualiza relacao indicador x fonte |
| POST | `/admin/ppa/relacoes/delete/{id}` | `PpaAdminController@deleteIndicatorSource` | sessao admin com token | Exclui relacao indicador x fonte |
| GET | `/admin/ppa/variaveis` | `PpaAdminController@variables` | sessao admin | Lista variaveis dos indicadores do PPA |
| GET | `/admin/ppa/variaveis/novo` | `PpaAdminController@createVariable` | sessao admin | Formulario de cadastro de variavel PPA |
| POST | `/admin/ppa/variaveis/store` | `PpaAdminController@storeVariable` | sessao admin com token | Persiste variavel do PPA |
| GET | `/admin/ppa/variaveis/editar/{id}` | `PpaAdminController@editVariable` | sessao admin | Formulario de edicao de variavel PPA |
| POST | `/admin/ppa/variaveis/update/{id}` | `PpaAdminController@updateVariable` | sessao admin com token | Atualiza variavel do PPA |
| POST | `/admin/ppa/variaveis/delete/{id}` | `PpaAdminController@deleteVariable` | sessao admin com token | Exclui variavel do PPA |
| GET | `/admin/ppa/resultados` | `PpaAdminController@results` | sessao admin | Lista resultados manuais e consolidados do PPA |
| GET | `/admin/ppa/resultados/novo` | `PpaAdminController@createResult` | sessao admin | Formulario de lancamento manual de resultado |
| POST | `/admin/ppa/resultados/store` | `PpaAdminController@storeResult` | sessao admin com token | Persiste resultado manual do PPA |
| GET | `/admin/ppa/resultados/editar/{id}` | `PpaAdminController@editResult` | sessao admin | Formulario de edicao de resultado do PPA |
| POST | `/admin/ppa/resultados/update/{id}` | `PpaAdminController@updateResult` | sessao admin com token | Atualiza resultado do PPA |
| POST | `/admin/ppa/resultados/delete/{id}` | `PpaAdminController@deleteResult` | sessao admin com token | Exclui resultado do PPA |
| GET | `/` | `IndexController@index` | publica/local | Menu principal do sistema |
| GET | `/bsc` | `BscController@index` | publica/local | Dashboard principal do BSC |
| GET | `/ppa` | `PpaController@index` | publica/local | Dashboard geral inicial do modulo PPA |
| GET | `/ppa/{slug}` | `PpaController@show` | publica/local | Dashboard público de um indicador do PPA |
| GET | `/ppa/{slug}/data` | `PpaController@dashboardData` | publica/local | Payload JSON para filtros do dashboard PPA |
| GET | `/cadunico` | `CadunicoController@index` | publica/local | Tela inicial do modulo Cad Unico |
| GET | `/bsc/dashboard-data` | `BscController@dashboardData` | publica/local | Retorna payload JSON do dashboard |
| POST | `/bsc/filter/local` | `BscController@setLocalPeriod` | publica/local | Aplica filtro de periodo na sessao |
| POST | `/bsc/filter/local/clear` | `BscController@clearLocalPeriod` | publica/local | Remove filtro local da sessao |
| POST | `/bsc/filter/global` | `BscController@setGlobalPeriod` | admin por perfil em `.env` | Persiste periodo global do dashboard |
| GET | `/bsc/registros` | `BscController@records` | sessao admin | Lista completa dos registros BSC |
| GET | `/bsc/create` | `BscController@create` | publica/local | Formulario de criacao |
| POST | `/bsc/store` | `BscController@store` | publica/local com token | Persiste novo registro BSC |
| GET | `/bsc/show/{id}` | `BscController@show` | publica/local | Exibe um registro BSC |
| GET | `/bsc/edit/{id}` | `BscController@edit` | publica/local | Formulario de edicao |
| POST | `/bsc/update/{id}` | `BscController@update` | publica/local com token | Atualiza registro existente |
| POST | `/bsc/delete/{id}` | `BscController@delete` | publica/local com token | Exclui registro existente |

## Controllers

### AdminController

- Responsabilidade: controlar login, sessao, painel inicial, CRUD de usuarios administrativos, configuracao das fontes PPA e logout da area administrativa
- Metodos principais:
  - `login()`
  - `authenticate()`
  - `panel()`
  - `users()`
  - `createUser()`
  - `storeUser()`
  - `editUser($id)`
  - `updateUser($id)`
  - `deleteUser($id)`
  - `logout()`
- Models utilizados:
  - `AdminModel`
- Views renderizadas:
  - `admin/login.html`
  - `admin/panel.html`
  - `admin/users/index.html`
  - `admin/users/create.html`
  - `admin/users/edit.html`
- Observacoes:
  - rota nao aparece em menus publicos
  - usa CPF com mascara no front e somente digitos no back
  - usa `password_verify` para validar senha em hash
  - usa `adminRequireAuth()` para proteger o painel autenticado
  - impede autoexclusao e autoinativacao do usuario autenticado na sessao atual
  - centraliza no painel administrativo o acesso a configuracao das fontes do modulo PPA

### IndexController

- Responsabilidade: exibir a home principal com acesso aos modulos disponiveis
- Metodos principais:
  - `index()`
  - `show($id)`
- Models utilizados:
  - nenhum
- Views renderizadas:
  - `home/index.html`
- Observacoes:
  - a rota `/` deixa de apontar diretamente para o dashboard BSC
  - os atalhos atuais da home levam para `BSC` e `Cad Unico`

### BscController

- Responsabilidade: orquestrar dashboard, filtros de periodo e CRUD do modulo BSC
- Metodos principais:
  - `index()`
  - `dashboardData()`
  - `setLocalPeriod()`
  - `clearLocalPeriod()`
  - `setGlobalPeriod()`
  - `records()`
  - `create()`
  - `store()`
  - `show($id)`
  - `edit($id)`
  - `update($id)`
  - `delete($id)`
- Models utilizados:
  - `BscModel`
  - `DashboardConfigModel`
- Views renderizadas:
  - `bsc/index.html`
  - `bsc/list.html`
  - `bsc/create.html`
  - `bsc/show.html`
  - `bsc/edit.html`
- Observacoes:
  - usa sessao para filtro local (`dashboard_local_period`)
  - usa `.env` com `DASHBOARD_USER_PROFILE` para liberar persistencia do filtro global
  - responde JSON para atualizacao dinamica do dashboard
  - a rota `records()` exige sessao administrativa e redireciona para `/admin` se o usuario nao estiver autenticado

### CadunicoController

- Responsabilidade: disponibilizar a tela inicial do modulo Cad Unico
- Metodos principais:
  - `index()`
- Models utilizados:
  - `CadunicoModel`
- Views renderizadas:
  - `cadunico/index.html`
- Observacoes:
  - usa o retorno atual placeholder do model para indicar o estado inicial do modulo

### PpaController

- Responsabilidade: orquestrar catálogo, dashboards públicos e endpoint JSON do
  PPA, delegando catálogo, consultas, resolução e payloads extraídos
- Metodos principais:
  - `index()`
  - `show($slug)`
  - `dashboardData($slug)`
- Models utilizados:
  - `PpaIndicatorModel`
  - `PpaIndicatorQueryModel`
  - `PpaResultModel`
- Serviços utilizados:
  - `PpaCatalogService`
  - `PpaLinkedQueryService`
  - `PpaDashboardResolver`
  - `PpaSingleQueryPayloadBuilder`
  - `PpaMonthlyUnitPayloadBuilder`
  - `PpaFamilyRmaPayloadBuilder`
- Views renderizadas:
  - `ppa/catalog.html`
  - templates específicos em `ppa/`
- Observacoes:
  - `/ppa` não executa consultas externas
  - dashboards detalhados executam de uma a três consultas conforme o tipo
  - filtros AJAX repetem as consultas do dashboard correspondente
  - os builders especializados de fotografia familiar e atualização cadastral
    ainda permanecem no controller

### Serviços do PPA

- `PpaCatalogService`: compõe métricas e resumo do catálogo usando resultados
  locais em lote.
- `PpaLinkedQueryService`: resolve e executa consultas externas vinculadas.
- `PpaDashboardResolver`: classifica o tipo de dashboard e seus vínculos.
- `PpaSingleQueryPayloadBuilder`: payload de consulta simples.
- `PpaMonthlyUnitPayloadBuilder`: payload mensal por unidade.
- `PpaFamilyRmaPayloadBuilder`: payload de progresso familiar regular.

As extrações preservam rotas, templates, nomes dos campos e fórmulas. O próximo
serviço será `PpaFamilySnapshotRmaPayloadBuilder`.

### PpaAdminController

- Responsabilidade: concentrar o painel administrativo do modulo PPA e os CRUDs manuais de fontes, indicadores, relacoes, variaveis e resultados
- Metodos principais:
  - `dashboard()`
  - `dataSources()`
  - `createDataSource()`
  - `storeDataSource()`
  - `editDataSource($id)`
  - `updateDataSource($id)`
  - `deleteDataSource($id)`
  - `indicators()`
  - `createIndicator()`
  - `storeIndicator()`
  - `editIndicator($id)`
  - `updateIndicator($id)`
  - `deleteIndicator($id)`
  - `indicatorSources()`
  - `createIndicatorSource()`
  - `storeIndicatorSource()`
  - `editIndicatorSource($id)`
  - `updateIndicatorSource($id)`
  - `deleteIndicatorSource($id)`
  - `variables()`
  - `createVariable()`
  - `storeVariable()`
  - `editVariable($id)`
  - `updateVariable($id)`
  - `deleteVariable($id)`
  - `results()`
  - `createResult()`
  - `storeResult()`
  - `editResult($id)`
  - `updateResult($id)`
  - `deleteResult($id)`
- Models utilizados:
  - `PpaFonteDadosModel`
  - `PpaIndicadorModel`
  - `PpaIndicadorFonteModel`
  - `PpaIndicadorVariavelModel`
  - `PpaResultadoModel`
- Views renderizadas:
  - `admin/ppa/index.html`
  - `admin/ppa/fontes/*`
  - `admin/ppa/indicadores/*`
  - `admin/ppa/relacoes/*`
  - `admin/ppa/variaveis/*`
  - `admin/ppa/resultados/*`

## Models

### AdminModel

- Tabela principal: `admin_users`
- Responsabilidade: localizar administradores ativos por CPF e manter o cadastro de usuarios da area administrativa
- Metodos principais:
  - `findActiveByCpf(string $cpf)`
  - `readAll()`
  - `readById(int $id)`
  - `create(array $data)`
  - `updateById(int $id, array $data)`
  - `deleteById(int $id)`
  - `cpfExists(string $cpf, ?int $ignoreId = null)`
- Regras identificadas:
  - consulta apenas usuarios com `ativo = 1`
  - usa prepared statement via PDO

### BscModel

- Tabela principal: `bsc`
- Responsabilidade: leitura, agregacao, criacao, atualizacao e exclusao de registros BSC
- Metodos principais:
  - `readAll(?string $dataInicio = null, ?string $dataFim = null)`
  - `readById(int $id)`
  - `create(array $data)`
  - `updateById(int $id, array $data)`
  - `deleteById(int $id)`
  - `countColumn($column = null, ?string $dataInicio = null, ?string $dataFim = null)`
- Regras identificadas:
  - `estrategia`, `eixo` e `situacao` sao obrigatorios
  - `status_detalhado` e `data_referencia` sao opcionais
  - aceita hoje os status `Nao iniciado`, `Em andamento`, `Concluido` e `cancelado` em nivel de model

### DashboardConfigModel

- Tabela principal: `dashboard_config`
- Responsabilidade: ler e persistir o periodo global do dashboard
- Metodos principais:
  - `getGlobalPeriod()`
  - `updateGlobalPeriod(?string $dataInicio = null, ?string $dataFim = null)`
- Regras identificadas:
  - cria a tabela automaticamente se ela ainda nao existir
  - grava sempre o registro `id = 1`

### PpaFonteDadosModel

- Tabela principal: `ppa_fontes_dados`
- Responsabilidade: listar, cadastrar, editar, excluir e validar fontes de dados do modulo PPA
- Metodos principais:
  - `readAll()`
  - `readById(int $id)`
  - `countAll()`
  - `create(array $data)`
  - `updateById(int $id, array $data)`
  - `deleteById(int $id)`
  - `nomeExists(string $nome, ?int $ignoreId = null)`
- Regras identificadas:
  - garante a existencia do schema PPA ao inicializar o model
  - restringe `tipo_fonte` aos valores previstos no schema
  - valida a unicidade do nome da fonte

### PpaIndicadorModel

- Tabela principal: `ppa_indicadores`
- Responsabilidade: CRUD do cadastro principal dos indicadores do modulo PPA

### PpaIndicadorFonteModel

- Tabela principal: `ppa_indicador_fontes`
- Responsabilidade: CRUD das relacoes entre indicadores e fontes com o papel funcional de cada origem

### PpaIndicadorVariavelModel

- Tabela principal: `ppa_indicador_variaveis`
- Responsabilidade: CRUD das variaveis tecnicas que compoem os calculos do modulo PPA

### PpaResultadoModel

- Tabela principal: `ppa_resultados`
- Responsabilidade: CRUD dos lancamentos manuais e resultados consolidados do modulo PPA

### Outros models presentes

- `CadunicoModel.php`
- `OscModel.php`
- `PopwebModel.php`
- `SascsaModel.php`
- `SascsaModel copy.php`

Observacao: esses models ainda nao aparecem ligados a rotas ativas no estado atual identificado.

## Views

### `app/Views/home/index.html`

- Tela: menu principal do sistema
- Escopo: navegacao inicial
- Dados recebidos:
  - `name`
  - `description`
  - `modules`
- Observacoes:
  - usa a logo oficial em `/assets/img/2cm-Brasão.png`
  - exibe dois modulos centrais: BSC e Cad Unico
  - cada modulo possui apenas bloco visual e botao com o titulo, sem texto descritivo
  - usa o mesmo header compartilhado das demais telas

### `app/Views/bsc/index.html`

- Tela: dashboard principal BSC
- Escopo: visualizacao executiva
- Dados recebidos:
  - `name`
  - `description`
  - `dados`
  - `periodo`
  - `is_admin`

### `app/Views/bsc/list.html`

- Tela: tabela completa de registros
- Escopo: operacao e consulta
- Dados recebidos:
  - `name`
  - `description`
  - `erro`
  - `dados`
- Observacoes:
  - usa DataTables com exportacao Excel e PDF
  - possui acoes de visualizar, editar e excluir

### `app/Views/bsc/create.html`

- Tela: criacao de registro BSC
- Escopo: cadastro
- Dados recebidos:
  - `name`
  - `description`
  - `erro`
  - `dados`

### `app/Views/bsc/edit.html`

- Tela: edicao de registro BSC
- Escopo: manutencao
- Dados recebidos:
  - `name`
  - `description`
  - `erro`
  - `dados`

### `app/Views/bsc/show.html`

- Tela: visualizacao individual do registro
- Escopo: consulta detalhada
- Dados recebidos:
  - `name`
  - `description`
  - `dados`

### `app/Views/a_modulos/header.twig`

- Tela: cabecalho compartilhado do dashboard
- Escopo: filtro de periodo, alternancia visual, acesso a registros e status
- Dados recebidos:
  - `name`
  - `description`
  - `periodo`
  - `is_admin`
  - `status` quando disponivel
- Observacoes:
  - usa o componente compartilhado `a_modulos/app_header.twig`
  - concentra as opcoes da tela no menu hamburguer, com `Home` sempre como primeiro item
  - exibe periodo filtrado e status abaixo do menu

### `app/Views/a_modulos/app_header.twig`

- Tela: cabecalho compartilhado das paginas
- Escopo: identidade visual, logo, titulo e menu hamburguer contextual
- Dados recebidos:
  - `header_title`
  - `header_subtitle`
  - `header_menu_items`
  - `header_meta_items`
- Observacoes:
  - deve ser reutilizado nas paginas atuais e futuras para evitar divergencia de layout
  - depende do espacamento estrutural definido nos layouts base `base.twig` e `base_form.twig`

### `app/Views/cadunico/index.html`

- Tela: entrada inicial do modulo Cad Unico
- Escopo: transicao para futuras funcionalidades
- Dados recebidos:
  - `title`
  - `description`
  - `dados`
  - `header_menu_items`

### `app/Views/ppa/index.html`

- Tela: dashboard geral inicial do modulo PPA
- Escopo: entrada pública do modulo e listagem inicial dos indicadores cadastrados
- Dados recebidos:
  - `header_menu_items`
  - `totais`
  - `ultimos_indicadores`
  - `tem_indicadores`

### `app/Views/ppa/show.html`

- Tela: página pública individual do indicador do PPA
- Escopo: visualização pública detalhada de um indicador
- Dados recebidos:
  - `header_menu_items`
  - `indicador`
  - `resultado_atual`
  - `historico`
  - `tem_historico`

### `app/Views/admin/login.html`

- Tela: login administrativo
- Escopo: autenticacao por rota oculta
- Dados recebidos:
  - `cpf`
  - `erro`

### `app/Views/admin/panel.html`

- Tela: painel administrativo inicial
- Escopo: ponto de entrada autenticado da area administrativa
- Dados recebidos:
  - `admin`
-  `usuarios_total`
-  `fontes_ppa_total`
- Observacoes:
  - usa o header padrao compartilhado do sistema
  - expoe atalhos para rotas protegidas, CRUD de usuarios administrativos e configuracao das fontes do PPA

### `app/Views/admin/users/index.html`

- Tela: listagem de usuarios administrativos
- Escopo: manutencao de acessos
- Dados recebidos:
  - `admin`
  - `erro`
  - `usuarios`

### `app/Views/admin/users/create.html`

- Tela: cadastro de usuario administrativo
- Escopo: criacao de acesso
- Dados recebidos:
  - `erro`
  - `dados`

### `app/Views/admin/users/edit.html`

- Tela: edicao de usuario administrativo
- Escopo: manutencao de acesso
- Dados recebidos:
  - `erro`
  - `dados`

### `app/Views/admin/ppa/fontes/index.html`

- Tela: listagem de fontes de dados do PPA
- Escopo: configuracao administrativa do modulo PPA
- Dados recebidos:
  - `admin`
  - `erro`
  - `fontes`
  - `tipos_map`

### `app/Views/admin/ppa/fontes/create.html`

- Tela: cadastro de fonte de dados do PPA
- Escopo: configuracao administrativa do modulo PPA
- Dados recebidos:
  - `erro`
  - `dados`
  - `tipos_map`

### `app/Views/admin/ppa/fontes/edit.html`

- Tela: edicao de fonte de dados do PPA
- Escopo: configuracao administrativa do modulo PPA
- Dados recebidos:
  - `erro`
  - `dados`
  - `tipos_map`

### `app/Views/admin/ppa/index.html`

- Tela: painel administrativo proprio do modulo PPA
- Escopo: entrada central para os CRUDs administrativos do modulo

### `app/Views/admin/ppa/indicadores/*`

- Tela: listagem, cadastro e edicao de indicadores do PPA
- Escopo: cadastro principal dos indicadores

### `app/Views/admin/ppa/relacoes/*`

- Tela: listagem, cadastro e edicao das relacoes indicador x fonte
- Escopo: mapeamento funcional das fontes do PPA

### `app/Views/admin/ppa/variaveis/*`

- Tela: listagem, cadastro e edicao das variaveis dos indicadores
- Escopo: configuracao tecnica dos calculos do PPA

### `app/Views/admin/ppa/resultados/*`

- Tela: listagem, cadastro e edicao de resultados manuais do PPA
- Escopo: manutencao do resultado consolidado

### `app/Utils/AdminAuth.php`

- Escopo: helper global de autenticacao administrativa
- Responsabilidades:
  - garantir inicializacao de sessao
  - verificar se existe sessao administrativa valida
  - redirecionar para `/admin` quando uma rota protegida for acessada sem autenticacao

## Banco de Dados

### `admin_users`

- Finalidade: armazenar usuarios administrativos autenticados por CPF e senha
- Campos principais:
  - `id`
  - `nome`
  - `cpf`
  - `senha_hash`
  - `ativo`
  - `created_at`
  - `updated_at`
- Relacionamentos: nao ha relacionamentos definidos neste momento
- Observacoes:
  - `cpf` unico
  - senha armazenada em hash

### `database/admin_users.seed.sql`

- Finalidade: inserir o primeiro usuario administrativo para homologacao inicial do login
- Observacoes:
  - usa CPF sem mascara no banco
  - senha armazenada em hash gerado por `password_hash`

### `bsc`

- Finalidade: armazenar os acompanhamentos do Balanced Scorecard
- Campos principais:
  - `id`
  - `estrategia`
  - `eixo`
  - `situacao`
  - `status_detalhado`
  - `data_referencia`
  - `created_at`
  - `updated_at`
- Relacionamentos: nao identificados no script atual
- Observacoes:
  - indices por `eixo`, `situacao` e `data_referencia`
  - existe `CHECK` com os valores `Nao iniciado`, `Em andamento`, `Concluido` e `Suspenso`
  - divergencia conhecida com o model, que aceita `cancelado`

### `dashboard_config`

- Finalidade: armazenar o periodo global do dashboard
- Campos principais:
  - `id`
  - `data_inicio`
  - `data_fim`
  - `updated_at`
- Relacionamentos: nao aplicavel
- Observacoes:
  - tabela de configuracao singleton logica, usando `id = 1`

### `ppa_indicadores`

- Finalidade: cadastro principal dos indicadores do Plano Plurianual
- Campos principais:
  - `id`
  - `codigo`
  - `numero_programa`
  - `nome`
  - `unidade_medida`
  - `indice_recente`
  - `indice_futuro`
  - `tipo_alimentacao`
  - `periodicidade`
  - `status`
  - `created_at`
  - `updated_at`
- Relacionamentos:
  - 1:N com `ppa_indicador_fontes`
  - 1:N com `ppa_indicador_variaveis`
  - 1:N com `ppa_resultados`
  - 1:N com `ppa_sincronizacoes`
- Observacoes:
  - `codigo` unico por indicador
  - contem os campos institucionais e tecnicos descritos no plano do modulo

### `ppa_fontes_dados`

- Finalidade: catalogar as fontes de dados que alimentam os indicadores PPA
- Campos principais:
  - `id`
  - `nome`
  - `tipo_fonte`
  - `nome_banco`
  - `nome_tabela`
  - `status`
  - `created_at`
  - `updated_at`
- Relacionamentos:
  - 1:N com `ppa_indicador_fontes`
  - 1:N com `ppa_sincronizacoes`
- Observacoes:
  - `nome` unico para evitar cadastros duplicados da mesma fonte
  - possui tela administrativa protegida em `/admin/ppa/fontes`

### `ppa_indicador_fontes`

- Finalidade: relacionar cada indicador a uma ou mais fontes com papel funcional no calculo
- Campos principais:
  - `id`
  - `indicador_id`
  - `fonte_id`
  - `papel_fonte`
  - `observacao`
  - `created_at`
  - `updated_at`
- Relacionamentos:
  - N:1 com `ppa_indicadores`
  - N:1 com `ppa_fontes_dados`
- Observacoes:
  - a combinacao `indicador_id + fonte_id + papel_fonte` e unica

### `ppa_indicador_variaveis`

- Finalidade: armazenar as variaveis de calculo de cada indicador
- Campos principais:
  - `id`
  - `indicador_id`
  - `chave`
  - `nome`
  - `tipo_valor`
  - `origem`
  - `obrigatorio`
  - `created_at`
  - `updated_at`
- Relacionamentos:
  - N:1 com `ppa_indicadores`
  - 1:N com `ppa_resultado_variaveis`
- Observacoes:
  - a chave da variavel e unica dentro de cada indicador

### `ppa_resultados`

- Finalidade: guardar o resultado consolidado do indicador por periodo e recortes de analise
- Campos principais:
  - `id`
  - `indicador_id`
  - `ano_referencia`
  - `competencia`
  - `valor_numerador`
  - `valor_denominador`
  - `valor_meta_quantitativa`
  - `valor_resultado`
  - `indice_recente`
  - `indice_futuro`
  - `cras`
  - `creas`
  - `bairro`
  - `regiao`
  - `tipo_lancamento`
  - `status`
  - `created_at`
  - `validated_at`
- Relacionamentos:
  - N:1 com `ppa_indicadores`
  - 1:N com `ppa_resultado_variaveis`
- Observacoes:
  - preparado para resultados manuais, automaticos e hibridos
  - inclui indices para os filtros previstos na pagina do indicador

### `ppa_resultado_variaveis`

- Finalidade: detalhar os valores usados no calculo de cada resultado consolidado
- Campos principais:
  - `id`
  - `resultado_id`
  - `variavel_id`
  - `valor_decimal`
  - `valor_texto`
  - `origem`
  - `created_at`
  - `updated_at`
- Relacionamentos:
  - N:1 com `ppa_resultados`
  - N:1 com `ppa_indicador_variaveis`
- Observacoes:
  - evita a criacao de colunas especificas para cada novo tipo de indicador

### `ppa_sincronizacoes`

- Finalidade: registrar historico de importacoes, calculos e reprocessamentos do modulo PPA
- Campos principais:
  - `id`
  - `indicador_id`
  - `fonte_id`
  - `ano_referencia`
  - `competencia`
  - `tipo_execucao`
  - `status`
  - `total_lidos`
  - `total_processados`
  - `total_inseridos`
  - `total_atualizados`
  - `mensagem`
  - `erro`
  - `executado_por`
  - `started_at`
  - `finished_at`
- Relacionamentos:
  - N:1 com `ppa_indicadores`
  - N:1 com `ppa_fontes_dados`
- Observacoes:
  - usa `ON DELETE SET NULL` para preservar o historico mesmo se o cadastro-base for removido futuramente

## Regras de Negocio

- A rota `/admin` nao deve ser exposta nos menus publicos da aplicacao.
- O login administrativo exige CPF com 11 digitos e senha.
- O backend normaliza o CPF para somente numeros antes da autenticacao.
- O acesso ao painel administrativo depende de sessao ativa.
- O CRUD de usuarios administrativos depende de sessao ativa.
- O cadastro de fontes de dados do PPA depende de sessao administrativa ativa.
- A rota `/bsc/registros` depende de sessao administrativa ativa.
- O usuario autenticado nao pode excluir nem inativar a propria conta na mesma sessao.
- A rota raiz `/` funciona como menu principal do sistema.
- O dashboard BSC segue disponivel pela rota dedicada `/bsc`.
- O modulo PPA passa a ter uma rota publica inicial em `/ppa`.
- O modulo Cad Unico passa a ter uma rota publica inicial em `/cadunico`.
- O modulo PPA passa a ter sua estrutura de banco planejada para consolidacao de indicadores, resultados, variaveis e sincronizacoes sem depender de consulta em tempo real na tela.
- O dashboard usa um periodo efetivo que pode vir da sessao local ou da configuracao global.
- Se existir filtro local em sessao, ele tem precedencia sobre o global.
- Apenas perfil `admin`, vindo de `DASHBOARD_USER_PROFILE`, pode salvar periodo global.
- Operacoes de criacao, edicao e exclusao exigem token de formulario.
- O modulo BSC trabalha hoje com dados referenciais por `data_referencia`.

## Permissoes e Escopos

- Perfil: `admin`
  - O que pode acessar: dashboard BSC, painel administrativo, CRUD de usuarios admins, configuracao de fontes PPA, rota protegida `/bsc/registros` e persistir filtro global
  - O que nao pode acessar: nao ha restricoes adicionais mapeadas no codigo atual

- Perfil: nao admin
  - O que pode acessar: menu principal, dashboard BSC, dashboard inicial do PPA, Cad Unico e demais rotas ainda publicas
  - O que nao pode acessar: persistencia do filtro global do dashboard e `/bsc/registros`
