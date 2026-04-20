# 🚀 Módulo de Lançamento e Gerenciamento de Campanhas (v2)

Módulo avançado desenvolvido para o ecossistema **ImportadorV2**, focado na automação do ciclo de vida de campanhas de fornecedores, integração com o ERP Consinco e gerenciamento dinâmico de metas.

## 🛠️ Novas Funcionalidades

### 1. Assistente de Lançamento (4 Passos)
Um fluxo de trabalho híbrido (Formulários + Tabelas Dinâmicas) que garante a integridade dos dados:
- **Passo 1: Dados Básicos**: Cadastro de vigência, nome e seleção dinâmica de tipos de meta (Positivação, Faturamento, Vendas).
- **Passo 2: Vínculo de Produtos**: Interface com busca em tempo real na `CONSINCO.MAP_PRODUTO` e vinculação de produtos específicos a metas pré-definidas.
- **Passo 3: Premiações**: Configuração dinâmica de prêmios por ranking (Ouro, Prata, Bronze) para múltiplos grupos (G1, G2, G3).
- **Passo 4: Importação**: Processamento massivo de metas por representante com amarração automática do `CODCAMPANHA`.

### 2. Painel de Gerenciamento Centralizado
Interface premium para controle total das campanhas existentes:
- Listagem com filtros em tempo real.
- Status dinâmicos (Ativo/Inativo).
- **Edição via Modal XL**: Permite alterar qualquer campo de uma campanha lançada sem sair da tela de gestão, reutilizando toda a lógica do assistente de lançamento.

### 3. Inteligência de Dados & UX
- **Carregamento Assíncrono**: Busca de descrições de produtos via AJAX com indicadores de loading.
- **Geração Automática de IDs**: Lógica de `MAX(ID) + 1` centralizada na API para evitar conflitos.
- **Design SaaS Premium**: Interface limpa, responsiva e com notificações em efeito *glassmorphism*.

## 📂 Estrutura Técnica

| Arquivo | Descrição |
| :--- | :--- |
| `pages/lancamento_campanhas.php` | O coração do módulo. Wizard de 4 passos com suporte a modo "embed". |
| `pages/gerenciamento_campanhas.php` | Painel de controle e listagem de campanhas. |
| `api/api_campanhas.php` | API centralizada (CRUD, Listagem, Busca de IDs e Produtos). |
| `processors/processa_universal_insert.php` | Motor de importação atualizado para suportar `fixed_parameters`. |

## 🗄️ Integração com Banco de Dados (Oracle)

O sistema opera sobre as seguintes tabelas staging:
- `CONSINCO.MEGAG_CAMPFORN`: Cabeçalho da Campanha.
- `CONSINCO.MEGAG_CAMPFORNCAMPMETA`: Definição de tipos de meta por campanha.
- `CONSINCO.MEGAG_CAMPFORNMETAPROD`: Vínculo de produtos e metas.
- `CONSINCO.MEGAG_CAMPFORNGRPREM`: Configuração de premiações e rankings.

---
*Atualizado em 20/04/2026 - Módulo pronto para implantação em produção.*
