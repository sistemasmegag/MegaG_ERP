# Mega G ERP 🚀

Sistema web desenvolvido em **PHP** para automação, importação de planilhas Excel e gestão operacional integrada ao **Oracle (ERP Consinco)**.

O projeto hoje contempla:

- 📊 Importações BI / Metas / Vendas (SSE)
- 💰 Importação de Comissões (SSE)
- 📦 Importação de Cargas/Metas Operacional (SSE)
- 📉 GAP / Faixas / Perspectivas (SSE)
- 🔎 Monitor de Importação Unificado (consulta dinâmica por tabela)
- 🔐 Controle de Usuários e Permissões
- 🗂️ **Módulo de Tarefas (Kanban + Detalhes + Comentários + Anexos)**
- 🎨 UI **Clean SaaS** (tema dark/light, cards, badges, sidebar moderna)

![Status do Projeto](https://img.shields.io/badge/Status-Em%20Produ%C3%A7%C3%A3o-brightgreen)
![PHP](https://img.shields.io/badge/PHP-7.4%20|%208.x-blue)
![Oracle](https://img.shields.io/badge/Database-Oracle-red)
![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-purple)

👨‍💻 Desenvolvido por:  
**Felipe Fernando Gonçalves**  
Dev Full Stack

---

## ✨ Destaques (Clean SaaS UI)

O sistema foi padronizado com layout **Clean SaaS**, focado em usabilidade, clareza e performance:

- 🌗 **Tema Dark/Light**
  - Persistência via `localStorage`
  - Tokens CSS globais (compatível com Bootstrap 5)
- 🧭 **Sidebar moderna**
  - Busca rápida
  - Item ativo por página
  - Mobile overlay + tecla ESC
- 🧱 **Componentes SaaS**
  - Cards com sombras suaves
  - Badges e chips
  - Tabelas com cabeçalho sticky
  - Modais elegantes para detalhe/log
- 📊 **Monitor unificado**
  - Consulta dinâmica sem reload
  - Contadores em tempo real
  - Logs completos sem truncamento
  - Renderização dinâmica de colunas

---

## 📋 Funcionalidades

### 📊 Dashboard
- Visão geral do sistema
- Totais por módulo
- Atalhos para operações

---

### 📦 Importação de Cargas/Metas (Operacional)
- Upload `.xls` / `.xlsx`
- Processamento **linha a linha**
- Log em tempo real via **SSE**
- Persistência em:
  - `MEGAG_IMP_SETORMETACAPAC`
- Status:
  - `S` (Sucesso)
  - `E` (Erro)
  - `P` (Pendente)

Processador:
- `processar.php`

---

### 💰 Importação de Comissões
- Upload `.xls` / `.xlsx`
- Processamento em tempo real (SSE)
- Persistência em:
  - `MEGAG_IMP_REPCCOMISSAO` *(ou equivalente no ambiente Consinco)*
- Interface dedicada e integrada ao monitor

Processador:
- `processa_comissao.php`

---

### 🎯 Importação de Custo de Comercialização (Tabela de Venda por Raio)
- Upload de planilhas com:
  - Nº Tabela de Venda
  - Sequência do Produto
  - Raio
  - Percentual de Redução (PERAD)
- Persistência em:
  - `MEGAG_IMP_TABVDAPRODRAIO`
- Controle de status, log e data de inclusão
- Integrado ao Monitor de Importação

Página:
- `imp_tabvdaprodraio.php`

Processador:
- `processors/processa_tabvdaprodraio.php`

---

## 🧩 Importações BI / Metas / Vendas

Todos os módulos abaixo seguem o padrão:
- Upload `.xls/.xlsx`
- Processamento **SSE**
- Cabeçalho validado por colunas obrigatórias
- Escrita em Oracle com status e logs (quando existir na tabela)
- UI Clean SaaS com cor de tema por módulo

---

### 📈 Importação BI Metas (Warning)
- Colunas esperadas:
  - `CODMETA, CODVENDEDOR, CODPERIODO, META, CODREGIAO, SEGMENTO, TIPORETIRA, CATEGORIA, SEQPRODUTO, DTAATUALIZACAO`
- Persistência em:
  - `MEGAG_IMP_BI_METAS`

Página:
- `imp_bi_metas.php`

Processador:
- `processors/processa_imp_bi_metas.php`

---

### 🧩 BI Metas Perspect (Warning)
- Colunas esperadas:
  - `CODMETA, PERSPEC, DATA, STATUS, ATUALIZACAO`
- Persistência em:
  - `MEGAG_BI_METAS_PERSPECT`

Página:
- `bi_metas_perspect.php`

Processador:
- `processors/processa_bi_metas_perspect.php`

---

### 🧱 Importação Metas Faixas (Danger)
- Colunas esperadas:
  - `CODPERIODO, CODVENDEDOR, CODMETA, CODFAIXA, DESCFAIXA, DESCFAIXARCA, DESCFATURAMENTO, FAIXAINI, FAIXAFIM, GANHO, DATAATAULIZACAO`
- Persistência em:
  - `MEGAG_IMP_METAS_FAIXAS`

Página:
- `imp_metas_faixa.php`

Processador:
- `processors/processa_metas_faixas.php`

---

### 🎯 Importação Metas (Danger)
- Colunas esperadas:
  - `CODMETA, CODVENDEDOR, META, CODREGIAO, SEGMENTO, TIPORETIRA, CATEGORIA, SEQPRODUTO, DTAATUALIZACAO`
- Persistência em:
  - `MEGAG_IMP_METAS`

Página:
- `imp_metas.php`

Processador:
- `processors/processa_imp_metas.php`

---

### 🧭 Importação Metas Perspec (Danger)
- Colunas esperadas:
  - `CODMETA, PERSPEC, DATA, STATUS, ATUALIZACAO`
- Persistência em:
  - `MEGAG_IMP_METAS_PERSPEC`

Página:
- `imp_metas_perspec.php`

Processador:
- `processors/processa_imp_metas_perspec.php`

---

### 📉 Importação Metas GAP (Danger)
- Colunas esperadas:
  - `CODPERIODO, CODMETA, GAP`
- Persistência em:
  - `MEGAG_IMP_METAS_GAP`

Página:
- `imp_metas_gap.php`

Processador:
- `processors/processa_imp_metas_gap.php`

---

### 💼 Importação Lançamento Comissão (Extra - Oracle)
- Colunas esperadas:
  - `CODEVENTO, SEQPESSOA, DTAHREMISSAO, OBSERVACAO, VLRTOTAL`
- Persistência em:
  - `MEGAG_IMP_LANCTOCOMISSAO`

Página:
- `imp_lanctocomissao.php`

Processador:
- `processors/processa_imp_lanctocomissao.php`

---

## 🗂️ Módulo de Tarefas (Kanban) — Novo 🚀

Módulo completo de tarefas estilo Kanban, com API própria e páginas dedicadas.

### ✅ Recursos
- **Spaces** (agrupadores)
- **Lists** (listas dentro do space)
- **Kanban por Status** (TODO/DOING/DONE)
- **Criar task** com:
  - Título, descrição, status, prioridade, tags, responsável, entrega, criado_por
- **Detalhes da task** com:
  - Edição e salvar
  - Excluir
  - Comentários
  - Anexos (upload, download, excluir)
- UI Clean SaaS aplicada em:
  - `tarefas.php` (Kanban)
  - `tarefas_criar_task.php` (Create)
  - `tarefas_detalhes.php` (Detalhes + Comentários + Anexos)

### 📌 API de Tarefas
Arquivo:
- `api/tasks.php`

Entities suportadas:
- `entity=ping`
- `entity=spaces`
- `entity=lists`
- `entity=tasks`
- `entity=comments`
- `entity=files`

Endpoints (exemplos):
- `GET  api/tasks.php?entity=spaces`
- `GET  api/tasks.php?entity=lists&space_id=3`
- `GET  api/tasks.php?entity=tasks&list_id=2`
- `GET  api/tasks.php?entity=tasks&task_id=1`
- `POST api/tasks.php?entity=tasks`
- `PUT  api/tasks.php?entity=tasks&task_id=1`
- `DELETE api/tasks.php?entity=tasks&task_id=1&user=Fulano`

Comentários:
- `GET    api/tasks.php?entity=comments&task_id=1`
- `POST   api/tasks.php?entity=comments`
- `DELETE api/tasks.php?entity=comments&comment_id=10&user=Fulano`

Anexos:
- `GET    api/tasks.php?entity=files&task_id=1`
- `POST   api/tasks.php?entity=files&action=upload` *(multipart/form-data)*
- `GET    api/tasks.php?entity=files&action=download&file_id=10`
- `DELETE api/tasks.php?entity=files&file_id=10&user=Fulano`

### 🧩 Observações técnicas (tarefas)
- `descricao` (CLOB) tratado com `DBMS_LOB.SUBSTR(...)` no detalhe da task
- Upload de arquivo gravando `BLOB` via `PDO::PARAM_LOB`
- Download retornando stream ou conteúdo do blob

---

## 🔎 Visualização de Dados (Monitor de Importação) — Atualizado

Consulta **unificada e inteligente** para **qualquer tabela de importação** cadastrada no Oracle.

Página:
- `dados_visualizar.php`

API:
- `api/api_dados.php`

### ✅ Mudanças implementadas (conforme orientação)
- **Filtros fixos (sempre visíveis)**
  - Tipo de dado *(via `CONSINCO.MEGAG_TABS_IMPORTACAO`)*
  - Usuário de inclusão
  - Data de inclusão
  - Status (`S`, `E`, `C`, `P`)
- **Tipo de dado dinâmico**
  - `<select>` populado por `MEGAG_TABS_IMPORTACAO`
  - Endpoint:
    - `GET api/api_dados.php?action=list_tipos`
- **Grid com tabela completa**
  - `SELECT t.*`
  - Adiciona campos padrão quando existirem:
    - `USULANCTO` / `USUINCLUSAO`
    - `DTAINCLUSAO`
    - `MSG_LOG` / `LOG` / `OBS` / `RESULTADO`
    - `STATUS`
- **Renderização dinâmica de colunas**
  - `thead/tbody` gerados conforme retorno
  - `STATUS` com badge
  - logs longos em modal (sem truncar)

### Status (legendas oficiais)
- `S` = Sucesso  
- `E` = Erro  
- `C` = Cancelado  
- `P` = Pendente  

> A API aplica filtros apenas se a coluna existir na tabela selecionada (detecção via `ALL_TAB_COLUMNS`).

---

## 🔐 Controle de Usuários & Permissões

Sistema baseado em aplicações e permissões granulares:

- Permissões carregadas em sessão
- Função central:
  - `temPermissao($app, $acao)`
- Alias inteligente para mapear módulos
- Admin (`ADMIN`) possui acesso total

### UX
- Módulos sem permissão:
  - aparecem desabilitados na sidebar
  - clique exibe modal informativo
- Backend valida permissão sempre (segurança real)

---

## 🧠 Processamento em Tempo Real (SSE)

O sistema utiliza **Server-Sent Events** para acompanhar o processamento das planilhas sem travar o navegador.

Fluxo:
1. Upload do arquivo
2. Salvamento no servidor
3. Abertura do `EventSource`
4. Processamento linha a linha
5. Log em tempo real
6. Evento `close` encerra o stream

Scripts (exemplos):
- `processar.php`
- `processa_comissao.php`
- `processors/processa_*.php`

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP (Vanilla)
- **Banco de Dados:** Oracle Database (PDO_OCI)
- **Frontend:**
  - HTML5
  - CSS3 (tokens SaaS)
  - Bootstrap 5
  - JavaScript (Fetch API + SSE)
- **Bibliotecas:**
  - PhpSpreadsheet

---

## 📂 Estrutura do Projeto (Atualizada)

- `pages/`
  - `home.php` (dashboard)
  - `cargas.php` (importação cargas/metas)
  - `comissoes.php` (importação comissões)
  - `imp_tabvdaprodraio.php` (custo comercialização)
  - `imp_lanctocomissao.php` (lançamento comissão)
  - `imp_bi_metas.php` (BI Metas)
  - `bi_metas_perspect.php` (BI Metas Perspect)
  - `imp_metas_faixa.php` (Metas Faixas)
  - `imp_metas.php` (Metas)
  - `imp_metas_perspec.php` (Metas Perspec)
  - `imp_metas_gap.php` (Metas GAP)
  - `dados_visualizar.php` (monitor unificado)
  - `tarefas.php` (kanban)
  - `tarefas_criar_task.php` (criar task)
  - `tarefas_detalhes.php` (detalhes + comentários + anexos)
  - `usuarios.php` (admin)
- `api/`
  - `api_dados.php`
  - `tasks.php` (API tarefas)
  - `api_usuarios.php`
- `processors/`
  - `processa_tabvdaprodraio.php`
  - `processa_imp_lanctocomissao.php`
  - `processa_imp_bi_metas.php`
  - `processa_bi_metas_perspect.php`
  - `processa_metas_faixas.php`
  - `processa_imp_metas.php`
  - `processa_imp_metas_perspec.php`
  - `processa_imp_metas_gap.php`
- `includes/`
  - `header.php` (layout + tokens + tema)
  - `sidebar.php` (menu)
  - `footer.php` (scripts + toggle mobile/overlay/ESC)
- `db_config/`
  - `db_connect.php`
- `assets/`
  - `images/logo.png`
- `routes/`
  - `check_session.php`
- `uploads/`
- `vendor/`
- `index.php` (controlador central com whitelist dinâmica de páginas)

---

## ✅ Requisitos

- PHP **7.4+** (recomendado 8.x)
- Extensão/driver Oracle para PHP (**PDO_OCI**)
- Composer (dependências como PhpSpreadsheet)
- Acesso ao Oracle (Consinco) com permissões de leitura/escrita nas tabelas usadas

---

## 📌 Status do Projeto

✅ Estável  
🚀 Em produção  
🧩 Arquitetura modular  
🎨 UI Clean SaaS  
🔐 Segurança por permissão  
⚡ Processamento em tempo real  
🗂️ Kanban de Tarefas com Comentários e Anexos  

---
