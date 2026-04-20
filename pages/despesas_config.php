<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'despesas_config';
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>


<style>
    /* ===== Clean SaaS: Configurações ===== */
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

    /* Tabs modern SaaS */
    .saas-nav-tabs {
        display: flex;
        gap: 1rem;
        border-bottom: 1px solid var(--saas-border);
        margin-top: 2rem;
    }

    .saas-nav-link {
        background: none;
        border: none;
        padding: 10px 16px 14px;
        font-size: 14px;
        font-weight: 700;
        color: var(--saas-muted);
        position: relative;
        cursor: pointer;
        transition: .2s;
    }

    .saas-nav-link:hover {
        color: var(--saas-text);
    }

    .saas-nav-link.active {
        color: #0d6efd;
    }

    .saas-nav-link.active::after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 3px;
        background: #0d6efd;
        border-radius: 3px 3px 0 0;
    }

    /* Card Settings */
    .settings-card {
        background: var(--saas-surface);
        border: 1px solid var(--saas-border);
        border-radius: 18px;
        box-shadow: var(--saas-shadow-soft);
        padding: 1.5rem;
        margin-top: 2rem;
        display: none;
    }

    .settings-card.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form Styles */
    .saas-label {
        font-size: 12px;
        font-weight: 800;
        color: var(--saas-muted);
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
    }

    .saas-input,
    .saas-select {
        width: 100%;
        border-radius: 12px;
        border: 1px solid var(--saas-border);
        background: var(--saas-surface);
        color: var(--saas-text);
        padding: 10px 14px;
        font-size: 14px;
        transition: .2s ease;
    }

    .saas-input:focus,
    .saas-select:focus {
        border-color: #0d6efd;
        outline: none;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
    }

    /* Custom Buttons */
    .btn-primary-custom {
        background: #0d6efd;
        color: #fff;
        border: none;
        border-radius: 14px;
        padding: 12px 28px;
        font-weight: 700;
        font-size: 14px;
        transition: all .2s ease;
        box-shadow: 0 4px 15px rgba(13, 110, 253, .25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
    }

    .btn-primary-custom:hover {
        background: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(13, 110, 253, .35);
        color: #fff;
    }

    .btn-primary-custom:active {
        transform: translateY(0);
    }

    .btn-light {
        background: var(--saas-surface);
        border: 1px solid var(--saas-border);
        color: var(--saas-text);
        border-radius: 14px;
        padding: 12px 28px;
        font-weight: 700;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-light:hover {
        background: rgba(17, 24, 39, .04);
        border-color: var(--saas-muted);
        transform: translateY(-1px);
    }

    /* Tabela Simplificada p/ Configs */
    .cfg-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }

    .cfg-table th {
        padding: 12px 16px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--saas-muted);
        text-align: left;
        border-bottom: 1px solid var(--saas-border);
    }

    .cfg-table td {
        padding: 16px;
        border-bottom: 1px solid var(--saas-border);
        font-size: 14px;
        color: var(--saas-text);
    }

    .cfg-table tr:hover {
        background: rgba(13, 110, 253, .02);
    }

    .wizard-steps {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .wizard-step {
        border: 1px solid var(--saas-border);
        border-radius: 14px;
        padding: 12px 14px;
        background: var(--saas-surface);
        transition: .2s ease;
    }

    .wizard-step.active {
        border-color: rgba(13, 110, 253, .35);
        background: rgba(13, 110, 253, .06);
        box-shadow: 0 10px 24px rgba(13, 110, 253, .10);
    }

    .wizard-step.done {
        border-color: rgba(16, 185, 129, .35);
        background: rgba(16, 185, 129, .06);
    }

    .wizard-step-label {
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--saas-muted);
    }

    .wizard-step-title {
        margin-top: 5px;
        font-size: 14px;
        font-weight: 800;
        color: var(--saas-text);
    }

    .wizard-panel {
        border: 1px solid var(--saas-border);
        border-radius: 18px;
        background: var(--saas-surface);
        padding: 1rem;
    }

    .wizard-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .wizard-summary-card {
        border: 1px solid var(--saas-border);
        border-radius: 14px;
        padding: 12px 14px;
        background: rgba(13, 110, 253, .03);
    }

    .wizard-summary-card span {
        display: block;
        font-size: 11px;
        font-weight: 900;
        color: var(--saas-muted);
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .wizard-summary-card strong {
        display: block;
        margin-top: 6px;
        font-size: 14px;
        color: var(--saas-text);
    }

    .wizard-hint {
        font-size: 13px;
        color: var(--saas-muted);
        margin-bottom: 1rem;
        line-height: 1.55;
    }

    @media (max-width: 768px) {
        .wizard-steps,
        .wizard-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="main-content">
    <div class="container-fluid pb-5">

        <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
            <button class="mobile-toggle me-3" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
            <h4 class="m-0 fw-bold text-dark">CRM Mega G</h4>
        </div>

        <!-- HEADER -->
        <div class="saas-head">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="saas-title">Ajustes do Módulo</h2>
                    <p class="saas-subtitle">Gerencie os cadastros auxiliares de prestação de contas.</p>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="saas-nav-tabs">
            <button class="saas-nav-link active" onclick="switchTab(this, 'tabCategorias')"><i
                    class="bi bi-tags me-1"></i> Categorias</button>
            <button class="saas-nav-link" onclick="switchTab(this, 'tabGrupos')"><i
                    class="bi bi-collection me-1"></i> Grupos</button>
            <button class="saas-nav-link" onclick="switchTab(this, 'tabPoliticas')"><i
                    class="bi bi-shield-check me-1"></i> Políticas</button>
            <button class="saas-nav-link" onclick="switchTab(this, 'tabAprovadores')"><i
                    class="bi bi-people me-1"></i> Aprovadores</button>
            <button class="saas-nav-link" onclick="switchTab(this, 'tabFluxos')"><i
                    class="bi bi-bezier2 me-1"></i> Fluxos</button>
        </div>

        <!-- TAB 1: CATEGORIAS (MEGAG_DESP_TIPO) -->
        <div id="tabCategorias" class="settings-card active">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold m-0"><i class="bi bi-bookmark-fill text-primary me-2"></i> Tipos de Despesa</h5>
                <button class="btn-primary-custom py-2 px-3" onclick="modalItem('Categoria')">Nova Categoria</button>
            </div>

            <table class="cfg-table" id="tableCategorias">
                <thead>
                    <tr>
                        <th style="width:100px;">Código</th>
                        <th>Descrição</th>
                        <th style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- TAB GRUPOS -->
        <div id="tabGrupos" class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold m-0"><i class="bi bi-collection-fill text-primary me-2"></i> Grupos de Alçada</h5>
                <button class="btn-primary-custom py-2 px-3" onclick="modalItem('Grupo')">Novo Grupo</button>
            </div>

            <table class="cfg-table" id="tableGrupos">
                <thead>
                    <tr>
                        <th style="width:100px;">Código</th>
                        <th>Nome do Grupo</th>
                        <th style="width:140px;">Pessoas</th>
                        <th style="width:180px;">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- TAB POLÍTICAS -->
        <div id="tabPoliticas" class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold m-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Políticas por C.C</h5>
                <button class="btn-primary-custom py-2 px-3" onclick="modalItem('Politica')">Nova Política</button>
            </div>

            <table class="cfg-table" id="tablePoliticas">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Centro de Custo</th>
                        <th>Descrição</th>
                        <th>Nível</th>
                        <th style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- TAB 2: APROVADORES / CENTRO DE CUSTO (MEGAG_DESP_APROVADORES) -->
        <div id="tabAprovadores" class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold m-0"><i class="bi bi-diagram-3-fill text-primary me-2"></i> Configurar Aprovadores
                    por C.C</h5>
                <button class="btn-primary-custom py-2 px-3" onclick="modalItem('Aprovador')">Vincular
                    Aprovador</button>
            </div>

            <table class="cfg-table" id="tableAprovadores">
                <thead>
                    <tr>
                        <th>Centro de Custo</th>
                        <th>Aprovador (Gestor)</th>
                        <th>Grupo</th>
                        <th>Data Vinculação</th>
                        <th style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JS load -->
                </tbody>
            </table>
        </div>

        <div id="tabFluxos" class="settings-card">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div>
                    <h5 class="fw-bold m-0"><i class="bi bi-bezier2 text-primary me-2"></i> Fluxos de Aprovação</h5>
                    <div class="small text-muted mt-2">Monte o fluxo completo em uma sequência guiada: grupo, aprovador por centro de custo e política.</div>
                </div>
                <button class="btn-primary-custom py-2 px-3" onclick="openAprovacaoWizard()">Novo Fluxo</button>
            </div>

            <div class="wizard-panel">
                <div class="wizard-hint mb-0">
                    Use esta área quando quiser cadastrar tudo em sequência no mesmo fluxo. As outras abas seguem independentes para manutenção individual.
                </div>
            </div>
        </div>



    </div>
</main>


<!-- Modal Wizard de Fluxo -->
<div class="modal fade" id="modalFluxoAprovacao" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
            <div class="modal-header border-bottom-0 pb-0 mt-3 mx-2">
                <div>
                    <h5 class="modal-title fw-bold m-0">Novo fluxo de aprovação</h5>
                    <div class="text-muted small mt-1">Siga a ordem: Grupo, Aprovador por C.C e Política.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="wizard-steps" id="wizardSteps"></div>
                <div id="wizardBody"></div>
            </div>

            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" id="btnWizardBack" onclick="wizardPrev()">Voltar</button>
                <button type="button" class="btn-primary-custom rounded-pill px-4" id="btnWizardNext" onclick="wizardNext()">Continuar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Genérico de Inserção -->
<div class="modal fade" id="modalConfig" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
            <div class="modal-header border-bottom-0 pb-0 mt-3 mx-2">
                <div>
                    <h5 class="modal-title fw-bold m-0" id="modalConfigTitle">Configuração</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" id="modalConfigBody">
                <!-- Injetado via JS dependendo do botao clicado -->
            </div>

            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-primary-custom rounded-pill px-4" onclick="salvarConfig()"><i
                        class="bi bi-check-circle me-1"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditCentroCusto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
            <div class="modal-header border-bottom-0 pb-0 mt-3 mx-2">
                <div>
                    <h5 class="modal-title fw-bold m-0">Editar Centro de Custo</h5>
                    <div class="text-muted small mt-1" id="editCcSubtitle">Gerencie os aprovadores vinculados a este centro de custo.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="wizard-summary mb-3">
                    <div class="wizard-summary-card">
                        <span>Centro de custo</span>
                        <strong id="editCcCodigo">-</strong>
                    </div>
                    <div class="wizard-summary-card">
                        <span>Grupo</span>
                        <strong id="editCcGrupo">-</strong>
                    </div>
                </div>

                <div class="wizard-panel mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div class="fw-bold">Aprovadores vinculados</div>
                    </div>
                    <div id="editCcAprovadoresList" class="d-flex flex-column gap-2"></div>
                </div>

                <div class="wizard-panel">
                    <div class="fw-bold mb-3">Adicionar novo aprovador</div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label class="saas-label">Aprovador</label>
                            <select class="saas-select" id="editCcNovoAprovador"></select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn-primary-custom w-100 justify-content-center" onclick="adicionarAprovadorCentroCusto()">Adicionar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGrupoPessoas" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
            <div class="modal-header border-bottom-0 pb-0 mt-3 mx-2">
                <div>
                    <h5 class="modal-title fw-bold m-0">Pessoas do Grupo</h5>
                    <div class="text-muted small mt-1" id="grupoPessoasSubtitle">Gerencie as pessoas vinculadas ao grupo selecionado.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="wizard-summary mb-3">
                    <div class="wizard-summary-card">
                        <span>Grupo</span>
                        <strong id="grupoPessoasNome">-</strong>
                    </div>
                    <div class="wizard-summary-card">
                        <span>Código</span>
                        <strong id="grupoPessoasCodigo">-</strong>
                    </div>
                </div>

                <div class="wizard-panel mb-3">
                    <div class="fw-bold mb-3">Pessoas vinculadas</div>
                    <div id="grupoPessoasList" class="d-flex flex-column gap-2"></div>
                </div>

                <div class="wizard-panel">
                    <div class="fw-bold mb-3">Adicionar pessoa ao grupo</div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="saas-label">Pessoa</label>
                            <select class="saas-select" id="grupoPessoaUsuario"></select>
                        </div>
                        <div class="col-md-5">
                            <label class="saas-label">Centro de Custo</label>
                            <select class="saas-select" id="grupoPessoaCc"></select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn-primary-custom px-4" onclick="adicionarPessoaAoGrupo()">Adicionar pessoa</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variável para saber qual form está aberto
    let modalMode = '';
    let editingPolitica = null;
    let wizardStep = 1;
    let wizardState = {};
    let editCentroCustoState = {
        codgrupo: '',
        grupoNome: '',
        centroCusto: ''
    };
    let grupoPessoasState = {
        codgrupo: '',
        nomegrupo: ''
    };

    function resetWizardState() {
        wizardStep = 1;
        wizardState = {
            grupoId: null,
            grupoNome: '',
            centroCusto: '',
            centroCustoLabel: '',
            gestorValor: '',
            gestorNome: '',
            gestorSeq: '',
            politicaDescricao: '',
            politicaNivel: 1
        };
    }

    function wizardStepsMarkup() {
        const steps = [
            { id: 1, label: 'Etapa 1', title: 'Selecionar Grupo' },
            { id: 2, label: 'Etapa 2', title: 'Selecionar Vinculo' },
            { id: 3, label: 'Etapa 3', title: 'Criar Política' }
        ];

        return steps.map(step => `
            <div class="wizard-step ${wizardStep === step.id ? 'active' : ''} ${wizardStep > step.id ? 'done' : ''}">
                <div class="wizard-step-label">${step.label}</div>
                <div class="wizard-step-title">${step.title}</div>
            </div>
        `).join('');
    }

    async function carregarGruposWizard(selectId, selectedValue = '') {
        await carregarDomDinamico(null, selectId, null);
        const selectEl = document.getElementById(selectId);
        if (!selectEl?.tomselect) return;
        if (selectedValue) {
            selectEl.tomselect.setValue(String(selectedValue), true);
        }
    }

    async function carregarAprovadoresVinculadosSelect(codgrupo, centroCusto, selectId, selectedValue = '') {
        const selectEl = document.getElementById(selectId);
        if (!selectEl) return [];

        if (selectEl.tomselect) {
            selectEl.tomselect.destroy();
        }

        selectEl.innerHTML = '<option value="">Selecione...</option>';

        if (!centroCusto) {
            new TomSelect('#' + selectId, { create: false, placeholder: 'Selecione um aprovador já vinculado...' });
            return [];
        }

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'list_aprovadores_vinculados',
                codgrupo,
                centro_custo: centroCusto
            })
        });
        const json = await res.json();
        const aprovadores = json.sucesso ? (json.dados || []) : [];

        aprovadores.forEach(item => {
            const seq = item.SEQUSUARIO || item.sequsuario;
            const nome = item.NOME || item.nome || '';
            selectEl.innerHTML += `<option value="${seq}|${nome}">${nome}</option>`;
        });

        new TomSelect('#' + selectId, { create: false, placeholder: 'Selecione um aprovador já vinculado...' });

        if (selectedValue && selectEl.tomselect) {
            selectEl.tomselect.setValue(selectedValue, true);
        }

        return aprovadores;
    }

    async function carregarBaseAprovadores() {
        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list_aprovadores' })
        });
        const json = await res.json();
        window._aprovadoresConfigData = json.sucesso ? (json.dados || []) : [];
        return window._aprovadoresConfigData;
    }

    async function abrirEdicaoCentroCusto(codgrupo, centroCusto, grupoNome) {
        editCentroCustoState = {
            codgrupo: codgrupo || '',
            grupoNome: grupoNome || '-',
            centroCusto: centroCusto || ''
        };

        document.getElementById('editCcCodigo').textContent = centroCusto || '-';
        document.getElementById('editCcGrupo').textContent = grupoNome || '-';
        document.getElementById('editCcSubtitle').textContent = `Gerencie os aprovadores vinculados ao centro ${centroCusto || '-'} neste grupo.`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditCentroCusto')).show();
        await carregarListaEdicaoCentroCusto();
        await carregarDomDinamico(null, null, 'editCcNovoAprovador');
    }

    async function carregarListaEdicaoCentroCusto() {
        const listEl = document.getElementById('editCcAprovadoresList');
        listEl.innerHTML = '<div class="text-muted small">Carregando aprovadores...</div>';

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'list_aprovadores_vinculados',
                codgrupo: editCentroCustoState.codgrupo,
                centro_custo: editCentroCustoState.centroCusto
            })
        });
        const json = await res.json();
        const aprovadores = json.sucesso ? (json.dados || []) : [];

        if (!aprovadores.length) {
            listEl.innerHTML = '<div class="text-muted small">Nenhum aprovador vinculado a este centro de custo.</div>';
            return;
        }

        listEl.innerHTML = aprovadores.map(item => {
            const seq = item.SEQUSUARIO || item.sequsuario || '';
            const nome = item.NOME || item.nome || '-';
            return `
                <div class="d-flex align-items-center justify-content-between gap-3 border rounded-4 px-3 py-2">
                    <div>
                        <div class="fw-bold">${nome}</div>
                        <div class="small text-muted">Seq. usuário: ${seq}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-3" onclick="removerAprovadorCentroCusto('${seq}', '${String(nome).replace(/'/g, "\\'")}')">Remover</button>
                </div>
            `;
        }).join('');
    }

    async function adicionarAprovadorCentroCusto() {
        const selectEl = document.getElementById('editCcNovoAprovador');
        const gestor = selectEl?.tomselect ? selectEl.tomselect.getValue() : selectEl?.value;
        if (!gestor) {
            alert('Selecione um aprovador para vincular.');
            return;
        }

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_aprovador',
                centro_custo: editCentroCustoState.centroCusto,
                gestor,
                codgrupo: editCentroCustoState.codgrupo
            })
        });
        const json = await res.json();
        if (!json.sucesso) {
            alert(json.erro || 'Erro ao adicionar aprovador.');
            return;
        }

        await carregarListaEdicaoCentroCusto();
        await carregarTabelas();
        if (selectEl?.tomselect) {
            selectEl.tomselect.clear(true);
        }
    }

    async function removerAprovadorCentroCusto(sequsuario, nome) {
        if (!confirm(`Remover ${nome} deste centro de custo?`)) return;

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'del_aprovador_vinculo',
                codgrupo: editCentroCustoState.codgrupo,
                centro_custo: editCentroCustoState.centroCusto,
                sequsuario
            })
        });
        const json = await res.json();
        if (!json.sucesso) {
            alert(json.erro || 'Erro ao remover aprovador.');
            return;
        }

        await carregarListaEdicaoCentroCusto();
        await carregarTabelas();
    }

    async function excluirCentroCustoVinculado(codgrupo, centroCusto, grupoNome) {
        const grupoLabel = (grupoNome && grupoNome !== '-') ? ` do grupo ${grupoNome}` : '';
        if (!confirm(`Excluir o centro de custo ${centroCusto} do grupo ${grupoNome}? Isso removerá todos os aprovadores vinculados nele.`)) {
            return;
        }

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'del_centro_custo_vinculo',
                codgrupo,
                centro_custo: centroCusto
            })
        });
        const json = await res.json();
        if (!json.sucesso) {
            alert(json.erro || 'Erro ao excluir centro de custo.');
            return;
        }

        await carregarTabelas();
    }

    async function abrirGrupoPessoas(codgrupo, nomegrupo) {
        grupoPessoasState = {
            codgrupo: codgrupo || '',
            nomegrupo: nomegrupo || '-'
        };

        document.getElementById('grupoPessoasNome').textContent = nomegrupo || '-';
        document.getElementById('grupoPessoasCodigo').textContent = codgrupo || '-';
        document.getElementById('grupoPessoasSubtitle').textContent = `Gerencie as pessoas vinculadas ao grupo ${nomegrupo || '-'}.`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGrupoPessoas')).show();
        await carregarDomDinamico('grupoPessoaCc', null, 'grupoPessoaUsuario');
        await carregarListaPessoasGrupo();
    }

    async function carregarListaPessoasGrupo() {
        const listEl = document.getElementById('grupoPessoasList');
        if (!listEl) return;

        listEl.innerHTML = '<div class="text-muted small">Carregando pessoas do grupo...</div>';
        const rows = await carregarBaseAprovadores();
        const grupoRows = rows.filter(item => String(item.CODGRUPO || item.codgrupo || '') === String(grupoPessoasState.codgrupo || ''));

        if (!grupoRows.length) {
            listEl.innerHTML = '<div class="text-muted small">Nenhuma pessoa vinculada a este grupo.</div>';
            return;
        }

        listEl.innerHTML = grupoRows.map(item => {
            const nome = item.GESTOR || item.gestor || '-';
            const seq = item.SEQUSUARIO || item.sequsuario || '';
            const centro = item.CENTROCUSTO || item.centrocusto || '-';
            return `
                <div class="d-flex align-items-center justify-content-between gap-3 border rounded-4 px-3 py-2">
                    <div>
                        <div class="fw-bold">${nome}</div>
                        <div class="small text-muted">Centro de custo: ${centro} | Seq. usuário: ${seq}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-3" onclick="removerPessoaDoGrupo('${seq}', '${centro}', '${String(nome).replace(/'/g, "\\'")}')">Remover</button>
                </div>
            `;
        }).join('');
    }

    async function adicionarPessoaAoGrupo() {
        const usuarioEl = document.getElementById('grupoPessoaUsuario');
        const ccEl = document.getElementById('grupoPessoaCc');
        const gestor = usuarioEl?.tomselect ? usuarioEl.tomselect.getValue() : usuarioEl?.value;
        const centro_custo = ccEl?.tomselect ? ccEl.tomselect.getValue() : ccEl?.value;

        if (!grupoPessoasState.codgrupo || !gestor || !centro_custo) {
            alert('Selecione a pessoa e o centro de custo para este grupo.');
            return;
        }

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_aprovador',
                codgrupo: grupoPessoasState.codgrupo,
                gestor,
                centro_custo
            })
        });
        const json = await res.json();
        if (!json.sucesso) {
            alert(json.erro || 'Erro ao adicionar pessoa ao grupo.');
            return;
        }

        await carregarListaPessoasGrupo();
        await carregarTabelas();
        usuarioEl?.tomselect?.clear(true);
        ccEl?.tomselect?.clear(true);
    }

    async function removerPessoaDoGrupo(sequsuario, centroCusto, nome) {
        if (!confirm(`Remover ${nome} do grupo ${grupoPessoasState.nomegrupo}?`)) return;

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'del_aprovador_vinculo',
                codgrupo: grupoPessoasState.codgrupo,
                centro_custo: centroCusto,
                sequsuario
            })
        });
        const json = await res.json();
        if (!json.sucesso) {
            alert(json.erro || 'Erro ao remover pessoa do grupo.');
            return;
        }

        await carregarListaPessoasGrupo();
        await carregarTabelas();
    }

    async function openAprovacaoWizard() {
        resetWizardState();
        const wizardModalEl = document.getElementById('modalFluxoAprovacao');
        bootstrap.Modal.getOrCreateInstance(wizardModalEl).show();
        await renderWizardStep();
    }

    async function renderWizardStep() {
        document.getElementById('wizardSteps').innerHTML = wizardStepsMarkup();
        const body = document.getElementById('wizardBody');
        const btnBack = document.getElementById('btnWizardBack');
        const btnNext = document.getElementById('btnWizardNext');

        btnBack.style.display = wizardStep === 1 ? 'none' : 'inline-flex';
        btnNext.innerHTML = wizardStep === 3 ? '<i class="bi bi-check-circle me-1"></i> Finalizar' : 'Continuar';

        if (wizardStep === 1) {
            body.innerHTML = `
                <div class="wizard-panel">
                    <div class="wizard-hint">Primeiro criamos o grupo que vai sustentar a alçada deste fluxo.</div>
                    <label class="saas-label">Grupo</label>
                    <select class="saas-select" id="wizGrupo"></select>
                </div>
            `;
            await carregarGruposWizard('wizGrupo', wizardState.grupoId);
            return;
        }

        if (wizardStep === 2) {
            body.innerHTML = `
                <div class="wizard-panel">
                    <div class="wizard-hint">Agora escolha um aprovador já cadastrado neste centro de custo e depois selecione o centro correspondente.</div>
                    <div class="wizard-summary mb-3">
                        <div class="wizard-summary-card">
                            <span>Grupo selecionado</span>
                            <strong>${wizardState.grupoNome || '-'}</strong>
                        </div>
                        <div class="wizard-summary-card">
                            <span>Código do grupo</span>
                            <strong>${wizardState.grupoId || '-'}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="saas-label">Aprovador</label>
                        <select class="saas-select" id="wizGestor"></select>
                    </div>
                    <div class="mb-0">
                        <label class="saas-label">Centro de custo vinculado</label>
                        <select class="saas-select" id="wizCc"></select>
                    </div>
                </div>
            `;
            const baseAprovadores = await carregarBaseAprovadores();
            const grupoRows = baseAprovadores.filter(item => String(item.CODGRUPO || item.codgrupo || '') === String(wizardState.grupoId || ''));
            const gestoresUnicos = Object.values(grupoRows.reduce((acc, item) => {
                const key = String(item.SEQUSUARIO || item.sequsuario || '');
                if (!acc[key]) {
                    acc[key] = {
                        value: `${item.SEQUSUARIO || item.sequsuario}|${item.GESTOR || item.gestor || ''}`,
                        label: item.GESTOR || item.gestor || ''
                    };
                }
                return acc;
            }, {}));

            const gestorEl = document.getElementById('wizGestor');
            gestorEl.innerHTML = '<option value="">Selecione...</option>' + gestoresUnicos.map(item => `<option value="${item.value}">${item.label}</option>`).join('');
            new TomSelect('#wizGestor', { create: false, placeholder: 'Selecione um aprovador...' });

            const syncWizardCentros = () => {
                const gestorValue = document.getElementById('wizGestor')?.tomselect ? document.getElementById('wizGestor').tomselect.getValue() : document.getElementById('wizGestor')?.value;
                const gestorSeq = String(gestorValue || '').split('|')[0];
                const centros = grupoRows.filter(item => String(item.SEQUSUARIO || item.sequsuario || '') === gestorSeq);
                const ccEl = document.getElementById('wizCc');
                if (ccEl.tomselect) {
                    ccEl.tomselect.destroy();
                }
                ccEl.innerHTML = '<option value="">Selecione...</option>' + centros.map(item => {
                    const cod = item.CENTROCUSTO || item.centrocusto || '';
                    return `<option value="${cod}">${cod}</option>`;
                }).join('');
                new TomSelect('#wizCc', { create: false, placeholder: 'Selecione um centro de custo...' });

                if (wizardState.centroCusto && document.getElementById('wizCc')?.tomselect) {
                    document.getElementById('wizCc').tomselect.setValue(wizardState.centroCusto, true);
                }
            };

            if (wizardState.gestorValor && document.getElementById('wizGestor')?.tomselect) {
                document.getElementById('wizGestor').tomselect.setValue(wizardState.gestorValor, true);
            }
            document.getElementById('wizGestor')?.tomselect?.on('change', syncWizardCentros);
            syncWizardCentros();
            return;
        }

        body.innerHTML = `
            <div class="wizard-panel">
                <div class="wizard-hint">Por fim, criamos a política já apontando para o aprovador vinculado ao centro de custo.</div>
                <div class="wizard-summary mb-3">
                    <div class="wizard-summary-card">
                        <span>Grupo</span>
                        <strong>${wizardState.grupoNome || '-'}</strong>
                    </div>
                    <div class="wizard-summary-card">
                        <span>Centro de custo</span>
                        <strong>${wizardState.centroCustoLabel || wizardState.centroCusto || '-'}</strong>
                    </div>
                    <div class="wizard-summary-card">
                        <span>Aprovador</span>
                        <strong>${wizardState.gestorNome || '-'}</strong>
                    </div>
                    <div class="wizard-summary-card">
                        <span>Código do grupo</span>
                        <strong>${wizardState.grupoId || '-'}</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="saas-label">Descrição da política</label>
                    <input type="text" class="saas-input" id="wizPoliticaDesc" placeholder="Ex: Aprovação nível 1" value="${wizardState.politicaDescricao || ''}">
                </div>
                <div class="mb-0">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <label class="saas-label mb-0">Aprovadores desta política</label>
                        <button type="button" class="btn btn-light border rounded-pill px-3 py-2" id="btnAddWizardAprovador">Adicionar aprovador</button>
                    </div>
                    <div id="wizPoliticaAprovadores"></div>
                    <div class="small text-muted mt-2">Você pode incluir mais de um aprovador, definindo o nível de cada um.</div>
                </div>
            </div>
        `;
        const initialRows = [{
            sequsuario: wizardState.gestorSeq || '',
            nivel: wizardState.politicaNivel || 1
        }];
        const options = await carregarAprovadoresPolitica(wizardState.grupoId, wizardState.centroCusto, 'wizPoliticaAprovadores', initialRows);
        document.getElementById('btnAddWizardAprovador').onclick = () => appendPoliticaAprovadorRow('wizPoliticaAprovadores', options);
    }

    function wizardPrev() {
        if (wizardStep <= 1) return;
        wizardStep--;
        renderWizardStep();
    }

    async function wizardNext() {
        if (wizardStep === 1) {
            const grupoEl = document.getElementById('wizGrupo');
            const grupoId = grupoEl?.tomselect ? grupoEl.tomselect.getValue() : grupoEl?.value;
            const grupoNome = grupoEl?.tomselect?.getItem(grupoId)?.textContent
                || grupoEl?.options?.[grupoEl.selectedIndex]?.text
                || '';
            if (!grupoId) return alert('Selecione um grupo já cadastrado.');

            wizardState.grupoId = grupoId;
            wizardState.grupoNome = grupoNome;
            wizardStep = 2;
            await renderWizardStep();
            return;
        }

        if (wizardStep === 2) {
            const ccEl = document.getElementById('wizCc');
            const gestorEl = document.getElementById('wizGestor');
            const centroCusto = ccEl?.tomselect ? ccEl.tomselect.getValue() : ccEl?.value;
            const gestor = gestorEl?.tomselect ? gestorEl.tomselect.getValue() : gestorEl?.value;

            if (!centroCusto || !gestor || !wizardState.grupoId) {
                return alert('Selecione o centro de custo e o aprovador.');
            }

            const gestorParts = String(gestor).split('|');
            const gestorSeq = gestorParts[0] || '';
            const gestorNome = gestorParts[1] || '';
            const ccSelectedText = ccEl?.tomselect?.getItem(centroCusto)?.textContent
                || ccEl?.options?.[ccEl.selectedIndex]?.text
                || centroCusto;
            const gestorSelectedText = gestorEl?.tomselect?.getItem(gestor)?.textContent
                || gestorEl?.options?.[gestorEl.selectedIndex]?.text
                || gestorNome
                || gestor;

            wizardState.centroCusto = centroCusto;
            wizardState.centroCustoLabel = ccSelectedText;
            wizardState.gestorValor = gestor;
            wizardState.gestorSeq = gestorSeq;
            wizardState.gestorNome = gestorSelectedText;
            wizardStep = 3;
            await renderWizardStep();
            return;
        }

        const descricao = (document.getElementById('wizPoliticaDesc')?.value || '').trim();
        const aprovadores = collectPoliticaAprovadores('wizPoliticaAprovadores');
        if (!aprovadores.length) return alert('Adicione ao menos um aprovador para a política.');
        if (!descricao) return alert('Informe a descrição da política.');

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_politica_lote',
                codgrupo: wizardState.grupoId,
                centro_custo: wizardState.centroCusto,
                descricao,
                aprovadores
            })
        });
        const json = await res.json();
        if (!json.sucesso) return alert(json.erro || 'Erro ao criar política.');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFluxoAprovacao')).hide();
        carregarTabelas();
        document.querySelectorAll('.saas-nav-link').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.settings-card').forEach(el => el.classList.remove('active'));
        const fluxoTabBtn = Array.from(document.querySelectorAll('.saas-nav-link')).find(el => el.textContent.includes('Fluxos'));
        if (fluxoTabBtn) fluxoTabBtn.classList.add('active');
        document.getElementById('tabFluxos').classList.add('active');
    }

    function switchTab(btn, tabId) {
        document.querySelectorAll('.saas-nav-link').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.settings-card').forEach(el => el.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    function modalItem(tipo) {
        modalMode = tipo;
        editingPolitica = null;
        const title = document.getElementById('modalConfigTitle');
        const body = document.getElementById('modalConfigBody');
        if (tipo === 'Categoria') {
            title.innerHTML = 'Criar Categoria';
            body.innerHTML = `
            <div class="mb-3">
                <label class="saas-label">Nome da Categoria</label>
                <input type="text" class="saas-input" placeholder="Ex: Combustível, Alimentação..." id="fDescCateg">
            </div>`;
            new bootstrap.Modal('#modalConfig').show();
        } else if (tipo === 'Grupo') {
            title.innerHTML = 'Criar Grupo de Alçada';
            body.innerHTML = `
            <div class="mb-3">
                <label class="saas-label">Nome do Grupo</label>
                <input type="text" class="saas-input" placeholder="Ex: Diretoria, Vendas, TI..." id="fNomeGrupo">
            </div>`;
            new bootstrap.Modal('#modalConfig').show();
        } else if (tipo === 'Politica') {
            title.innerHTML = 'Nova Política';
            body.innerHTML = `
            <div class="mb-4">
                <label class="saas-label">Descrição da Regra (Nome da Política)</label>
                <input type="text" class="saas-input" id="fPolDesc" placeholder="Ex: Política de Despesas TI">
            </div>
            
            <div class="mb-3 border-top pt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-layers-fill text-primary me-2"></i> Níveis de Aprovação</h6>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-2" onclick="appendPoliticaNivelRow('fPolNiveisContainer')">
                        <i class="bi bi-plus-circle me-1"></i> Adicionar Nível
                    </button>
                </div>
                
                <div id="fPolNiveisContainer"></div>
                <div class="small text-muted mt-2">Selecione o grupo e os aprovadores. O centro de custo será identificado automaticamente pelo vínculo de cada aprovador.</div>
            </div>`;

            new bootstrap.Modal('#modalConfig').show();
            renderPoliticaNiveisContainer('fPolNiveisContainer');
        } else if (tipo === 'Aprovador') {
            title.innerHTML = 'Vincular Aprovador';
            body.innerHTML = `
            <div class="mb-3">
                <label class="saas-label">Centro de Custo</label>
                <select class="saas-select" id="fCcAprov"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Usuário Gestor</label>
                <select class="saas-select" id="fGestorAprov"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Grupo (Opcional)</label>
                <select class="saas-select" id="fGrupoAprov"></select>
            </div>`;
            new bootstrap.Modal('#modalConfig').show();
            carregarDomDinamico('fCcAprov', 'fGrupoAprov', 'fGestorAprov');
        }
    }

    async function abrirEdicaoPolitica(encodedRow) {
        let row = null;
        try {
            row = JSON.parse(decodeURIComponent(encodedRow));
        } catch (e) {
            alert('Nao foi possivel carregar a politica para edicao.');
            return;
        }

        editingPolitica = row;
        modalMode = 'PoliticaEdit';

        const title = document.getElementById('modalConfigTitle');
        const body = document.getElementById('modalConfigBody');
        title.innerHTML = 'Editar Política';
        body.innerHTML = `
            <div class="mb-3">
                <label class="saas-label">Grupo</label>
                <select class="saas-select" id="fPolGrupo"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Centro de Custo</label>
                <select class="saas-select" id="fPolCc"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Aprovador</label>
                <div id="fPolAprovadores"></div>
            </div>
            <div class="mb-3">
                <label class="saas-label">Descrição da Regra</label>
                <input type="text" class="saas-input" id="fPolDesc" placeholder="Ex: Aprovação Nível 1">
            </div>`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfig')).show();
        await carregarDomDinamico('fPolCc', 'fPolGrupo');

        const grupoValue = String(row.CODGRUPO || row.codgrupo || '');
        const ccCodigo = row.CODIGO_CC || row.CENTROCUSTO || '';
        const ccValue = String(ccCodigo || '');
        const aprovadorValue = String(row.SEQUSUARIO || row.sequsuario || '');
        const nivelValue = Number(row.NIVEL_APROVACAO || row.nivel_aprovacao || 1);

        if (document.getElementById('fPolGrupo')?.tomselect && grupoValue) {
            document.getElementById('fPolGrupo').tomselect.setValue(grupoValue, true);
        }
        if (document.getElementById('fPolCc')?.tomselect && ccValue) {
            document.getElementById('fPolCc').tomselect.setValue(ccValue, true);
        }
        document.getElementById('fPolDesc').value = row.DESCRICAO || row.descricao || '';

        const syncPoliticaEdit = async (selectedSeq = '', selectedNivel = 1) => {
            const grupo = document.getElementById('fPolGrupo')?.value || '';
            const cc = document.getElementById('fPolCc')?.value || '';
            await carregarAprovadoresPolitica(grupo, cc, 'fPolAprovadores', [{
                sequsuario: selectedSeq,
                nivel: selectedNivel
            }]);

            const removeBtn = document.querySelector('#fPolAprovadores .politica-row-remove');
            if (removeBtn) {
                removeBtn.disabled = true;
            }
        };

        document.getElementById('fPolGrupo')?.tomselect?.on('change', () => syncPoliticaEdit('', 1));
        document.getElementById('fPolCc')?.tomselect?.on('change', () => syncPoliticaEdit('', 1));

        await syncPoliticaEdit(aprovadorValue, nivelValue);
    }

    async function carregarDomDinamico(idCc, idGrupo, idUsu = null) {
         try {
             let res = await fetch('api/api_despesas_config.php', {
                 method: 'POST', 
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify({action: 'get_doms_aprovador'})
             });
             let json = await res.json();
             console.log("DOMS carregados na Config:", json);

             if(json.sucesso){
                 // Centro de Custo
                 if(idCc) {
                    let selCc = document.getElementById(idCc);
                    if (selCc.tomselect) selCc.tomselect.destroy();
                    selCc.innerHTML = '<option value="">Selecione...</option>';
                    json.dados.ccs?.forEach(c => {
                        let cfCod = c.CENTROCUSTO || c.centrocusto;
                        let cfNome = (c.NOME || c.nome || c.DESCRICAO || c.descricao || '').trim();
                        selCc.innerHTML += `<option value="${cfCod}">${cfCod} | ${cfNome}</option>`;
                    });
                    new TomSelect('#' + idCc, { create: false, placeholder: 'Pesquise o centro de custo...' });
                 }

                 // Grupos
                 if(idGrupo) {
                    let selG = document.getElementById(idGrupo);
                    if (selG.tomselect) selG.tomselect.destroy();
                    selG.innerHTML = '<option value="">Selecione...</option>';
                    if (json.dados.groups) {
                        json.dados.groups.forEach(g => {
                            let gId = g.CODGRUPO || g.codgrupo;
                            let gNome = g.NOMEGRUPO || g.nomegrupo;
                            selG.innerHTML += `<option value="${gId}">${gNome}</option>`;
                        });
                    }
                    if (json.dados.grupos) {
                        json.dados.grupos.forEach(g => {
                            let gId = g.CODGRUPO || g.codgrupo;
                            let gNome = g.NOMEGRUPO || g.nomegrupo;
                            selG.innerHTML += `<option value="${gId}">${gNome}</option>`;
                        });
                    }
                    new TomSelect('#' + idGrupo, { create: false, placeholder: 'Pesquise o grupo...' });
                 }

                 // Usuários
                 if(idUsu) {
                    let selU = document.getElementById(idUsu);
                    if (selU.tomselect) selU.tomselect.destroy();
                    selU.innerHTML = '<option value="">Selecione...</option>';
                    json.dados.usuarios?.forEach(u => {
                        let uId = u.SEQUSUARIO || u.sequsuario;
                        let uNome = (u.NOME || u.nome || '').trim();
                        selU.innerHTML += `<option value="${uId}|${uNome}">${uNome}</option>`;
                    });
                    new TomSelect('#' + idUsu, { create: false, placeholder: 'Pesquise o gestor...' });
                 }
             }
         } catch(e) { console.error("Erro ao carregar domínios dinâmicos", e); }
    }

    function renderPoliticaNiveisContainer(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        appendPoliticaNivelRow(containerId);
    }

    async function appendPoliticaNivelRow(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const idx = container.querySelectorAll('.politica-nivel-row').length + 1;
        const rowId = 'polNivel_' + Date.now() + '_' + idx;

        const rowHtml = `
            <div class="card p-3 mb-3 border-dashed politica-nivel-row" id="${rowId}" style="border: 1px dashed var(--saas-border); border-radius: 16px; background: rgba(13, 110, 253, .01);">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="saas-label">Nível</label>
                        <input type="number" class="saas-input pol-nivel-num" value="${idx}" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="saas-label">Grupo</label>
                        <select class="saas-select pol-nivel-grupo" id="${rowId}_grupo"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="saas-label">Aprovadores</label>
                        <select class="saas-select pol-nivel-aprovadores" id="${rowId}_aprovs" multiple></select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-light border text-danger w-100 rounded-pill py-2" onclick="this.closest('.politica-nivel-row').remove()">Excluir</button>
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', rowHtml);

        // Carregar Grupos para este nível
        await carregarDomDinamico(null, `${rowId}_grupo`, null);

        const selGrupo = document.getElementById(`${rowId}_grupo`);
        const selAprovs = document.getElementById(`${rowId}_aprovs`);
        
        // Inicializa TomSelect para aprovadores (múltiplo)
        const tsAprovs = new TomSelect(`#${rowId}_aprovs`, {
            plugins: ['remove_button'],
            create: false,
            placeholder: 'Selecione aprovadores...'
        });

        const syncAprovs = async () => {
            const grupo = selGrupo?.value || '';
            
            tsAprovs.clear();
            tsAprovs.clearOptions();

            if (grupo) {
                const res = await fetch('api/api_despesas_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'list_aprovadores_vinculados',
                        codgrupo: grupo
                    })
                });
                const json = await res.json();
                if (json.sucesso) {
                    (json.dados || []).forEach(item => {
                        tsAprovs.addOption({
                            value: `${item.SEQUSUARIO || item.sequsuario}|${item.CENTROCUSTO || item.centrocusto}`,
                            text: `${item.NOME || item.nome} (${item.CENTROCUSTO || '-'})`
                        });
                    });
                }
            }
        };

        if (selGrupo?.tomselect) {
            selGrupo.tomselect.on('change', syncAprovs);
        }
    }

    function collectPoliticaNiveis() {
        const container = document.getElementById('fPolNiveisContainer');
        if (!container) return [];

        const niveis = [];
        container.querySelectorAll('.politica-nivel-row').forEach(row => {
            const nivel = row.querySelector('.pol-nivel-num')?.value || 1;
            const grupo = row.querySelector('.pol-nivel-grupo')?.value || '';
            
            const selAprovs = row.querySelector('.pol-nivel-aprovadores');
            const aprovadoresRaw = selAprovs?.tomselect ? selAprovs.tomselect.getValue() : [];
            const aprovadores = (Array.isArray(aprovadoresRaw) ? aprovadoresRaw : [aprovadoresRaw])
                .filter(Boolean)
                .map(v => {
                    const parts = String(v).split('|');
                    return {
                        sequsuario: parts[0],
                        centro_custo: parts[1],
                        nivel: parseInt(nivel)
                    };
                });

            if (grupo && aprovadores.length) {
                niveis.push({
                    nivel: parseInt(nivel),
                    grupo: parseInt(grupo),
                    aprovadores: aprovadores
                });
            }
        });
        return niveis;
    }

    async function collectPoliticaAprovadores(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return [];
        const rows = [];
        container.querySelectorAll('.politica-row').forEach(row => {
             const sel = row.querySelector('.pol-aprov-select');
             const niv = row.querySelector('.pol-aprov-nivel');
             const val = sel?.tomselect ? sel.tomselect.getValue() : sel?.value;
             if (val) {
                 rows.push({
                     sequsuario: String(val).split('|')[0],
                     nivel: parseInt(niv?.value || 1)
                 });
             }
        });
        return rows;
    }

    async function carregarAprovadoresPolitica(grupo, cc, containerId, rows = []) {
        const container = document.getElementById(containerId);
        if (!container) return [];
        container.innerHTML = '';

        if (!cc) return [];

        const res = await fetch('api/api_despesas_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'list_aprovadores_vinculados',
                codgrupo: grupo || '',
                centro_custo: cc
            })
        });
        const json = await res.json();
        const options = json.sucesso ? (json.dados || []) : [];

        if (rows.length) {
            rows.forEach(r => appendPoliticaAprovadorRow(containerId, options, r));
        } else {
            appendPoliticaAprovadorRow(containerId, options);
        }
        return options;
    }

    function appendPoliticaAprovadorRow(containerId, options, data = { sequsuario: '', nivel: 1 }) {
        const container = document.getElementById(containerId);
        const rowId = 'row_' + Date.now() + Math.random().toString(36).substr(2, 5);
        const html = `
            <div class="row g-2 mb-2 politica-row" id="${rowId}">
                <div class="col-8">
                    <select class="saas-select pol-aprov-select"></select>
                </div>
                <div class="col-2">
                    <input type="number" class="saas-input pol-aprov-nivel" value="${data.nivel}" min="1" title="Nível">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-light border text-danger w-100 rounded-pill py-2 politica-row-remove" onclick="document.getElementById('${rowId}').remove()"><i class="bi bi-trash"></i></button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const sel = container.querySelector(`#${rowId} .pol-aprov-select`);
        sel.innerHTML = '<option value="">Selecione...</option>' + options.map(o => `<option value="${o.SEQUSUARIO || o.sequsuario}">${o.NOME || o.nome}</option>`).join('');
        new TomSelect(sel, { create: false, placeholder: 'Aprovador...' });

        if (data.sequsuario && sel.tomselect) {
            sel.tomselect.setValue(data.sequsuario, true);
        }
    }

    async function carregarTabelas() {
        // Categorias
        try {
            let r = await fetch('api/api_despesas_config.php', { method: 'POST', body: JSON.stringify({action: 'list_tipos'}) });
            let j = await r.json();
            if (j.sucesso) {
                let tb = document.querySelector('#tableCategorias tbody');
                tb.innerHTML = j.dados?.map(c => `<tr>
                    <td class="text-muted fw-bold">${c.CODTIPODESPESA}</td>
                    <td class="fw-bold">${c.DESCRICAO}</td>
                    <td><button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_tipo', ${c.CODTIPODESPESA})"><i class="bi bi-trash"></i></button></td>
                </tr>`).join('') || '';
            }
        } catch(e) {}

        // Grupos
        try {
            const [r, aprovadoresRows] = await Promise.all([
                fetch('api/api_despesas_config.php', { method: 'POST', body: JSON.stringify({action: 'list_grupos'}) }),
                carregarBaseAprovadores()
            ]);
            let j = await r.json();
            if (j.sucesso) {
                let tb = document.querySelector('#tableGrupos tbody');
                tb.innerHTML = j.dados?.map(g => `<tr>
                    <td class="text-muted fw-bold">${g.CODGRUPO}</td>
                    <td class="fw-bold">${g.NOMEGRUPO}</td>
                    <td>
                        <span class="badge bg-light text-dark">
                            ${new Set(aprovadoresRows.filter(a => String(a.CODGRUPO || a.codgrupo || '') === String(g.CODGRUPO)).map(a => String(a.SEQUSUARIO || a.sequsuario || ''))).size} pessoa(s)
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="abrirGrupoPessoas('${g.CODGRUPO}', '${String(g.NOMEGRUPO).replace(/'/g, "\\'")}')">Pessoas</button>
                            <button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_grupo', ${g.CODGRUPO})"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>`).join('') || '';
            }
        } catch(e) {}

        // Políticas
        try {
            let r = await fetch('api/api_despesas_config.php', { method: 'POST', body: JSON.stringify({action: 'list_politicas'}) });
            let j = await r.json();
            if (j.sucesso) {
                let tb = document.querySelector('#tablePoliticas tbody');
                tb.innerHTML = j.dados?.map(p => `<tr>
                    <td class="text-muted small">${p.NOMEGRUPO || 'N/A'}</td>
                    <td><div class="fw-bold">${p.CODIGO_CC || p.CENTROCUSTO}</div><div class="small text-muted">${p.NOME_CC}</div></td>
                    <td><div>${p.DESCRICAO}</div><div class="small text-muted">${p.NOME_USUARIO || 'Usuário não identificado'}</div></td>
                    <td><span class="badge bg-light text-dark">Nível ${p.NIVEL_APROVACAO}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="abrirEdicaoPolitica('${encodeURIComponent(JSON.stringify(p))}')">Editar</button>
                            <button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_politica', ${p.CODPOLIT_CC})"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>`).join('') || '';
            }
        } catch(e) {}

        // Aprovadores
        try {
            let ra = await fetch('api/api_despesas_config.php', {
                method: 'POST', body: JSON.stringify({action: 'list_aprovadores'})
            });
            let ja = await ra.json();
            if (ja.sucesso) {
                let tba = document.querySelector('#tableAprovadores tbody');
                tba.innerHTML = ja.dados?.map(a => `<tr>
                    <td><div class="fw-bold">${a.CENTROCUSTO}</div></td>
                    <td><div class="fw-bold">${a.GESTOR}</div></td>
                    <td><span class="badge bg-primary" style="font-size:10px;">${a.NOMEGRUPO || '-'}</span></td>
                    <td class="text-muted">${a.DATA_VINCULO || '-'}</td>
                    <td><button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_aprovador', null, '${a.GESTOR}')"><i class="bi bi-x"></i></button></td>
                </tr>`).join('') || '';

                let tbc = document.querySelector('#tableCentroCustos tbody');
                if (tbc) {
                    const groupedCc = Object.values((ja.dados || []).reduce((acc, row) => {
                        const key = `${row.CODGRUPO || ''}|${row.CENTROCUSTO || ''}`;
                        if (!acc[key]) {
                            acc[key] = {
                                codgrupo: row.CODGRUPO || row.codgrupo || '',
                                centroCusto: row.CENTROCUSTO || '-',
                                nomeGrupo: row.NOMEGRUPO || '-',
                                aprovadores: []
                            };
                        }
                        acc[key].aprovadores.push(row.GESTOR || '-');
                        return acc;
                    }, {}));

                    tbc.innerHTML = groupedCc.map(item => `<tr>
                        <td><div class="fw-bold">${item.centroCusto}</div></td>
                        <td><span class="badge bg-primary" style="font-size:10px;">${item.nomeGrupo}</span></td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                ${item.aprovadores.map(nome => `<span class="fw-bold">${nome}</span>`).join('')}
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="badge bg-light text-dark">${item.aprovadores.length} aprovador(es)</span>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="abrirEdicaoCentroCusto('${item.codgrupo || ''}', '${item.centroCusto}', '${String(item.nomeGrupo).replace(/'/g, "\\'")}')">Editar</button>
                                    <button class="btn btn-sm btn-light border text-danger rounded-pill px-3" onclick="excluirCentroCustoVinculado('${item.codgrupo || ''}', '${item.centroCusto}', '${String(item.nomeGrupo).replace(/'/g, "\\'")}')">Excluir</button>
                                </div>
                            </div>
                        </td>
                    </tr>`).join('') || '';
                }
            }
        } catch(e) {}
    }

    function organizarAbasConfig() {
        const nav = document.querySelector('.saas-nav-tabs');
        if (!nav) return;

        const buttons = Array.from(nav.querySelectorAll('.saas-nav-link'));
        const findButton = (text) => buttons.find(btn => (btn.textContent || '').toLowerCase().includes(text));

        const btnCategorias = findButton('categor');
        const btnFluxos = findButton('fluxos');
        const btnGrupos = findButton('grupos');
        const btnAprovadores = findButton('centros de custo') || findButton('aprovadores');
        const btnPoliticas = findButton('pol');

        let btnCentroCustos = document.querySelector('.saas-nav-link[data-tab="tabCentroCustos"]');
        if (!btnCentroCustos) {
            btnCentroCustos = document.createElement('button');
            btnCentroCustos.className = 'saas-nav-link';
            btnCentroCustos.dataset.tab = 'tabCentroCustos';
            btnCentroCustos.innerHTML = '<i class="bi bi-people me-1"></i> Aprovadores';
            btnCentroCustos.onclick = function() { switchTab(this, 'tabCentroCustos'); };
        }

        if (btnAprovadores) {
            btnAprovadores.remove();
        }

        if (btnFluxos) {
            btnFluxos.remove();
        }

        [btnCategorias, btnGrupos, btnCentroCustos, btnPoliticas]
            .filter(Boolean)
            .forEach(btn => nav.appendChild(btn));

        const tabAprovadores = document.getElementById('tabAprovadores');
        if (tabAprovadores) {
            tabAprovadores.style.display = 'none';
        }

        let tabCentroCustos = document.getElementById('tabCentroCustos');
        if (!tabCentroCustos && tabAprovadores) {
            tabCentroCustos = document.createElement('div');
            tabCentroCustos.id = 'tabCentroCustos';
            tabCentroCustos.className = 'settings-card';
            tabCentroCustos.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold m-0"><i class="bi bi-people-fill text-primary me-2"></i> Aprovadores</h5>
                    <button class="btn-primary-custom py-2 px-3" onclick="modalItem('Aprovador')">Vincular Aprovador</button>
                </div>
                <table class="cfg-table" id="tableCentroCustos">
                    <thead>
                        <tr>
                            <th>Centro de Custo</th>
                            <th>Grupo</th>
                            <th>Aprovadores</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `;
            tabAprovadores.insertAdjacentElement('afterend', tabCentroCustos);
        }

        const tabFluxos = document.getElementById('tabFluxos');
        if (tabFluxos) {
            const fluxoAtivo = tabFluxos.classList.contains('active');
            tabFluxos.classList.remove('active');
            tabFluxos.style.display = 'none';
            if (fluxoAtivo && btnCategorias) {
                switchTab(btnCategorias, 'tabCategorias');
            }
        }

        if (btnGrupos) {
            btnGrupos.innerHTML = '<i class="bi bi-collection me-1"></i> Grupos';
        }
    }

    async function salvarConfig() {
        let payload = {};
        const title = document.getElementById('modalConfigTitle').innerText;

        if (modalMode === 'Categoria') {
            const desc = document.getElementById('fDescCateg').value;
            if (!desc) return alert('Informe a descrição.');
            payload = { action: 'add_tipo', descricao: desc };
        } else if (modalMode === 'Grupo') {
            const nome = document.getElementById('fNomeGrupo').value;
            if (!nome) return alert('Informe o nome do grupo.');
            payload = { action: 'add_grupo', nome: nome };
        } else if (modalMode === 'Politica') {
            const niveis = collectPoliticaNiveis();
            if (!niveis.length) {
                alert('Adicione ao menos um nível de aprovação com grupo e aprovadores.');
                return;
            }
            payload = {
                action: 'add_politica_lote',
                descricao: document.getElementById('fPolDesc').value,
                niveis: niveis
            };
        } else if (modalMode === 'PoliticaEdit') {
            const desc = document.getElementById('fPolDesc').value;
            const grupoEl = document.getElementById('fPolGrupo');
            const ccEl = document.getElementById('fPolCc');
            
            const grupo = grupoEl?.tomselect ? grupoEl.tomselect.getValue() : grupoEl?.value;
            const ccValue = ccEl?.tomselect ? ccEl.tomselect.getValue() : ccEl?.value;
            const aprovadores = await collectPoliticaAprovadores('fPolAprovadores');

            if (!desc || !grupo || !ccValue || !aprovadores.length) {
                return alert('Preencha todos os campos obrigatórios.');
            }

            payload = {
                action: 'edit_politica',
                id: editingPolitica.CODPOLIT_CC,
                codpolitica: editingPolitica.CODPOLITICA,
                codgrupo: grupo,
                centro_custo: ccValue,
                descricao: desc,
                sequsuario: aprovadores[0].sequsuario,
                nivel: aprovadores[0].nivel
            };
        } else if (modalMode === 'Aprovador') {
            const ccEl = document.getElementById('fCcAprov');
            const gestorEl = document.getElementById('fGestorAprov');
            const grupoEl = document.getElementById('fGrupoAprov');

            const cc = ccEl?.tomselect ? ccEl.tomselect.getValue() : ccEl?.value;
            const gestorRaw = gestorEl?.tomselect ? gestorEl.tomselect.getValue() : gestorEl?.value;
            const grupo = grupoEl?.tomselect ? grupoEl.tomselect.getValue() : grupoEl?.value;

            if (!cc || !gestorRaw) return alert('Selecione centro de custo e gestor.');

            const gestorParts = String(gestorRaw).split('|');
            payload = {
                action: 'add_aprovador',
                centro_custo: cc,
                gestor: gestorRaw,
                sequsuario: gestorParts[0],
                nome: gestorParts[1],
                codgrupo: grupo
            };
        } else if (modalMode === 'CentroCustoEdit') {
            const ccEl = document.getElementById('fEditCc');
            const grupoEl = document.getElementById('fEditGrupo');
            
            const cc = ccEl?.tomselect ? ccEl.tomselect.getValue() : ccEl?.value;
            const grupo = grupoEl?.tomselect ? grupoEl.tomselect.getValue() : grupoEl?.value;

            if (!cc) return alert('Selecione o centro de custo.');
            
            payload = {
                action: 'edit_centro_custo_vinculado',
                codgrupo: editCentroCustoState.codgrupo,
                centro_custo_atual: editCentroCustoState.centroCusto,
                centro_custo_novo: cc,
                codgrupo_novo: grupo
            };
        }

        try {
            let res = await fetch('api/api_despesas_config.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            let json = await res.json();
            if (json.sucesso) {
                bootstrap.Modal.getOrCreateInstance('#modalConfig').hide();
                carregarTabelas();
                // Se for Política, talvez precise de um refresh maior
                if (modalMode === 'Politica' || modalMode === 'PoliticaEdit') {
                    // carregarTabelas() já deve ser suficiente
                }
            } else {
                alert(json.erro || 'Erro ao salvar.');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de comunicação com o servidor.');
        }
    }

    async function deletarItem(action, id = null, nome = null) {
        if(!confirm("Deseja realmente excluir?")) return;
        let payload = { action: action };
        if (id) payload.id = id;
        if (nome) payload.nome = nome;

        try {
            let res = await fetch('api/api_despesas_config.php', {
                method: 'POST', body: JSON.stringify(payload)
            });
            let json = await res.json();
            if (json.sucesso) carregarTabelas();
            else alert(json.erro);
        } catch(e) {}
    }

    document.addEventListener("DOMContentLoaded", () => {
        organizarAbasConfig();
        carregarTabelas();
    });
</script>
