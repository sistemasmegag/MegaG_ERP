<?php 
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'dados_visualizar'; 
?>

<style>
/* ===== Clean SaaS (escopado pra Visualizar) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(108,117,125,.10), rgba(108,117,125,.04));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(108,117,125,.14), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(13,110,253,.18), transparent 60%);
    filter: blur(6px);
    transform: rotate(10deg);
    pointer-events:none;
}
.saas-title{
    font-weight: 900;
    letter-spacing: -.02em;
    margin:0;
    color: var(--saas-text);
}
.saas-subtitle{
    margin: 6px 0 0;
    color: var(--saas-muted);
    font-size: 14px;
}

/* Cards mini (contadores) */
.saas-metrics{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
    position: relative;
    z-index: 1;
}
@media (max-width: 900px){
    .saas-metrics{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.saas-metric{
    border: 1px solid var(--saas-border);
    background: rgba(255,255,255,.55);
    border-radius: 16px;
    padding: 12px 12px;
    box-shadow: var(--saas-shadow-soft);
    backdrop-filter: blur(10px);
}
html[data-theme="dark"] .saas-metric{
    background: rgba(255,255,255,.06);
}
.saas-metric .label{
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--saas-muted);
}
.saas-metric .value{
    margin-top: 4px;
    font-size: 24px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--saas-text);
    line-height: 1.1;
}
.saas-metric .hint{
    margin-top: 2px;
    font-size: 12px;
    color: var(--saas-muted);
}

/* Chips rápidos */
.saas-chips{
    display:flex;
    flex-wrap:wrap;
    gap: 8px;
    margin-top: 12px;
    position: relative;
    z-index: 1;
}
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
.saas-chip:hover{
    transform: translateY(-1px);
    border-color: rgba(13,110,253,.25);
}
.saas-chip.active{
    background: rgba(13,110,253,.12);
    border-color: rgba(13,110,253,.22);
    box-shadow: 0 10px 18px rgba(13,110,253,.10);
}

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

/* Inputs clean */
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
.saas-form .form-control:focus,
.saas-form .form-select:focus{
    border-color: rgba(13,110,253,.45);
    box-shadow: 0 0 0 .22rem var(--ring);
    background: var(--saas-surface);
}

/* Botão buscar */
.saas-search-btn{
    height: 44px;
    border-radius: 14px;
    font-weight: 900;
    box-shadow: 0 10px 18px rgba(13,110,253,.18);
}

/* Tabela container SaaS */
.saas-table-wrap{
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow);
    overflow: hidden;
}

/* Cabeçalho sticky */
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

/* Hover */
.saas-table.table-hover tbody tr:hover{
    background: rgba(13,110,253,.06) !important;
}
html[data-theme="dark"] .saas-table.table-hover tbody tr:hover{
    background: rgba(13,110,253,.12) !important;
}

/* Scroll tabela */
.saas-table-scroll{
    max-height: 62vh;
    overflow:auto;
    scrollbar-width: thin;
}
.saas-table-scroll::-webkit-scrollbar{ width: 10px; height: 10px; }
.saas-table-scroll::-webkit-scrollbar-track{ background: rgba(17,24,39,.04); }
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-track{ background: rgba(255,255,255,.06); }
.saas-table-scroll::-webkit-scrollbar-thumb{
    background: rgba(17,24,39,.18);
    border-radius: 999px;
    border: 2px solid rgba(0,0,0,0.06);
}
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-thumb{
    background: rgba(255,255,255,.18);
    border-color: rgba(0,0,0,0.25);
}
.saas-table-scroll::-webkit-scrollbar-thumb:hover{ background: rgba(17,24,39,.28); }
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-thumb:hover{ background: rgba(255,255,255,.26); }

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }

/* ====== Upgrade SaaS: link com ícone abrir ====== */
.saas-detail-link{
    display:flex;
    align-items:center;
    gap:8px;
    max-width: 100%;
}
.saas-detail-link .label{
    display:block;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.saas-detail-link .icon{
    width: 28px;
    height: 28px;
    border-radius: 10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
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
.saas-detail-link:hover{
    text-decoration:none;
}

/* Empty/loading */
.saas-state{
    border-top: 1px solid var(--saas-border);
    background: transparent;
}
</style>

<main class="main-content">
    <div class="container-fluid">

        <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
            <button class="mobile-toggle me-3" onclick="toggleMenu()">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="m-0 fw-bold text-dark">Monitor de Importação</h4>
        </div>

        <div class="saas-head mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
                <div>
                    <h3 class="saas-title">Monitor de Importação</h3>
                    <p class="saas-subtitle">
                        Filtre e acompanhe as cargas/metas e comissões importadas, com status e logs detalhados.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">
                        Consulta Oracle
                    </span>
                </div>
            </div>

            <div class="saas-chips" id="quickChips">
                <div class="saas-chip active" data-status="">
                    <i class="bi bi-grid-3x3-gap"></i> Todos
                </div>
                <div class="saas-chip" data-status="S">
                    <i class="bi bi-check-circle"></i> Sucesso
                </div>
                <div class="saas-chip" data-status="E">
                    <i class="bi bi-exclamation-circle"></i> Erro
                </div>
                <div class="saas-chip" data-status="P">
                    <i class="bi bi-hourglass-split"></i> Pendente
                </div>
            </div>

            <div class="saas-metrics">
                <div class="saas-metric">
                    <div class="label">Total</div>
                    <div class="value" id="mTotal">0</div>
                    <div class="hint">registros retornados</div>
                </div>
                <div class="saas-metric">
                    <div class="label">Sucesso</div>
                    <div class="value" id="mSucesso">0</div>
                    <div class="hint">status S</div>
                </div>
                <div class="saas-metric">
                    <div class="label">Erro</div>
                    <div class="value" id="mErro">0</div>
                    <div class="hint">status E</div>
                </div>
                <div class="saas-metric">
                    <div class="label">Pendente</div>
                    <div class="value" id="mPendente">0</div>
                    <div class="hint">status P</div>
                </div>
            </div>
        </div>

        <div class="card saas-card mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-secondary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-funnel-fill fs-5 text-secondary"></i>
                    </div>
                    <div>
                        <div class="saas-kicker">Filtros</div>
                        <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Pesquisa de Registros</div>
                    </div>
                </div>
                <div class="text-muted small">Refine por tipo, data e status</div>
            </div>

            <div class="card-body saas-form">
                <div class="row g-2 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label">Tipo de Dados</label>
                        <select id="filtroTipo" class="form-select" onchange="mudarModoVisualizacao()">
                            <option value="PADRAO">📊 Cargas e Metas</option>
                            <option value="COMISSAO">💰 Comissões</option>
                            <option value="TABVDAPRODRAIO">🎯 Custo Comercialização</option>

                            <!-- NOVOS TIPOS (para trazer "todos os dados") -->
                            <option value="IMP_BI_METAS">📈 BI Metas (Importação)</option>
                            <option value="BI_METAS_PERSPECT">🧩 BI Metas Perspect</option>

                            <option value="IMP_METAS">🎯 Metas (Importação)</option>
                            <option value="IMP_METAS_FAIXA">🧱 Metas Faixas</option>
                            <option value="IMP_METAS_PERSPEC">🧭 Metas Perspect (Importação)</option>
                            <option value="IMP_METAS_GAP">📉 Metas GAP</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Data Referência</label>
                        <input type="date" id="filtroData" class="form-control">
                    </div>

                    <div class="col-md-2 grupo-metas">
                        <label class="form-label">Setor</label>
                        <input type="number" id="filtroSetor" class="form-control" placeholder="Cód.">
                    </div>

                    <div class="col-md-2 grupo-metas">
                        <label class="form-label">Turno</label>
                        <select id="filtroTurno" class="form-select">
                            <option value="">Todos</option>
                            <option value="DIA">DIA</option>
                            <option value="NOITE">NOITE</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select id="filtroStatus" class="form-select">
                            <option value="">Todos</option>
                            <option value="S">Sucesso</option>
                            <option value="E">Erro</option>
                            <option value="P">Pendente</option>
                        </select>
                    </div>

                    <div class="col-md-2 grupo-raio" style="display:none;">
                        <label class="form-label">Nro Tab. Venda</label>
                        <input type="number" id="filtroNroTabVenda" class="form-control" placeholder="Ex: 1">
                    </div>

                    <div class="col-md-2 grupo-raio" style="display:none;">
                        <label class="form-label">Seq. Produto</label>
                        <input type="number" id="filtroSeqProduto" class="form-control" placeholder="Ex: 401">
                    </div>

                    <div class="col-md-2 grupo-raio" style="display:none;">
                        <label class="form-label">Raio</label>
                        <input type="number" id="filtroRaio" class="form-control" placeholder="Ex: 50">
                    </div>

                    <div class="col-md-1 d-flex gap-2">
                        <button onclick="carregarDados()" class="btn btn-primary w-100 saas-search-btn" title="Pesquisar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="saas-table-wrap">
            <div class="saas-table-scroll">
                <table class="table table-hover mb-0 align-middle table-sm saas-table">
                    <thead id="tabelaHead"></thead>
                    <tbody id="tabelaCorpo"></tbody>
                </table>
            </div>

            <div id="loading" class="text-center p-5 text-muted saas-state">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <p class="mb-0">Buscando dados no Oracle...</p>
            </div>

            <div id="emptyState" class="text-center p-5 text-muted saas-state" style="display:none">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                <div class="fw-bold text-dark mb-1">Nenhum registro encontrado</div>
                <div class="text-muted">Ajuste os filtros e tente novamente.</div>
            </div>
        </div>

    </div>
</main>

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
        if(num === null || num === undefined) return '-';
        return parseFloat(num).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const formatNumber = (num) => {
        if(num === null || num === undefined) return '-';
        return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(num);
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        if (String(dateString).includes('/')) return dateString;
        const partes = String(dateString).split(' ')[0].split('-'); 
        return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : dateString;
    };

    const renderStatus = (status) => {
        if(status === 'S') return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sucesso</span>';
        if(status === 'E') return '<span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Erro</span>';
        if(status === 'P') return '<span class="badge bg-warning text-dark">Pendente</span>';
        return status;
    };

    (function initChips(){
        const chipsWrap = document.getElementById('quickChips');
        if (!chipsWrap) return;

        chipsWrap.addEventListener('click', (e) => {
            const chip = e.target.closest('.saas-chip');
            if (!chip) return;

            const status = chip.getAttribute('data-status') ?? '';
            const filtroStatus = document.getElementById('filtroStatus');
            if (filtroStatus) filtroStatus.value = status;

            chipsWrap.querySelectorAll('.saas-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            carregarDados();
        });
    })();

    function syncChipActive(){
        const chipsWrap = document.getElementById('quickChips');
        const filtroStatus = document.getElementById('filtroStatus');
        if (!chipsWrap || !filtroStatus) return;

        const val = filtroStatus.value || '';
        chipsWrap.querySelectorAll('.saas-chip').forEach(c => {
            c.classList.toggle('active', (c.getAttribute('data-status') ?? '') === val);
        });
    }

    function openDetailModal({ title, meta, body }) {
        const elTitle = document.getElementById('detailModalTitle');
        const elMeta  = document.getElementById('detailModalMeta');
        const elBody  = document.getElementById('detailModalBody');

        if (elTitle) elTitle.textContent = title || 'Detalhe';
        if (elMeta)  elMeta.textContent  = meta || '';
        if (elBody)  elBody.textContent  = body || '-';

        const modalEl = document.getElementById('detailModal');
        if (!modalEl) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    (function initTableDetailClicks(){
        const tbody = document.getElementById('tabelaCorpo');
        if (!tbody) return;

        tbody.addEventListener('click', (e) => {
            const target = e.target.closest('.js-open-detail');
            if (!target) return;

            const title = target.getAttribute('data-title') || 'Detalhe';
            const meta  = target.getAttribute('data-meta') || '';
            const body  = target.getAttribute('data-body') || target.textContent || '-';

            openDetailModal({ title, meta, body });
        });
    })();

    /* ========= NOVO: helpers para renderização dinâmica (trazer todos os dados/colunas) ========= */
    const upper = (s) => String(s ?? '').toUpperCase();

    const isDateKey = (k) => {
        const key = upper(k);
        return key.startsWith('DTA') || key.includes('DATA') || key.startsWith('DT');
    };

    const isMoneyKey = (k) => {
        const key = upper(k);
        return key.startsWith('VLR') || key.includes('VALOR') || key.includes('TOTAL');
    };

    const isLongTextKey = (k) => {
        const key = upper(k);
        return key.includes('MSG_LOG') || key.includes('OBSERVACAO') || key.includes('OBSERVAÇÃO') || key.includes('LOG');
    };

    const normalizeNumber = (v) => {
        if (v === null || v === undefined) return null;
        if (typeof v === 'number') return v;
        const s = String(v).trim();
        if (!s) return null;
        // tenta converter pt-BR: 1.234,56 -> 1234.56
        const t = s.replace(/\./g, '').replace(',', '.');
        if (!isNaN(t)) return Number(t);
        if (!isNaN(s)) return Number(s);
        return null;
    };

    function renderDynamicCell(key, val, row){
        const k = upper(key);

        // status
        if (k === 'STATUS') {
            return renderStatus(val);
        }

        // valores longos: abre modal
        if (isLongTextKey(k)) {
            const safe = escapeHtml(val || '-');
            const label = (val === null || val === undefined || val === '') ? '-' : escapeHtml(String(val));

            const meta = Object.keys(row || {})
                .filter(x => ['ID','STATUS'].includes(upper(x)))
                .map(x => `${x}: ${row[x]}`)
                .join(' | ');

            return `
              <span class="js-open-detail saas-detail-link"
                    data-title="${escapeHtml(key)}"
                    data-meta="${escapeHtml(meta)}"
                    data-body="${safe}">
                  <span class="icon" aria-hidden="true" title="Abrir"><i class="bi bi-box-arrow-up-right"></i></span>
                  <span class="label">${label}</span>
              </span>
            `;
        }

        // vazio
        if (val === null || val === undefined || val === '') return '-';

        // datas
        if (isDateKey(k)) {
            return escapeHtml(formatDate(String(val)));
        }

        // dinheiro
        if (isMoneyKey(k)) {
            const n = normalizeNumber(val);
            if (n !== null) return escapeHtml(formatMoney(n));
        }

        // números gerais
        const n = normalizeNumber(val);
        if (n !== null) {
            if (Number.isInteger(n)) return escapeHtml(String(n));
            return escapeHtml(formatNumber(n));
        }

        // string padrão
        return escapeHtml(String(val));
    }

    function buildDynamicHeader(keys){
        const keysSorted = [...keys].sort((a,b)=>{
            if (upper(a)==='ID') return -1;
            if (upper(b)==='ID') return 1;
            return 0;
        });

        const ths = keysSorted.map((k, idx) => {
            const cls = (idx === 0) ? 'py-3 ps-3' : 'py-3';
            return `<th class="${cls}">${escapeHtml(k)}</th>`;
        }).join('');

        return `<tr>${ths}</tr>`;
    }

    function buildDynamicRow(keys, row){
        const keysSorted = [...keys].sort((a,b)=>{
            if (upper(a)==='ID') return -1;
            if (upper(b)==='ID') return 1;
            return 0;
        });

        const tds = keysSorted.map((k, idx) => {
            const cls = (idx === 0) ? 'ps-3 text-muted small' : '';
            const html = renderDynamicCell(k, row?.[k], row);
            return `<td class="${cls}">${html}</td>`;
        }).join('');

        return tds;
    }

    function mudarModoVisualizacao() {
        const tipo = document.getElementById('filtroTipo').value;

        const camposMetas = document.querySelectorAll('.grupo-metas');
        const camposRaio  = document.querySelectorAll('.grupo-raio');

        // default: esconde tudo específico
        camposMetas.forEach(el => el.style.display = 'none');
        camposRaio.forEach(el => el.style.display = 'none');

        // mostra somente filtros que fazem sentido
        if (tipo === 'PADRAO') {
            camposMetas.forEach(el => el.style.display = 'block');
        } else if (tipo === 'TABVDAPRODRAIO') {
            camposRaio.forEach(el => el.style.display = 'block');
        }

        // agora o cabeçalho é dinâmico (será definido após o fetch)
        document.getElementById('tabelaHead').innerHTML = '';
        document.getElementById('tabelaCorpo').innerHTML = '';

        syncChipActive();
        carregarDados();
    }

    function updateMetrics(dados){
        const total = dados.length;
        let s=0,e=0,p=0;

        dados.forEach(r => {
            if (r.STATUS === 'S') s++;
            else if (r.STATUS === 'E') e++;
            else if (r.STATUS === 'P') p++;
        });

        const mTotal = document.getElementById('mTotal');
        const mS = document.getElementById('mSucesso');
        const mE = document.getElementById('mErro');
        const mP = document.getElementById('mPendente');

        if (mTotal) mTotal.textContent = total;
        if (mS) mS.textContent = s;
        if (mE) mE.textContent = e;
        if (mP) mP.textContent = p;
    }

    async function carregarDados() {
        const tipo = document.getElementById('filtroTipo').value;
        const data = document.getElementById('filtroData').value;
        const setor = document.getElementById('filtroSetor').value;
        const turno = document.getElementById('filtroTurno').value;
        const status = document.getElementById('filtroStatus').value;
        const nroTabVenda = document.getElementById('filtroNroTabVenda')?.value || '';
        const seqProduto  = document.getElementById('filtroSeqProduto')?.value || '';
        const raio        = document.getElementById('filtroRaio')?.value || '';

        const tbody = document.getElementById('tabelaCorpo');
        const thead = document.getElementById('tabelaHead');
        const loading = document.getElementById('loading');
        const empty = document.getElementById('emptyState');

        syncChipActive();

        tbody.innerHTML = '';
        thead.innerHTML = '';
        loading.style.display = 'block';
        empty.style.display = 'none';

        try {
            const params = new URLSearchParams({ tipo, data, setor, turno, status, nroTabVenda, seqProduto, raio });
            const resp = await fetch(`api/api_dados.php?${params.toString()}`);
            if (!resp.ok) {
                const txt = await resp.text(); // ajuda debug (vem HTML/erro)
                throw new Error(`HTTP ${resp.status} - ${txt.substring(0, 200)}`);
            }
            
            const json = await resp.json();

            loading.style.display = 'none';

            if (!json.sucesso) {
                alert('Erro na API: ' + (json.erro || 'Erro desconhecido'));
                updateMetrics([]);
                empty.style.display = 'block';
                return;
            }

            if (!json.dados || json.dados.length === 0) {
                updateMetrics([]);
                empty.style.display = 'block';
                return;
            }

            updateMetrics(json.dados);

            // ===== Render dinâmico: pega a união de chaves de todos os registros =====
            const keySet = new Set();
            json.dados.forEach(r => Object.keys(r || {}).forEach(k => keySet.add(k)));
            const keys = Array.from(keySet);

            // monta o cabeçalho
            thead.innerHTML = buildDynamicHeader(keys);

            // monta o corpo
            json.dados.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = buildDynamicRow(keys, row);
                tbody.appendChild(tr);
            });

        } catch (error) {
            console.error(error);
            loading.style.display = 'none';
            alert('Erro de conexão com o servidor.');
        }
    }

    window.onload = () => {
        mudarModoVisualizacao();
    };
</script>
