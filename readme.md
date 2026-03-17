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
- 🚀 **Aplicativos SaaS (CRM, Wiki Corporativa e RH/DP)**
- 🔔 **Sistema de Notificações Global (Toasts + Painel Inteligente)**
- 📈 **Analytics Avançado com ApexCharts no Dashboard de Dados**
- 💸 **Módulo de Reembolsos / Despesas Corporativas** ← _novo_
- 🎨 UI **Clean SaaS** (tema dark/light, tokens CSS centralizados, glassmorphism)

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
  - **Diferencial:** Gráficos estáticos interativos gerados sob os dados (ApexCharts).
- 🔔 **Ecossistema de Notificação**
  - Painel global no Footer com divisão de "Lido/Não Lido".
  - Notificações Toasts (popups de não-bloqueio) avisando do tráfego.
  - Sinos com badge counter rodando de forma invisível via polling longo.

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

---

### 🧩 BI Metas Perspect / Metas Perspec / Metas GAP / Metas Faixas / Lançamento Comissão

Todos integrados ao Monitor de Importação com as respectivas tabelas Oracle e processadores SSE em `processors/`.

---

## 💸 Módulo de Reembolsos / Despesas Corporativas — Novo 🚀

Módulo completo de **solicitação, acompanhamento e aprovação hierárquica** de despesas, integrando com as PKGs Oracle do ERP.

### 🗂️ Tabelas Oracle envolvidas

| Tabela | Finalidade |
|---|---|
| `MEGAG_DESP` | Registro principal da despesa |
| `MEGAG_DESP_TIPO` | Categorias de despesa |
| `MEGAG_DESP_RATEIO` | Rateio entre múltiplos Centros de Custo |
| `MEGAG_DESP_APROVADORES` | Aprovadores por CC e hierarquia |
| `MEGAG_DESP_APROVACAO` | Log de aprovações / reprovações |
| `MEGAG_DESP_ARQUIVO` | Anexos (recibos, notas) |
| `MEGAG_DESP_POLIT_CENTRO_CUSTO` | Políticas de aprovação por CC |
| `ABA_CENTRORESULTADO` | Centros de Custo do ERP Consinco |

### ✅ Funcionalidades implementadas

#### 📋 Lançamento de Despesas (`pages/despesas.php`)
- Modal split (drag & drop de anexo esquerda / formulário direita)
- Campos:
  - Moeda, Valor, Estabelecimento (Fornecedor), Data da Despesa
  - Categoria (`MEGAG_DESP_TIPO`) com **TomSelect autocomplete**
  - Centro de Custo com **TomSelect autocomplete**
  - Data de Vencimento, Comentário/Observação
- Upload de anexo com preview (PDF = iframe, imagem = background)

#### 🏢 Múltiplos Centros de Custo (Rateio)
- Botão **`+`** ao lado do campo de CC para adicionar quantos CCs forem necessários
- Cada CC adicional tem botão **`×`** para remover com animação `fadeInDown`
- **Campo de valor por CC**: ao adicionar o 2º CC, aparecem inputs de valor individuais (roxos) próximos a cada linha
- **Indicador de soma em tempo real**:
  - Barra de progresso preenchendo conforme a soma dos valores
  - 🟡 Amarelo: `Faltam R$ X,XX`
  - 🔴 Vermelho: `Excesso R$ X,XX`
  - 🟢 Verde: `✓ Ok` (bate com o total)
- **Validação no envio**: bloqueia o submit com SweetAlert2 se a soma não coincidir com o total da despesa
- Rateio gravado em `MEGAG_DESP_RATEIO` via `PRC_INS_MEGAG_DESP_RATEIO`, com o valor exato digitado por CC

#### 📊 Tabela de Despesas
- Métricas no header: Total, Em Aprovação, Reembolsado, Reprovado (com valores)
- Coluna "Centro de custo" exibe badge **`◆ Rateio N CCs`** (roxo/índigo) quando há múltiplos centros

#### 🔍 Modal de Detalhes
- Visualizador de anexo (PDF ou imagem) no lado esquerdo
- Dados da despesa no lado direito
- **Seção de Rateio expansível**: aparece automaticamente quando há > 1 CC
  - Cards por CC com nome, código, barra de proporção gradiente e valor + percentual
  - Collapse com chevron animado

#### ✅ Aprovação Hierárquica
- PKG `PRC_UPD_MEGAG_DESP_APROVACAO` gere aprovação por nível por CC
- Impede auto-aprovação
- Finaliza a despesa como `APROVADO` quando todos os níveis aprovam
- Rejeita imediatamente ao `REJEITADO`
- Timeline de aprovação no modal de detalhes com nome, nível e data

### 📡 API: `api/api_despesas.php`

| Action | Descrição |
|---|---|
| `get_doms` | Lista categorias e centros de custo para os selects |
| `create` | Cria nova despesa + arquivo + rateio por CC |
| `list_mine` | Lista despesas do usuário logado com métricas e contagem de rateio |
| `list_approvals` | Lista despesas pendentes de aprovação para o aprovador logado |
| `update_approval` | Aprova ou rejeita uma despesa |
| `get_history` | Trilha de aprovações de uma despesa |
| `get_rateio` | Retorna os CCs e valores do rateio de uma despesa |
| `get_dashboard_data` | Dados para gráficos: por categoria, mensal, top CCs |

### 🗄️ Package Oracle: `PKG_MEGAG_DESP_CADASTRO`

Arquivo: `PKG/PackageBody.sql`

Procedures principais:

| Procedure | Finalidade |
|---|---|
| `PRC_INS_MEGAG_DESP` | Inserir despesa principal |
| `PRC_LIST_MEGAG_DESP` | Listar despesas por usuário/status |
| `PRC_UPD_MEGAG_DESP` | Atualizar despesa (só status LANCADO) |
| `PRC_INS_MEGAG_DESP_RATEIO` | Inserir linha de rateio por CC |
| `PRC_LIST_MEGAG_DESP_RATEIO` | Listar rateio de uma despesa |
| `PRC_UPD_MEGAG_DESP_APROVACAO` | Aprovar / Rejeitar despesa com hierarquia |
| `PRC_LIST_MEGAG_DESP_APROVACAO` | Listar despesas pendentes de aprovação |
| `PRC_INS_MEGAG_DESP_ARQUIVO` | Inserir arquivo na tabela de anexos |
| `PRC_LIST_MEGAG_DESP_CENTRO_CUSTO` | Listar centros de custo disponíveis |

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
- UI Clean SaaS aplicada

### 📌 API de Tarefas
Arquivo: `api/tasks.php`

---

## 🚀 Novos Módulos SaaS Incorporados

### 💼 CRM & Leads (Gestão Comercial)
- Quadro **Kanban Drag & Drop** super leve (sem lib externa).
- Funil com etapas de venda: Novos Leads, Contato, Proposta, Ganho, Perdido.

### 📚 Base de Conhecimento (Wiki)
- Divisão responsiva: Lateral fixa com **Tópicos** (TI, RH, Comerciais, etc).
- Dois Estados Visuais: Grid de visualização e State "Reader" focado no conteúdo.

### 👥 Recursos Humanos (RH / Departamento Pessoal)
- Tabela de **Minhas Solicitações** para empregados.
- **Mural de Avisos** para comunicação interna.

---

## 🔎 Visualização de Dados (Monitor de Importação)

Consulta **unificada e inteligente** para **qualquer tabela de importação** cadastrada no Oracle.

Página: `dados_visualizar.php` | API: `api/api_dados.php`

### Status (legendas oficiais)
- `S` = Sucesso  
- `E` = Erro  
- `C` = Cancelado  
- `P` = Pendente  

---

## 🔐 Controle de Usuários & Permissões

- Permissões carregadas em sessão
- Função central: `temPermissao($app, $acao)`
- Admin (`ADMIN`) possui acesso total
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

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP (Vanilla)
- **Banco de Dados:** Oracle Database (PDO_OCI)
- **Frontend:**
  - HTML5 + CSS3 (tokens SaaS)
  - Bootstrap 5
  - JavaScript (Fetch API + SSE)
- **Bibliotecas:**
  - PhpSpreadsheet
  - TomSelect (autocomplete)
  - SweetAlert2 (alertas)
  - ApexCharts (gráficos)

---

## 📂 Estrutura do Projeto

```
importadorV2/
├── pages/
│   ├── home.php                  # Dashboard
│   ├── despesas.php              # 💸 Módulo de Reembolsos
│   ├── gerenciar_despesas.php    # Aprovação de Despesas
│   ├── dados_visualizar.php      # Monitor de Importação
│   ├── tarefas.php               # Kanban de Tarefas
│   ├── crm.php                   # CRM / Leads
│   ├── wiki.php                  # Base de Conhecimento
│   ├── rh.php                    # RH / DP
│   ├── usuarios.php              # Admin de Usuários
│   └── imp_*.php                 # Importações (cargas, metas, BI, etc.)
├── api/
│   ├── api_despesas.php          # 💸 API de Despesas/Reembolsos
│   ├── api_dados.php
│   ├── tasks.php
│   ├── api_usuarios.php
│   ├── crm.php
│   ├── wiki.php
│   ├── rh.php
│   └── notif.php
├── PKG/
│   ├── PackageBody.sql           # Package Oracle principal
│   ├── DespesaCRUD.sql
│   ├── RateioCRUD.sql
│   ├── CentroCustoDespesaCRUD.sql
│   ├── TipoDespesaCRUD.sql
│   └── AprovadoresCRUD.sql
├── processors/
│   └── processa_*.php
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── db_config/
│   └── db_connect.php
├── uploads/
├── vendor/
└── index.php                     # Controlador central
```

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
🧩 Arquitetura modular e APIs RESTful  
🎨 UI Clean SaaS Padronizada  
🔐 Segurança por permissão  
⚡ Processamento em tempo real (SSE)  
🗂️ Kanban de Tarefas com Comentários e Anexos  
💸 Módulo de Reembolsos com Rateio multi-CC e Aprovação Hierárquica  
🚀 Central Global Integrada (Wiki, CRM, RH e Notificações Toasts)

---

> Última atualização: **Março 2026**
