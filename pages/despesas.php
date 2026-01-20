<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'despesas';
?>

<style>
/* ===== Clean SaaS (escopado pra DESPESAS) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(13,110,253,.04));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(13,110,253,.14), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(13,110,253,.22), transparent 60%);
    filter: blur(6px);
    transform: rotate(10deg);
    pointer-events:none;
}
.saas-title{ font-weight: 900; letter-spacing: -.02em; margin:0; color: var(--saas-text); }
.saas-subtitle{ margin: 6px 0 0; color: var(--saas-muted); font-size: 14px; }

/* Card SaaS */
.saas-card{
    background: var(--saas-surface) !important;
    border: 1px solid var(--saas-border) !important;
    border-radius: 18px !important;
    box-shadow: var(--saas-shadow) !important;
    overflow:hidden;
    backdrop-filter: blur(10px);
}
.saas-card .card-header{
    background: transparent !important;
    border-bottom: 1px solid var(--saas-border) !important;
}
.saas-kicker{
    color: var(--saas-muted);
    font-size: 12px;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 900;
}

/* Form */
.saas-form .form-label{
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
    color: var(--saas-muted);
    margin-bottom: .35rem;
}
.saas-form .form-control,
.saas-form .form-select{
    border-radius: 14px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
    color: var(--saas-text);
    height: 44px;
}
html[data-theme="dark"] .saas-form .form-control,
html[data-theme="dark"] .saas-form .form-select{
    background: rgba(255,255,255,.06);
}
.saas-form textarea.form-control{ height:auto; min-height: 110px; padding-top: 10px; }
.saas-form .form-control:focus,
.saas-form .form-select:focus{
    border-color: rgba(13,110,253,.45);
    box-shadow: 0 0 0 .22rem var(--ring);
    background: var(--saas-surface);
}

.saas-btn{
    height: 44px;
    border-radius: 14px;
    font-weight: 900;
    box-shadow: 0 10px 18px rgba(13,110,253,.18);
}

/* Table container SaaS */
.saas-table-wrap{
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow);
    overflow: hidden;
}
.saas-table-scroll{
    max-height: 62vh;
    overflow:auto;
    scrollbar-width: thin;
}
.saas-table thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: rgba(17,24,39,.03) !important;
    color: var(--saas-text) !important;
}
html[data-theme="dark"] .saas-table thead th{
    background: rgba(255,255,255,.06) !important;
}
.saas-table.table-hover tbody tr:hover{
    background: rgba(13,110,253,.06) !important;
}
html[data-theme="dark"] .saas-table.table-hover tbody tr:hover{
    background: rgba(13,110,253,.12) !important;
}

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }

/* Detail link like your monitor */
.saas-detail-link{
    display:flex; align-items:center; gap:8px; max-width: 100%;
}
.saas-detail-link .label{
    display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.saas-detail-link .icon{
    width: 28px; height: 28px; border-radius: 10px;
    display:inline-flex; align-items:center; justify-content:center;
    border: 1px solid var(--saas-border);
    background: rgba(13,110,253,.08);
    color: rgba(13,110,253,.95);
    flex: 0 0 auto;
    transition: .16s ease;
}
html[data-theme="dark"] .saas-detail-link .icon{
    background: rgba(13,110,253,.14);
    border-color: rgba(255,255,255,.10);
    color: rgba(255,255,255,.92);
}
.saas-detail-link:hover .icon{
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(13,110,253,.12);
}
.saas-detail-link:hover{ text-decoration:none; }
</style>

<main class="main-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
      <button class="mobile-toggle me-3" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
      </button>
      <h4 class="m-0 fw-bold text-dark">CRM Mega G</h4>
    </div>

    <div class="saas-head mb-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
        <div>
          <h3 class="saas-title">Despesas</h3>
          <p class="saas-subtitle">
            Lance despesas e acompanhe o fluxo de aprovação do gestor.
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">
            Workflow: Pendente → Aprovada/Reprovada
          </span>
        </div>
      </div>
    </div>

    <div class="card saas-card mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
            <i class="bi bi-receipt fs-4 text-primary"></i>
          </div>
          <div>
            <div class="saas-kicker">Novo lançamento</div>
            <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Cadastrar despesa</div>
          </div>
        </div>
        <span class="text-muted small">Campos obrigatórios: Data, Valor, Gestor</span>
      </div>

      <div class="card-body saas-form">
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label">Data da despesa *</label>
            <input type="date" class="form-control" id="fData">
          </div>

          <div class="col-md-2">
            <label class="form-label">Valor (R$) *</label>
            <input type="number" class="form-control" id="fValor" step="0.01" min="0">
          </div>

          <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <input type="text" class="form-control" id="fCategoria" placeholder="Ex: Combustível, Refeição...">
          </div>

          <div class="col-md-2">
            <label class="form-label">Centro de custo</label>
            <input type="text" class="form-control" id="fCentroCusto" placeholder="Ex: 1102">
          </div>

          <div class="col-md-3">
            <label class="form-label">Fornecedor</label>
            <input type="text" class="form-control" id="fFornecedor" placeholder="Ex: Posto X, Restaurante Y">
          </div>

          <div class="col-md-3">
            <label class="form-label">Forma de pagamento</label>
            <select class="form-select" id="fFormaPgto">
              <option value="">Selecione</option>
              <option value="PIX">PIX</option>
              <option value="CARTAO">Cartão</option>
              <option value="DINHEIRO">Dinheiro</option>
              <option value="BOLETO">Boleto</option>
              <option value="TRANSFERENCIA">Transferência</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Gestor responsável *</label>
            <input type="text" class="form-control" id="fGestor" placeholder="Usuário do gestor (ex: JOAO.SILVA)">
          </div>

          <div class="col-md-6">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" id="fDescricao" placeholder="Detalhe a despesa..."></textarea>
          </div>

          <div class="col-12 d-flex gap-2 mt-2">
            <button class="btn btn-primary saas-btn px-4" onclick="criarDespesa()">
              <i class="bi bi-send me-1"></i> ENVIAR PARA APROVAÇÃO
            </button>
            <button class="btn btn-outline-secondary saas-btn px-4" onclick="limparForm()">
              <i class="bi bi-eraser me-1"></i> LIMPAR
            </button>
          </div>

          <div class="col-12">
            <div class="text-muted small mt-2">
              Ao enviar, a despesa entra como <strong>Pendente</strong>. Você poderá editar/cancelar enquanto estiver pendente.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card saas-card mb-3">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
            <i class="bi bi-list-check fs-4 text-primary"></i>
          </div>
          <div>
            <div class="saas-kicker">Minhas despesas</div>
            <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Acompanhar status e histórico</div>
          </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <select class="form-select" id="fStatus" style="height:44px;border-radius:14px;" onchange="carregarMinhas()">
            <option value="">Todos</option>
            <option value="P">Pendente</option>
            <option value="A">Aprovada</option>
            <option value="R">Reprovada</option>
            <option value="C">Cancelada</option>
          </select>
          <button class="btn btn-primary saas-btn" onclick="carregarMinhas()" title="Atualizar">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        </div>
      </div>

      <div class="saas-table-wrap">
        <div class="saas-table-scroll">
          <table class="table table-hover mb-0 align-middle table-sm saas-table">
            <thead>
              <tr>
                <th class="py-3 ps-3">ID</th>
                <th class="py-3">Data</th>
                <th class="py-3 text-end">Valor</th>
                <th class="py-3">Categoria</th>
                <th class="py-3">Centro</th>
                <th class="py-3">Gestor</th>
                <th class="py-3 text-center">Status</th>
                <th class="py-3">Detalhe</th>
                <th class="py-3 text-end pe-3">Ações</th>
              </tr>
            </thead>
            <tbody id="tbodyMinhas"></tbody>
          </table>
        </div>

        <div id="loadingMinhas" class="text-center p-5 text-muted" style="display:none;">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <p class="mb-0">Carregando...</p>
        </div>

        <div id="emptyMinhas" class="text-center p-5 text-muted" style="display:none;">
          <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
          <div class="fw-bold text-dark mb-1">Nenhuma despesa encontrada</div>
          <div class="text-muted">Ajuste o filtro e tente novamente.</div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Modal detalhe -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px; border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text); box-shadow: var(--saas-shadow);">
      <div class="modal-header" style="border-bottom:1px solid var(--saas-border);">
        <h5 class="modal-title fw-bold" id="detailModalTitle" style="letter-spacing:-.01em;">Detalhe</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted small mb-2" id="detailModalMeta"></div>
        <pre class="mb-0" id="detailModalBody" style="white-space:pre-wrap; word-break:break-word; background: rgba(17,24,39,.03); border:1px solid var(--saas-border); border-radius:14px; padding: 14px; color: var(--saas-text);"></pre>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--saas-border);">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px; border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text); box-shadow: var(--saas-shadow);">
      <div class="modal-header" style="border-bottom:1px solid var(--saas-border);">
        <h5 class="modal-title fw-bold" style="letter-spacing:-.01em;">Editar despesa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body saas-form">
        <input type="hidden" id="eId">

        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Data *</label>
            <input type="date" class="form-control" id="eData">
          </div>
          <div class="col-md-3">
            <label class="form-label">Valor *</label>
            <input type="number" class="form-control" id="eValor" step="0.01" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <input type="text" class="form-control" id="eCategoria">
          </div>
          <div class="col-md-3">
            <label class="form-label">Centro de custo</label>
            <input type="text" class="form-control" id="eCentro">
          </div>

          <div class="col-md-4">
            <label class="form-label">Fornecedor</label>
            <input type="text" class="form-control" id="eFornecedor">
          </div>
          <div class="col-md-4">
            <label class="form-label">Forma pgto</label>
            <select class="form-select" id="eForma">
              <option value="">Selecione</option>
              <option value="PIX">PIX</option>
              <option value="CARTAO">Cartão</option>
              <option value="DINHEIRO">Dinheiro</option>
              <option value="BOLETO">Boleto</option>
              <option value="TRANSFERENCIA">Transferência</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Gestor *</label>
            <input type="text" class="form-control" id="eGestor">
          </div>

          <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" id="eDesc"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--saas-border);">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary rounded-pill px-4" onclick="salvarEdicao()">
          <i class="bi bi-save me-1"></i> Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const escapeHtml = (str) => {
  if (str === null || str === undefined) return '';
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
};

const formatMoney = (num) => {
  if(num === null || num === undefined || num === '') return '-';
  return parseFloat(num).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  if (String(dateString).includes('/')) return dateString;
  const partes = String(dateString).split(' ')[0].split('-');
  return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : dateString;
};

const renderStatus = (status) => {
  if(status === 'P') return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pendente</span>';
  if(status === 'A') return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aprovada</span>';
  if(status === 'R') return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Reprovada</span>';
  if(status === 'C') return '<span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Cancelada</span>';
  return escapeHtml(status);
};

function openDetailModal({ title, meta, body }) {
  document.getElementById('detailModalTitle').textContent = title || 'Detalhe';
  document.getElementById('detailModalMeta').textContent = meta || '';
  document.getElementById('detailModalBody').textContent = body || '-';

  const modalEl = document.getElementById('detailModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function limparForm(){
  document.getElementById('fData').value = '';
  document.getElementById('fValor').value = '';
  document.getElementById('fCategoria').value = '';
  document.getElementById('fCentroCusto').value = '';
  document.getElementById('fFornecedor').value = '';
  document.getElementById('fFormaPgto').value = '';
  document.getElementById('fGestor').value = '';
  document.getElementById('fDescricao').value = '';
}

async function apiPost(payload){
  const resp = await fetch('api/api_despesas.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await resp.json();
  if(!json.sucesso) throw new Error(json.erro || 'Erro desconhecido');
  return json;
}

async function criarDespesa(){
  const data = document.getElementById('fData').value;
  const valor = document.getElementById('fValor').value;
  const gestor = document.getElementById('fGestor').value.trim();

  if(!data) return alert('Informe a data da despesa.');
  if(!valor) return alert('Informe o valor.');
  if(!gestor) return alert('Informe o gestor responsável.');

  try{
    await apiPost({
      action: 'create',
      data_despesa: data,
      valor: valor,
      categoria: document.getElementById('fCategoria').value,
      centro_custo: document.getElementById('fCentroCusto').value,
      fornecedor: document.getElementById('fFornecedor').value,
      forma_pgto: document.getElementById('fFormaPgto').value,
      gestor: gestor,
      descricao: document.getElementById('fDescricao').value
    });

    limparForm();
    await carregarMinhas();
    alert('Despesa enviada para aprovação com sucesso!');
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function carregarMinhas(){
  const tbody = document.getElementById('tbodyMinhas');
  const loading = document.getElementById('loadingMinhas');
  const empty = document.getElementById('emptyMinhas');
  const status = document.getElementById('fStatus').value;

  tbody.innerHTML = '';
  loading.style.display = 'block';
  empty.style.display = 'none';

  try{
    const json = await apiPost({ action: 'list_mine', status });
    loading.style.display = 'none';

    const dados = json.dados || [];
    if(!dados.length){
      empty.style.display = 'block';
      return;
    }

    dados.forEach(row => {
      const tr = document.createElement('tr');

      const safeDesc = escapeHtml(row.DESCRICAO || '-');
      const meta = `ID: #${row.ID} | Gestor: ${row.GESTOR || '-'} | Status: ${row.STATUS || '-'}`;

      const btnEdit = (row.STATUS === 'P')
        ? `<button class="btn btn-sm btn-outline-primary" onclick="abrirEditar(${row.ID})" title="Editar"><i class="bi bi-pencil"></i></button>`
        : `<button class="btn btn-sm btn-outline-primary" disabled title="Editar (somente pendente)"><i class="bi bi-pencil"></i></button>`;

      const btnCancel = (row.STATUS === 'P')
        ? `<button class="btn btn-sm btn-outline-danger" onclick="cancelar(${row.ID})" title="Cancelar"><i class="bi bi-x-lg"></i></button>`
        : `<button class="btn btn-sm btn-outline-danger" disabled title="Cancelar (somente pendente)"><i class="bi bi-x-lg"></i></button>`;

      tr.innerHTML = `
        <td class="ps-3 text-muted small">#${row.ID}</td>
        <td>${formatDate(row.DATA_DESPESA)}</td>
        <td class="text-end fw-bold">${formatMoney(row.VALOR)}</td>
        <td>${escapeHtml(row.CATEGORIA || '-')}</td>
        <td>${escapeHtml(row.CENTRO_CUSTO || '-')}</td>
        <td>${escapeHtml(row.GESTOR || '-')}</td>
        <td class="text-center">${renderStatus(row.STATUS)}</td>
        <td class="small text-muted text-truncate" style="max-width: 260px;" title="${safeDesc}">
          <span class="js-open-detail saas-detail-link"
                data-title="Descrição"
                data-meta="${escapeHtml(meta)}"
                data-body="${safeDesc}">
              <span class="icon" aria-hidden="true" title="Abrir"><i class="bi bi-box-arrow-up-right"></i></span>
              <span class="label">${escapeHtml(row.DESCRICAO || '-')}</span>
          </span>
        </td>
        <td class="text-end pe-3">
          <div class="d-inline-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary" onclick="verDetalhe(${row.ID})" title="Detalhe"><i class="bi bi-eye"></i></button>
            ${btnEdit}
            ${btnCancel}
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

  }catch(e){
    loading.style.display = 'none';
    alert('Erro: ' + e.message);
  }
}

async function verDetalhe(id){
  try{
    const json = await apiPost({ action: 'detail', id });
    const d = json.dados;

    let body = '';
    body += `Solicitante: ${d.SOLICITANTE}\n`;
    body += `Gestor: ${d.GESTOR}\n`;
    body += `Data: ${d.DATA_DESPESA}\n`;
    body += `Valor: ${formatMoney(d.VALOR)}\n`;
    body += `Categoria: ${d.CATEGORIA || '-'}\n`;
    body += `Centro de custo: ${d.CENTRO_CUSTO || '-'}\n`;
    body += `Fornecedor: ${d.FORNECEDOR || '-'}\n`;
    body += `Forma Pgto: ${d.FORMA_PGTO || '-'}\n`;
    body += `Status: ${d.STATUS}\n`;
    body += `Descrição: ${d.DESCRICAO || '-'}\n`;
    if(d.MOTIVO_REPROVACAO){
      body += `\nMotivo reprovação:\n${d.MOTIVO_REPROVACAO}\n`;
    }

    // histórico
    const hist = json.historico || [];
    if(hist.length){
      body += `\n--- Histórico ---\n`;
      hist.forEach(h => {
        body += `[${h.DT_EVENTO}] ${h.USUARIO} | ${h.ACAO} | ${h.STATUS_ANTES || '-'} → ${h.STATUS_DEPOIS || '-'}\n`;
        if(h.OBS) body += `  Obs: ${h.OBS}\n`;
      });
    }

    openDetailModal({
      title: `Despesa #${d.ID}`,
      meta: `Criada em: ${d.DT_CRIACAO || '-'} | Última ação: ${d.USU_ULT_ACAO || '-'}`,
      body
    });
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function abrirEditar(id){
  try{
    const json = await apiPost({ action: 'detail', id });
    const d = json.dados;

    if(d.STATUS !== 'P'){
      return alert('Somente despesas Pendentes podem ser editadas.');
    }

    document.getElementById('eId').value = d.ID;
    document.getElementById('eData').value = (d.DATA_DESPESA || '').split(' ')[0];
    document.getElementById('eValor').value = d.VALOR;
    document.getElementById('eCategoria').value = d.CATEGORIA || '';
    document.getElementById('eCentro').value = d.CENTRO_CUSTO || '';
    document.getElementById('eFornecedor').value = d.FORNECEDOR || '';
    document.getElementById('eForma').value = d.FORMA_PGTO || '';
    document.getElementById('eGestor').value = d.GESTOR || '';
    document.getElementById('eDesc').value = d.DESCRICAO || '';

    const modalEl = document.getElementById('editModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function salvarEdicao(){
  const id = document.getElementById('eId').value;
  const data = document.getElementById('eData').value;
  const valor = document.getElementById('eValor').value;
  const gestor = document.getElementById('eGestor').value.trim();

  if(!id) return alert('ID inválido.');
  if(!data) return alert('Informe a data.');
  if(!valor) return alert('Informe o valor.');
  if(!gestor) return alert('Informe o gestor.');

  try{
    await apiPost({
      action: 'update',
      id,
      data_despesa: data,
      valor,
      categoria: document.getElementById('eCategoria').value,
      centro_custo: document.getElementById('eCentro').value,
      fornecedor: document.getElementById('eFornecedor').value,
      forma_pgto: document.getElementById('eForma').value,
      gestor,
      descricao: document.getElementById('eDesc').value
    });

    const modalEl = document.getElementById('editModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();

    await carregarMinhas();
    alert('Despesa atualizada com sucesso!');
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function cancelar(id){
  if(!confirm('Deseja cancelar esta despesa?')) return;

  try{
    await apiPost({ action: 'cancel', id });
    await carregarMinhas();
    alert('Despesa cancelada.');
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

// clique em elementos de detalhe no modal (padrão monitor)
(function initDetailClicks(){
  const tbody = document.getElementById('tbodyMinhas');
  if(!tbody) return;

  tbody.addEventListener('click', (e) => {
    const target = e.target.closest('.js-open-detail');
    if(!target) return;

    const title = target.getAttribute('data-title') || 'Detalhe';
    const meta  = target.getAttribute('data-meta') || '';
    const body  = target.getAttribute('data-body') || target.textContent || '-';

    openDetailModal({ title, meta, body });
  });
})();

window.onload = () => {
  carregarMinhas();
};
</script>
