<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'despesas_aprovacao';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  /* ===== Clean SaaS: Aprovação de Despesas ====== */
  .saas-head {
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13, 110, 253, .08), rgba(13, 110, 253, .02));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
  }

  html[data-theme="dark"] .saas-head {
    background: linear-gradient(135deg, rgba(13, 110, 253, .15), rgba(255, 255, 255, .02));
  }

  .saas-head:before {
    content: "";
    position: absolute;
    inset: -100px -150px auto auto;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle at 30% 30%, rgba(13, 110, 253, .2), transparent 60%);
    filter: blur(8px);
    transform: rotate(15deg);
    pointer-events: none;
  }

  .saas-title {
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--saas-text);
    margin: 0;
  }

  .saas-subtitle {
    margin: 6px 0 0;
    color: var(--saas-muted);
    font-size: 14px;
  }

  /* Dashboard Metrics */
  .metrics-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
    position: relative;
    z-index: 1;
  }

  .metric-card {
    flex: 1;
    min-width: 200px;
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 16px;
    padding: 1rem 1.25rem;
    box-shadow: var(--saas-shadow-soft);
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
  }

  .metric-title {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--saas-muted);
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .metric-value {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--saas-text);
    margin-top: 4px;
    display: flex;
    align-items: baseline;
    gap: 6px;
  }

  /* Modal Split Premium Detailed View */
  .modal-split-body {
    display: flex;
    min-height: 600px;
    padding: 0 !important;
    background: var(--saas-surface);
  }

  .split-left {
    flex: 1.2;
    border-right: 1px solid var(--saas-border);
    padding: 2rem;
    display: flex;
    flex-direction: column;
    background: rgba(17, 24, 39, .015);
    position: relative;
  }

  html[data-theme="dark"] .split-left {
    background: rgba(255, 255, 255, .015);
  }

  .pdf-viewer-fake {
    width: 100%;
    height: 100%;
    min-height: 500px;
    background: #525659;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .pdf-toolbar {
    background: #323639;
    color: #fff;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
  }

  .pdf-page {
    background: #fff;
    margin: auto;
    width: 80%;
    height: 80%;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
  }

  .split-right {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
    background: var(--saas-surface);
  }

  /* Details List Row */
  .detail-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px dashed var(--saas-border);
  }

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--saas-muted);
    font-weight: 700;
    width: 140px;
  }

  .detail-value {
    font-size: 13px;
    font-weight: 800;
    color: var(--saas-text);
    text-align: right;
    word-break: break-word;
  }

  /* Timeline Aprovadores */
  .timeline-aprovadores {
    position: relative;
    padding-left: 20px;
    margin-top: 1rem;
  }

  .timeline-aprovadores::before {
    content: "";
    position: absolute;
    left: 6px;
    top: 10px;
    bottom: 10px;
    width: 2px;
    background: var(--saas-border);
  }

  .timeline-node {
    position: relative;
    margin-bottom: 1.5rem;
  }

  .timeline-node::before {
    content: "";
    position: absolute;
    left: -21px;
    top: 0px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--saas-surface);
    border: 2px solid var(--saas-border);
    z-index: 1;
  }

  .timeline-node.active::before {
    border-color: #0d6efd;
    background: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, .2);
  }

  .timeline-node.done::before {
    border-color: #10b981;
    background: #10b981;
  }

  .timeline-card {
    border: 1px solid var(--saas-border);
    border-radius: 12px;
    padding: 14px;
    background: var(--saas-surface);
    box-shadow: var(--saas-shadow-soft);
    margin-left: 10px;
  }

  .timeline-card.focused {
    border-color: rgba(13, 110, 253, .4);
    background: rgba(13, 110, 253, .02);
  }

  /* Table Aesthetics */
  .saas-table-card {
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow);
    margin-top: 1.5rem;
    overflow: hidden;
  }

  .saas-table-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--saas-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .saas-table {
    width: 100%;
    border-collapse: collapse;
  }

  .saas-table th {
    padding: 1rem 1.5rem;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--saas-muted);
    text-align: left;
    background: rgba(17, 24, 39, .02);
    border-bottom: 1px solid var(--saas-border);
  }

  html[data-theme="dark"] .saas-table th {
    background: rgba(255, 255, 255, .02);
  }

  .saas-table td {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid var(--saas-border);
    font-size: 14px;
    color: var(--saas-text);
    vertical-align: middle;
  }

  .saas-table tr:hover {
    background: rgba(13, 110, 253, .02);
    cursor: pointer;
  }

  /* Badges / Chips */
  .chip {
    padding: 6px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    letter-spacing: -0.01em;
  }

  .chip-primary {
    background: rgba(13, 110, 253, .1);
    color: #0d6efd;
  }

  .chip-green {
    background: rgba(16, 185, 129, .1);
    color: #059669;
  }

  .chip-gray {
    background: rgba(107, 114, 128, .1);
    color: #4b5563;
  }

  html[data-theme="dark"] .chip-primary {
    color: #6ea8fe;
  }

  html[data-theme="dark"] .chip-green {
    color: #34d399;
  }

  html[data-theme="dark"] .chip-gray {
    color: #9ca3af;
  }

  /* Custom Buttons */
  .btn-primary-custom {
    background: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 800;
    font-size: 14px;
    transition: .2s ease;
    box-shadow: 0 4px 12px rgba(13, 110, 253, .3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-primary-custom:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
    color: #fff;
  }

  .btn-light {
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    color: var(--saas-text);
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 700;
    transition: .2s;
  }

  .btn-light:hover {
    background: rgba(17, 24, 39, .03);
  }

  /* Avatar / Initials */
  .avatar-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(13, 110, 253, .1);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
  }

  .avatar-lg {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 18px;
  }
</style>

<main class="main-content">
  <div class="container-fluid pb-5">

    <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
      <button class="mobile-toggle me-3" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
      </button>
      <h4 class="m-0 fw-bold text-dark">CRM Mega G</h4>
    </div>

    <!-- HEADER & METRICS -->
    <div class="saas-head">
      <div class="d-flex justify-content-between align-items-start position-relative z-1">
        <div>
          <h2 class="saas-title">Aprovação de Reembolsos</h2>
          <p class="saas-subtitle">Fila do gestor para avaliar despesas corporativas.</p>
        </div>
      </div>

      <div class="metrics-row">
        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-hourglass-top text-primary" style="font-size:12px;"></i> Na sua fila
            (Pendente)</div>
          <div class="metric-value text-primary"><span id="metricPendentes">0</span> &nbsp; <span
              class="text-muted fw-normal" style="font-size:12px;">Despesas</span></div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-check-circle-fill text-success" style="font-size:12px;"></i>
            Aprovadas hoje</div>
          <div class="metric-value" id="metricAprovadas">0</div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-x-circle-fill text-danger" style="font-size:12px;"></i> Reprovadas
            hoje</div>
          <div class="metric-value" id="metricReprovadas">0</div>
        </div>
      </div>
    </div>

    <!-- TABLE AREA (FILA DO GESTOR) -->
    <div class="saas-table-card">
      <div class="saas-table-header">
        <div style="position: relative; width: 350px;">
          <i class="bi bi-search"
            style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--saas-muted);"></i>
          <input type="text" class="form-control" placeholder="Buscar solicitante ou id..."
            style="padding-left: 40px; border-radius: 99px; font-size:14px; height:42px; border:1px solid var(--saas-border); background:var(--saas-surface); color:var(--saas-text);">
        </div>
      </div>
      <div class="table-responsive">
        <table class="saas-table">
          <thead>
            <tr>
              <th class="ps-4">Solicitante</th>
              <th>Data</th>
              <th>Descrição / Fornecedor</th>
              <th class="text-end">Valor</th>
              <th class="text-center">Status</th>
              <th class="text-end pe-4">Avaliar</th>
            </tr>
          </thead>
          <tbody id="tbodyAprovacao">
            <tr>
              <td colspan="6" class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i> Buscando
                pendências de aprovação...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Modal APROVAÇÃO E DETALHES -->
<div class="modal fade" id="modalDetalhesAprovacao" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1400px; height: 90vh;">
    <div class="modal-content"
      style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden; height:100%;">

      <!-- HEADER DO MODAL -->
      <div class="modal-header d-flex justify-content-between align-items-center"
        style="border-bottom: 1px solid var(--saas-border); padding: 1rem 2rem; background: var(--saas-surface);">
        <div class="d-flex align-items-center gap-3">
          <button type="button"
            class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-1 fw-bold"
            data-bs-dismiss="modal">
            <i class="bi bi-chevron-left"></i> Reembolsos
          </button>
          <span class="text-muted mx-1">/</span>
          <span class="fw-bold text-dark">Detalhes do reembolso</span>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-light rounded-circle p-0" style="width:36px;height:36px;"><i
              class="bi bi-pencil"></i></button>
          <button class="btn-light rounded-circle p-0 text-danger" style="width:36px;height:36px;"><i
              class="bi bi-trash"></i></button>
        </div>
      </div>

      <div class="modal-split-body flex-grow-1" style="min-height:0; overflow:hidden;">

        <!-- LADO ESQUERDO: Visualizador de Anexo (Recibo/NFe) -->
        <div class="split-left" style="overflow-y:auto;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-light btn-sm"><i class="bi bi-image"></i></button>
            <div class="text-muted fw-bold small">4 / 4 &nbsp;&nbsp; <i class="bi bi-chevron-left"></i> &nbsp; <i
                class="bi bi-chevron-right"></i></div>
          </div>
          <div class="pdf-viewer-fake flex-grow-1">
            <div class="pdf-toolbar">
              <div class="d-flex gap-3 align-items-center">
                <i class="bi bi-list"></i>
                <span>ab...</span>
                <span>1 / 2</span>
                <span class="px-2" style="border-left:1px solid #555; border-right:1px solid #555;">85% &nbsp; -
                  +</span>
              </div>
              <div class="d-flex gap-3">
                <i class="bi bi-arrow-counterclockwise"></i>
                <i class="bi bi-download"></i>
                <i class="bi bi-printer"></i>
              </div>
            </div>
            <div id="visualizadorAnexoAprov" class="pdf-page d-flex align-items-center justify-content-center text-muted flex-column gap-2 mt-4"
              style="background:#fff no-repeat center center; background-size: contain; width: 100%; height: 100%; min-height: 500px;">
              <i class="bi bi-file-earmark-image fs-1 opacity-25"></i>
              <span>Carregando documento...</span>
            </div>
          </div>
        </div>

        <!-- LADO DIREITO: Dados + Timeline -->
        <div class="split-right" style="position:relative; border-left: 1px solid var(--saas-border);">

          <!-- Card Top -->
          <div
            style="border:1px solid var(--saas-border); border-radius:16px; padding:1.5rem; background:var(--saas-surface); box-shadow:var(--saas-shadow-soft); margin-bottom:1.5rem;">
            <div class="d-flex justify-content-between">
              <div>
                <span class="text-muted fw-bold text-uppercase" style="font-size:10px;">Estabelecimento</span>
                <h4 class="fw-bold m-0" id="detAprovForn" style="letter-spacing:-.02em; color:var(--saas-text);">...
                </h4>
              </div>
              <div class="text-end">
                <span class="text-muted fw-bold text-uppercase" style="font-size:10px;">Valor da despesa</span>
                <h4 class="fw-bold m-0" id="detAprovVal" style="letter-spacing:-.02em; color:var(--saas-text);">R$ 0,00
                </h4>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
              <span class="chip chip-green"><i class="bi bi-check-circle"></i> Dentro da política</span>
              <span id="detAprovStatus"></span>
            </div>
          </div>

          <!-- Lista de Detalhes -->
          <div class="mb-4">
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-receipt text-muted border rounded p-1"></i> ID da despesa</div>
              <div class="detail-value text-muted" id="detAprovId">EXP-000</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-folder2-open text-muted border rounded p-1"></i> Projeto</div>
              <div class="detail-value text-muted">-</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-grid-1x2 text-muted border rounded p-1"></i> Categoria</div>
              <div class="detail-value" id="detAprovCat">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-calendar3 text-muted border rounded p-1"></i> Data da despesa
              </div>
              <div class="detail-value" id="detAprovData">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-building text-muted border rounded p-1"></i> Centro de custo
              </div>
              <div class="detail-value" id="detAprovCC">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-calendar-check text-muted border rounded p-1"></i> Data
                Vencimento</div>
              <div class="detail-value" id="detAprovVenc">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-chat-left-text text-muted border rounded p-1"></i> Comentário
              </div>
              <div class="detail-value text-muted" id="detAprovObs">--</div>
            </div>
          </div>

          <div style="width: 100%; height: 1px; background: var(--saas-border); margin: 2rem 0;"></div>

          <h6 class="fw-bold mb-3">Status da prestação (Aprovadores)</h6>

          <!-- Timeline de Aprovadores Baseado na Flash e PKG MEGAG_DESP_APROVADORES -->
          <div class="timeline-aprovadores" id="timelineAprov">
             <div class="text-center py-3 text-muted"><i class="bi bi-hourglass-split"></i> Carregando histórico...</div>
          </div>

          </div>

          <div style="height: 100px;"></div> <!-- Spacer para botoes fixos -->

        </div>
      </div>

      <!-- FOOTER FIXO PARA APROVAÇÃO -->
      <div class="modal-footer d-flex justify-content-between align-items-center"
        style="border-top: 1px solid var(--saas-border); padding: 1.25rem 2rem; background: var(--saas-surface); position: absolute; bottom: 0; width: 100%;">
        <div class="text-muted small">
          Ao aprovar, esta despesa irá para o próximo nível da alçada ou ficará liberada para pagamento.
        </div>
        <div class="d-flex gap-3">
          <button class="btn btn-danger rounded-pill px-4 fw-bold"
            id="btnReprovar"><i
               class="bi bi-x-circle me-1"></i> Reprovar</button>
          <button class="btn-primary-custom rounded-pill px-5"
            id="btnAprovar">Aprovar
            <i class="bi bi-check2 ms-1"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let currentDespesaId = null;

  async function loadHistory(id) {
    const container = document.getElementById('timelineAprov');
    if(!container) return;
    try {
        let res = await fetch('api/api_despesas.php', {
            method: 'POST',
            body: JSON.stringify({action: 'get_history', id: id})
        });
        let json = await res.json();
        
        if (json.sucesso && json.dados.length > 0) {
            container.innerHTML = json.dados.map((h, i) => `
                <div class="timeline-node ${h.STATUS === 'APROVADO' ? 'active' : ''} d-flex align-items-start mb-3">
                  <span class="text-muted fw-bold me-3" style="font-size:12px; margin-top:10px;">${i+1}</span>
                  <div class="timeline-card focused flex-grow-1 p-3 border rounded-3 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                       <span class="chip ${h.STATUS === 'APROVADO' ? 'chip-green' : 'chip-red'}" style="font-size:10px;">${h.STATUS}</span>
                       <span class="text-muted" style="font-size:10px;">${h.DTAACAO_FORMAT || h.DTAACAO}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-small bg-primary text-white d-flex align-items-center justify-content-center" style="width:30px;height:30px;border-radius:50%;font-size:10px;">${getInitials(h.NOME_APROVADOR)}</div>
                      <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:13px;">${h.NOME_APROVADOR}</div>
                        <div class="text-muted" style="font-size:11px;">Nível ${h.NIVEL_APROVACAO}</div>
                      </div>
                    </div>
                    ${h.OBSERVACAO ? `<div class="mt-2 p-2 bg-light rounded small text-muted border-start border-primary border-4">${h.OBSERVACAO}</div>` : ''}
                  </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="alert alert-light border text-muted small"><i class="bi bi-info-circle me-1"></i> Nenhum histórico de aprovação registrado.</div>';
        }
    } catch(e) { container.innerHTML = 'Erro ao carregar histórico.'; }
  }

  async function atualizarStatus(status) {
    if (!currentDespesaId) return;

    const { value: obs } = await Swal.fire({
      title: status === 'APROVADO' ? 'Aprovar Despesa' : 'Reprovar Despesa',
      input: 'textarea',
      inputLabel: 'Observação/Comentário',
      inputPlaceholder: 'Digite aqui...',
      showCancelButton: true,
      confirmButtonText: 'Confirmar',
      cancelButtonText: 'Cancelar'
    });

    if (obs === undefined) return; // Cancelou

    // No pacote PL/SQL o status de reprovação é comparado com 'REJEITADO'
    const statusPkg = status === 'REPROVADO' ? 'REJEITADO' : status;

    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update_approval',
          id: currentDespesaId,
          status: statusPkg,
          pago: status === 'APROVADO' ? 'S' : 'N',
          observacao: obs
        })
      });
      let json = await res.json();
      if (json.sucesso) {
        Swal.fire('Sucesso', json.dados.mensagem, 'success');
        bootstrap.Modal.getInstance(document.getElementById('modalDetalhesAprovacao')).hide();
        loadApprovals();
      } else {
        Swal.fire('Erro', json.erro, 'error');
      }
    } catch (e) {
      Swal.fire('Erro', 'Falha na conexão com o servidor.', 'error');
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('btnAprovar').onclick = () => atualizarStatus('APROVADO');
    document.getElementById('btnReprovar').onclick = () => atualizarStatus('REPROVADO');
    loadApprovals();
  });

  function abrirModalAprovacao(jsonEncoded) {
    if (!jsonEncoded) return;
    try {
      let d = JSON.parse(decodeURIComponent(jsonEncoded));
      currentDespesaId = d.CODDESPESA;
      document.getElementById('detAprovForn').innerText = d.FORNECEDOR || 'Despesa Corporativa';
      document.getElementById('detAprovVal').innerText = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      let dateOnly = d.DTAINCLUSAO_FORMAT ? d.DTAINCLUSAO_FORMAT.split(' ')[0] : (d.DTAINCLUSAO ? d.DTAINCLUSAO.split(' ')[0] : '');
      document.getElementById('detAprovData').innerText = dateOnly ? new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR') : '--';

      document.getElementById('detAprovId').innerText = 'EXP-' + d.CODDESPESA;
      document.getElementById('detAprovCC').innerText = d.CENTROCUSTO + ' | ' + (d.DESC_CC || 'Centro de Custo');
      document.getElementById('detAprovObs').innerText = d.OBSERVACAO || '--';

      document.getElementById('detAprovCat').innerText = d.DESCRICAO || '--'; 

      document.getElementById('detAprovStatus').innerHTML = parseStatusChip(d.STATUS);

      // Tratar Anexo - Suporte a PDF e Imagens na Aprovação
      let visualizador = document.getElementById('visualizadorAnexoAprov');
      if (d.NOMEARQUIVO) {
         let ext = d.NOMEARQUIVO.split('.').pop().toLowerCase();
         let fileUrl = `uploads/${d.NOMEARQUIVO}`;
         
         if (ext === 'pdf') {
            visualizador.style.backgroundImage = 'none';
            visualizador.style.background = '#525659';
            visualizador.innerHTML = `<iframe src="${fileUrl}" style="width:100%; height:100%; min-height:600px; border:none;"></iframe>`;
         } else {
            visualizador.style.backgroundImage = `url('${fileUrl}')`;
            visualizador.style.backgroundSize = 'contain';
            visualizador.style.backgroundRepeat = 'no-repeat';
            visualizador.style.backgroundPosition = 'center';
            visualizador.innerHTML = '';
         }
      } else {
         visualizador.style.backgroundImage = 'none';
         visualizador.style.background = '#f8f9fa';
         visualizador.innerHTML = '<i class="bi bi-file-earmark-image fs-1 opacity-25"></i><span>Sem anexo</span>';
      }

      loadHistory(d.CODDESPESA);
      new bootstrap.Modal('#modalDetalhesAprovacao').show();
    } catch (e) {
      console.error(e);
    }
  }

  function parseStatusChip(status) {
    status = (status || 'LANCADO').toUpperCase();
    if (status === 'LANCADO' || status === 'EM_APROVACAO' || status === 'APROVACAO') return '<span class="chip chip-primary">• Pendente Avaliação</span>';
    if (status === 'APROVADO' || status === 'REEMBOLSADO') return '<span class="chip chip-green">• Aprovado</span>';
    if (status === 'REJEITADO' || status === 'REPROVADO') return '<span class="chip" style="background: rgba(220,53,69,.1); color: #dc3545;">• Reprovado</span>';
    return '<span class="chip chip-gray">• ' + status + '</span>';
  }

  function getInitials(name) {
    if (!name) return '??';
    let parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
  }

  async function loadApprovals() {
    let tbody = document.getElementById('tbodyAprovacao');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i> Atualizando fila...</td></tr>';

    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'list_approvals' })
      });
      let json = await res.json();

      if (json.sucesso) {
        let m = json.dados.metricas;
        if (m) {
          document.getElementById('metricPendentes').innerText = m.pendentes || 0;
          document.getElementById('metricAprovadas').innerText = m.aprovadas_hoje || 0;
          document.getElementById('metricReprovadas').innerText = m.reprovadas_hoje || 0;
        }

        let html = '';
        if (json.dados.dados.length === 0) {
          html = '<tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-check-circle-fill text-success fs-4 d-block mb-2"></i> Sua fila está zerada! Bom trabalho.</td></tr>';
        } else {
          json.dados.dados.forEach(d => {
            let dataStr = 'Data inválida';
            if (d.DTAINCLUSAO_FORMAT) {
               let dateOnly = d.DTAINCLUSAO_FORMAT.split(' ')[0];
               dataStr = new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR');
            } else if (d.DTAINCLUSAO) {
              let dateOnly = d.DTAINCLUSAO.split(' ')[0];
              dataStr = new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR');
            }

            let valFormat = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            let nomeSol = d.NOME_SOLICITANTE || 'Usuário ' + d.USUARIOSOLICITANTE;
            let iniciais = getInitials(nomeSol);

            html += `
                <tr onclick="abrirModalAprovacao('${encodeURIComponent(JSON.stringify(d))}')">
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-small">${iniciais}</div>
                      <div>
                        <div class="fw-bold" style="font-size:13px; color: var(--saas-text);">${nomeSol}</div>
                        <div class="text-muted" style="font-size:11px;">Solicitante</div>
                      </div>
                    </div>
                  </td>
                  <td class="text-muted fw-bold">${dataStr}</td>
                  <td>
                    <div class="fw-bold" style="font-size:13px; letter-spacing: -0.01em; color: var(--saas-text);">${d.FORNECEDOR || 'Despesa Corporativa'}</div>
                    <div class="text-muted" style="font-size:11.5px; margin-top: 2px;">${d.DESC_CC || 'Centro de Custo'} • EXP-${d.CODDESPESA}</div>
                  </td>
                  <td class="text-end fw-bold" style="font-size: 13.5px;">${valFormat}</td>
                  <td class="text-center">${parseStatusChip(d.STATUS)}</td>
                  <td class="text-end pe-4">
                    <button class="btn btn-sm btn-light p-1 px-3 d-inline-flex align-items-center gap-1"
                      style="border-radius:8px; font-size:12px;" onclick="event.stopPropagation(); abrirModalAprovacao('${encodeURIComponent(JSON.stringify(d))}');">
                      <i class="bi bi-box-arrow-in-right"></i> Abrir
                    </button>
                  </td>
                </tr>
                `;
          });
        }
        tbody.innerHTML = html;
      } else {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Erro vindo da API: ' + json.erro + '</td></tr>';
      }
    } catch (e) {
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Erro de conexão com o servidor.</td></tr>';
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadApprovals();
  });
</script>