# Importador Mega G 🚀

Sistema web desenvolvido em **PHP** para automação e importação de planilhas Excel diretamente para o **Oracle (ERP Consinco)**.  
Atualmente suporta **Cargas/Metas**, **Comissões**, **Custo de Comercialização (Tabela de Venda por Raio)** e o conjunto completo de **importações de Metas (BI e Vendas)**, com **processamento em tempo real via SSE**, **dashboards**, **monitor avançado de dados**, **controle de permissões** e **UI Clean SaaS**.

![Status do Projeto](https://img.shields.io/badge/Status-Concluído-brightgreen)
![PHP](https://img.shields.io/badge/PHP-7.4%20|%208.x-blue)
![Oracle](https://img.shields.io/badge/Database-Oracle-red)
![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-purple)

👨‍💻 Desenvolvido por:  
Felipe Fernando Gonçalves  
Dev Full Stack

---

## ✨ Destaques (Clean SaaS UI)

O sistema foi totalmente modernizado com um layout **Clean SaaS**, focado em usabilidade, clareza e performance:

- 🌗 **Tema Dark/Light automático**
  - Detecta preferência do sistema
  - Persistência via `localStorage`
  - Tokens CSS globais compatíveis com Bootstrap 5
- 🧭 **Sidebar moderna**
  - Busca rápida
  - Estado ativo por página
  - Comportamento mobile (overlay + ESC)
- 🧱 **Componentes SaaS**
  - Cards com sombras suaves
  - Tabelas com cabeçalho sticky
  - Chips rápidos de filtro
  - Modais elegantes para detalhe/log
- 📊 **Monitor unificado**
  - Consulta dinâmica sem reload
  - Contadores em tempo real
  - Logs completos sem truncamento
  - Renderização dinâmica de colunas (thead/tbody gerado conforme retorno da API)

---

## 📋 Funcionalidades

### 📊 Dashboard
- Visão geral do sistema:
  - Total de Cargas/Metas importadas
  - Total de Comissões importadas
  - Total de registros com erro
- Acesso rápido para os módulos operacionais

---

### 📦 Importação de Cargas/Metas (Operacional)
- Upload de planilhas `.xls` / `.xlsx`
- Processamento **linha a linha**
- Log em tempo real via **SSE (Server-Sent Events)**
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
- Upload de planilhas `.xls` / `.xlsx`
- Processamento financeiro em tempo real (SSE)
- Persistência em:
  - `MEGAG_IMP_REPCCOMISSAO`
- Interface dedicada e integrada ao monitor

Processador:
- `processa_comissao.php`

---

### 🎯 Importação de Custo de Comercialização
**(Tabela de Venda por Raio)**

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

## 🧩 Importações BI / Metas / Vendas (Novos Módulos)

Todos os módulos abaixo seguem o padrão:
- Upload `.xls/.xlsx`
- Processamento **SSE**
- Cabeçalho validado por colunas obrigatórias
- Escrita em Oracle com status e logs (quando existir na tabela)
- UI Clean SaaS com cor de tema por módulo (Warning/Danger)

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

## 🔎 Visualização de Dados (Monitor de Importação)

Consulta **unificada e inteligente** para todos os módulos.

Página:
- `dados_visualizar.php`

API:
- `api/api_dados.php`

### Tipos suportados (select / modos):
- 📊 Cargas/Metas (Operacional)
- 💰 Comissões
- 🎯 Custo de Comercialização (Tab. Venda por Raio)
- 📈 BI Metas (Importação)
- 🧩 BI Metas Perspect
- 🎯 Metas (Importação)
- 🧱 Metas Faixas
- 🧭 Metas Perspec (Importação)
- 📉 Metas GAP

### Filtros dinâmicos:
- Tipo de dados
- Data de referência
- Status
- Setor / Turno (modo operacional de metas)
- Nº Tabela Venda / Produto / Raio (custo comercialização)

### Recursos avançados:
- Chips rápidos:
  - Todos
  - Sucesso
  - Erro
  - Pendente
- Contadores dinâmicos:
  - Total
  - Sucesso
  - Erro
  - Pendente
- Modal de detalhe:
  - Visualização completa de logs
  - Observações sem truncamento
  - UX consistente com SaaS
- Renderização dinâmica:
  - Cabeçalho e colunas geradas conforme os dados retornados pela API
  - Mantém comportamento especial para `STATUS`, `MSG_LOG`, `OBSERVACAO`

---

## 🔐 Controle de Usuários & Permissões

Sistema baseado em **aplicações e permissões granulares**:

- Permissões carregadas em sessão
- Função central:
  - `temPermissao($app, $acao)`
- Alias inteligente para mapear módulos
- Admin (`ADMIN`) possui acesso total

### Comportamento de UX:
- Módulos sem permissão:
  - Aparecem desabilitados na sidebar
  - Clique abre **modal informativo**
    > “Você não tem permissão para acessar este módulo”
- Backend sempre valida permissão (segurança real)

---

## 🧠 Processamento em Tempo Real (SSE)

O sistema utiliza **Server-Sent Events** para acompanhar o processamento das planilhas **sem travar o navegador**.

Fluxo:
1. Upload do arquivo
2. Salvamento no servidor (`upload.php`)
3. Abertura de `EventSource`
4. Processamento linha a linha
5. Log exibido em tempo real
6. Evento `close` encerra o stream e libera o botão na UI

Scripts (exemplos):
- `processar.php` → Cargas/Metas
- `processa_comissao.php` → Comissões
- `processors/processa_tabvdaprodraio.php` → Custo Comercialização
- `processors/processa_imp_bi_metas.php` → BI Metas
- `processors/processa_bi_metas_perspect.php` → BI Metas Perspect
- `processors/processa_metas_faixas.php` → Metas Faixas
- `processors/processa_imp_metas.php` → Metas
- `processors/processa_imp_metas_perspec.php` → Metas Perspec
- `processors/processa_imp_metas_gap.php` → Metas GAP
- `processors/processa_imp_lanctocomissao.php` → Lançamento Comissão

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
  - PhpSpreadsheet (Excel)

---

## 📂 Estrutura do Projeto (Atualizada)

- `pages/`  
  - `home.php` (dashboard)
  - `cargas.php` (importação cargas/metas)
  - `comissoes.php` (importação comissões)
  - `imp_tabvdaprodraio.php` (custo comercialização)
  - `imp_lanctocomissao.php` (lançamento comissão)
  - `imp_bi_metas.php` (BI Metas - warning)
  - `bi_metas_perspect.php` (BI Metas Perspect - warning)
  - `imp_metas_faixa.php` (Metas Faixas - danger)
  - `imp_metas.php` (Metas - danger)
  - `imp_metas_perspec.php` (Metas Perspec - danger)
  - `imp_metas_gap.php` (Metas GAP - danger)
  - `dados_visualizar.php` (monitor unificado)
  - `tarefas.php` (kanban)
  - `usuarios.php` (admin)
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
  - `header.php` (layout + CSS tokens + tema)
  - `sidebar.php` (menu)
  - `footer.php` (scripts + toggle mobile/overlay/ESC)
- `db_config/`
  - `db_connect.php`
- `assets/`
  - `images/logo.png`
- `api/`
  - `api_dados.php`
  - `api_tarefas.php`
  - `api_usuarios.php`
- `upload.php`

---

## ✅ Requisitos

- PHP **7.4+** (recomendado 8.x)
- Extensão/driver Oracle para PHP (PDO_OCI)
- Composer (para dependências como PhpSpreadsheet)
- Acesso ao Oracle (Consinco) e permissões de leitura/escrita nas tabelas usadas

---

## 📌 Status do Projeto

✅ Estável  
🚀 Em produção  
🧩 Arquitetura modular  
🎨 UI Clean SaaS  
🔐 Segurança por permissão  
⚡ Processamento em tempo real  

---
