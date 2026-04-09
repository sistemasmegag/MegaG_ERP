# Mega G ERP

Sistema web em `PHP` integrado ao `Oracle / Consinco`, com foco em operacao interna, importacoes, tarefas, despesas, permissoes e modulos corporativos.

## Visao Geral

O projeto atualmente contempla:

- Importacoes BI / Metas / Vendas / Comissoes / Cargas
- Dashboard principal com `Home Central Pessoal`
- Modulo de tarefas em estilo kanban
- Modulo de reembolsos / despesas corporativas
- Modulo de inventario geral da MEGA G
- Gestao de aprovadores, grupos e politicas de despesas
- CRM, Wiki, RH e notificacoes globais
- UI padronizada no estilo `Clean SaaS`

## Principais Entregas Desta Rodada

### Home / Dashboard

Arquivo principal:

- `pages/home.php`

Melhorias implementadas:

- nova home em formato de `Central Pessoal`
- hero com contexto do usuario logado
- KPIs de tarefas, recados e atalhos
- prioridades do dia
- mural de recados
- radar de despesas
- atalhos dinamicos baseados em `$_SESSION['menu_apps']`
- fallbacks seguros quando nao houver dados reais

Fontes de dados usadas na home:

- `$_SESSION['usuario']`
- `$_SESSION['menu_apps']`
- `megag_task_tasks`
- `megag_task_notificacoes`
- `CONSINCO.MEGAG_DESP`
- `CONSINCO.MEGAG_DESP_TIPO`
- `CONSINCO.MEGAG_DESP_APROVACAO`

### Modulo de Tarefas

Arquivos principais:

- `pages/tarefas.php`
- `pages/tarefas_criar_tasks.php`
- `pages/tarefas_detalhes.php`
- `api/tasks.php`
- `assets/js/tasks-kanban-enhancements.js`

Recursos implementados:

- spaces e lists para organizacao do trabalho
- task com titulo, descricao, prioridade, tags, responsavel e entrega
- vinculacao do kanban ao usuario logado
- filtro de acesso por criador e participantes da list
- suporte a participantes da list
- suporte a status dinamicos por list
- resumo leve na tela principal do kanban
- botao `Ver mais` por status
- quadro completo em modal fullscreen
- criacao de novos status por list
- drag and drop no quadro completo
- navegacao para tela de detalhes/edicao da task

Regras de visibilidade:

- o usuario pode ver a list se for criador ou participante
- as tasks visiveis dependem da list acessivel
- o fluxo deixou de depender apenas do `responsavel`

Objetos novos de banco usados pelo modulo:

- `MEGAG_TASK_LIST_PARTICIPANTES`
- `MEGAG_TASK_LIST_STATUS`
- `SEQ_MEGAG_TASK_LIST_STATUS`

Objetos ja usados pelo modulo:

- `MEGAG_TASK_SPACES`
- `MEGAG_TASK_LISTS`
- `MEGAG_TASK_TASKS`
- `MEGAG_TASK_COMMENTS`
- `MEGAG_TASK_ATTACHMENTS`
- `MEGAG_TASK_NOTIFICACOES`

### Modulo de Despesas / Reembolsos

Arquivos principais:

- `pages/despesas.php`
- `pages/despesas_aprovacao.php`
- `pages/despesas_config.php`
- `api/api_despesas.php`
- `api/api_despesas_config.php`

Recursos implementados:

- solicitacao de reembolso em modal split
- upload de anexo
- autocomplete de fornecedor
- autocomplete de centro de custo
- categorias dinamicas
- rateio entre multiplos centros de custo
- barra de soma do rateio
- radar de despesas na home
- detalhe da despesa com secao de rateio
- timeline de aprovacao
- aprovacao hierarquica

Ajustes importantes feitos na integracao:

- a API de despesas foi alinhada com a `PKG_MEGAG_DESP_CADASTRO`
- o create passou a resolver e enviar `CODPOLITICA` automaticamente
- o cadastro agora bloqueia quando:
  - nao existe politica para o centro de custo
  - existe mais de uma politica para o mesmo centro de custo

Observacao:

- o backend continua recebendo `valores_rateio` em valor absoluto
- futuras evolucoes de percentual podem converter para valor antes do submit, sem quebrar a API

### Modulo de Inventario Geral e Almoxarifado

Arquivos principais:

- `pages/inventario_ti.php`
- `api/inventario_ti.php`
- `assets/js/inventario-geral.js`
- `PKG/CREATE_TABLE_TI_INVENTARIO.sql`

Recursos implementados:

- dashboard com total de ativos, itens em uso, em estoque, em manutencao e garantia proxima
- inventario geral da empresa, nao mais restrito a TI
- listagem com busca por patrimonio, serie, colaborador, localizacao e modelo
- cadastro e edicao de itens com dados de patrimonio, responsavel, datas e garantia
- historico basico de movimentacoes e alteracoes cadastrais
- termo digital de responsabilidade com duas assinaturas internas
- requisicao digital ao almoxarifado
- assinatura do solicitante obrigatoria
- assinatura do almoxarifado somente no momento da entrega
- centro de custo com autocomplete usando `ABA_CENTRORESULTADO`
- filial com selecao assistida
- responsavel da proxima etapa carregado a partir dos aprovadores vinculados ao centro de custo no modulo de despesas
- estrutura Oracle dedicada para equipamentos, trilha de auditoria e solicitacoes do almoxarifado

### Notificacoes

Arquivos principais:

- `includes/footer.php`
- `api/notif.php`
- `api/api_despesas.php`
- `api/firebase.php`
- `helpers/firebase.php`
- `firebase-messaging-sw.php`
- `includes/firebase.local.php`
- `includes/firebase.local.example.php`

Recursos implementados:

- painel global de notificacoes no layout compartilhado
- criacao de notificacoes internas pelo endpoint `api/notif.php`
- disparo de notificacoes internas junto ao fluxo do modulo de despesas
- leitura individual e em lote das notificacoes do usuario
- registro de tokens web push por usuario com Firebase Cloud Messaging
- endpoint de teste de push para o usuario autenticado
- service worker para recebimento de push em background

Configuracao do Firebase:

- preencher `includes/firebase.local.php` com os dados do app web no Firebase
- informar a chave `vapid_key` do Web Push Certificate
- apontar `service_account_json_path` para o JSON da conta de servico com permissao de `Firebase Cloud Messaging API Admin`
- executar a criacao da tabela `MEGAG_PUSH_TOKENS` e da sequence `SEQ_MEGAG_PUSH_TOKENS`
- manter o site em `HTTPS` para o push funcionar fora de `localhost`

## APIs Principais

### `api/tasks.php`

Entidades principais:

- `spaces`
- `lists`
- `tasks`
- `comments`
- `attachments`
- `statuses`

Capacidades:

- CRUD de spaces e lists
- CRUD de tasks
- mover task entre status
- suporte a participantes
- suporte a status dinamicos

### `api/api_despesas.php`

Acoes principais:

- `get_doms`
- `search_fornecedores`
- `create`
- `list_mine`
- `list_approvals`
- `update_approval`
- `get_history`
- `get_rateio`
- `get_dashboard_data`

### `api/inventario_ti.php`

Acoes principais:

- `dashboard`
- `list`
- `get`
- `save`
- `history`
- `save_term`
- `list_requests`
- `get_request`
- `save_request`
- `request_form_domains`
- `request_responsibles`

### `api/api_despesas_config.php`

Acoes principais:

- `list_grupos`
- `add_grupo`
- `del_grupo`
- `list_politicas`
- `add_politica`
- `del_politica`
- `list_tipos`
- `add_tipo`
- `del_tipo`
- `list_aprovadores`
- `add_aprovador`
- `del_aprovador`

## Tabelas Oracle Envolvidas

### Tarefas

- `MEGAG_TASK_SPACES`
- `MEGAG_TASK_LISTS`
- `MEGAG_TASK_TASKS`
- `MEGAG_TASK_COMMENTS`
- `MEGAG_TASK_ATTACHMENTS`
- `MEGAG_TASK_NOTIFICACOES`
- `MEGAG_PUSH_TOKENS`
- `MEGAG_TASK_LIST_PARTICIPANTES`
- `MEGAG_TASK_LIST_STATUS`

### Despesas

- `MEGAG_DESP`
- `MEGAG_DESP_TIPO`
- `MEGAG_DESP_RATEIO`
- `MEGAG_DESP_APROVADORES`
- `MEGAG_DESP_APROVACAO`
- `MEGAG_DESP_ARQUIVO`
- `MEGAG_DESP_POLIT_CENTRO_CUSTO`
- `MEGAG_DESP_GRUPO`
- `MEGAG_DESP_FORNEC_AUX`

### Inventario Geral / Almoxarifado

- `MEGAG_TI_EQUIPAMENTOS`
- `MEGAG_TI_EQUIP_HIST`
- `MEGAG_TI_TERMOS`
- `MEGAG_ALMOX_SOLICITACOES`
- `SEQ_MEGAG_TI_EQUIPAMENTOS`
- `SEQ_MEGAG_TI_EQUIP_HIST`
- `SEQ_MEGAG_TI_TERMOS`
- `SEQ_MEGAG_ALMOX_SOLICITACOES`

### Tabelas de apoio do ERP

- `ABA_CENTRORESULTADO`
- `GE_USUARIO`
- `GE_PESSOA`

## Estrutura Resumida

```text
importadorV2/
|-- pages/
|   |-- home.php
|   |-- tarefas.php
|   |-- tarefas_criar_tasks.php
|   |-- tarefas_detalhes.php
|   |-- despesas.php
|   |-- despesas_aprovacao.php
|   |-- despesas_config.php
|   |-- crm.php
|   |-- wiki.php
|   `-- rh.php
|-- api/
|   |-- tasks.php
|   |-- api_despesas.php
|   |-- api_despesas_config.php
|   |-- firebase.php
|   |-- inventario_ti.php
|   `-- notif.php
|-- assets/
|   `-- js/
|       |-- tasks-kanban-enhancements.js
|       `-- inventario-geral.js
|-- includes/
|   |-- header.php
|   |-- sidebar.php
|   |-- firebase.local.php
|   |-- firebase.local.example.php
|   `-- footer.php
|-- helpers/
|   `-- firebase.php
|-- PKG/
|   |-- CREATE_TABLE.sql
|   |-- CREATE_TABLE_TASKS.sql
|   |-- CREATE_TABLE_TI_INVENTARIO.sql
|   `-- scripts / packages Oracle
|-- firebase-messaging-sw.php
`-- index.php
```

## Requisitos

- PHP `7.4+`
- Oracle com acesso por `PDO_OCI`
- Composer
- permissao de leitura e escrita nas tabelas Oracle usadas pelo sistema

## Observacoes para Publicacao

- validar a existencia das tabelas novas do modulo de tarefas em producao
- validar a package `PKG_MEGAG_DESP_CADASTRO` atualizada em producao
- validar politicas por centro de custo no modulo de despesas
- validar a estrutura `MEGAG_ALMOX_SOLICITACOES` em producao
- validar grants do schema para as tabelas e sequences do inventario / almox
- validar a tabela `MEGAG_PUSH_TOKENS` em producao
- validar as credenciais do Firebase no ambiente publicado
- revisar permissoes do schema utilizado pela aplicacao

## Ultima Atualizacao

- `Marco / 2026`
