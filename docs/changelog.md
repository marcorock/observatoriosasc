# Changelog

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
