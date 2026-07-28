# Changelog

## 2026-07-28

### Consolidação documental

- Alinhados `continuity.md`, `project_status.md`, `roadmap.md` e `task.todo.md`
  com a branch `integration/update-repository` e o checkpoint `405e648`.
- Registrada a conclusão do plano original de 14 etapas e definido smoke tests
  HTTP como próximo incremento.
- Corrigidos em `documentacao_tecnica.md` os nomes atuais de rotas, controllers,
  models, serviços e views do PPA.
- Atualizado `architecture/ppa-module.md` para registrar a integração
  cache-first e a suíte atual de doze testes com 186 assertions.
- Removidos o briefing de auditoria já executado e o `.docx` duplicado do plano
  inicial.
- Mantido `plano_inicial_modulo_ppa.md` como registro histórico pesquisável e
  versionável.
- Nenhum comportamento da aplicação foi alterado.

### Smoke test do catálogo PPA

- Permitida a injeção opcional de `PpaIndicatorModel` e `PpaCatalogService` no
  `PpaController`, mantendo criação tardia das dependências no runtime.
- Criado `PpaCatalogHttpSmokeTest.php` para renderização normal, catálogo vazio
  e falha controlada de leitura.
- Criado `PpaDashboardHtmlSmokeTest.php` para slug inexistente, fallback ao
  catálogo e mensagem controlada.
- Ampliado o mesmo teste para indicador válido sem vínculos, estado
  `Em Preparacao` e mensagem explicativa.
- Tornado o emissor JSON substituível em testes, sem alterar resposta ou
  encerramento no runtime.
- Criado `PpaDashboardJsonSmokeTest.php` para os erros 404 de indicador
  inexistente e indicador sem vínculos.
- Permitida a injeção opcional de `PpaLinkedQueryService` no controller.
- Criado `PpaDashboardFilterCacheSmokeTest.php` para filtro por CRAS e região,
  cache hit e ausência de chamada externa.
- Ampliado o mesmo teste para cache miss, uma chamada externa controlada e
  preservação dos filtros no fallback.
- Criado `AdminRouteProtectionTest.php` para painel, usuários, bases, consultas,
  PPA administrativo, indicadores, vínculos e registros BSC.
- Confirmado que as oito ações exigem autenticação antes de acessar models.
- Suíte ampliada para dezessete testes e 220 assertions.
- Próximo incremento definido: homologação visual, login e logout.

### Feedback visual de navegação

- Criado `a_modulos/navigation_loader.twig` com overlay, indicador animado e
  mensagem de carregamento.
- Integrado o componente a `base.twig` e `base_form.twig`, cobrindo catálogo,
  dashboards, formulários e área administrativa.
- Limitada a ativação a links internos que mudam de página; links externos,
  nova aba, downloads e âncoras locais são ignorados.
- Adicionados estado `aria-busy`, anúncio acessível e limpeza no evento
  `pageshow` para retorno pelo navegador.
- Criado `NavigationLoaderMarkupTest.php` com 8 assertions.
- Suíte ampliada para dezoito testes e 228 assertions.

### Autenticação administrativa

- Permitida a injeção opcional do model de autenticação no `AdminController`.
- Centralizados os redirecionamentos de login e logout em método substituível
  durante testes, sem alterar headers ou encerramento no runtime.
- Criado `AdminAuthenticationFlowTest.php` com usuário e senha exclusivamente
  locais ao teste.
- Cobertos login válido, senha inválida, criação da sessão, logout válido e
  rejeição de logout com token inválido.
- Suíte ampliada para dezenove testes e 239 assertions.
- Próximo incremento definido: validações dos CRUDs administrativos.

### Validações do CRUD de usuários

- Reutilizado o `AdminModel` injetável em todas as operações de usuários.
- Centralizados os redirecionamentos do CRUD no método testável do controller,
  sem alterar os destinos no runtime.
- Criado `AdminUserCrudValidationTest.php` sem persistência real.
- Cobertos criação com senha em hash, CPF normalizado, CPF duplicado, edição
  sem troca de senha, bloqueio de autodesativação, bloqueio de autoexclusão e
  exclusão de outra conta.
- Suíte ampliada para vinte testes e 255 assertions.
- Próximo incremento definido: CRUDs de fontes e consultas externas.

### Validações de fontes e consultas externas

- Criado `ExternalAdminPayloadValidationTest.php` sem banco ou conexão externa.
- Cobertos campos obrigatórios, porta, charset, senha obrigatória e preservação
  da senha existente na edição.
- Confirmado que a senha é criptografada e recuperável somente pela chave
  configurada no teste.
- Cobertos fonte obrigatória, nome da consulta, bloqueio de SQL não `SELECT`,
  normalização do SQL e status ativo.
- Suíte ampliada para vinte e um testes e 273 assertions.
- Próximo incremento definido: controllers com models em memória.

### CRUD de fontes e consultas externas

- Permitida a injeção opcional de `ExternalDataSourceModel` e
  `ExternalQueryModel` no controller administrativo.
- Centralizados os redirecionamentos do fluxo em método substituível durante
  testes, preservando os destinos do runtime.
- Criado `ExternalAdminCrudFlowTest.php` com armazenamento somente em memória.
- Cobertos criação, edição e exclusão de fontes e consultas, normalização de
  IDs e porta, senha vazia na edição e redirecionamentos.
- Suíte ampliada para vinte e dois testes e 289 assertions.
- Próximo incremento definido: indicadores e vínculos PPA.

### CRUD de indicadores e vínculos PPA

- Permitida a injeção opcional dos models de indicadores, vínculos e consultas
  externas no controller administrativo do PPA.
- Centralizados os redirecionamentos em método substituível durante testes,
  preservando o comportamento do runtime.
- Criado `PpaAdminCrudFlowTest.php` com armazenamento somente em memória.
- Cobertas criação, edição e exclusão de indicadores e vínculos, incluindo
  normalização dos dados e redirecionamentos.
- Suíte ampliada para vinte e três testes e 307 assertions.
- Próximo incremento definido: homologação visual e revisão da PR #2.

### Padronização visual da Home

- Removido o fundo gelo fixo da Home em favor da cor padrão do tema ativo.
- Cards dos módulos passaram a usar fundo, texto e borda dos temas claro e
  escuro.
- Mantidas as cores de identificação e os links de BSC, PPA e CadÚnico.
- Criado `HomeThemeMarkupTest.php` com 7 assertions.
- Suíte ampliada para vinte e quatro testes e 314 assertions.

### Sincronização pelo painel administrativo

- Criado `PpaAdminSynchronizationService` para atualizar primeiro o cache
  detalhado e depois persistir o resultado consolidado do catálogo.
- Adicionada a rota protegida `POST /admin/ppa/sincronizar`.
- Incluídos seleção de indicador ativo, confirmação e estado visual
  `Sincronizando...` no painel PPA.
- Mantido o último cache válido quando a consulta externa falha.
- Criados testes do serviço e do fluxo administrativo; a proteção contra
  acesso anônimo também foi ampliada.
- Corrigida a geração do token no Twig com a função registrada
  `form_token_input`, incluindo renderização real do template no teste.
- Tratada a ausência de escrita no diretório do cache sem expor warnings
  nativos ou rastros técnicos na tela.
- Documentada a necessidade de o usuário do servidor web escrever em
  `storage/cache/ppa/queries`.
- Substituído o seletor por uma tabela responsiva com indicador, situação,
  última atualização concluída e botão individual de sincronização.
- Indicadores sem histórico concluído passam a aparecer como `Pendente` e
  `Nunca sincronizado`.
- Corrigidos os formulários da tabela para usar um token de segurança exclusivo
  por indicador, evitando que uma linha invalide o botão das anteriores.
- Suíte ampliada para vinte e seis testes e 350 assertions.

### Sincronização automática programada

- Execuções passam a ser registradas como `manual` ou `automatico`.
- Criado `PpaScheduledSynchronizationBatchService` para processar indicadores
  elegíveis sequencialmente e continuar a fila após falhas individuais.
- Indicadores sem vínculos e o indicador de visão geral são ignorados de forma
  explícita, sem transformar o lote em falha.
- Criado `bin/ppa-scheduled-sync.php --all` com trava não bloqueante, resumo
  JSON e código de saída para monitoramento.
- Adicionada receita diária em `deploy/cron/observatoriosasc-ppa`, às 04:15 no
  horário de São Paulo.
- Criado `deploy/cron/install-ppa-sync-cron.sh`, sem credenciais, para validar
  cron, PHP, usuário web, projeto e escrita no cache antes da instalação.
- Adicionado `deploy/cron/README.md` com instalação, verificação e remoção da
  regra para entrega ao sysadmin.
- O ambiente de desenvolvimento atual não possui daemon `cron`; a receita
  ainda precisa ser instalada no servidor real.
- Suíte ampliada para vinte e nove testes e 386 assertions.

### Integração final em main

- Publicados os 58 commits locais restantes em
  `integration/update-repository`.
- Confirmado que a branch estava 116 commits à frente e zero atrás de `main`.
- Avançada `main` por fast-forward até `1cde072`, sem reescrita de histórico.
- PR #2 reconhecida pelo GitHub como integrada.
- Removida a branch `integration/update-repository` local e remota.
- Próximo incremento definido: homologação visual e posterior agendamento
  automático.

## 2026-07-24

### Documentação

- Criado `docs/continuity.md` como ponto canônico de retomada.
- Criado no Notion um checklist de 14 etapas, com critérios de conclusão e
  cálculo de progresso.
- Alinhados status, roadmap, tarefas, arquitetura, performance, manual e
  documentação técnica com a branch `update-repository`.
- Registrado o último commit conhecido (`0fcef27`), os 23 commits sobre `main`,
  a ausência de PR e issues abertas e a alteração local pendente em
  `DataConect.php`.
- Registrada a execução bem-sucedida dos sete testes, com 87 assertions.
- Definida a atualização da documentação local e do Notion como critério
  obrigatório de conclusão de cada incremento.
- Marcados o plano inicial em Markdown e o `.docx` como artefatos históricos.

### Próximo incremento

- Caracterizar o contrato de `buildFamilySnapshotRmaPayload()`.

### Decisões de continuidade

- Mantidos os defaults `localhost` e senha vazia em `DataConect.php`.
- Ambientes Docker ou remotos devem configurar a conexão no `.env`.
- Corrigido `core/app.php` para repassar as variáveis `DB_*` do `.env` no caso
  padrão, evitando fallback indevido da conexão.
- Correção validada e publicada no commit `e17ee07`.
- Checklist atualizado para 2 de 14 etapas concluídas, ou 14,3%.
- Checkpoint documental registrado no commit `651e652`.
- Caracterizado o contrato completo do payload de fotografia familiar.
- Checklist avançado para 3 de 14 etapas concluídas, ou 21,4%.
- Criado teste de equivalência do payload legado de fotografia familiar com 26
  assertions.
- Suíte ampliada para oito arquivos e 113 assertions.
- Checklist avançado para 4 de 14 etapas concluídas, ou 28,6%.
- Teste publicado no commit `b325692`.
- Criado `PpaFamilySnapshotRmaPayloadBuilder` sem integração ao controller.
- Adicionadas quatro comparações completas entre builder e método legado.
- Corrigida durante o desenvolvimento a preservação do tipo `float` para
  `percentual_periodo` quando não existem meses.
- Suíte ampliada para 117 assertions.
- Checklist avançado para 5 de 14 etapas concluídas, ou 35,7%.
- Builder publicado no commit `f6ebb0b`.
- Integrado `PpaFamilySnapshotRmaPayloadBuilder` ao `PpaController`.
- Removido `buildFamilySnapshotRmaPayload()` do controller.
- Renomeado o teste legado para `PpaFamilySnapshotRmaPayloadBuilderTest.php`.
- Suíte mantida com oito testes e 113 assertions.
- Checklist avançado para 6 de 14 etapas concluídas, ou 42,9%.
- Integração publicada no commit `36d0cfd`.
- Caracterizado o contrato especializado de atualização cadastral, incluindo
  seleção antecipada, duas fontes, filtros, cálculos e diferenças em relação à
  fotografia familiar.
- Checklist avançado para 7 de 14 etapas concluídas, ou 50%.
- Criado teste de equivalência do payload cadastral legado com 28 assertions.
- Suíte ampliada para nove testes e 141 assertions.
- Checklist avançado para 8 de 14 etapas concluídas, ou 57,1%.
- Teste publicado no commit `2d451c2`.
- Criado `PpaCadUpdateRmaPayloadBuilder` sem integração ao controller.
- Adicionadas quatro comparações completas entre builder cadastral e legado.
- Suíte ampliada para 145 assertions.
- Checklist avançado para 9 de 14 etapas concluídas, ou 64,3%.
- Builder publicado no commit `2a63a16`.
- Integrado `PpaCadUpdateRmaPayloadBuilder` ao `PpaController`.
- Removidos `buildCadUpdateRmaPayload()`, `filterBaseRows()` e
  `filterRmaRows()` do controller.
- Renomeado o teste para `PpaCadUpdateRmaPayloadBuilderTest.php`.
- Suíte mantida com nove testes e 141 assertions.
- Checklist avançado para 10 de 14 etapas concluídas, ou 71,4%.
- Integração publicada no commit `05e2801`.
- Revisadas e classificadas as responsabilidades restantes no
  `PpaController`.
- Mantidas no controller as ações HTTP, filtros de entrada, seleção de
  dashboard, renderização e coordenação dos serviços.
- Registrados como candidatos futuros a sincronização do catálogo,
  configurações visuais e orquestração das respostas, condicionados às
  medições.
- Identificados helpers sem chamadas para limpeza posterior isolada.
- Checklist avançado para 11 de 14 etapas concluídas, ou 78,6%.
- Executada a campanha pós-refatoração com três amostras por fluxo, sem
  persistência e com monitoramento temporário somente nos processos CLI.
- Registradas medianas de 740,2 ms para consulta simples, 71,6 ms para mensal
  por unidade, 618,7 ms para progresso familiar, 4.041,3 ms para atualização
  cadastral e 12.997,9 ms para a fotografia familiar simulada.
- Confirmado que as consultas CECAD dominam os fluxos mais lentos, enquanto
  conexão, RMA e builders têm participação menor.
- Checklist avançado para 12 de 14 etapas concluídas, ou 85,7%.
- Aprovado cache local em arquivo para as linhas agregadas das consultas dos
  dashboards, com sincronização manual e possibilidade de agendamento.
- Definidos escrita atômica, trava por chave, identidade por hash da consulta,
  preservação do último arquivo válido e fallback externo.
- Redis removido do escopo atual; revisão do SQL CECAD mantida como frente
  independente.
- Checklist avançado para 13 de 14 etapas concluídas, ou 92,9%.
- Criado `PpaQueryFileCache` com identidade por fonte, consulta, hash do SQL,
  limite e versão do formato.
- Implementadas validação do envelope, travas de leitura/escrita, arquivo
  temporário e substituição atômica.
- Adicionado `PpaQueryFileCacheTest.php` com 16 assertions; suíte ampliada para
  dez testes e 157 assertions.
- Armazenamento publicado no commit `b66a4c0`, ainda sem integração ao runtime.
- Criados `PpaQueryCacheSynchronizer` e
  `bin/ppa-dashboard-cache.php` para atualizar manualmente o cache de um
  indicador.
- Validado o comando com `PPA-CREAS-MULHERES-F1`: uma entrada, 13 linhas e
  65,729 ms, sem escrita no banco.
- Adicionado teste do sincronizador com 10 assertions; suíte ampliada para onze
  testes e 167 assertions.
- Comando manual publicado no commit `4df0c63`; dashboards continuam sem ler o
  cache nesta etapa.
- Integrada leitura cache-first em `PpaLinkedQueryService`, sem escrita ou
  renovação durante requisições públicas.
- Preservado fallback externo para ausência, corrupção, mudança de SQL ou
  limite incompatível.
- Ampliado `PpaLinkedQueryServiceTest.php` para 15 assertions; suíte total com
  174 assertions.
- Validado hit real de `PPA-CREAS-MULHERES-F1`: métricas preservadas, 46,381 ms
  e zero consultas externas.
- Integração publicada no commit `9baaa3d`.
- Sincronizados e homologados `PPA-ACOMPANHAR-FAMILIAS-PBF` e
  `PPA-CRAS-ATUALIZACAO-C3`.
- Confirmada equivalência integral das prévias, excluindo somente o tempo de
  execução.
- Progresso familiar passou de 11.508,229 ms para 30,548 ms; atualização
  cadastral passou de 4.069,384 ms para 31,560 ms.
- Cada fluxo passou de duas consultas externas para zero no cache hit.
- Homologados catálogo, HTML e JSON dos indicadores mensal, familiar e
  cadastral com HTTP 200.
- Homologados filtros de unidade/CRAS e mês, com uma linha territorial e um mês
  por resposta, sem erro.
- Endpoints filtrados responderam entre 36,721 ms e 44,228 ms, com zero
  consultas externas registradas.
- Corrigida a atualização manual para ignorar cache existente e sempre consultar
  a fonte; correção publicada em `fa905b0`.
- Renovação real do indicador mensal registrou uma consulta externa e concluiu
  em 65,439 ms.
- Criado `PpaQueryCacheBatchSynchronizer` e adicionada a opção `--all` ao
  comando, com processamento sequencial e relatório de falhas parciais.
- Adicionado teste do lote com 10 assertions; suíte ampliada para doze testes e
  186 assertions.
- Lote publicado em `359a269` e agendamento documentado com trava `flock`.
- Criada `integration/update-repository` para conectar os históricos
  independentes de `main` e `update-repository`, preservando a árvore
  refatorada.
- Criada a pull request draft #2:
  `https://github.com/marcorock/observatoriosasc/pull/2`.
- Confirmado pelo GitHub que a PR está aberta e mergeável.
- Documentado o fluxo completo para cadastro, validação, consolidação do
  catálogo e geração do cache de um novo indicador.
- Checklist avançado para 14 de 14 etapas concluídas, ou 100%.

## 2026-07-23

### Performance e arquitetura do PPA

- Aplicado `LIMIT` diretamente nas consultas externas.
- Adicionado monitoramento opt-in de tempo, linhas e memória.
- Removidas consultas externas do catálogo `/ppa`.
- Implementada leitura local em lote das métricas consolidadas.
- Implementada sincronização CLI com prévia, persistência e idempotência.
- Sincronizados nove indicadores quantitativos públicos.
- Classificado `PPA-ERRADICAR-POBREZA` como indicador de visão geral.
- Extraídos catálogo, consultas vinculadas, resolução de dashboards e builders
  de consulta simples, mensal por unidade e progresso familiar.
- Criados testes para runtime, catálogo, resolver, serviços e builders.

## 2026-06-09

### Adicionado

- Criado `database/ppa.sql` com a estrutura inicial de banco do modulo PPA.
- Adicionadas as tabelas `ppa_indicadores`, `ppa_fontes_dados`, `ppa_indicador_fontes`, `ppa_indicador_variaveis`, `ppa_resultados`, `ppa_resultado_variaveis` e `ppa_sincronizacoes`.
- Criado `app/Models/PpaFonteDadosModel.php` para gerenciar o cadastro administrativo das fontes de dados do modulo PPA.
- Criado `app/Database/PpaSchemaManager.php` para carregar o schema base do modulo PPA a partir de `database/ppa.sql`.
- Criados `app/Controllers/PpaAdminController.php`, `app/Models/PpaIndicadorModel.php`, `app/Models/PpaIndicadorFonteModel.php`, `app/Models/PpaIndicadorVariavelModel.php` e `app/Models/PpaResultadoModel.php`.
- Criado `app/Controllers/PpaController.php` para a camada pública inicial do modulo PPA.
- Criadas as views `app/Views/admin/ppa/fontes/index.html`, `app/Views/admin/ppa/fontes/create.html` e `app/Views/admin/ppa/fontes/edit.html`.
- Criado o painel administrativo proprio do PPA em `app/Views/admin/ppa/index.html`.
- Criadas as views administrativas de `indicadores`, `relacoes`, `variaveis` e `resultados` do PPA.
- Criada `app/Views/ppa/index.html` como dashboard pública geral inicial dos indicadores do PPA.
- Criada `app/Views/ppa/show.html` como a primeira página pública individual de indicador do módulo PPA.

### Alterado

- Atualizada a documentacao de acompanhamento para registrar o inicio da implementacao do modulo PPA pela camada de banco.
- Alterado `app/Controllers/AdminController.php` para incluir listagem, cadastro, edicao e exclusao de fontes de dados do PPA protegidas por sessao administrativa.
- Alterado `routes/web.php` para usar o novo `PpaAdminController` nas rotas administrativas do modulo PPA.
- Alterado `app/Views/admin/panel.html` para centralizar o acesso ao painel administrativo do modulo PPA e seus atalhos principais.
- Alterado `routes/web.php` para incluir a rota pública `/ppa`.
- Alterado `routes/web.php` para incluir a rota pública `/ppa/indicadores/{id}`.
- Alterado `app/Controllers/IndexController.php` para incluir o botão do PPA na home entre `BSC` e `Cad Unico`.
- Alterado `app/Views/ppa/index.html` para remover o padding do stage, reforçar contraste e adicionar links públicos para os indicadores disponíveis.

### Corrigido

- Nenhuma correcao funcional aplicada nesta tarefa.

### Arquivos Impactados

- `database/ppa.sql`
- `app/Models/PpaFonteDadosModel.php`
- `app/Database/PpaSchemaManager.php`
- `app/Controllers/PpaAdminController.php`
- `app/Controllers/PpaController.php`
- `app/Models/PpaIndicadorModel.php`
- `app/Models/PpaIndicadorFonteModel.php`
- `app/Models/PpaIndicadorVariavelModel.php`
- `app/Models/PpaResultadoModel.php`
- `app/Views/admin/ppa/index.html`
- `app/Views/ppa/index.html`
- `app/Views/admin/ppa/fontes/index.html`
- `app/Views/admin/ppa/fontes/create.html`
- `app/Views/admin/ppa/fontes/edit.html`
- `app/Views/admin/ppa/indicadores/index.html`
- `app/Views/admin/ppa/indicadores/create.html`
- `app/Views/admin/ppa/indicadores/edit.html`
- `app/Views/admin/ppa/relacoes/index.html`
- `app/Views/admin/ppa/relacoes/create.html`
- `app/Views/admin/ppa/relacoes/edit.html`
- `app/Views/admin/ppa/variaveis/index.html`
- `app/Views/admin/ppa/variaveis/create.html`
- `app/Views/admin/ppa/variaveis/edit.html`
- `app/Views/admin/ppa/resultados/index.html`
- `app/Views/admin/ppa/resultados/create.html`
- `app/Views/admin/ppa/resultados/edit.html`
- `app/Controllers/AdminController.php`
- `app/Views/admin/panel.html`
- `routes/web.php`
- `docs/project_status.md`
- `docs/task.todo.md`
- `docs/changelog.md`
- `docs/documentacao_tecnica.md`

## 2026-06-08

### Adicionado

- Criados os documentos de continuidade do projeto em `docs/`: `project_status.md`, `task.todo.md`, `changelog.md`, `documentacao_tecnica.md`, `manual_usuario.md` e `roadmap.md`.
- Registrado o estado atual do modulo BSC, incluindo dashboard, CRUD, filtros de periodo e riscos conhecidos.
- Criados `IndexController` e `CadunicoController` para sustentar a nova navegacao principal do sistema.
- Criadas as views `home/index.html` e `cadunico/index.html`.
- Criados `AdminController`, `AdminModel`, `app/Views/admin/login.html` e `app/Views/admin/panel.html` para o fluxo de autenticacao administrativa.
- Criado `database/admin_users.sql` com a nova tabela de administradores.
- Criado `database/admin_users.seed.sql` com o primeiro usuario administrativo inicial.
- Criado `app/Utils/AdminAuth.php` para centralizar a verificacao de sessao administrativa.
- Criadas as views `app/Views/admin/users/index.html`, `app/Views/admin/users/create.html` e `app/Views/admin/users/edit.html` para o CRUD administrativo de usuarios.

### Alterado

- Formalizada a linha de acompanhamento do projeto para que a continuidade nao dependa apenas do historico da conversa.
- Reorganizada a documentacao de acompanhamento para a pasta `docs/`.
- Alterada a rota `/` para exibir o menu principal do sistema.
- Adicionada a rota `/cadunico` para a entrada inicial do modulo Cad Unico.
- Refinada a home para usar a logo oficial em `public/assets/img/2cm-Brasão.png`.
- Adicionado botao de retorno para a home no cabecalho do dashboard BSC.
- Simplificada a home para o formato de dashboard de atalhos, removendo textos explicativos e mantendo apenas imagem/icone e botao com o titulo do modulo.
- Unificado o header das telas em um componente compartilhado com logo, titulo central e menu hamburguer.
- Reorganizado o menu do BSC para ter `Home` primeiro e mover periodo/status para abaixo do hamburguer.
- Ajustado o quadrante do Cad Unico na home para remover bordas coloridas e manter a consistencia visual.
- Alinhado o `base_form.twig` ao mesmo padding estrutural do layout do BSC para eliminar a diferenca visual entre `/` e `/bsc`.
- Removido o override local de `.full-hd-container` na home para que o header compartilhado tenha o mesmo espacamento em todas as telas.
- Adicionada a rota oculta `/admin` com autenticacao por CPF mascarado e senha.
- Protegida a rota `/bsc/registros` para exigir sessao administrativa e redirecionar para `/admin` quando nao autenticada.
- Adicionado atalho da rota protegida `/bsc/registros` dentro do painel administrativo.
- Adicionado CRUD de usuarios administrativos com listagem, cadastro, edicao e exclusao.
- Adaptado o painel administrativo para usar o header padrao compartilhado do sistema.

### Corrigido

- Nenhuma correcao funcional aplicada nesta tarefa.

### Arquivos Impactados

- `app/Controllers/AdminController.php`
- `app/Models/AdminModel.php`
- `app/Views/admin/login.html`
- `app/Views/admin/panel.html`
- `app/Views/admin/users/index.html`
- `app/Views/admin/users/create.html`
- `app/Views/admin/users/edit.html`
- `database/admin_users.sql`
- `database/admin_users.seed.sql`
- `app/Utils/AdminAuth.php`
- `public/index.php`
- `app/Models/AdminModel.php`
- `app/Controllers/BscController.php`
- `routes/web.php`
- `app/Controllers/IndexController.php`
- `app/Controllers/CadunicoController.php`
- `app/Views/home/index.html`
- `app/Views/cadunico/index.html`
- `docs/project_status.md`
- `docs/task.todo.md`
- `docs/changelog.md`
- `docs/documentacao_tecnica.md`
- `docs/manual_usuario.md`
- `docs/roadmap.md`

### Observacoes

- Existe uma divergencia funcional pendente entre o valor `cancelado` usado no model/views e o valor `Suspenso` presente no `CHECK` de `database/bsc.sql`.
- Existem alteracoes staged fora desta tarefa que devem ser preservadas e homologadas em fluxo proprio.
