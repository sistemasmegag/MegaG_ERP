<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'despesas_aprovacao';
?>

<style>
/* ===== Clean SaaS (escopado pra APROVAÇÃO) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(220,53,69,.10), rgba(220,53,69,.04)); /* danger */
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(220,53,69,.14), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(220,53,69,.22), transparent 60%);
    filter: blur(6px);
    transform: rotate(10deg);
    pointer-events:none;
}
.saas-title{ font-weight: 900; letter-spacing: -.02em; margin:0; color: var(--saas-text); }
.saas-subtitle{ margin: 6px 0 0; color: var(--saas-muted); font-size: 14px; }

/* Chips */
.saas-chips{ display:flex; flex-wrap:wrap; gap: 8px; margin-top: 12px; position: relative; z-index: 1; }
.saas-chip{
    border: 1px solid var(--saas-border);
    background: rgba(255,255,255,.55);
    color: var(--saas-text);
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: .01em;
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    user-select:none;
    transition: .16s ease;
}
html[data-theme="dark"] .saas-chip{ background: rgba(255,255,255,.06); }
.saas-chip:hover{ transform: translateY(-1px); border-color: rgba(220,53,69,.25); }
.saas-chip.active{
    background: rgba(220,53,69,.10);
    border-color: rgba(220,53,69,.22);
    box-shadow: 0 10px 18px rgba(220,53,69,.10);
}

/* Metrics */
.saas-metrics{
    display:grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px; margin-top: 14px; position: relative; z-index: 1;
}
@media (max-width: 900px){ .saas-metrics{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.saas-metric{
    border: 1px solid var(--saas-border);
    background: rgba(255,255,255,.55);
    border-radius: 16px;
    padding: 12px 12px;
    box-shadow: var(--saas-shadow-soft);
    backdrop-filter: blur(10px);
}
html[data-theme="dark"] .saas-metric{ background: rgba(255,255,255,.06); }
.saas-metric .label{ font-size: 11px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: var(--saas-muted); }
.saas-metric .value{ margin-top: 4px; font-size: 24px; font-weight: 900; letter-spacing: -.02em; color: var(--saas-text); line-height: 1.1; }
.saas-metric .hint{ margin-top: 2px; font-size: 12px; color: var(--saas-muted); }

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

/* Table wrap */
.saas-table-wrap{
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow);
    overflow: hidden;
}
.saas-table-scroll{ max-height: 65vh; overflow:auto; scrollbar-width: thin; }
.saas-table thead th{
    position: sticky; top: 0; z-index: 2;
    background: rgba(17,24,39,.03) !important;
    color: var(--saas-text) !important;
}
html[data-theme="dark"] .saas-table thead th{ background: rgba(255,255,255,.06) !important; }
.saas-table.table-hover tbody tr:hover{ background: rgba(220,53,69,.06) !important; }
html[data-theme="dark"] .saas-table.table-hover tbody tr:hover{ background: rgba(220,53,69,.12) !important; }

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }
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
          <h3 class="saas-title">Aprovação de Despesas</h3>
          <p class="saas-subtitle">
            Fila do gestor para aprovar ou reprovar despesas pendentes, com registro completo de histórico.
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger fw-bold px-3 py-2">
            Ações do Gestor
          </span>
        </div>
      </div>

      <div class="saas-chips" id="chipsStatus">
        <div class="saas-chip active" data-status="">
          <i class="bi bi-grid-3x3-gap"></i> Todas
        </div>
        <div class="saas-chip" data-status="P">
          <i class="bi bi-hourglass-split"></i> Pendentes
        </div>
        <div class="saas-chip" data-status="A">
          <i class="bi bi-check-circle"></i> Aprovadas
        </div>
        <div class="saas-chip" data-status="R">
          <i class="bi bi-x-circle"></i> Reprovadas
        </div>
        <div class="saas-chip" data-status="C">
          <i class="bi bi-slash-circle"></i> Canceladas
        </div>
      </div>

      <div class="saas-metrics">
        <div class="saas-metric">
          <div class="label">Total</div>
          <div class="value" id="mTotal">0</div>
          <div class="hint">na fila</div>
        </div>
        <div class="saas-metric">
          <div class="label">Pendentes</div>
          <div class="value" id="mP">0</div>
          <div class="hint">status P</div>
        </div>
        <div class="saas-metric">
          <div class="label">Aprovadas</div>
          <div class="value" id="mA">0</div>
          <div class="hint">status A</div>
        </div>
        <div class="saas-metric">
          <div class="label">Reprovadas</div>
          <div class="value" id="mR">0</div>
          <div class="hint">status R</div>
        </div>
      </div>
    </div>

    <div class="card saas-card mb-3">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <div class="bg-danger bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
            <i class="bi bi-check2-square fs-4 text-danger"></i>
          </div>
          <div>
            <div class="saas-kicker">Fila do gestor</div>
            <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Aprovar / Reprovar</div>
          </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <input type="text" id="fBusca" class="form-control" placeholder="Buscar por ID/Solicitante..." style="height:44px;border-radius:14px;">
          <button class="btn btn-danger" style="height:44px;border-radius:14px;font-weight:900;" onclick="carregarFila()">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </div>

      <div class="saas-table-wrap">
        <div class="saas-table-scroll">
          <table class="table table-hover mb-0 align-middle table-sm saas-table">
            <thead>
              <tr>
                <th class="py-3 ps-3">ID</th>
                <th class="py-3">Solicitante</th>
                <th class="py-3">Data</th>
                <th class="py-3 text-end">Valor</th>
                <th class="py-3">Categoria</th>
                <th class="py-3">Centro</th>
                <th class="py-3 text-center">Status</th>
                <th class="py-3 text-end pe-3">Ações</th>
              </tr>
            </thead>
            <tbody id="tbodyFila"></tbody>
          </table>
        </div>

        <div id="loadingFila" class="text-center p-5 text-muted" style="display:none;">
          <div class="spinner-border text-danger mb-2" role="status"></div>
          <p class="mb-0">Carregando fila...</p>
        </div>

        <div id="emptyFila" class="text-center p-5 text-muted" style="display:none;">
          <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
          <div class="fw-bold text-dark mb-1">Nenhum registro encontrado</div>
          <div class="text-muted">Ajuste o filtro e tente novamente.</div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Modal motivo reprovação -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px; border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text); box-shadow: var(--saas-shadow);">
      <div class="modal-header" style="border-bottom:1px solid var(--saas-border);">
        <h5 class="modal-title fw-bold" style="letter-spacing:-.01em;">Reprovar despesa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rId">
        <div class="text-muted small mb-2">Informe o motivo (obrigatório).</div>
        <textarea class="form-control" id="rMotivo" style="min-height:120px;border-radius:14px;"></textarea>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--saas-border);">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-danger rounded-pill px-4" onclick="confirmarReprovacao()">
          <i class="bi bi-x-circle me-1"></i> Reprovar
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

function updateMetrics(dados){
  const total = dados.length;
  let p=0,a=0,r=0;
  dados.forEach(x => {
    if(x.STATUS==='P') p++;
    else if(x.STATUS==='A') a++;
    else if(x.STATUS==='R') r++;
  });
  document.getElementById('mTotal').textContent = total;
  document.getElementById('mP').textContent = p;
  document.getElementById('mA').textContent = a;
  document.getElementById('mR').textContent = r;
}

let currentStatus = '';

(function initChips(){
  const wrap = document.getElementById('chipsStatus');
  if(!wrap) return;

  wrap.addEventListener('click', (e) => {
    const chip = e.target.closest('.saas-chip');
    if(!chip) return;

    currentStatus = chip.getAttribute('data-status') ?? '';
    wrap.querySelectorAll('.saas-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');

    carregarFila();
  });
})();

async function carregarFila(){
  const tbody = document.getElementById('tbodyFila');
  const loading = document.getElementById('loadingFila');
  const empty = document.getElementById('emptyFila');

  tbody.innerHTML = '';
  loading.style.display = 'block';
  empty.style.display = 'none';

  try{
    const busca = document.getElementById('fBusca').value || '';
    const json = await apiPost({ action: 'list_approve', status: currentStatus, busca });
    loading.style.display = 'none';

    const dados = json.dados || [];
    updateMetrics(dados);

    if(!dados.length){
      empty.style.display = 'block';
      return;
    }

    dados.forEach(row => {
      const tr = document.createElement('tr');

      const canAct = row.STATUS === 'P';

      tr.innerHTML = `
        <td class="ps-3 text-muted small">#${row.ID}</td>
        <td class="fw-bold">${escapeHtml(row.SOLICITANTE || '-')}</td>
        <td>${formatDate(row.DATA_DESPESA)}</td>
        <td class="text-end fw-bold">${formatMoney(row.VALOR)}</td>
        <td>${escapeHtml(row.CATEGORIA || '-')}</td>
        <td>${escapeHtml(row.CENTRO_CUSTO || '-')}</td>
        <td class="text-center">${renderStatus(row.STATUS)}</td>
        <td class="text-end pe-3">
          <div class="d-inline-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary" onclick="ver(${row.ID})" title="Detalhe"><i class="bi bi-eye"></i></button>
            <button class="btn btn-sm btn-success" ${canAct ? '' : 'disabled'} onclick="aprovar(${row.ID})" title="Aprovar"><i class="bi bi-check-lg"></i></button>
            <button class="btn btn-sm btn-danger" ${canAct ? '' : 'disabled'} onclick="abrirReprovar(${row.ID})" title="Reprovar"><i class="bi bi-x-lg"></i></button>
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

async function ver(id){
  try{
    const json = await apiPost({ action: 'detail', id });
    const d = json.dados;
    let txt = '';
    txt += `Solicitante: ${d.SOLICITANTE}\n`;
    txt += `Gestor: ${d.GESTOR}\n`;
    txt += `Data: ${d.DATA_DESPESA}\n`;
    txt += `Valor: ${formatMoney(d.VALOR)}\n`;
    txt += `Categoria: ${d.CATEGORIA || '-'}\n`;
    txt += `Centro de custo: ${d.CENTRO_CUSTO || '-'}\n`;
    txt += `Fornecedor: ${d.FORNECEDOR || '-'}\n`;
    txt += `Forma Pgto: ${d.FORMA_PGTO || '-'}\n`;
    txt += `Status: ${d.STATUS}\n`;
    txt += `Descrição: ${d.DESCRICAO || '-'}\n`;
    if(d.MOTIVO_REPROVACAO){
      txt += `\nMotivo reprovação:\n${d.MOTIVO_REPROVACAO}\n`;
    }

    const hist = json.historico || [];
    if(hist.length){
      txt += `\n--- Histórico ---\n`;
      hist.forEach(h => {
        txt += `[${h.DT_EVENTO}] ${h.USUARIO} | ${h.ACAO} | ${h.STATUS_ANTES || '-'} → ${h.STATUS_DEPOIS || '-'}\n`;
        if(h.OBS) txt += `  Obs: ${h.OBS}\n`;
      });
    }

    alert(txt);
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function aprovar(id){
  if(!confirm('Confirmar aprovação desta despesa?')) return;
  try{
    await apiPost({ action: 'approve', id });
    await carregarFila();
    alert('Despesa aprovada.');
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

function abrirReprovar(id){
  document.getElementById('rId').value = id;
  document.getElementById('rMotivo').value = '';
  const modalEl = document.getElementById('rejectModal');
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

async function confirmarReprovacao(){
  const id = document.getElementById('rId').value;
  const motivo = document.getElementById('rMotivo').value.trim();
  if(!motivo) return alert('Informe o motivo da reprovação.');

  try{
    await apiPost({ action: 'reject', id, motivo });
    const modalEl = document.getElementById('rejectModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    await carregarFila();
    alert('Despesa reprovada.');
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

window.onload = () => {
  currentStatus = 'P';
  const wrap = document.getElementById('chipsStatus');
  wrap.querySelectorAll('.saas-chip').forEach(c => {
    c.classList.toggle('active', (c.getAttribute('data-status') ?? '') === 'P');
  });
  carregarFila();
};
</script>
