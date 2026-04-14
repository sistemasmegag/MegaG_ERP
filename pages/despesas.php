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
    min-width: 0;
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
    min-width: 0;
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
    flex-shrink: 0;
  }

  .detail-value {
    font-size: 13px;
    font-weight: 800;
    color: var(--saas-text);
    text-align: right;
    word-break: break-word;
    flex-grow: 1;
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

  /* ===== Multi-CC Styles ===== */
  .cc-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .cc-row {
    display: flex;
    align-items: center;
    gap: 8px;
    animation: fadeInDown .22s ease;
  }

  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .cc-row .saas-select {
    flex: 1;
    min-width: 0;
  }

  .cc-rateio-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .cc-rateio-toggle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border-radius: 999px;
    border: 1px solid rgba(99, 102, 241, .18);
    background: rgba(99, 102, 241, .06);
  }

  .cc-rateio-toggle button {
    border: 0;
    background: transparent;
    color: var(--saas-muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .04em;
    border-radius: 999px;
    padding: 7px 12px;
    transition: .18s ease;
  }

  .cc-rateio-toggle button.active {
    background: #6366f1;
    color: #fff;
    box-shadow: 0 8px 18px rgba(99, 102, 241, .25);
  }

  .cc-rateio-hint {
    font-size: 11px;
    color: var(--saas-muted);
    font-weight: 700;
  }

  .btn-cc-add {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1.5px dashed rgba(13,110,253,.45);
    background: rgba(13,110,253,.06);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: .18s ease;
    flex-shrink: 0;
    line-height: 1;
  }

  .btn-cc-add:hover {
    background: rgba(13,110,253,.14);
    border-color: #0d6efd;
    transform: scale(1.08);
  }

  .btn-fornecedor-add {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 1.5px dashed rgba(13,110,253,.45);
    background: rgba(13,110,253,.06);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 800;
    cursor: pointer;
    transition: .18s ease;
    flex-shrink: 0;
  }

  .btn-fornecedor-add:hover {
    background: rgba(13,110,253,.14);
    border-color: #0d6efd;
    transform: translateY(-1px);
  }

  .fornecedor-select-row {
    display: flex;
    align-items: stretch;
    gap: 10px;
  }

  .fornecedor-select-row .ts-wrapper,
  .fornecedor-select-row .saas-select {
    flex: 1;
    min-width: 0;
  }

  .fornecedor-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .fornecedor-form-grid .full {
    grid-column: 1 / -1;
  }

  @media (max-width: 768px) {
    .fornecedor-form-grid {
      grid-template-columns: 1fr;
    }

    .fornecedor-form-grid .full {
      grid-column: auto;
    }
  }

  .btn-cc-remove {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid rgba(220,53,69,.3);
    background: rgba(220,53,69,.06);
    color: #dc3545;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: .18s ease;
    flex-shrink: 0;
  }

  .btn-cc-remove:hover {
    background: rgba(220,53,69,.14);
    border-color: #dc3545;
  }

  /* CC Valor Input */
  .cc-valor-input {
    width: 120px !important;
    flex-shrink: 0;
    padding: 10px 12px !important;
    font-size: 13px !important;
    font-weight: 700;
    text-align: right;
    border-radius: 10px !important;
    color: #6366f1 !important;
    border-color: rgba(99,102,241,.35) !important;
    background: rgba(99,102,241,.04) !important;
    transition: .18s ease;
  }

  .cc-valor-input:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15) !important;
  }

  .cc-soma-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 10px;
    background: rgba(99,102,241,.05);
    border: 1px solid rgba(99,102,241,.18);
    margin-top: 8px;
    font-size: 12px;
    font-weight: 700;
    transition: .2s ease;
  }

  .cc-soma-bar.ok {
    background: rgba(16,185,129,.07);
    border-color: rgba(16,185,129,.3);
    color: #059669;
  }

  .cc-soma-bar.warn {
    background: rgba(245,158,11,.07);
    border-color: rgba(245,158,11,.3);
    color: #d97706;
  }

  .cc-soma-progress {
    flex: 1;
    height: 5px;
    border-radius: 99px;
    background: rgba(0,0,0,.07);
    overflow: hidden;
  }

  .cc-soma-progress-fill {
    height: 100%;
    border-radius: 99px;
    transition: width .3s ease, background .2s;
  }

  .cc-soma-extra {
    margin-left: auto;
    font-size: 11px;
    color: var(--saas-muted);
    font-weight: 700;
    white-space: nowrap;
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
  .det-anexos-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 0 1rem 1rem;
    max-height: 210px;
    overflow-y: auto;
  }
  .det-anexo-item {
    width: 100%;
    border: 1px solid var(--saas-border);
    background: rgba(255,255,255,.9);
    border-radius: 12px;
    padding: .8rem .95rem;
    text-align: left;
    transition: .2s ease;
    color: var(--saas-text);
  }
  .det-anexo-item:hover,
  .det-anexo-item.active {
    border-color: rgba(13,110,253,.35);
    box-shadow: 0 10px 20px rgba(13,110,253,.08);
    background: rgba(13,110,253,.05);
  }
  .det-anexo-item small {
    display: block;
    color: var(--saas-text-muted);
    margin-top: 4px;
  }

  .filtros-resumo {
    font-size: 12px;
    color: var(--saas-muted);
  }
  /* ===== Rateio Styles ===== */
  .rateio-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 99px;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .03em;
    background: linear-gradient(135deg, rgba(99,102,241,.13), rgba(139,92,246,.10));
    color: #6366f1;
    border: 1px solid rgba(99,102,241,.22);
    vertical-align: middle;
    margin-left: 6px;
    white-space: nowrap;
  }

  html[data-theme="dark"] .rateio-badge {
    background: rgba(99,102,241,.2);
    color: #a5b4fc;
  }

  .rateio-section {
    margin-top: 1rem;
    border: 1px solid var(--saas-border);
    border-radius: 14px;
    overflow: hidden;
    background: var(--saas-surface);
  }

  .rateio-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: linear-gradient(135deg, rgba(99,102,241,.07), rgba(139,92,246,.04));
    border-bottom: 1px solid var(--saas-border);
    cursor: pointer;
    user-select: none;
  }

  .rateio-section-header .rateio-title {
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: #6366f1;
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .rateio-section-body {
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .rateio-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(99,102,241,.04);
    border: 1px solid rgba(99,102,241,.12);
  }

  html[data-theme="dark"] .rateio-card {
    background: rgba(99,102,241,.08);
  }

  .rateio-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: rgba(99,102,241,.12);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
  }

  .rateio-card-body {
    flex: 1;
    min-width: 0;
  }

  .rateio-card-name {
    font-size: 12px;
    font-weight: 800;
    color: var(--saas-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .rateio-card-code {
    font-size: 11px;
    color: var(--saas-muted);
    margin-top: 1px;
  }

  .rateio-card-val {
    font-size: 13px;
    font-weight: 900;
    color: #6366f1;
    white-space: nowrap;
  }

  .rateio-bar-wrap {
    margin-top: 4px;
    height: 4px;
    border-radius: 99px;
    background: rgba(99,102,241,.12);
    overflow: hidden;
  }

  .rateio-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #6366f1, #a78bfa);
    transition: width .4s ease;
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
          <button class="btn-light d-none d-md-flex align-items-center gap-2" id="btnPeriodoResumo" type="button">
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
          <input type="text" class="saas-input" id="inputBuscaDespesas" placeholder="Buscar por categoria ou código da despesa"
            style="padding-left: 40px; border-radius: 99px;">
        </div>
        <button class="btn-light rounded-pill px-3" id="btnFiltrosDespesas" type="button" style="font-size: 13px;">
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

<div class="modal fade" id="modalFiltrosDespesas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:20px; border:1px solid var(--saas-border); overflow:hidden;">
      <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom:1px solid var(--saas-border);">
        <div>
          <h5 class="modal-title fw-bold m-0">Filtros de Reembolso</h5>
          <div class="filtros-resumo">Refine a listagem sem perder os dados já carregados.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <div class="mb-3">
          <label class="saas-label mb-1">Status</label>
          <select class="saas-select" id="filtroStatusDespesas">
            <option value="">Todos</option>
            <option value="EM_APROVACAO">Em aprovação</option>
            <option value="REEMBOLSADO">Reembolsado</option>
            <option value="REPROVADO">Reprovado</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="saas-label mb-1">Centro de custo</label>
          <input type="text" class="saas-input" id="filtroCentroDespesas" placeholder="Código ou descrição do centro de custo">
        </div>
        <div class="mb-3">
          <label class="saas-label mb-1">Período</label>
          <select class="saas-select" id="filtroPeriodoDespesas">
            <option value="30">Últimos 30 dias</option>
            <option value="7">Últimos 7 dias</option>
            <option value="90">Últimos 90 dias</option>
            <option value="all">Todo o histórico</option>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="saas-label mb-1">Anexos</label>
            <select class="saas-select" id="filtroAnexoDespesas">
              <option value="">Todos</option>
              <option value="com">Com anexo</option>
              <option value="sem">Sem anexo</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="saas-label mb-1">Rateio</label>
            <select class="saas-select" id="filtroRateioDespesas">
              <option value="">Todos</option>
              <option value="com">Com rateio</option>
              <option value="sem">Sem rateio</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding: 1rem 1.5rem; border-top:1px solid var(--saas-border);">
        <button type="button" class="btn btn-light" id="btnLimparFiltrosDespesas">Limpar</button>
        <button type="button" class="btn btn-primary-custom" id="btnAplicarFiltrosDespesas">Aplicar filtros</button>
      </div>
    </div>
  </div>
</div>

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
            <h6 class="fw-bold mb-1 text-dark" style="font-size: 15px;">Arraste e solte aqui os seus arquivos</h6>
            <p style="font-size:12px;" class="text-muted mb-4 opacity-75">Formatos permitidos: PDF, PNG, JPEG, JPG</p>
            <input type="file" id="fArquivo" class="d-none" accept=".pdf,.png,.jpeg,.jpg" multiple>
            <button class="btn-light rounded-pill" onclick="document.getElementById('fArquivo').click()"
              style="color:#0d6efd; border-color: rgba(13,110,253,.3); padding: 8px 24px; font-size: 13px;">
              Selecionar arquivos <i class="bi bi-upload ms-1"></i>
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
                <span id="ccSomaExtra" class="cc-soma-extra"></span>
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
              <div class="fornecedor-select-row">
                <select class="saas-select" id="fEstabelecimento" placeholder="Digite para buscar o fornecedor..."></select>
                <button type="button" class="btn-fornecedor-add" id="btnNovoFornecedor" title="Cadastrar novo fornecedor" onclick="abrirModalNovoFornecedor()">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>
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

            <div class="saas-input-group mt-4" id="grupoCentroCusto">
              <label class="saas-label d-flex align-items-center justify-content-between">
                <span>Centro de custo * <small class="fw-normal text-muted ms-1">(SEQCENTRORESULTADO)</small></span>
                <div class="cc-rateio-toolbar">
                  <div class="cc-rateio-toggle" id="ccRateioToggle" style="display:none;">
                    <button type="button" id="btnRateioValor" class="active" onclick="setRateioMode('valor')">R$</button>
                    <button type="button" id="btnRateioPercentual" onclick="setRateioMode('percentual')">%</button>
                  </div>
                  <button type="button" class="btn-cc-add" id="btnAddCC" title="Adicionar mais um centro de custo" onclick="addCentroCusto()">+</button>
                </div>
              </label>
              <div class="cc-container" id="ccContainer">
                <div class="cc-row" id="ccRow0">
                  <select class="saas-select" id="fCentroCusto" name="centro_custo[]"></select>
                  <input type="number" step="0.01" min="0" class="saas-input cc-valor-input" id="fCCValor_0"
                    placeholder="R$ 0,00" style="display:none;" oninput="atualizarSomaRateio()">
                </div>
              </div>
              <div class="cc-rateio-hint" id="ccRateioHint" style="display:none;">Defina quanto cada centro participa. Em %, a soma deve fechar em 100%.</div>
              <!-- Indicador de soma do rateio -->
              <div class="cc-soma-bar warn" id="ccSomaBar" style="display:none;">
                <i class="bi bi-calculator" style="font-size:13px;"></i>
                <span>Soma: <b id="ccSomaVal">R$ 0,00</b> / <b id="ccSomaTotal">R$ 0,00</b></span>
                <div class="cc-soma-progress">
                  <div class="cc-soma-progress-fill" id="ccSomaFill" style="width:0%; background:#d97706;"></div>
                </div>
                <span id="ccSomaDiff" style="white-space:nowrap;">—</span>
              </div>
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
          <button class="btn-primary-custom rounded-pill px-4" id="btnSolicitarReembolso" onclick="enviarReembolso()"><span
              style="font-size:13px;">Solicitar reembolso <i class="bi bi-check2 ms-1"></i></span></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Novo Fornecedor -->
<div class="modal fade" id="modalNovoFornecedor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
      <div class="modal-header d-flex justify-content-between align-items-center"
        style="border-bottom: 1px solid var(--saas-border); padding: 1.25rem 1.5rem; background: var(--saas-surface);">
        <div>
          <div class="saas-kicker">Cadastro rapido</div>
          <h5 class="modal-title fw-bold m-0 text-dark">Novo fornecedor</h5>
        </div>
        <button type="button" class="btn btn-link text-decoration-none text-muted p-0 fw-bold" onclick="voltarParaReembolsoModal()">
          Fechar
        </button>
      </div>
      <div class="modal-body" style="padding: 1.5rem; background: var(--saas-surface);">
        <form id="formNovoFornecedor">
          <div class="fornecedor-form-grid">
            <div class="saas-input-group full">
              <label class="saas-label">Razao social / Nome *</label>
              <input type="text" class="saas-input" id="nfNomeRazao" maxlength="100" placeholder="Nome completo do fornecedor">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Nome fantasia</label>
              <input type="text" class="saas-input" id="nfFantasia" maxlength="35" placeholder="Opcional">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Palavra-chave</label>
              <input type="text" class="saas-input" id="nfPalavraChave" maxlength="35" placeholder="Busca rapida">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Tipo de pessoa *</label>
              <select class="saas-select" id="nfTipoPessoa" onchange="toggleSexoFornecedor()">
                <option value="J" selected>Juridica</option>
                <option value="F">Fisica</option>
              </select>
            </div>
            <div class="saas-input-group">
              <label class="saas-label">CPF / CNPJ *</label>
              <input type="text" class="saas-input" id="nfDocumento" maxlength="18" placeholder="Somente numeros ou formatado">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Sexo</label>
              <select class="saas-select" id="nfSexo" disabled>
                <option value="">Nao se aplica</option>
                <option value="M">Masculino</option>
                <option value="F">Feminino</option>
              </select>
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Data e hora de ativacao</label>
              <input type="datetime-local" class="saas-input" id="nfDataAtivacao">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">E-mail</label>
              <input type="email" class="saas-input" id="nfEmail" maxlength="50" placeholder="contato@fornecedor.com">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Inscricao RG / Estadual</label>
              <input type="text" class="saas-input" id="nfInscricaoOrg" maxlength="20" placeholder="Opcional">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">CEP</label>
              <input type="text" class="saas-input" id="nfCep" maxlength="9" placeholder="00000-000">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Cidade *</label>
              <input type="text" class="saas-input" id="nfCidade" maxlength="60" placeholder="Cidade">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">UF *</label>
              <input type="text" class="saas-input text-uppercase" id="nfUf" maxlength="2" placeholder="UF">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Bairro</label>
              <input type="text" class="saas-input" id="nfBairro" maxlength="30" placeholder="Bairro">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Logradouro</label>
              <input type="text" class="saas-input" id="nfLogradouro" maxlength="35" placeholder="Rua, avenida, etc.">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Numero</label>
              <input type="text" class="saas-input" id="nfNumeroLogradouro" maxlength="10" placeholder="Numero">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">DDD</label>
              <input type="text" class="saas-input" id="nfDdd" maxlength="3" placeholder="11">
            </div>
            <div class="saas-input-group">
              <label class="saas-label">Telefone</label>
              <input type="text" class="saas-input" id="nfTelefone" maxlength="12" placeholder="999999999">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between align-items-center"
        style="border-top: 1px solid var(--saas-border); padding: 1rem 1.5rem; background: var(--saas-surface);">
        <button type="button" class="btn btn-link text-decoration-none text-muted p-0 fw-bold" onclick="voltarParaReembolsoModal()">
          Voltar ao reembolso
        </button>
        <button type="button" class="btn-primary-custom rounded-pill px-4" id="btnSalvarFornecedor" onclick="salvarNovoFornecedor()">
          <span style="font-size:13px;">Salvar fornecedor</span>
        </button>
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
          <div class="split-left" style="overflow-y:auto; background: #eee; min-height: 400px; display: flex; flex-direction: column; align-items: stretch; justify-content: flex-start;">
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white border-bottom w-100" style="border-radius: 8px 8px 0 0;">
               <button class="btn btn-light btn-sm"><i class="bi bi-image"></i></button>
             <div class="text-muted fw-bold small">Documentação da despesa</div>
             <a id="btnDownloadAllDocs" class="btn btn-light btn-sm d-none" href="#" download>
               <i class="bi bi-download me-1"></i> Baixar tudo
             </a>
            </div>
            <div id="detAnexosList" class="det-anexos-list"></div>
            <div class="pdf-viewer-fake flex-grow-1 p-3 w-100">
              <div id="visualizadorAnexo" class="pdf-page d-flex align-items-center justify-content-center text-muted flex-column gap-2" 
                   style="background:#fff no-repeat center center; background-size: contain; min-height: 600px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%;">
                 <i class="bi bi-file-earmark-image fs-1 opacity-25"></i>
               <span>Carregando anexo...</span>
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

            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-receipt text-muted border rounded p-1"></i> ID da despesa</div>
              <div class="detail-value text-muted" id="detId">EXP-000</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-grid-1x2 text-muted border rounded p-1"></i> Categoria</div>
              <div class="detail-value" id="detCat">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-calendar3 text-muted border rounded p-1"></i> Data da despesa</div>
              <div class="detail-value" id="detData">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-building text-muted border rounded p-1"></i> Centro de custo</div>
              <div class="detail-value" id="detCC">--</div>
            </div>

            <!-- Seção de Rateio (visível apenas quando há múltiplos CCs) -->
            <div id="secaoRateio" style="display:none;">
              <div class="rateio-section mt-2">
                <div class="rateio-section-header" onclick="toggleRateioBody()">
                  <span class="rateio-title">
                    <i class="bi bi-diagram-3-fill"></i>
                    Rateio entre Centros de Custo
                  </span>
                  <i class="bi bi-chevron-down" id="rateioChevron" style="font-size:12px; color:#6366f1; transition:.2s;"></i>
                </div>
                <div class="rateio-section-body" id="rateioBody">
                  <div class="text-center text-muted py-2" style="font-size:12px;"><i class="bi bi-hourglass-split"></i> Carregando rateio...</div>
                </div>
              </div>
            </div>
            <div class="detail-row">
               <div class="detail-label"><i class="bi bi-calendar-check text-muted border rounded p-1"></i> Vencimento</div>
               <div class="detail-value" id="detVenc">--</div>
            </div>
            <div class="detail-row">
              <div class="detail-label"><i class="bi bi-chat-left-text text-muted border rounded p-1"></i> Comentário</div>
              <div class="detail-value text-muted" id="detObs">--</div>
            </div>

          <div style="width: 100%; height: 1px; background: var(--saas-border); margin: 2rem 0;"></div>
          <h6 class="fw-bold mb-3">Status de Aprovação</h6>
          <div class="timeline-aprovadores" id="timelineDesp">
             <div class="text-center py-3 text-muted"><i class="bi bi-hourglass-split"></i> Carregando...</div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Cache global de centros de custo (carregados uma vez)
  window._ccsData = [];
  let _ccCounter = 0;
  let _rateioMode = 'valor';
  let _fornecedorCriadoRecente = null;

  function getRateioMode() {
    return _rateioMode === 'percentual' ? 'percentual' : 'valor';
  }

  function setRateioMode(mode) {
    _rateioMode = mode === 'percentual' ? 'percentual' : 'valor';

    let btnValor = document.getElementById('btnRateioValor');
    let btnPerc = document.getElementById('btnRateioPercentual');
    if (btnValor) btnValor.classList.toggle('active', _rateioMode === 'valor');
    if (btnPerc) btnPerc.classList.toggle('active', _rateioMode === 'percentual');

    document.querySelectorAll('#ccContainer .cc-valor-input').forEach(inp => {
      inp.placeholder = _rateioMode === 'percentual' ? '% 0,00' : 'R$ 0,00';
      inp.step = '0.01';
      inp.min = '0';
    });

    let hint = document.getElementById('ccRateioHint');
    if (hint) {
      hint.textContent = _rateioMode === 'percentual'
        ? 'Defina quanto cada centro participa em %. A soma deve fechar em 100%.'
        : 'Defina quanto cada centro participa em R$. A soma deve fechar no valor total da despesa.';
    }

    atualizarSomaRateio();
  }

  // Popula um <select> de CC com TomSelect
  function _initCCSelect(selectEl) {
    if (selectEl.tomselect) selectEl.tomselect.destroy();
    selectEl.innerHTML = '<option value="">Selecione ou digite para buscar...</option>';
    window._ccsData.forEach(c => {
      let opt = document.createElement('option');
      opt.value = c.CENTROCUSTO + '|' + c.SEQCENTRORESULTADO;
      opt.textContent = c.CENTROCUSTO + ' | ' + c.NOME;
      selectEl.appendChild(opt);
    });
    new TomSelect(selectEl, {
      create: false,
      sortField: { field: "text", direction: "asc" },
      placeholder: 'Selecione ou digite para buscar...'
    });
  }

  // Adiciona um novo campo de centro de custo
  function addCentroCusto() {
    _ccCounter++;
    let rowId   = 'ccRow'        + _ccCounter;
    let selectId = 'fCentroCusto_' + _ccCounter;
    let valorId  = 'fCCValor_'    + _ccCounter;

    let container = document.getElementById('ccContainer');

    // Ao adicionar o 2º CC, mostra o campo de valor do 1º CC
    let extraRows = container.querySelectorAll('.cc-row:not(#ccRow0)');
    if (extraRows.length === 0) {
      document.getElementById('fCCValor_0').style.display = '';
      document.getElementById('ccSomaBar').style.display  = 'flex';
      let toggle = document.getElementById('ccRateioToggle');
      let hint = document.getElementById('ccRateioHint');
      if (toggle) toggle.style.display = 'inline-flex';
      if (hint) hint.style.display = 'block';
      atualizarSomaRateio();
    }

    let row = document.createElement('div');
    row.className = 'cc-row';
    row.id = rowId;
    row.innerHTML = `
      <select class="saas-select" id="${selectId}" name="centro_custo[]"></select>
      <input type="number" step="0.01" min="0" class="saas-input cc-valor-input" id="${valorId}"
        placeholder="R$ 0,00" oninput="atualizarSomaRateio()">
      <button type="button" class="btn-cc-remove" title="Remover este centro de custo"
        onclick="removeCentroCusto('${rowId}', '${selectId}', '${valorId}')">
        <i class="bi bi-x"></i>
      </button>
    `;
    container.appendChild(row);

    // Inicializa TomSelect no novo select
    let newSelect = document.getElementById(selectId);
    if (window._ccsData.length > 0) _initCCSelect(newSelect);
    setRateioMode(getRateioMode());
  }

  // Remove um campo de CC adicional
  function removeCentroCusto(rowId, selectId, valorId) {
    let sel = document.getElementById(selectId);
    if (sel && sel.tomselect) sel.tomselect.destroy();
    let row = document.getElementById(rowId);
    if (row) row.remove();

    // Se só restou o ccRow0, oculta o campo de valor do 1º CC e the soma bar
    let container = document.getElementById('ccContainer');
    let remaining = container.querySelectorAll('.cc-row:not(#ccRow0)');
    if (remaining.length === 0) {
      let v0 = document.getElementById('fCCValor_0');
      if (v0) { v0.style.display = 'none'; v0.value = ''; }
      document.getElementById('ccSomaBar').style.display = 'none';
      let toggle = document.getElementById('ccRateioToggle');
      let hint = document.getElementById('ccRateioHint');
      if (toggle) toggle.style.display = 'none';
      if (hint) hint.style.display = 'none';
      setRateioMode('valor');
    } else {
      atualizarSomaRateio();
    }
  }

  // Atualiza o indicador de soma do rateio em tempo real
  function atualizarSomaRateio() {
    let totalDespesa = parseFloat(document.getElementById('fValor').value) || 0;
    let inputs = document.querySelectorAll('#ccContainer .cc-valor-input');
    let soma = 0;
    inputs.forEach(inp => { soma += parseFloat(inp.value) || 0; });

    let bar        = document.getElementById('ccSomaBar');
    let valEl      = document.getElementById('ccSomaVal');
    let totalEl    = document.getElementById('ccSomaTotal');
    let fillEl     = document.getElementById('ccSomaFill');
    let diffEl     = document.getElementById('ccSomaDiff');

    let fmt = v => v.toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
    valEl.textContent   = fmt(soma);
    totalEl.textContent = fmt(totalDespesa);

    let pct  = totalDespesa > 0 ? Math.min((soma / totalDespesa) * 100, 100) : 0;
    let diff = totalDespesa - soma;
    let isOk = Math.abs(diff) < 0.02;

    fillEl.style.width      = pct.toFixed(1) + '%';
    fillEl.style.background = isOk ? '#10b981' : (pct > 100 ? '#ef4444' : '#f59e0b');
    bar.className           = 'cc-soma-bar ' + (isOk ? 'ok' : 'warn');
    diffEl.textContent      = isOk ? '✓ Ok' : (diff > 0 ? 'Faltam ' + fmt(diff) : 'Excesso ' + fmt(Math.abs(diff)));
  }

  // Carregamento dinâmico via API
  function atualizarSomaRateio() {
    let totalDespesa = parseFloat(document.getElementById('fValor').value) || 0;
    let inputs = document.querySelectorAll('#ccContainer .cc-valor-input');
    let soma = 0;
    inputs.forEach(inp => { soma += parseFloat(inp.value) || 0; });

    let bar     = document.getElementById('ccSomaBar');
    let valEl   = document.getElementById('ccSomaVal');
    let totalEl = document.getElementById('ccSomaTotal');
    let fillEl  = document.getElementById('ccSomaFill');
    let diffEl  = document.getElementById('ccSomaDiff');
    let extraEl = document.getElementById('ccSomaExtra');
    let modo    = getRateioMode();

    let fmt = v => v.toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
    let fmtPct = v => `${Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    let pct = 0;
    let diff = 0;
    let isOk = false;

    if (modo === 'percentual') {
      valEl.textContent = fmtPct(soma);
      totalEl.textContent = '100,00%';
      pct = Math.min(soma, 100);
      diff = 100 - soma;
      isOk = Math.abs(diff) < 0.05;
      if (extraEl) {
        let equivalente = totalDespesa > 0 ? (totalDespesa * (soma / 100)) : 0;
        extraEl.textContent = `Equivale a ${fmt(equivalente)}`;
      }
    } else {
      valEl.textContent = fmt(soma);
      totalEl.textContent = fmt(totalDespesa);
      pct = totalDespesa > 0 ? Math.min((soma / totalDespesa) * 100, 100) : 0;
      diff = totalDespesa - soma;
      isOk = Math.abs(diff) < 0.02;
      if (extraEl) extraEl.textContent = '';
    }

    fillEl.style.width = pct.toFixed(1) + '%';
    fillEl.style.background = isOk ? '#10b981' : (pct > 100 ? '#ef4444' : '#f59e0b');
    bar.className = 'cc-soma-bar ' + (isOk ? 'ok' : 'warn');
    diffEl.textContent = isOk
      ? 'OK'
      : (diff > 0
          ? 'Faltam ' + (modo === 'percentual' ? fmtPct(diff) : fmt(diff))
          : 'Excesso ' + (modo === 'percentual' ? fmtPct(Math.abs(diff)) : fmt(Math.abs(diff))));
  }

  async function loadDomMock() {
    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_doms' })
      });
      let json = await parseApiResponse(res);
      if (json.sucesso) {
        let catSelect = document.getElementById('fCategoria');
        let fornSelect = document.getElementById('fEstabelecimento');
        if (catSelect.tomselect) catSelect.tomselect.destroy();
        if (fornSelect.tomselect) fornSelect.tomselect.destroy();
        catSelect.innerHTML = '<option value="">Selecione...</option>';
        fornSelect.innerHTML = '<option value="">Digite ao menos 2 letras para buscar...</option>';
        json.dados.tipos.forEach(t => {
          catSelect.innerHTML += `<option value="${t.CODTIPODESPESA}">${t.DESCRICAO}</option>`;
        });

        // Cache os dados dos CCs e inicializa o 1º campo
        window._ccsData = json.dados.ccs;
        let firstCC = document.getElementById('fCentroCusto');
        _initCCSelect(firstCC);

        // Initialize autocomplete TomSelect para categoria
        new TomSelect('#fCategoria', {
          create: false,
          sortField: { field: "text", direction: "asc" },
          placeholder: 'Selecione ou digite para buscar...'
        });

        new TomSelect('#fEstabelecimento', {
          create: false,
          valueField: 'value',
          labelField: 'text',
          searchField: ['text', 'value'],
          maxOptions: 30,
          preload: false,
          loadThrottle: 300,
          placeholder: 'Digite ao menos 2 letras para buscar...',
          shouldLoad: function(query) {
            return query.length >= 2;
          },
          render: {
            no_results: function(data, escape) {
              if (!data.input || data.input.length < 2) {
                return `<div class="no-results">Digite ao menos 2 letras para buscar.</div>`;
              }
              return `<div class="no-results">Nenhum fornecedor encontrado para "${escape(data.input)}".</div>`;
            },
            loading: function() {
              return `<div class="px-3 py-2 text-muted"><span class="spinner-border spinner-border-sm text-primary me-2" role="status"></span>Buscando fornecedores...</div>`;
            }
          },
          load: async function(query, callback) {
            try {
              let res = await fetch('api/api_despesas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'search_fornecedores', q: query })
              });
              let json = await res.json();
              if (!json.sucesso) {
                callback();
                return;
              }

              let itens = (json.dados || []).map(f => {
                let nome = (f.NOMERAZAO || '').trim();
                let fantasia = (f.FANTASIA || '').trim();
                return {
                  value: nome,
                  text: fantasia && fantasia !== nome ? `${nome} (${fantasia})` : nome
                };
              }).filter(f => f.value);

              callback(itens);
            } catch (e) {
              console.error('Erro ao buscar fornecedores:', e);
              callback();
            }
          }
        });

        // Reinicializa CCs extras que já existam no container (ex: após fechar/abrir modal)
        if (_fornecedorCriadoRecente) {
          preencherFornecedorRecemCriado(_fornecedorCriadoRecente);
        }

        document.querySelectorAll('#ccContainer .cc-row select').forEach(sel => {
          if (sel.id !== 'fCentroCusto' && !sel.tomselect) {
            _initCCSelect(sel);
          }
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

  function abrirModalNovoFornecedor() {
    const modalReembolsoEl = document.getElementById('modalNovaDespesa');
    const modalFornecedorEl = document.getElementById('modalNovoFornecedor');
    const modalReembolso = bootstrap.Modal.getOrCreateInstance(modalReembolsoEl);
    const modalFornecedor = bootstrap.Modal.getOrCreateInstance(modalFornecedorEl);
    modalReembolso.hide();
    modalFornecedor.show();
    prepararFormularioNovoFornecedor();
  }

  function voltarParaReembolsoModal() {
    const modalFornecedor = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNovoFornecedor'));
    const modalReembolso = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNovaDespesa'));
    modalFornecedor.hide();
    modalReembolso.show();
  }

  function prepararFormularioNovoFornecedor() {
    const dataAtivacao = document.getElementById('nfDataAtivacao');
    if (dataAtivacao && !dataAtivacao.value) {
      const agora = new Date();
      const tzOffset = agora.getTimezoneOffset() * 60000;
      dataAtivacao.value = new Date(agora.getTime() - tzOffset).toISOString().slice(0, 16);
    }
    toggleSexoFornecedor();
  }

  function toggleSexoFornecedor() {
    const tipo = document.getElementById('nfTipoPessoa').value;
    const sexo = document.getElementById('nfSexo');
    if (!sexo) return;
    const habilitar = tipo === 'F';
    sexo.disabled = !habilitar;
    if (!habilitar) {
      sexo.value = '';
    }
  }

  function normalizarDigits(value) {
    return String(value || '').replace(/\D+/g, '');
  }

  function preencherFornecedorRecemCriado(fornecedor) {
    const select = document.getElementById('fEstabelecimento');
    if (!select || !select.tomselect || !fornecedor || !fornecedor.value) {
      return;
    }

    const ts = select.tomselect;
    const option = {
      value: fornecedor.value,
      text: fornecedor.text || fornecedor.value
    };

    if (!ts.options[option.value]) {
      ts.addOption(option);
    }

    ts.setValue(option.value, true);
    ts.refreshOptions(false);
  }

  async function salvarNovoFornecedor() {
    const btn = document.getElementById('btnSalvarFornecedor');
    const originalHtml = btn.innerHTML;

    const payload = {
      action: 'create_fornecedor',
      nomerazao: document.getElementById('nfNomeRazao').value.trim(),
      fantasia: document.getElementById('nfFantasia').value.trim(),
      palavrachave: document.getElementById('nfPalavraChave').value.trim(),
      fisicajuridica: document.getElementById('nfTipoPessoa').value,
      sexo: document.getElementById('nfSexo').value,
      documento: document.getElementById('nfDocumento').value.trim(),
      dtaativacao: document.getElementById('nfDataAtivacao').value,
      email: document.getElementById('nfEmail').value.trim(),
      inscricaorg: document.getElementById('nfInscricaoOrg').value.trim(),
      cep: document.getElementById('nfCep').value.trim(),
      cidade: document.getElementById('nfCidade').value.trim(),
      uf: document.getElementById('nfUf').value.trim().toUpperCase(),
      bairro: document.getElementById('nfBairro').value.trim(),
      logradouro: document.getElementById('nfLogradouro').value.trim(),
      nrologradouro: document.getElementById('nfNumeroLogradouro').value.trim(),
      foneddd1: document.getElementById('nfDdd').value.trim(),
      fonenro1: document.getElementById('nfTelefone').value.trim()
    };

    if (!payload.nomerazao) {
      Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a razão social ou nome do fornecedor.' });
      return;
    }
    if (!payload.documento || normalizarDigits(payload.documento).length < 11) {
      Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe um CPF ou CNPJ válido.' });
      return;
    }
    if (!payload.cidade || !payload.uf) {
      Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha cidade e UF para continuar.' });
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

    try {
      const res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await parseApiResponse(res);

      if (!json.sucesso) {
        Swal.fire({ icon: 'error', title: 'Erro ao cadastrar', text: json.erro || 'Não foi possível cadastrar o fornecedor.' });
        return;
      }

      _fornecedorCriadoRecente = json.dados || null;
      preencherFornecedorRecemCriado(_fornecedorCriadoRecente);
      document.getElementById('formNovoFornecedor').reset();
      prepararFormularioNovoFornecedor();
      voltarParaReembolsoModal();

      const mensagem = json.dados?.ja_existia
        ? 'Esse fornecedor já existia no cadastro e foi selecionado para você continuar o reembolso.'
        : 'Fornecedor cadastrado com sucesso e já selecionado no reembolso.';

      Swal.fire({
        icon: 'success',
        title: json.dados?.ja_existia ? 'Fornecedor encontrado' : 'Fornecedor cadastrado',
        text: mensagem,
        confirmButtonColor: '#0d6efd'
      });
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha de conexão ao cadastrar fornecedor.' });
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  }

  function renderSelectedFiles(files) {
    const target = document.getElementById('fileDisplayName');
    if (!target) return;
    if (!files || !files.length) {
      target.innerHTML = '';
      return;
    }

    const items = Array.from(files).map(file =>
      `<div class="d-flex align-items-center justify-content-center gap-2 mt-1"><i class="bi bi-check-circle-fill"></i><span>${file.name}</span></div>`
    ).join('');

    const resumo = files.length === 1
      ? '1 arquivo selecionado'
      : `${files.length} arquivos selecionados`;

    target.innerHTML = `<div>${resumo}</div>${items}`;
  }

  document.getElementById('fArquivo').addEventListener('change', function (e) {
    renderSelectedFiles(this.files);
  });

  function updateValorDisplay(val) {
    let num = parseFloat(val) || 0;
    document.getElementById('displayValor').innerText = num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    // Atualiza indicador de rateio ao mudar o valor total
    let bar = document.getElementById('ccSomaBar');
    if (bar && bar.style.display !== 'none') atualizarSomaRateio();
  }

  function updateCatDisplay(sel) {
    let text = sel.options[sel.selectedIndex].text;
    document.getElementById('displayCategoria').innerText = text === 'Selecione...' ? 'Estabelecimento' : text;
  }

  async function parseApiResponse(res) {
    const rawText = await res.text();

    try {
      return JSON.parse(rawText);
    } catch (e) {
      throw new Error(rawText || `Resposta invalida da API. HTTP ${res.status}`);
    }
  }

  function abrirLoadingReembolso() {
    Swal.fire({
      title: 'Aguarde',
      html: 'Estamos gravando e processando o seu reembolso.<br>Nao feche esta janela nem clique novamente.',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }

  function fecharLoadingReembolso() {
    if (Swal.isVisible()) {
      Swal.close();
    }
  }

  async function enviarReembolso() {
    let btn = document.getElementById('btnSolicitarReembolso');
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
    btn.disabled = true;

    try {
      // Coletar centros de custo e valores individuais
      let ccRows = document.querySelectorAll('#ccContainer .cc-row');
      let centrosCusto   = [];
      let valoresRateio  = [];
      let temMultiplos   = ccRows.length > 1;

      ccRows.forEach(row => {
        let sel = row.querySelector('select');
        let valInput = row.querySelector('.cc-valor-input');
        let ccVal = sel && sel.tomselect ? sel.tomselect.getValue() : (sel ? sel.value : '');
        if (ccVal && ccVal.trim() !== '') {
          centrosCusto.push(ccVal.trim());
          valoresRateio.push(valInput ? (parseFloat(valInput.value) || 0) : 0);
        }
      });

      if (centrosCusto.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione pelo menos um Centro de Custo.' });
        btn.innerHTML = originalHtml; btn.disabled = false;
        return;
      }

      // Validar soma dos valores quando há múltiplos CCs
      if (temMultiplos) {
        let totalDespesa = parseFloat(document.getElementById('fValor').value) || 0;
        let somaRateio   = valoresRateio.reduce((a, b) => a + b, 0);
        let diff = Math.abs(totalDespesa - somaRateio);
        if (diff > 0.02) {
          let fmt = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
          Swal.fire({
            icon: 'warning',
            title: 'Divisão incompleta',
            html: `A soma dos valores por Centro de Custo (<b>${fmt(somaRateio)}</b>) não coincide com o valor total da despesa (<b>${fmt(totalDespesa)}</b>).<br><br>Ajuste os valores antes de continuar.`,
            confirmButtonColor: '#6366f1',
            confirmButtonText: 'Corrigir'
          });
          btn.innerHTML = originalHtml; btn.disabled = false;
          return;
        }
      }

      let fornecedorSelect = document.getElementById('fEstabelecimento');
      let fornecedor = fornecedorSelect && fornecedorSelect.tomselect
        ? fornecedorSelect.tomselect.getValue()
        : fornecedorSelect.value;

      if (!fornecedor || !fornecedor.trim()) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione um estabelecimento/fornecedor.' });
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        return;
      }

      let formData = new FormData();
      formData.append('action', 'create');
      formData.append('valor', document.getElementById('fValor').value);
      formData.append('estabelecimento', fornecedor);
      formData.append('data_despesa', document.getElementById('fData').value);
      formData.append('categoria', document.getElementById('fCategoria').value);
      formData.append('centros_custo',  JSON.stringify(centrosCusto));
      formData.append('valores_rateio', JSON.stringify(valoresRateio));
      formData.append('centro_custo', centrosCusto[0]);
      formData.append('vencimento', document.getElementById('fVencimento').value);
      formData.append('comentario', document.getElementById('fComentario').value);

      let fileInput = document.getElementById('fArquivo');
      if (fileInput.files.length > 0) {
        Array.from(fileInput.files).forEach(file => {
          formData.append('arquivo[]', file);
        });
      }

      abrirLoadingReembolso();

      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        // Omit Content-Type when using FormData so the browser formats multipart correctly
        body: formData
      });
      let json = await parseApiResponse(res);
      fecharLoadingReembolso();

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
          if (document.getElementById('fEstabelecimento').tomselect) {
            document.getElementById('fEstabelecimento').tomselect.clear();
          }
          document.getElementById('fArquivo').value = '';
          document.getElementById('fileDisplayName').innerHTML = '';
          document.getElementById('displayValor').innerText = 'R$ 0,00';
          // Remover CCs extras e limpar inputs de valor
          let container = document.getElementById('ccContainer');
          container.querySelectorAll('.cc-row:not(#ccRow0)').forEach(r => r.remove());
          let v0 = document.getElementById('fCCValor_0');
          if (v0) { v0.style.display = 'none'; v0.value = ''; }
          document.getElementById('ccSomaBar').style.display = 'none';
          btn.innerHTML = originalHtml;
          btn.disabled = false;
          btn.classList.remove('bg-success');
          loadList();
        });

      } else {
        fecharLoadingReembolso();
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Erro: ' + json.erro });
        btn.innerHTML = originalHtml;
        btn.disabled = false;
      }

    } catch (err) {
      fecharLoadingReembolso();
      await loadList();
      let msg = err && err.message ? err.message : 'Falha de conexao com a API.';
      if (msg.length > 220) msg = msg.slice(0, 220) + '...';
      Swal.fire({
        icon: 'warning',
        title: 'Retorno do servidor indisponivel',
        html: `Seu clique foi recebido e a lista foi atualizada para conferencia.<br><br>Se o reembolso apareceu abaixo, nao envie novamente.<br><br><small>${msg}</small>`,
        confirmButtonText: 'Entendi'
      });
      btn.innerHTML = originalHtml;
      btn.disabled = false;
      return;
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

  function formatDateBr(dateStr) {
    if (!dateStr) return '--';
    let normalized = String(dateStr).trim().substring(0, 10);
    let parts = normalized.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return String(dateStr).trim();
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
            if (d.DTAINCLUSAO_FORMAT) {
              let dateOnly = d.DTAINCLUSAO_FORMAT.split(' ')[0];
              dataStr = new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR');
            } else if (d.DTAINCLUSAO) {
              let dateOnly = d.DTAINCLUSAO.split(' ')[0];
              dataStr = new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR');
            }
            dataStr = formatDateBr(d.DTADESPESA_FORMAT || d.DTAINCLUSAO_FORMAT || d.DTADESPESA || d.DTAINCLUSAO);

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
                     <button class="btn-receipt ms-auto" title="${d.QTD_ARQUIVOS > 0 ? `${d.QTD_ARQUIVOS} arquivo(s)` : (d.NOMEARQUIVO || 'Sem arquivo')}"><i class="bi bi-file-earmark-text"></i> ${parseInt(d.QTD_ARQUIVOS || 0) > 0 ? `<span class="badge-count text-white bg-primary">${d.QTD_ARQUIVOS}</span>` : ''}</button>
                    </td>
                  <td>${parseStatusChip(d.STATUS)}</td>
                  <td>${fpHtml}</td>
                  <td style="font-size:12px;" class="text-muted">
                    ${d.CODIGO_CC || d.CENTROCUSTO} | ${d.DESC_CC || 'Centro de Custo'}
                    ${parseInt(d.QTD_RATEIO) > 1
                      ? `<span class="rateio-badge"><i class="bi bi-diagram-3-fill" style="font-size:9px;"></i> Rateio ${d.QTD_RATEIO} CCs</span>`
                      : ''}
                  </td>
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

  async function loadHistory(id) {
    const container = document.getElementById('timelineDesp');
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
                       <span class="text-muted" style="font-size:10px;">${h.DTAACAO}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-small bg-primary text-white d-flex align-items-center justify-content-center" style="width:30px;height:30px;border-radius:50%;font-size:10px;">${h.NOME_APROVADOR[0]}${h.NOME_APROVADOR[1]}</div>
                      <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:13px;">${h.NOME_APROVADOR}</div>
                        <div class="text-muted" style="font-size:11px;">Nível ${h.NIVEL_APROVACAO}</div>
                      </div>
                    </div>
                    ${h.OBSERVACAO ? `<div class="mt-2 p-2 bg-light rounded small text-muted border-start border-primary border-4">${h.OBSERVACAO}</div>` : ''}
                  </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="alert alert-light border text-muted small">Aguardando análise inicial por um aprovador.</div>';
        }
    } catch(e) { }
  }

  // =====================================================
  // RATEIO
  // =====================================================
  function toggleRateioBody() {
    let body = document.getElementById('rateioBody');
    let chev = document.getElementById('rateioChevron');
    let isShown = body.style.display !== 'none';
    body.style.display = isShown ? 'none' : 'flex';
    chev.style.transform = isShown ? 'rotate(0deg)' : 'rotate(180deg)';
  }

  async function loadRateio(id, vlrTotal) {
    let secao = document.getElementById('secaoRateio');
    let body  = document.getElementById('rateioBody');
    secao.style.display = 'none';
    body.innerHTML = '<div class="text-center text-muted py-2" style="font-size:12px;"><i class="bi bi-hourglass-split"></i> Carregando rateio...</div>';

    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_rateio', id: id })
      });
      let json = await res.json();

      if (json.sucesso && json.dados && json.dados.length > 1) {
        // Exibe a seção somente se houver mais de 1 CC no rateio
        secao.style.display = 'block';

        let total = json.dados.reduce((s, r) => s + parseFloat(r.VALORRATEIO || 0), 0) || vlrTotal || 1;

        body.innerHTML = json.dados.map(r => {
          let val  = parseFloat(r.VALORRATEIO || 0);
          let pct  = Math.round((val / total) * 100);
          let nome = r.DESC_CC || 'Centro de Custo';
          let valFmt = val.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

          return `
            <div class="rateio-card">
              <div class="rateio-card-icon"><i class="bi bi-building"></i></div>
              <div class="rateio-card-body">
                <div class="rateio-card-name" title="${nome}">${nome}</div>
                <div class="rateio-card-code">${r.CODIGO_CC || r.CENTROCUSTO}</div>
                <div class="rateio-bar-wrap mt-2">
                  <div class="rateio-bar-fill" style="width:${pct}%"></div>
                </div>
              </div>
              <div class="rateio-card-val">
                ${valFmt}<br>
                <span style="font-size:10px; font-weight:600; color:var(--saas-muted);">${pct}%</span>
              </div>
            </div>
          `;
        }).join('');
      }
    } catch(e) {
      console.error('Erro ao carregar rateio:', e);
    }
  }

  function renderAnexoPreview(file) {
    let visualizador = document.getElementById('visualizadorAnexo');
    if (!visualizador) return;

    if (!file || !file.NOMEARQUIVO) {
      visualizador.style.backgroundImage = 'none';
      visualizador.style.background = '#f8f9fa';
      visualizador.innerHTML = '<i class="bi bi-file-earmark-image fs-1 opacity-25"></i><span>Sem anexo</span>';
      return;
    }

    let ext = String(file.NOMEARQUIVO).split('.').pop().toLowerCase();
    let fileUrl = `uploads/${file.NOMEARQUIVO}`;

    if (ext === 'pdf') {
      visualizador.style.backgroundImage = 'none';
      visualizador.style.background = '#525659';
      visualizador.innerHTML = `<iframe src="${fileUrl}" style="width:100%; height:100%; min-height:600px; border:none; border-radius:8px;"></iframe>`;
    } else {
      visualizador.style.backgroundImage = `url('${fileUrl}')`;
      visualizador.style.backgroundSize = 'contain';
      visualizador.style.backgroundRepeat = 'no-repeat';
      visualizador.style.backgroundPosition = 'center';
      visualizador.style.backgroundColor = '#fff';
      visualizador.innerHTML = '';
    }
  }

  async function loadAnexosDespesa(id, fallbackNomeArquivo = '') {
    const list = document.getElementById('detAnexosList');
    const downloadBtn = document.getElementById('btnDownloadAllDocs');
    if (!list || !downloadBtn) return;

    list.innerHTML = '<div class="text-muted small px-2"><i class="bi bi-hourglass-split me-1"></i>Carregando anexos...</div>';
    downloadBtn.classList.add('d-none');
    downloadBtn.removeAttribute('href');

    try {
      let res = await fetch('api/api_despesas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_attachments', id: id })
      });
      let json = await res.json();
      let anexos = (json.sucesso && Array.isArray(json.dados)) ? json.dados : [];

      if (!anexos.length && fallbackNomeArquivo) {
        anexos = [{ NOMEARQUIVO: fallbackNomeArquivo, TIPOARQUIVO: '', CODARQUIVO: 0 }];
      }

      if (!anexos.length) {
        list.innerHTML = '<div class="text-muted small px-2">Nenhum anexo vinculado a esta despesa.</div>';
        renderAnexoPreview(null);
        return;
      }

      list.innerHTML = anexos.map((file, index) => `
        <button type="button" class="det-anexo-item ${index === 0 ? 'active' : ''}" data-file="${encodeURIComponent(JSON.stringify(file))}">
          <div class="fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text"></i>
            <span>${file.NOMEARQUIVO}</span>
          </div>
          <small>${(file.TIPOARQUIVO || '').trim() || 'Arquivo anexado'}</small>
        </button>
      `).join('');

      list.querySelectorAll('.det-anexo-item').forEach(button => {
        button.addEventListener('click', function () {
          list.querySelectorAll('.det-anexo-item').forEach(el => el.classList.remove('active'));
          this.classList.add('active');
          let payload = JSON.parse(decodeURIComponent(this.dataset.file));
          renderAnexoPreview(payload);
        });
      });

      renderAnexoPreview(anexos[0]);
      downloadBtn.href = `api/download_despesa_documentos.php?id=${encodeURIComponent(id)}`;
      downloadBtn.classList.remove('d-none');
    } catch (e) {
      console.error('Erro ao carregar anexos:', e);
      list.innerHTML = '<div class="text-danger small px-2">Não foi possível carregar os anexos.</div>';
      renderAnexoPreview(null);
    }
  }

  async function abrirModalDetalhes(jsonEncoded) {
    try {
      let d = JSON.parse(decodeURIComponent(jsonEncoded));
      document.getElementById('detForn').innerText = d.FORNECEDOR || 'Despesa Corporativa';
      document.getElementById('detVal').innerText = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

      let dateOnly = d.DTAINCLUSAO_FORMAT ? d.DTAINCLUSAO_FORMAT.split(' ')[0] : (d.DTAINCLUSAO ? d.DTAINCLUSAO.split(' ')[0] : '');
      document.getElementById('detData').innerText = dateOnly ? new Date(dateOnly + "T00:00:00").toLocaleDateString('pt-BR') : '--';
      document.getElementById('detData').innerText = formatDateBr(d.DTADESPESA_FORMAT || d.DTAINCLUSAO_FORMAT || d.DTADESPESA || d.DTAINCLUSAO);

      document.getElementById('detId').innerText = 'EXP-' + d.CODDESPESA;
      document.getElementById('detCC').innerText = (d.CODIGO_CC || d.CENTROCUSTO) + ' | ' + (d.DESC_CC || 'Centro de Custo');
      document.getElementById('detObs').innerText = d.OBSERVACAO || '--';
      document.getElementById('detCat').innerText = d.DESC_TIPO || '--';
      document.getElementById('detVenc').innerText = '--';
      document.getElementById('detVenc').innerText = formatDateBr(d.DTAVENCIMENTO_FORMAT || d.DTAVENCIMENTO);

      document.getElementById('detStatus').innerHTML = parseStatusChip(d.STATUS);

      // Tratar Anexo - Suporte a PDF e Imagens
      let visualizador = document.getElementById('visualizadorAnexo');
      if (d.NOMEARQUIVO) {
         let ext = d.NOMEARQUIVO.split('.').pop().toLowerCase();
         let fileUrl = `uploads/${d.NOMEARQUIVO}`;
         
         if (ext === 'pdf') {
            visualizador.style.backgroundImage = 'none';
            visualizador.style.background = '#525659';
            visualizador.innerHTML = `<iframe src="${fileUrl}" style="width:100%; height:100%; min-height:600px; border:none; border-radius:8px;"></iframe>`;
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
      loadAnexosDespesa(d.CODDESPESA, d.NOMEARQUIVO || '');
      // Carrega rateio se houver múltiplos CCs
      loadRateio(d.CODDESPESA, parseFloat(d.VLRRATDESPESA || 0));
      new bootstrap.Modal('#modalDetalhesDespesa').show();
    } catch (e) {
      console.error(e);
    }
  }

  let despesasCache = [];
  let filtrosDespesas = {
    busca: '',
    status: '',
    centro: '',
    periodo: '30',
    anexo: '',
    rateio: ''
  };

  function normalizarTextoFiltro(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function obterDataFiltroDespesa(row) {
    const raw = row.DTADESPESA_FORMAT || row.DTADESPESA || row.DTAINCLUSAO_FORMAT || row.DTAINCLUSAO || '';
    if (!raw) return null;
    const normalized = String(raw).trim().substring(0, 10);
    const dt = new Date(normalized + 'T00:00:00');
    return Number.isNaN(dt.getTime()) ? null : dt;
  }

  function obterResumoPeriodoDespesa() {
    if (filtrosDespesas.periodo === '7') return 'Últimos 7 dias';
    if (filtrosDespesas.periodo === '90') return 'Últimos 90 dias';
    if (filtrosDespesas.periodo === 'all') return 'Todo o histórico';
    return 'Últimos 30 dias';
  }

  function atualizarResumoPeriodoDespesa() {
    const btn = document.getElementById('btnPeriodoResumo');
    if (!btn) return;
    btn.innerHTML = `<i class="bi bi-calendar3"></i> ${obterResumoPeriodoDespesa()}`;
  }

  function filtrarDespesasLocal(rows) {
    const agora = new Date();
    const termoBusca = normalizarTextoFiltro(filtrosDespesas.busca);
    const centroBusca = normalizarTextoFiltro(filtrosDespesas.centro);

    return (rows || []).filter(d => {
      const status = String(d.STATUS || '').toUpperCase();
      const qtdArquivos = parseInt(d.QTD_ARQUIVOS || 0, 10);
      const qtdRateio = parseInt(d.QTD_RATEIO || 0, 10);
      const data = obterDataFiltroDespesa(d);

      if (filtrosDespesas.status === 'EM_APROVACAO' && !['LANCADO', 'EM_APROVACAO', 'APROVACAO'].includes(status)) return false;
      if (filtrosDespesas.status === 'REEMBOLSADO' && !['APROVADO', 'REEMBOLSADO'].includes(status)) return false;
      if (filtrosDespesas.status === 'REPROVADO' && !['REJEITADO', 'REPROVADO'].includes(status)) return false;

      if (centroBusca) {
        const centroTexto = normalizarTextoFiltro(`${d.CODIGO_CC || d.CENTROCUSTO || ''} ${d.DESC_CC || ''}`);
        if (!centroTexto.includes(centroBusca)) return false;
      }

      if (termoBusca) {
        const texto = normalizarTextoFiltro([
          d.CODDESPESA,
          d.FORNECEDOR,
          d.OBSERVACAO,
          d.DESC_TIPO,
          d.CODIGO_CC,
          d.DESC_CC,
          d.STATUS
        ].join(' '));
        if (!texto.includes(termoBusca)) return false;
      }

      if (filtrosDespesas.anexo === 'com' && qtdArquivos <= 0) return false;
      if (filtrosDespesas.anexo === 'sem' && qtdArquivos > 0) return false;
      if (filtrosDespesas.rateio === 'com' && qtdRateio <= 1) return false;
      if (filtrosDespesas.rateio === 'sem' && qtdRateio > 1) return false;

      if (filtrosDespesas.periodo !== 'all' && data) {
        const dias = parseInt(filtrosDespesas.periodo || '30', 10);
        const limite = new Date(agora);
        limite.setHours(0, 0, 0, 0);
        limite.setDate(limite.getDate() - dias);
        if (data < limite) return false;
      }

      return true;
    });
  }

  function renderListFiltrada() {
    const tbody = document.getElementById('tbodyReembolsos');
    const rows = filtrarDespesasLocal(despesasCache);

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5">Nenhum reembolso encontrado com os filtros aplicados.</td></tr>';
      atualizarResumoPeriodoDespesa();
      return;
    }

    let html = '';
    rows.forEach(d => {
      const dataStr = formatDateBr(d.DTADESPESA_FORMAT || d.DTAINCLUSAO_FORMAT || d.DTADESPESA || d.DTAINCLUSAO);
      const valFormat = parseFloat(d.VLRRATDESPESA || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      const fpHtml = '<span class="chip chip-green"><i class="bi bi-check-circle" style="font-size:11px;"></i> Dentro da regra</span>';

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
            <button class="btn-receipt ms-auto" title="${d.QTD_ARQUIVOS > 0 ? `${d.QTD_ARQUIVOS} arquivo(s)` : (d.NOMEARQUIVO || 'Sem arquivo')}"><i class="bi bi-file-earmark-text"></i> ${parseInt(d.QTD_ARQUIVOS || 0, 10) > 0 ? `<span class="badge-count text-white bg-primary">${d.QTD_ARQUIVOS}</span>` : ''}</button>
          </td>
          <td>${parseStatusChip(d.STATUS)}</td>
          <td>${fpHtml}</td>
          <td style="font-size:12px;" class="text-muted">
            ${d.CODIGO_CC || d.CENTROCUSTO} | ${d.DESC_CC || 'Centro de Custo'}
            ${parseInt(d.QTD_RATEIO || 0, 10) > 1 ? `<span class="rateio-badge"><i class="bi bi-diagram-3-fill" style="font-size:9px;"></i> Rateio ${d.QTD_RATEIO} CCs</span>` : ''}
          </td>
          <td class="text-end" style="padding-right: 2rem;">
            <div class="d-flex gap-2 justify-content-end">
              <button class="btn btn-sm btn-light p-1" style="width:30px;height:30px; border-radius:8px;" onclick="abrirModalDetalhes('${encodeURIComponent(JSON.stringify(d))}')"><i class="bi bi-eye text-muted"></i></button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    atualizarResumoPeriodoDespesa();
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

        despesasCache = Array.isArray(json.dados.dados) ? json.dados.dados : [];
        renderListFiltrada();
      }
    } catch (e) {
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-5">Erro ao comunicar com a API.</td></tr>';
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadList();
  });

  document.addEventListener("DOMContentLoaded", function () {
    const inputBusca = document.getElementById('inputBuscaDespesas');
    const btnFiltros = document.getElementById('btnFiltrosDespesas');
    const btnPeriodo = document.getElementById('btnPeriodoResumo');
    const btnAplicar = document.getElementById('btnAplicarFiltrosDespesas');
    const btnLimpar = document.getElementById('btnLimparFiltrosDespesas');

    inputBusca?.addEventListener('input', function () {
      filtrosDespesas.busca = this.value || '';
      renderListFiltrada();
    });

    const abrirModalFiltros = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosDespesas')).show();
    btnFiltros?.addEventListener('click', abrirModalFiltros);
    btnPeriodo?.addEventListener('click', abrirModalFiltros);

    btnAplicar?.addEventListener('click', function () {
      filtrosDespesas.status = document.getElementById('filtroStatusDespesas')?.value || '';
      filtrosDespesas.centro = document.getElementById('filtroCentroDespesas')?.value || '';
      filtrosDespesas.periodo = document.getElementById('filtroPeriodoDespesas')?.value || '30';
      filtrosDespesas.anexo = document.getElementById('filtroAnexoDespesas')?.value || '';
      filtrosDespesas.rateio = document.getElementById('filtroRateioDespesas')?.value || '';
      renderListFiltrada();
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosDespesas')).hide();
    });

    btnLimpar?.addEventListener('click', function () {
      filtrosDespesas = { busca: inputBusca?.value || '', status: '', centro: '', periodo: '30', anexo: '', rateio: '' };
      if (document.getElementById('filtroStatusDespesas')) document.getElementById('filtroStatusDespesas').value = '';
      if (document.getElementById('filtroCentroDespesas')) document.getElementById('filtroCentroDespesas').value = '';
      if (document.getElementById('filtroPeriodoDespesas')) document.getElementById('filtroPeriodoDespesas').value = '30';
      if (document.getElementById('filtroAnexoDespesas')) document.getElementById('filtroAnexoDespesas').value = '';
      if (document.getElementById('filtroRateioDespesas')) document.getElementById('filtroRateioDespesas').value = '';
      renderListFiltrada();
    });
  });
</script>
