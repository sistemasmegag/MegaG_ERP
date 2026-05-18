<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'inventario_ciclico';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .cyc-page-head{border:1px solid var(--saas-border);border-radius:20px;background:var(--saas-surface);box-shadow:var(--saas-shadow-soft);padding:1.4rem 1.5rem;margin-bottom:1rem}
  .cyc-title{font-weight:900;color:var(--saas-text);letter-spacing:0;margin:0}
  .cyc-subtitle{color:var(--saas-muted);font-size:13px;margin:.25rem 0 0}
  .cyc-shell{display:grid;grid-template-columns:minmax(330px,410px) minmax(0,1fr);gap:1rem;align-items:start}
  .cyc-panel{border:1px solid var(--saas-border);border-radius:16px;background:var(--saas-surface);box-shadow:var(--saas-shadow-soft)}
  .cyc-panel-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem 1.1rem;border-bottom:1px solid var(--saas-border)}
  .cyc-panel-body{padding:1rem 1.1rem}
  .cyc-label{display:block;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--saas-muted);margin-bottom:.35rem}
  .cyc-input,.cyc-select{width:100%;border:1px solid var(--saas-border);background:var(--saas-surface);color:var(--saas-text);border-radius:10px;padding:.65rem .75rem;font-size:13px;outline:none}
  .cyc-input:focus,.cyc-select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
  .cyc-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
  .cyc-actions{display:flex;gap:.5rem;flex-wrap:wrap}
  .cyc-table-wrap{overflow:auto;max-height:62vh}
  .cyc-table{width:100%;border-collapse:separate;border-spacing:0}
  .cyc-table th{position:sticky;top:0;background:rgba(17,24,39,.035);font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--saas-muted);padding:.75rem;border-bottom:1px solid var(--saas-border);white-space:nowrap}
  .cyc-table td{padding:.7rem .75rem;border-bottom:1px solid var(--saas-border);font-size:13px;vertical-align:middle;white-space:nowrap}
  .cyc-chip{display:inline-flex;align-items:center;border-radius:999px;padding:.2rem .55rem;font-size:10px;font-weight:900;letter-spacing:.04em;background:rgba(37,99,235,.1);color:#2563eb}
  .cyc-chip.released{background:rgba(16,185,129,.13);color:#059669}
  .cyc-mini{font-size:12px;color:var(--saas-muted)}
  .cyc-plan-list{display:flex;flex-direction:column;gap:.5rem;max-height:420px;overflow:auto}
  .cyc-plan{border:1px solid var(--saas-border);border-radius:12px;padding:.75rem;background:rgba(17,24,39,.015);cursor:pointer}
  .cyc-plan.active{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
  .cyc-empty{padding:2rem;text-align:center;color:var(--saas-muted)}
  .cyc-item-form{display:grid;grid-template-columns:repeat(6,minmax(110px,1fr));gap:.65rem;align-items:end}
  .cyc-item-form .span-2{grid-column:span 2}
  .cyc-lookup{position:relative}
  .cyc-lookup-menu{position:absolute;z-index:20;top:calc(100% + 4px);left:0;right:0;border:1px solid var(--saas-border);border-radius:12px;background:var(--saas-surface);box-shadow:var(--saas-shadow-soft);max-height:240px;overflow:auto;display:none}
  .cyc-lookup-menu.show{display:block}
  .cyc-lookup-item{padding:.65rem .75rem;cursor:pointer;border-bottom:1px solid var(--saas-border)}
  .cyc-lookup-item:hover{background:rgba(37,99,235,.08)}
  @media(max-width:1100px){.cyc-shell{grid-template-columns:1fr}.cyc-item-form{grid-template-columns:1fr 1fr}.cyc-item-form .span-2{grid-column:span 2}}
  @media(max-width:640px){.cyc-grid-2,.cyc-item-form{grid-template-columns:1fr}.cyc-item-form .span-2{grid-column:span 1}}
</style>

<div class="cyc-page-head">
  <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center">
    <div>
      <h2 class="cyc-title">Inventario Ciclico</h2>
      <p class="cyc-subtitle">Monte a base de enderecos/produtos e gere o plano que sera consumido pelo app de contagem.</p>
    </div>
    <div class="cyc-actions">
      <button class="btn btn-outline-secondary rounded-pill px-4" type="button" id="btnNovoPlano"><i class="bi bi-plus-circle me-1"></i>Novo</button>
      <button class="btn btn-primary rounded-pill px-4" type="button" id="btnSalvarPlano"><i class="bi bi-save me-1"></i>Salvar/Gerar</button>
    </div>
  </div>
</div>

<div class="cyc-shell">
  <aside class="cyc-panel">
    <div class="cyc-panel-head">
      <div>
        <div class="fw-bold">Planos</div>
        <div class="cyc-mini">Gerados pela package nova</div>
      </div>
      <button class="btn btn-sm btn-light border rounded-pill" id="btnRecarregarPlanos" type="button"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
    <div class="cyc-panel-body">
      <div class="mb-3">
        <label class="cyc-label" for="filtroPlano">Busca</label>
        <input class="cyc-input" id="filtroPlano" placeholder="ID, upload ou empresa">
      </div>
      <div class="cyc-plan-list" id="listaPlanos">
        <div class="cyc-empty">Carregando planos...</div>
      </div>
    </div>
  </aside>

  <section class="cyc-panel">
    <div class="cyc-panel-head">
      <div>
        <div class="fw-bold" id="formTitulo">Novo plano</div>
        <div class="cyc-mini" id="formSubtitulo">Busque itens no estoque para manter o SEQENDERECO correto.</div>
      </div>
      <span class="cyc-chip" id="statusPlano">RASCUNHO</span>
    </div>
    <div class="cyc-panel-body">
      <input type="hidden" id="planoId">
      <input type="hidden" id="planoNroEmpresa">
      <div class="cyc-grid-2 mb-3">
        <div>
          <label class="cyc-label" for="planoDescricao">Descricao do plano</label>
          <input class="cyc-input" id="planoDescricao" placeholder="Inventario ciclico - Camara seca">
        </div>
        <div>
          <label class="cyc-label" for="planoDeposito">Deposito / empresa</label>
          <input class="cyc-input" id="planoDeposito" placeholder="Preenchido pela busca de estoque">
        </div>
        <div class="cyc-lookup">
          <label class="cyc-label" for="planoProdutivoBusca">Produtivo</label>
          <input class="cyc-input" id="planoProdutivoBusca" placeholder="Digite nome, login ou SEQUSUARIO" autocomplete="off">
          <div class="cyc-lookup-menu" id="produtivoLookupMenu"></div>
        </div>
        <div>
          <label class="cyc-label" for="planoObs">Observacao</label>
          <input class="cyc-input" id="planoObs" placeholder="Opcional">
        </div>
      </div>

      <div class="border rounded-3 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
          <div>
            <div class="fw-bold">Endereco/produto</div>
            <div class="cyc-mini">A divisao par/impar e a validacao ficam na package do DBA.</div>
          </div>
          <button class="btn btn-outline-secondary rounded-pill px-3" type="button" id="btnLimparItem">Limpar item</button>
        </div>
        <div class="cyc-lookup mb-3">
          <label class="cyc-label" for="itemBuscaEstoque">Buscar no estoque</label>
          <input class="cyc-input" id="itemBuscaEstoque" placeholder="Digite endereco, codigo ou descricao do produto" autocomplete="off">
          <div class="cyc-lookup-menu" id="estoqueLookupMenu"></div>
        </div>
        <input type="hidden" id="itemSeqEndereco">
        <input type="hidden" id="itemNroEmpresa">
        <input type="hidden" id="itemCodRua">
        <input type="hidden" id="itemNroPredio">
        <input type="hidden" id="itemNroApartamento">
        <input type="hidden" id="itemNroSala">
        <input type="hidden" id="itemQtdEmbalagem">
        <input type="hidden" id="itemDtaRecebimento">
        <div class="cyc-item-form">
          <div>
            <label class="cyc-label" for="itemEndereco">Endereco</label>
            <input class="cyc-input" id="itemEndereco" placeholder="13.01.0.01">
          </div>
          <div>
            <label class="cyc-label" for="itemProduto">Cod. produto</label>
            <input class="cyc-input" id="itemProduto" placeholder="121">
          </div>
          <div class="span-2">
            <label class="cyc-label" for="itemDescricao">Descricao</label>
            <input class="cyc-input" id="itemDescricao" placeholder="Coca Cola PET 2L">
          </div>
          <div>
            <label class="cyc-label" for="itemQtd">Qtd. base</label>
            <input class="cyc-input" id="itemQtd" type="number" step="0.0001" placeholder="52">
          </div>
          <div>
            <label class="cyc-label" for="itemValidade">Validade base</label>
            <input class="cyc-input" id="itemValidade" type="date">
          </div>
          <div>
            <button class="btn btn-dark rounded-pill px-4 w-100" type="button" id="btnAddItem"><i class="bi bi-plus-lg me-1"></i>Adicionar</button>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
        <div>
          <div class="fw-bold">Base do plano</div>
          <div class="cyc-mini"><span id="itemCount">0</span> item(ns)</div>
        </div>
      </div>
      <div class="cyc-table-wrap border rounded-3">
        <table class="cyc-table">
          <thead>
            <tr>
              <th>Endereco</th>
              <th>Seq. End.</th>
              <th>Produto</th>
              <th>Descricao</th>
              <th>Qtd.</th>
              <th>Validade</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="itensTbody">
            <tr><td colspan="7" class="cyc-empty">Nenhum item adicionado.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<script>
const CYC_API = 'api/inventario_ciclico.php';
let planos = [];
let itens = [];

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
const norm = (value) => String(value ?? '').trim();

async function cycPost(action, payload = {}) {
  const res = await fetch(CYC_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...payload })
  });
  const text = await res.text();
  let json;
  try { json = JSON.parse(text); } catch (e) { throw new Error(text || 'Resposta invalida da API.'); }
  if (!json.sucesso) throw new Error(json.erro || 'Erro ao comunicar com a API.');
  return json.dados;
}

function setLoading(button, loading) {
  if (!button) return;
  button.disabled = loading;
  if (loading) {
    button.dataset.originalHtml = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processando';
  } else if (button.dataset.originalHtml) {
    button.innerHTML = button.dataset.originalHtml;
  }
}

function newPlan() {
  document.getElementById('planoId').value = '';
  document.getElementById('planoNroEmpresa').value = '';
  document.getElementById('planoDescricao').value = '';
  document.getElementById('planoDeposito').value = '';
  document.getElementById('planoProdutivoBusca').value = '';
  document.getElementById('planoObs').value = '';
  document.getElementById('formTitulo').textContent = 'Novo plano';
  document.getElementById('formSubtitulo').textContent = 'Busque itens no estoque para manter o SEQENDERECO correto.';
  document.getElementById('statusPlano').textContent = 'RASCUNHO';
  document.getElementById('statusPlano').className = 'cyc-chip';
  itens = [];
  clearItemForm();
  renderItens();
  renderPlanos();
}

function clearItemForm() {
  ['itemBuscaEstoque','itemEndereco','itemProduto','itemDescricao','itemQtd','itemValidade','itemSeqEndereco','itemNroEmpresa','itemCodRua','itemNroPredio','itemNroApartamento','itemNroSala','itemQtdEmbalagem','itemDtaRecebimento'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('estoqueLookupMenu').classList.remove('show');
  document.getElementById('estoqueLookupMenu').innerHTML = '';
}

function setEstoqueItem(item) {
  document.getElementById('itemBuscaEstoque').value = `${item.endereco} | ${item.seqProduto} | ${item.produto}`;
  document.getElementById('itemEndereco').value = item.endereco || '';
  document.getElementById('itemProduto').value = item.seqProduto || '';
  document.getElementById('itemDescricao').value = item.produto || '';
  document.getElementById('itemQtd').value = item.saldo ?? '';
  document.getElementById('itemValidade').value = item.dtaValidade || '';
  document.getElementById('itemSeqEndereco').value = item.seqEndereco || '';
  document.getElementById('itemNroEmpresa').value = item.nroEmpresa || '';
  document.getElementById('itemCodRua').value = item.codRua || '';
  document.getElementById('itemNroPredio').value = item.nroPredio || '';
  document.getElementById('itemNroApartamento').value = item.nroApartamento || '';
  document.getElementById('itemNroSala').value = item.nroSala || '';
  document.getElementById('itemQtdEmbalagem').value = item.qtdEmbalagem || '';
  document.getElementById('itemDtaRecebimento').value = item.dtaRecebimento || '';
  document.getElementById('planoNroEmpresa').value = item.nroEmpresa || document.getElementById('planoNroEmpresa').value;
  document.getElementById('planoDeposito').value = item.empresa || document.getElementById('planoDeposito').value;
  document.getElementById('estoqueLookupMenu').classList.remove('show');
}

function addItem() {
  const seqEndereco = Number(document.getElementById('itemSeqEndereco').value || 0);
  const endereco = norm(document.getElementById('itemEndereco').value);
  const codProduto = norm(document.getElementById('itemProduto').value);
  const descricao = norm(document.getElementById('itemDescricao').value);
  if (!seqEndereco) {
    Swal.fire('Atencao', 'Selecione o item pela busca de estoque para trazer o SEQENDERECO.', 'warning');
    return;
  }
  if (!endereco || !codProduto || !descricao) {
    Swal.fire('Atencao', 'Informe endereco, produto e descricao.', 'warning');
    return;
  }
  itens.push({
    endereco,
    seqEndereco,
    nroEmpresa: Number(document.getElementById('itemNroEmpresa').value || 0),
    codRua: norm(document.getElementById('itemCodRua').value),
    nroPredio: norm(document.getElementById('itemNroPredio').value),
    nroApartamento: norm(document.getElementById('itemNroApartamento').value),
    nroSala: norm(document.getElementById('itemNroSala').value),
    codProduto,
    descricao,
    quantidadeBase: Number(document.getElementById('itemQtd').value || 0),
    qtdEmbalagem: Number(document.getElementById('itemQtdEmbalagem').value || 1),
    validadeBase: document.getElementById('itemValidade').value,
    dtaRecebimento: document.getElementById('itemDtaRecebimento').value
  });
  clearItemForm();
  renderItens();
}

function removeItem(index) {
  itens.splice(index, 1);
  renderItens();
}

function renderItens() {
  const tbody = document.getElementById('itensTbody');
  if (!itens.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="cyc-empty">Nenhum item adicionado.</td></tr>';
  } else {
    tbody.innerHTML = itens.map((item, idx) => `
      <tr>
        <td class="fw-bold">${esc(item.endereco)}</td>
        <td>${esc(item.seqEndereco)}</td>
        <td>${esc(item.codProduto)}</td>
        <td>${esc(item.descricao)}</td>
        <td>${Number(item.quantidadeBase || 0)}</td>
        <td>${item.validadeBase ? esc(item.validadeBase.split('-').reverse().join('/')) : '-'}</td>
        <td class="text-end"><button class="btn btn-sm btn-light border text-danger" onclick="removeItem(${idx})" title="Remover"><i class="bi bi-trash"></i></button></td>
      </tr>
    `).join('');
  }
  document.getElementById('itemCount').textContent = itens.length;
}

function renderPlanos() {
  const q = norm(document.getElementById('filtroPlano').value).toLowerCase();
  const current = document.getElementById('planoId').value;
  const rows = planos.filter(p => !q || [p.ID_PLANO, p.ID_UPLOAD, p.NROEMPRESA, p.STATUS].join(' ').toLowerCase().includes(q));
  document.getElementById('listaPlanos').innerHTML = rows.length ? rows.map(p => `
    <div class="cyc-plan ${String(p.ID_PLANO) === String(current) ? 'active' : ''}" onclick="loadPlan('${esc(p.ID_PLANO)}')">
      <div class="d-flex justify-content-between gap-2">
        <div class="fw-bold">Plano ${esc(p.ID_PLANO)}</div>
        <span class="cyc-chip released">${esc(p.STATUS || 'A')}</span>
      </div>
      <div class="cyc-mini mt-1">Upload ${esc(p.ID_UPLOAD)} | Empresa ${esc(p.NROEMPRESA)}</div>
      <div class="cyc-mini mt-1">${Number(p.QTD_ITENS || 0)} endereco(s) | ${Number(p.QTD_GRUPOS || 0)} lote(s)</div>
    </div>
  `).join('') : '<div class="cyc-empty">Nenhum plano encontrado.</div>';
}

async function loadPlans() {
  document.getElementById('listaPlanos').innerHTML = '<div class="cyc-empty">Carregando planos...</div>';
  try {
    planos = await cycPost('list_plans');
    renderPlanos();
  } catch (e) {
    document.getElementById('listaPlanos').innerHTML = `<div class="cyc-empty text-danger">${esc(e.message)}</div>`;
  }
}

async function loadPlan(id) {
  try {
    const data = await cycPost('get_plan', { id });
    const p = data.plano;
    document.getElementById('planoId').value = p.ID_PLANO;
    document.getElementById('planoNroEmpresa').value = p.NROEMPRESA || '';
    document.getElementById('planoDescricao').value = `Plano ${p.ID_PLANO}`;
    document.getElementById('planoDeposito').value = `Empresa ${p.NROEMPRESA}`;
    document.getElementById('formTitulo').textContent = `Plano ${p.ID_PLANO}`;
    document.getElementById('formSubtitulo').textContent = `Upload ${p.ID_UPLOAD || '-'} | gerado em ${p.GERADO_EM || '-'}`;
    document.getElementById('statusPlano').textContent = p.STATUS || 'A';
    document.getElementById('statusPlano').className = 'cyc-chip released';
    itens = (data.itens || []).map(row => ({
      endereco: [row.CODRUA, row.NROPREDIO, row.NROAPARTAMENTO, row.NROSALA].filter(Boolean).join('.'),
      seqEndereco: row.SEQENDERECO,
      codProduto: row.SEQPRODUTO,
      descricao: row.DESCPRODUTO,
      quantidadeBase: Number(row.QTDATUAL || 0),
      validadeBase: row.DTAVALIDADE || ''
    }));
    renderItens();
    renderPlanos();
  } catch (e) {
    Swal.fire('Erro', e.message, 'error');
  }
}

async function savePlan(event) {
  if (!itens.length) {
    Swal.fire('Atencao', 'Adicione ao menos um item.', 'warning');
    return;
  }
  const button = event.currentTarget;
  try {
    setLoading(button, true);
    const result = await cycPost('save_plan', {
      descricao: document.getElementById('planoDescricao').value,
      deposito: document.getElementById('planoDeposito').value,
      nroEmpresa: Number(document.getElementById('planoNroEmpresa').value || 0),
      observacao: document.getElementById('planoObs').value,
      itens
    });
    await Swal.fire('Sucesso', result.mensagem || 'Plano gerado com sucesso.', 'success');
    await loadPlans();
    if (result.idPlano) await loadPlan(result.idPlano);
  } catch (e) {
    Swal.fire('Erro', e.message, 'error');
  } finally {
    setLoading(button, false);
  }
}

let estoqueTimer = null;
async function searchEstoqueItens() {
  const input = document.getElementById('itemBuscaEstoque');
  const menu = document.getElementById('estoqueLookupMenu');
  const q = norm(input.value);
  if (q.length < 2) {
    menu.classList.remove('show');
    menu.innerHTML = '';
    return;
  }
  try {
    const rows = await cycPost('search_stock_items', { q });
    menu.innerHTML = rows.length ? rows.map(row => `
      <div class="cyc-lookup-item" data-estoque="${encodeURIComponent(JSON.stringify(row))}">
        <div class="fw-bold">${esc(row.endereco)} | ${esc(row.seqProduto)} | ${esc(row.produto)}</div>
        <div class="cyc-mini">${esc(row.empresa || '')} | SeqEnd: ${esc(row.seqEndereco)} | Saldo: ${Number(row.saldo || 0)} ${esc(row.embalagem || '')}${row.dtaValidadeBr ? ' | Val.: ' + esc(row.dtaValidadeBr) : ''}</div>
      </div>
    `).join('') : '<div class="cyc-lookup-item cyc-mini">Nenhum endereco/produto encontrado.</div>';
    menu.classList.add('show');
  } catch (e) {
    menu.innerHTML = `<div class="cyc-lookup-item text-danger">${esc(e.message)}</div>`;
    menu.classList.add('show');
  }
}

let produtivoTimer = null;
async function searchProdutivoPlano() {
  const input = document.getElementById('planoProdutivoBusca');
  const menu = document.getElementById('produtivoLookupMenu');
  const q = norm(input.value);
  if (q.length < 2) {
    menu.classList.remove('show');
    menu.innerHTML = '';
    return;
  }
  try {
    const rows = await cycPost('search_productivos', { q });
    menu.innerHTML = rows.length ? rows.map(row => `
      <div class="cyc-lookup-item" data-label="${esc(`${row.seqUsuario} | ${row.nome || row.login}`)}">
        <div class="fw-bold">${esc(row.nome || row.login || row.seqUsuario)}</div>
        <div class="cyc-mini">${esc(row.seqUsuario)}${row.login ? ' | ' + esc(row.login) : ''}</div>
      </div>
    `).join('') : '<div class="cyc-lookup-item cyc-mini">Nenhum produtivo encontrado.</div>';
    menu.classList.add('show');
  } catch (e) {
    menu.innerHTML = `<div class="cyc-lookup-item text-danger">${esc(e.message)}</div>`;
    menu.classList.add('show');
  }
}

document.getElementById('btnNovoPlano').addEventListener('click', newPlan);
document.getElementById('btnSalvarPlano').addEventListener('click', savePlan);
document.getElementById('btnRecarregarPlanos').addEventListener('click', loadPlans);
document.getElementById('btnAddItem').addEventListener('click', addItem);
document.getElementById('btnLimparItem').addEventListener('click', clearItemForm);
document.getElementById('filtroPlano').addEventListener('input', renderPlanos);
document.getElementById('itemBuscaEstoque').addEventListener('input', () => {
  clearTimeout(estoqueTimer);
  estoqueTimer = setTimeout(searchEstoqueItens, 300);
});
document.getElementById('planoProdutivoBusca').addEventListener('input', () => {
  clearTimeout(produtivoTimer);
  produtivoTimer = setTimeout(searchProdutivoPlano, 250);
});
document.getElementById('estoqueLookupMenu').addEventListener('click', event => {
  const item = event.target.closest('.cyc-lookup-item[data-estoque]');
  if (!item) return;
  setEstoqueItem(JSON.parse(decodeURIComponent(item.dataset.estoque)));
});
document.getElementById('produtivoLookupMenu').addEventListener('click', event => {
  const item = event.target.closest('.cyc-lookup-item[data-label]');
  if (!item) return;
  document.getElementById('planoProdutivoBusca').value = item.dataset.label;
  document.getElementById('produtivoLookupMenu').classList.remove('show');
});
document.addEventListener('click', event => {
  if (!event.target.closest('.cyc-lookup')) {
    document.getElementById('estoqueLookupMenu').classList.remove('show');
    document.getElementById('produtivoLookupMenu').classList.remove('show');
  }
});
document.addEventListener('DOMContentLoaded', () => { newPlan(); loadPlans(); });
</script>
