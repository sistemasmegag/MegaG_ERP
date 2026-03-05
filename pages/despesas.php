<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'despesas';
?>

<style>
  /* ===== Clean SaaS: Despesas Corporativas ===== */
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

  .metric-card.main-metric {
    flex: 1.5;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, .8), rgba(255, 255, 255, .3));
  }

  html[data-theme="dark"] .metric-card.main-metric {
    background: linear-gradient(135deg, rgba(30, 30, 30, .8), rgba(30, 30, 30, .3));
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

  .metric-subtitle {
    font-size: 12px;
    color: var(--saas-muted);
    font-weight: 500;
  }

  .circ-progress {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 5px solid rgba(20, 184, 166, 0.2);
    border-top-color: rgb(20, 184, 166);
    /* teal */
    transform: rotate(45deg);
  }

  /* Modal Split Premium */
  .modal-split-body {
    display: flex;
    min-height: 600px;
    padding: 0 !important;
    background: var(--saas-surface);
  }

  .split-left {
    flex: 1;
    border-right: 1px solid var(--saas-border);
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(17, 24, 39, .015);
    position: relative;
  }

  html[data-theme="dark"] .split-left {
    background: rgba(255, 255, 255, .015);
  }

  .drag-drop-area {
    width: 100%;
    height: 100%;
    min-height: 400px;
    border: 2px dashed rgba(13, 110, 253, .3);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--saas-muted);
    transition: .2s ease;
    cursor: pointer;
    background: var(--saas-surface);
  }

  .drag-drop-area:hover {
    border-color: rgba(13, 110, 253, .8);
    background: rgba(13, 110, 253, .03);
  }

  .drag-drop-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: rgba(13, 110, 253, .1);
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 1rem;
  }

  .split-right {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
    background: var(--saas-surface);
  }

  /* Modern Form Elements */
  .saas-input-group {
    margin-bottom: 1.2rem;
  }

  .saas-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--saas-muted);
    margin-bottom: 6px;
    display: block;
    letter-spacing: 0.02em;
  }

  .saas-input,
  .saas-select {
    width: 100%;
    border-radius: 12px;
    border: 1px solid var(--saas-border);
    background: var(--saas-surface);
    color: var(--saas-text);
    padding: 12px 14px;
    font-size: 14px;
    transition: .2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .02);
  }

  .saas-input:focus,
  .saas-select:focus {
    border-color: #0d6efd;
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
  }

  .value-display {
    text-align: right;
  }

  .value-display span {
    font-size: 12px;
    color: var(--saas-muted);
    text-transform: uppercase;
    font-weight: 800;
  }

  .value-display h2 {
    font-size: 28px;
    font-weight: 900;
    margin: 0;
    color: var(--saas-text);
    letter-spacing: -.02em;
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

  /* Tiny receipt button */
  .btn-receipt {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid var(--saas-border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(17, 24, 39, .03);
    color: var(--saas-text);
    transition: .2s;
    position: relative;
  }

  .btn-receipt:hover {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
  }

  .btn-receipt .badge-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #3b82f6;
    color: #fff;
    font-size: 9px;
    font-weight: 900;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
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
          <h2 class="saas-title">Reembolsos</h2>
          <p class="saas-subtitle">Adicione e acompanhe suas solicitações de reembolso.</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-light d-none d-md-flex align-items-center gap-2">
            <i class="bi bi-calendar3"></i> Últimos 30 dias
          </button>
          <button class="btn-primary-custom" onclick="abrirModalNova()">
            Pedir reembolso
          </button>
        </div>
      </div>

      <div class="metrics-row">
        <div class="metric-card main-metric">
          <div class="circ-progress"></div>
          <div>
            <div class="metric-title">Total de Reembolsos</div>
            <div class="metric-value"><span id="metricTotalValor">R$ 0,00</span> <span class="metric-subtitle ms-2"> •
                <span id="metricTotalQtd">0</span> Reembolsos</span></div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-circle-fill text-primary" style="font-size:8px;"></i> Em prestação
            <i class="bi bi-info-circle ms-1"></i>
          </div>
          <div class="metric-value" style="color:var(--saas-text);" id="metricPrestacaoValor">R$ 0,00</div>
          <div class="metric-subtitle mt-1" id="metricPrestacaoQtd">Nenhum Reembolso</div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-circle-fill text-primary" style="font-size:8px;"></i> Em aprovação
            <i class="bi bi-info-circle ms-1"></i>
          </div>
          <div class="metric-value" id="metricAprovacaoValor">R$ 0,00</div>
          <div class="metric-subtitle mt-1" id="metricAprovacaoQtd">0 Reembolsos</div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-circle-fill text-success" style="font-size:8px;"></i> Reembolsado <i
              class="bi bi-info-circle ms-1"></i></div>
          <div class="metric-value" id="metricReembolsadoValor">R$ 0,00</div>
          <div class="metric-subtitle mt-1" id="metricReembolsadoQtd">0 Reembolsos</div>
        </div>

        <div class="metric-card">
          <div class="metric-title"><i class="bi bi-circle-fill text-danger" style="font-size:8px;"></i> Reprovado <i
              class="bi bi-info-circle ms-1"></i></div>
          <div class="metric-value" style="color:var(--saas-text);" id="metricReprovadoValor">R$ 0,00</div>
          <div class="metric-subtitle mt-1" id="metricReprovadoQtd">Nenhum Reembolso</div>
        </div>
      </div>
    </div>

    <!-- TABLE AREA -->
    <div class="saas-table-card">
      <div class="saas-table-header">
        <div style="position: relative; width: 350px;">
          <i class="bi bi-search"
            style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--saas-muted);"></i>
          <input type="text" class="saas-input" placeholder="Buscar por categoria ou código da despesa"
            style="padding-left: 40px; border-radius: 99px;">
        </div>
        <button class="btn-light rounded-pill px-3" style="font-size: 13px;">
          Filtros <i class="bi bi-funnel ms-1"></i>
        </button>
      </div>
      <div class="table-responsive">
        <table class="saas-table">
          <thead>
            <tr>
              <th style="width:50px;" class="text-center"><input type="checkbox" class="form-check-input"></th>
              <th>Data</th>
              <th>Descrição</th>
              <th class="text-end">Valor</th>
              <th class="text-center">Recibo</th>
              <th>Status</th>
              <th>Política</th>
              <th>Centro de custo</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody id="tbodyReembolsos">
            <tr>
              <td colspan="9" class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i> Carregando
                suas solicitações...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Modal Solicitar Reembolso -->
<div class="modal fade" id="modalNovaDespesa" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1250px;">
    <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
      <div class="modal-header d-flex justify-content-between align-items-center"
        style="border-bottom: 1px solid var(--saas-border); padding: 1.25rem 2rem; background: var(--saas-surface);">
        <button type="button"
          class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-1 fw-bold"
          data-bs-dismiss="modal">
          <i class="bi bi-chevron-left"></i> Voltar para Reembolsos
        </button>
        <h5 class="modal-title fw-bold m-0 text-dark"
          style="position: absolute; left: 50%; transform: translateX(-50%);">Solicitar reembolso</h5>
        <button class="btn-light rounded-pill py-1 px-3 d-flex align-items-center gap-2" style="font-size:12px;">
          <i class="bi bi-question-circle"></i> Central de ajuda
        </button>
      </div>

      <div class="modal-split-body">
        <!-- Esquerda: Drag & Drop Area -->
        <div class="split-left" style="padding: 3rem;">
          <div class="drag-drop-area" id="dropArea" style="background:transparent; border-width: 2px;">
            <div class="drag-drop-icon" style="background: rgba(13,110,253,.08);"><i
                class="bi bi-cloud-arrow-up-fill"></i></div>
            <h6 class="fw-bold mb-1 text-dark" style="font-size: 15px;">Arraste e solte aqui o seu arquivo</h6>
            <p style="font-size:12px;" class="text-muted mb-4 opacity-75">Formatos permitidos: PDF, PNG, JPEG, JPG</p>
            <input type="file" id="fArquivo" class="d-none" accept=".pdf,.png,.jpeg,.jpg">
            <button class="btn-light rounded-pill" onclick="document.getElementById('fArquivo').click()"
              style="color:#0d6efd; border-color: rgba(13,110,253,.3); padding: 8px 24px; font-size: 13px;">
              Selecionar arquivo <i class="bi bi-upload ms-1"></i>
            </button>
            <div id="fileDisplayName" class="mt-3 text-success fw-bold" style="font-size: 13px;"></div>
          </div>
        </div>

        <!-- Direita: Formulário -->
        <div class="split-right" style="padding: 3rem; border-left: 1px solid var(--saas-border);">

          <div class="d-flex justify-content-between align-items-start mb-4 pb-4"
            style="border-bottom: 1px solid var(--saas-border);">
            <div class="d-flex align-items-center gap-3">
              <div class="btn-receipt flex-shrink-0"
                style="width:46px;height:46px; border-radius:12px; font-size:18px; background: rgba(17,24,39,.04);">
                <i class="bi bi-receipt"></i>
              </div>
              <div>
                <span class="saas-label m-0 fw-bold" style="font-size:10px; text-transform:uppercase;">Categoria</span>
                <h5 class="fw-bold m-0 text-dark" id="displayCategoria" style="font-size: 18px; letter-spacing:-.02em;">
                  Estabelecimento</h5>
                <div class="mt-2"><span class="chip chip-gray"
                    style="font-size:10px; padding:3px 10px; font-weight:800;">• Pendente</span></div>
              </div>
            </div>
            <div class="value-display">
              <span style="font-size: 10px; font-weight: 800;">Valor da despesa</span>
              <h2 id="displayValor" style="font-size: 24px; margin-top: 4px;">R$ 0,00</h2>
            </div>
          </div>

          <!-- Formulário conectando com as PKGs -->
          <form id="formReembolso">
            <div class="row">
              <div class="col-md-5">
                <div class="saas-input-group">
                  <label class="saas-label">Moeda</label>
                  <select class="saas-select" id="fMoeda">
                    <option>R$ (BRL)</option>
                  </select>
                </div>
              </div>
              <div class="col-md-7">
                <div class="saas-input-group">
                  <label class="saas-label">Valor *</label>
                  <input type="number" step="0.01" class="saas-input fw-bold" placeholder="0,00" id="fValor"
                    oninput="updateValorDisplay(this.value)">
                </div>
              </div>
            </div>

            <div class="saas-input-group">
              <label class="saas-label">Estabelecimento * <small
                  class="fw-normal text-muted ms-1">(FORNECEDOR)</small></label>
              <input type="text" class="saas-input" placeholder="Onde foi a despesa?" id="fEstabelecimento">
            </div>

            <div class="saas-input-group mt-4">
              <label class="saas-label">Data da despesa *</label>
              <input type="date" class="saas-input" id="fData">
            </div>

            <div class="saas-input-group mt-4">
              <label class="saas-label border-top pt-4">Categoria * <small
                  class="fw-normal text-muted ms-1">(MEGAG_DESP_TIPO)</small></label>
              <select class="saas-select" id="fCategoria" onchange="updateCatDisplay(this)"></select>
            </div>

            <div class="saas-input-group mt-4">
              <label class="saas-label">Centro de custo * <small
                  class="fw-normal text-muted ms-1">(SEQCENTRORESULTADO)</small></label>
              <select class="saas-select" id="fCentroCusto"></select>
            </div>

            <div class="saas-input-group mt-4">
              <label class="saas-label">Data de Vencimento</label>
              <input type="date" class="saas-input" id="fVencimento">
            </div>

            <div class="saas-input-group mt-4 pb-4">
              <label class="saas-label">Comentário <small class="fw-normal text-muted ms-1">(OBSERVACAO)</small></label>
              <textarea class="saas-input" rows="3" placeholder="Detalhes opcionais..." id="fComentario"
                style="border-radius: 12px; resize: none;"></textarea>
            </div>
          </form>

        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between align-items-center"
        style="border-top: 1px solid var(--saas-border); padding: 1.25rem 2rem; background: var(--saas-surface);">
        <button class="btn btn-link text-decoration-none p-0 fw-bold" style="font-size:13px; color: var(--saas-text);">
          <span style="border-bottom: 1.5px solid var(--saas-text);">Sair e salvar alterações</span>
        </button>
        <div class="d-flex gap-3">
          <button class="btn-light rounded-pill px-4" style="font-size:13px;">Salvar e solicitar outro</button>
          <button class="btn-primary-custom rounded-pill px-4" onclick="enviarReembolso()"><span
              style="font-size:13px;">Solicitar reembolso <i class="bi bi-check2 ms-1"></i></span></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal DETALHES DA DESPESA -->
<div class="modal fade" id="modalDetalhesDespesa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1400px; height: 90vh;">
    <div class="modal-content"
      style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden; height:100%;">

      <div class="modal-header d-flex justify-content-between align-items-center"
        style="border-bottom: 1px solid var(--saas-border); padding: 1rem 2rem; background: var(--saas-surface);">
        <div class="d-flex align-items-center gap-3">
          <button type="button"
            class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-1 fw-bold"
            data-bs-dismiss="modal">
            <i class="bi bi-chevron-left"></i> Reembolsos
          </button>
          <span class="text-muted mx-1">/</span>
          <span class="fw-bold text-dark">Detalhes do seu reembolso</span>
        </div>
      </div>

      <div class="modal-split-body flex-grow-1" style="min-height:0; overflow:hidden;">
        <!-- LADO ESQUERDO: Visualizador de Anexo -->
        <div class="split-left" style="overflow-y:auto;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-light btn-sm"><i class="bi bi-image"></i></button>
            <div class="text-muted fw-bold small">Documento Anexo</div>
          </div>
          <div class="pdf-viewer-fake flex-grow-1">
            <div class="pdf-page d-flex align-items-center justify-content-center text-muted flex-column gap-2 mt-4"
              style="background:#fff url('https://upload.wikimedia.org/wikipedia/commons/e/ec/Danfe-exemplo.jpg') no-repeat center center; background-size: cover;">
            </div>
          </div>
        </div>

        <!-- LADO DIREITO: Dados -->
        <div class="split-right" style="position:relative; border-left: 1px solid var(--saas-border);">
          <div
            style="border:1px solid var(--saas-border); border-radius:16px; padding:1.5rem; background:var(--saas-surface); box-shadow:var(--saas-shadow-soft); margin-bottom:1.5rem;">
            <div class="d-flex justify-content-between">
              <div>
                <span class="text-muted fw-bold text-uppercase" style="font-size:10px;">Estabelecimento</span>
                <h4 class="fw-bold m-0" id="detForn" style="letter-spacing:-.02em; color:var(--saas-text);">...</h4>
              </div>
              <div class="text-end">
                <span class="text-muted fw-bold text-uppercase" style="font-size:10px;">Valor da despesa</span>
                <h4 class="fw-bold m-0" id="detVal" style="letter-spacing:-.02em; color:var(--saas-text);">R$ 0,00</h4>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
              <span class="chip chip-green"><i class="bi bi-check-circle"></i> Política de viagens</span>
              <span id="detStatus"></span>
            </div>
          </div>

          <div class="mb-4">
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-receipt text-muted border rounded p-1"></i> ID da despesa</div>
              <div class="detail-value text-muted" id="detId">EXP-000</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-calendar3 text-muted border rounded p-1"></i> Data da despesa
              </div>
              <div class="detail-value" id="detData">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-building text-muted border rounded p-1"></i> Centro de custo
              </div>
              <div class="detail-value" id="detCC">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-chat-left-text text-muted border rounded p-1"></i> Comentário
              </div>
              <div class="detail-value text-muted" id="detObs">--</div>
            </div>
          </div>

          <div style="width: 100%; height: 1px; background: var(--saas-border); margin: 2rem 0;"></div>
          <h6 class="fw-bold mb-3">Status de Aprovação</h6>
          <div class="timeline-aprovadores">
            <div class="timeline-node active d-flex align-items-start">
              <span class="text-muted fw-bold me-3" style="font-size:12px; margin-top:10px;">①</span>
              <div class="timeline-card focused flex-grow-1">
                <span class="chip chip-primary mb-2" style="font-size:10px;">Aprovador</span>
                <div class="text-muted" style="font-size:12px;">Despesa submetida ao fluxo de aprovação.</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Carregamento dinâmico via API
  async function loadDomMock() {
    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_doms' })
      });
      let json = await res.json();
      if (json.sucesso) {
        let catSelect = document.getElementById('fCategoria');
        if (catSelect.tomselect) catSelect.tomselect.destroy();
        catSelect.innerHTML = '<option value="">Selecione...</option>';
        json.dados.tipos.forEach(t => {
          catSelect.innerHTML += `<option value="${t.CODTIPODESPESA}">${t.DESCRICAO}</option>`;
        });

        let ccSelect = document.getElementById('fCentroCusto');
        if (ccSelect.tomselect) ccSelect.tomselect.destroy();
        ccSelect.innerHTML = '<option value="">Selecione...</option>';
        json.dados.ccs.forEach(c => {
          ccSelect.innerHTML += `<option value="${c.CENTROCUSTO}|${c.SEQCENTRORESULTADO}">${c.CENTROCUSTO} | ${c.NOME}</option>`;
        });

        // Initialize autocomplete TomSelect
        new TomSelect('#fCategoria', {
          create: false,
          sortField: { field: "text", direction: "asc" },
          placeholder: 'Selecione ou digite para buscar...'
        });

        new TomSelect('#fCentroCusto', {
          create: false,
          sortField: { field: "text", direction: "asc" },
          placeholder: 'Selecione ou digite para buscar...'
        });
      }
    } catch (e) {
      console.error("Erro ao carregar DOMS:", e);
    }
  }

  function abrirModalNova() {
    const p = new bootstrap.Modal('#modalNovaDespesa');
    p.show();
    loadDomMock(); // Carrega dinamicamente do BD
  }

  document.getElementById('fArquivo').addEventListener('change', function (e) {
    if (this.files && this.files[0]) {
      document.getElementById('fileDisplayName').innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + this.files[0].name;
    }
  });

  function updateValorDisplay(val) {
    let num = parseFloat(val) || 0;
    document.getElementById('displayValor').innerText = num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function updateCatDisplay(sel) {
    let text = sel.options[sel.selectedIndex].text;
    document.getElementById('displayCategoria').innerText = text === 'Selecione...' ? 'Estabelecimento' : text;
  }

  async function enviarReembolso() {
    let btn = document.querySelector('.btn-primary-custom');
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
    btn.disabled = true;

    try {
      let formData = new FormData();
      formData.append('action', 'create');
      formData.append('valor', document.getElementById('fValor').value);
      formData.append('estabelecimento', document.getElementById('fEstabelecimento').value);
      formData.append('data_despesa', document.getElementById('fData').value);
      formData.append('categoria', document.getElementById('fCategoria').value);
      formData.append('centro_custo', document.getElementById('fCentroCusto').value);
      formData.append('vencimento', document.getElementById('fVencimento').value);
      formData.append('comentario', document.getElementById('fComentario').value);

      let fileInput = document.getElementById('fArquivo');
      if (fileInput.files.length > 0) {
        formData.append('arquivo', fileInput.files[0]);
      }

      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        // Omit Content-Type when using FormData so the browser formats multipart correctly
        body: formData
      });
      let json = await res.json();

      if (json.sucesso) {
        // Sucesso UX com SweetAlert2
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Sucesso!';
        btn.classList.add('bg-success');

        Swal.fire({
          icon: 'success',
          title: 'Reembolso Solicitado!',
          html: 'Sua despesa foi registrada com sucesso e já está aparecendo no painel do seu gestor para aprovação.<br><br><b>Aguarde a Análise.</b>',
          confirmButtonColor: '#0d6efd',
          confirmButtonText: 'Entendi, fechar',
          customClass: { popup: 'rounded-4' }
        }).then(() => {
          bootstrap.Modal.getInstance(document.getElementById('modalNovaDespesa')).hide();
          document.getElementById('formReembolso').reset();
          document.getElementById('fileDisplayName').innerHTML = '';
          document.getElementById('displayValor').innerText = 'R$ 0,00';
          btn.innerHTML = originalHtml;
          btn.disabled = false;
          btn.classList.remove('bg-success');
          loadList(); // Recarrega lista sem reload da página
        });

      } else {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Erro: ' + json.erro });
        btn.innerHTML = originalHtml;
        btn.disabled = false;
      }

    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha de conexão com a API.' });
      btn.innerHTML = originalHtml;
      btn.disabled = false;
    }
  }

  function parseStatusChip(status) {
    status = (status || 'LANCADO').toUpperCase();
    if (status === 'LANCADO' || status === 'EM_APROVACAO' || status === 'APROVACAO') return '<span class="chip chip-primary">• Em aprovação</span>';
    if (status === 'APROVADO' || status === 'REEMBOLSADO') return '<span class="chip chip-green">• Reembolsado</span>';
    if (status === 'REJEITADO' || status === 'REPROVADO') return '<span class="chip" style="background: rgba(220,53,69,.1); color: #dc3545;">• Reprovado</span>';
    return '<span class="chip chip-gray">• ' + status + '</span>';
  }

  async function loadList() {
    let tbody = document.getElementById('tbodyReembolsos');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i> Atualizando...</td></tr>';

    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'list_mine' })
      });
      let json = await res.json();

      if (json.sucesso) {
        let m = json.dados.metricas;
        if (m) {
          document.getElementById('metricTotalValor').innerText = parseFloat(m.total_valor || 0).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' });
          document.getElementById('metricTotalQtd').innerText = m.total;

          document.getElementById('metricAprovacaoValor').innerText = parseFloat(m.em_aprovacao_valor || 0).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' });
          document.getElementById('metricAprovacaoQtd').innerText = m.em_aprovacao + ' Reembolsos';

          document.getElementById('metricReembolsadoValor').innerText = parseFloat(m.reembolsado_valor || 0).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' });
          document.getElementById('metricReembolsadoQtd').innerText = m.reembolsado + ' Reembolsos';

          document.getElementById('metricReprovadoValor').innerText = parseFloat(m.reprovado_valor || 0).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' });
          document.getElementById('metricReprovadoQtd').innerText = m.reprovado == 0 ? 'Nenhum Reembolso' : m.reprovado + ' Reembolsos';
        }

        let html = '';
        if (json.dados.dados.length === 0) {
          html = '<tr><td colspan="9" class="text-center text-muted py-5">Nenhum reembolso na sua fila.</td></tr>';
        } else {
          json.dados.dados.forEach(d => {
            let dataStr = 'Data inválida';
            if (d.DTAINCLUSAO) {
              // Pega apenas a data antes do espaço caso tenha hora vinda do oracle
              let dateOnly = d.DTAINCLUSAO.split(' ')[0];
              dataStr = new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR');
            }

            let valFormat = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

            // Exemplo verificacao de fora da politica. Se N, ok, senao badge
            let fpHtml = '<span class="chip chip-green"><i class="bi bi-check-circle" style="font-size:11px;"></i> Dentro da regra</span>';

            html += `
                <tr>
                  <td class="text-center"><input type="checkbox" class="form-check-input" style="width:20px;height:20px;border-radius:6px;border-color:var(--saas-border);"></td>
                  <td class="text-muted fw-bold">${dataStr}</td>
                  <td>
                    <div class="fw-bold" style="font-size:13px; letter-spacing: -0.01em; color: var(--saas-text);">${d.FORNECEDOR || 'Despesa'}</div>
                    <div class="text-muted" style="font-size:11.5px; margin-top: 2px;">${d.OBSERVACAO || ''}</div>
                  </td>
                  <td class="text-end fw-bold" style="font-size: 13.5px;">${valFormat}</td>
                  <td class="text-center">
                    <button class="btn-receipt ms-auto" title="${d.NOMEARQUIVO || 'Sem arquivo'}"><i class="bi bi-file-earmark-text"></i> ${d.CODARQUIVO > 0 ? `<span class="badge-count text-white bg-primary">1</span>` : ''}</button>
                  </td>
                  <td>${parseStatusChip(d.STATUS)}</td>
                  <td>${fpHtml}</td>
                  <td style="font-size:12px;" class="text-muted">${d.CENTROCUSTO} | ${d.DESC_CC || 'Centro de Custo'}</td>
                  <td class="text-end" style="padding-right: 2rem;">
                    <div class="d-flex gap-2 justify-content-end">
                      <button class="btn btn-sm btn-light p-1" style="width:30px;height:30px; border-radius:8px;" onclick="abrirModalDetalhes('${encodeURIComponent(JSON.stringify(d))}')"><i class="bi bi-eye text-muted"></i></button>
                    </div>
                  </td>
                </tr>
                `;
          });
        }
        tbody.innerHTML = html;
      }
    } catch (e) {
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-5">Erro ao comunicar com a API.</td></tr>';
    }
  }

  function abrirModalDetalhes(jsonEncoded) {
    try {
      let d = JSON.parse(decodeURIComponent(jsonEncoded));
      document.getElementById('detForn').innerText = d.FORNECEDOR || 'Despesa Corporativa';
      document.getElementById('detVal').innerText = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      let dateOnly = d.DTAINCLUSAO ? d.DTAINCLUSAO.split(' ')[0] : '';
      document.getElementById('detData').innerText = dateOnly ? new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR') : '--';

      document.getElementById('detId').innerText = 'EXP-' + d.CODDESPESA;
      document.getElementById('detCC').innerText = d.CENTROCUSTO + ' | ' + (d.DESC_CC || 'Centro de Custo');
      document.getElementById('detObs').innerText = d.OBSERVACAO || '--';

      document.getElementById('detStatus').innerHTML = parseStatusChip(d.STATUS);

      new bootstrap.Modal('#modalDetalhesDespesa').show();
    } catch (e) {
      console.error(e);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadList();
  });
</script>