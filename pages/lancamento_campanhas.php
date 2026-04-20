<?php
$paginaAtual = 'lancamento_campanhas';
$isEmbed = isset($_GET['embed']);
?>

<style>
/* ===== Clean SaaS Style ===== */
.saas-head {
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13, 202, 240, 0.14), rgba(13, 202, 240, 0.05));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 24px;
    overflow: hidden;
    position: relative;
}
.saas-title { font-weight: 900; letter-spacing: -.02em; margin: 0; color: var(--saas-text); }
.saas-subtitle { margin: 6px 0 0; color: var(--saas-muted); font-size: 14px; }

/* Step Cards */
.step-card {
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.step-card:hover { transform: translateY(-3px); border-color: var(--saas-info); }
.step-card.active { border-color: var(--saas-info); background: rgba(13, 202, 240, 0.05); box-shadow: 0 0 0 2px var(--saas-info); }
.step-card.completed { border-color: #198754; background: rgba(25, 135, 84, 0.05); }
.step-card.completed .step-number { color: rgba(25, 135, 84, 0.1); }
.step-number { position: absolute; top: -5px; right: -5px; font-size: 50px; font-weight: 900; color: rgba(13, 202, 240, 0.08); z-index: 0; }
.step-check { position: absolute; top: 12px; right: 12px; color: #198754; display: none; font-size: 20px; z-index: 2; }
.step-card.completed .step-check { display: block; }
.step-content { position: relative; z-index: 1; padding: 15px 20px; }

/* Form Style */
.form-section { display: none; }
.form-section.active { display: block; animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.saas-form-label { font-weight: 700; color: var(--saas-text); font-size: 13px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; display: block; }
.saas-input { border-radius: 12px; border: 1px solid var(--saas-border); padding: 12px 16px; width: 100%; transition: all 0.2s; background: var(--saas-surface); color: var(--saas-text); }
.saas-input:focus { border-color: var(--saas-info); outline: none; box-shadow: 0 0 0 4px rgba(13, 202, 240, 0.15); }
.saas-input[readonly] { background: rgba(var(--saas-surface-rgb), 0.5); cursor: not-allowed; }

/* Tables */
.prizes-table th { font-size: 11px; text-transform: uppercase; color: var(--saas-muted); border: none; letter-spacing: .1em; padding: 12px; }
.prizes-table td { border-top: 1px solid var(--saas-border); padding: 12px; vertical-align: middle; }

/* Console */
.saas-console { border-radius: 18px; background: #0b1220; color: #fff; overflow: hidden; }
html[data-theme="dark"] .saas-console { background: #070c16; }
.console-body { padding: 15px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px; }

/* Custom Premium Alerts */
#mgCustomAlert {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.mg-toast {
    background: rgba(var(--saas-surface-rgb, 255, 255, 255), 0.8);
    backdrop-filter: blur(12px);
    border: 1px solid var(--saas-border);
    border-left: 5px solid var(--saas-info);
    border-radius: 14px;
    padding: 16px 20px;
    min-width: 320px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 15px;
    animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transition: all 0.3s;
}
@keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
.mg-toast.error { border-left-color: #dc3545; }
.mg-toast.success { border-left-color: #198754; }
.mg-toast-icon { font-size: 24px; }
.mg-toast-content { flex-grow: 1; }
.mg-toast-title { font-weight: 800; font-size: 14px; margin-bottom: 2px; }
.mg-toast-text { color: var(--saas-muted); font-size: 13px; }

<?php if($isEmbed): ?>
    .saas-head { display: none; }
    .container-fluid { padding: 0 !important; }
    .card.saas-card { border: none; box-shadow: none; background: transparent; }
    body { background: transparent !important; }
<?php endif; ?>
</style>

<div id="mgCustomAlert"></div>

<div class="container-fluid pb-5">
    <?php if(!$isEmbed): ?>
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            <div class="saas-head mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="saas-title">Lançamento de Campanhas</h3>
                        <p class="saas-subtitle">Fluxo completo de cadastro, vínculo de produtos, premiações e metas.</p>
                    </div>
                </div>
            </div>
    <?php endif; ?>

            <!-- Steps -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="step-card active" id="card_step1" onclick="selectStep(1)">
                        <div class="step-number">01</div>
                        <i class="bi bi-check-circle-fill step-check"></i>
                        <div class="step-content">
                            <h6 class="fw-bold mb-1">Passo 1: Básico</h6>
                            <p class="small text-muted mb-0">Dados e Campanha</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card" id="card_step2" onclick="selectStep(2)">
                        <div class="step-number">02</div>
                        <i class="bi bi-check-circle-fill step-check"></i>
                        <div class="step-content">
                            <h6 class="fw-bold mb-1">Passo 2: Produtos</h6>
                            <p class="small text-muted mb-0">Vínculo de Metas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card" id="card_step3" onclick="selectStep(3)">
                        <div class="step-number">03</div>
                        <i class="bi bi-check-circle-fill step-check"></i>
                        <div class="step-content">
                            <h6 class="fw-bold mb-1">Passo 3: Prêmios</h6>
                            <p class="small text-muted mb-0">Configurar G1, G2, G3</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card" id="card_step4" onclick="selectStep(4)">
                        <div class="step-number">04</div>
                        <i class="bi bi-check-circle-fill step-check"></i>
                        <div class="step-content">
                            <h6 class="fw-bold mb-1">Passo 4: Importar</h6>
                            <p class="small text-muted mb-0">Planilha de Metas</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card saas-card shadow-sm">
                <div class="card-body p-4">
                    
                    <!-- STEP 1: FORM -->
                    <div id="section_step1" class="form-section active">
                        <div class="row g-4">
                            <div class="col-md-2">
                                <label class="saas-form-label">Cód. Campanha</label>
                                <input type="number" class="saas-input" id="in_codcampanha" placeholder="Buscando..." readonly>
                                <small class="text-muted">Gerado automaticamente</small>
                            </div>
                            <div class="col-md-6">
                                <label class="saas-form-label">Nome da Campanha</label>
                                <input type="text" class="saas-input" id="in_campanha" placeholder="Ex: Campanha de Vendas Abril/2026">
                            </div>
                            <div class="col-md-2">
                                <label class="saas-form-label">Data Início</label>
                                <input type="date" class="saas-input" id="in_dtainicial">
                            </div>
                            <div class="col-md-2">
                                <label class="saas-form-label">Data Final</label>
                                <input type="date" class="saas-input" id="in_dtafinal">
                            </div>
                            <div class="col-md-2">
                                <label class="saas-form-label">Qtd. Mín. Metas</label>
                                <input type="number" class="saas-input" id="in_qtdminmetas" value="2">
                            </div>
                            
                            <div class="col-md-10">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="saas-form-label mb-0">Tipos de Meta da Campanha</label>
                                    <button class="btn btn-link text-info p-0 fw-bold" style="font-size: 11px;" onclick="addMetaRow()">
                                        <i class="bi bi-plus-circle me-1"></i> ADICIONAR TIPO
                                    </button>
                                </div>
                                <div id="metas_container" class="d-flex flex-wrap gap-2 p-3 rounded-4" style="background: rgba(var(--saas-surface-rgb), 0.3); border: 1px solid var(--saas-border);"></div>
                            </div>

                            <div class="col-md-12 text-end mt-4">
                                <button class="btn btn-info px-5 text-white fw-bold py-2 d-inline-flex align-items-center" id="btn_step1" onclick="saveStep1()">
                                    <span>PRÓXIMO PASSO</span> <i class="bi bi-arrow-right ms-2 mt-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: PRODUCTS -->
                    <div id="section_step2" class="form-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-0">Vínculo de Produtos e Metas</h5>
                                <p class="text-muted small mb-0">Informe o código do produto e selecione a quais metas ele responde.</p>
                            </div>
                            <button class="btn btn-outline-info btn-sm fw-bold px-3" onclick="addProdRow()">
                                <i class="bi bi-plus-circle me-1"></i> ADICIONAR PRODUTO
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table prizes-table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">SeqProduto</th>
                                        <th>Descrição do Produto</th>
                                        <th style="width: 300px;">Metas Vinculadas</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="prods_body"></tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-outline-secondary px-4 fw-bold" onclick="selectStep(1)">
                                <i class="bi bi-arrow-left me-2"></i> VOLTAR
                            </button>
                            <button class="btn btn-info px-5 text-white fw-bold py-2 d-inline-flex align-items-center" id="btn_step2" onclick="saveStep2()">
                                <span>SALVAR PRODUTOS</span> <i class="bi bi-arrow-right ms-2 mt-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: PRIZES FORM -->
                    <div id="section_step3" class="form-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Configuração de Premiações por Grupo</h5>
                            <button class="btn btn-outline-info btn-sm fw-bold px-3" onclick="addPrizeRow()">
                                <i class="bi bi-plus-circle me-1"></i> ADICIONAR PRÊMIO
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table prizes-table">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Grupo</th>
                                        <th style="width: 180px;">Ranking (Posição)</th>
                                        <th>Descrição do Prêmio</th>
                                        <th style="width: 180px;">Valor do Prêmio (R$)</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="prizes_body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-outline-secondary px-4 fw-bold" onclick="selectStep(2)">
                                <i class="bi bi-arrow-left me-2"></i> VOLTAR
                            </button>
                            <button class="btn btn-info px-5 text-white fw-bold py-2 d-inline-flex align-items-center" id="btn_step3" onclick="saveStep3()">
                                <span>SALVAR PREMIAÇÕES</span> <i class="bi bi-arrow-right ms-2 mt-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: EXCEL IMPORT -->
                    <div id="section_step4" class="form-section">
                        <div class="row align-items-center mb-4">
                            <div class="col">
                                <h5 class="fw-bold mb-1">Importação de Metas por Representante</h5>
                                <p class="text-muted small mb-0">ID Campanha: <strong class="text-info" id="display_cod">---</strong></p>
                            </div>
                        </div>

                        <div class="p-4 rounded-4 mb-4" style="background: rgba(13,110,253,.03); border: 2px dashed var(--saas-border);">
                            <div class="row justify-content-center">
                                <div class="col-md-8 text-center">
                                    <div class="mb-3"><i class="bi bi-file-earmark-excel-fill text-success fs-1"></i></div>
                                    <h6>Selecione sua planilha de Metas</h6>
                                    <div class="input-group mt-4">
                                        <input type="file" class="form-control saas-input" id="arquivoInput" accept=".xls,.xlsx">
                                        <button class="btn btn-info text-white px-4 fw-bold d-inline-flex align-items-center" id="btn_step4" onclick="iniciarImport()">
                                            <span>IMPORTAR COLEÇÃO</span>
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <a href="javascript:void(0)" onclick="downloadTemplate()" class="text-info text-decoration-none small fw-bold">
                                            <i class="bi bi-download me-1"></i> Baixar Modelo (.csv)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="saas-console">
                            <div class="p-2 px-3 border-bottom border-secondary d-flex justify-content-between">
                                <small class="text-secondary fw-bold">TERMINAL DE IMPORTAÇÃO</small>
                            </div>
                            <div class="console-body" id="consoleLog">
                                <div class="text-secondary opacity-50">Pronto para importar...</div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-outline-info px-4" onclick="location.reload()">
                                <i class="bi bi-plus-circle me-2"></i> NOVO LANÇAMENTO
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <?php if(!$isEmbed): ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    let globalCodCampanha = null;
    let selectedMetas = [];

    async function fetchNextId() {
        if(globalCodCampanha) return;
        try {
            const resp = await fetch('api/api_campanhas.php?action=get_next_id');
            const json = await resp.json();
            if(json.sucesso) document.getElementById('in_codcampanha').value = json.next_id;
        } catch(e) {}
    }
    fetchNextId();

    async function loadEditData(cod) {
        try {
            const resp = await fetch(`api/api_campanhas.php?action=get_campanha_full&cod=${cod}`);
            const json = await resp.json();
            if(!json.sucesso) return mgAlert(json.erro, 'error');

            globalCodCampanha = cod;
            document.getElementById('in_codcampanha').value = cod;
            document.getElementById('display_cod').innerText = cod;
            
            document.getElementById('in_campanha').value = json.basico.CAMPANHA;
            document.getElementById('in_dtainicial').value = json.basico.DTAINICIAL ? json.basico.DTAINICIAL.split(' ')[0] : '';
            document.getElementById('in_dtafinal').value = json.basico.DTAFINAL ? json.basico.DTAFINAL.split(' ')[0] : '';
            document.getElementById('in_qtdminmetas').value = json.basico.QTDMINMETAS;
            
            document.getElementById('metas_container').innerHTML = '';
            selectedMetas = json.metas;
            json.metas.forEach(mId => addMetaRow(mId));
            document.getElementById('card_step1').classList.add('completed');

            document.getElementById('prods_body').innerHTML = '';
            json.produtos.forEach(p => addProdRow(p.seq, p.metas));
            document.getElementById('card_step2').classList.add('completed');

            document.getElementById('prizes_body').innerHTML = '';
            json.premios.forEach(pr => addPrizeRow(pr.CODGRUPO, pr.POSICAO, pr.PREMIODESC, pr.VLRPREMIO));
            document.getElementById('card_step3').classList.add('completed');

            mgAlert('Dados carregados!', 'success');
        } catch(e) { mgAlert('Erro ao carregar dados', 'error'); }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const urlCod = urlParams.get('codcampanha');
    if(urlCod) { globalCodCampanha = urlCod; loadEditData(urlCod); }

    function mgAlert(msg, type = 'info') {
        const container = document.getElementById('mgCustomAlert');
        const toast = document.createElement('div');
        toast.className = `mg-toast ${type === 'error' ? 'error' : (type === 'success' ? 'success' : '')}`;
        let icon = type === 'error' ? 'bi-x-circle-fill text-danger' : (type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-info-circle-fill text-info');
        toast.innerHTML = `<div class="mg-toast-icon"><i class="bi ${icon}"></i></div><div class="mg-toast-content"><div class="mg-toast-title">${type.toUpperCase()}</div><div class="mg-toast-text">${msg}</div></div>`;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 4000);
    }

    function setBtnLoading(btnId, isLoading) {
        const btn = document.getElementById(btnId);
        if(!btn) return;
        const span = btn.querySelector('span');
        const icon = btn.querySelector('i');
        if(isLoading) {
            btn.disabled = true; btn.dataset.oldText = span.innerText; span.innerText = 'Processando...';
            if(icon) icon.className = 'spinner-border spinner-border-sm ms-2';
        } else {
            btn.disabled = false; span.innerText = btn.dataset.oldText;
            if(icon) icon.className = (btnId === 'btn_step4') ? '' : 'bi bi-arrow-right ms-2 mt-1';
        }
    }

    function selectStep(step) {
        if(step > 1 && !globalCodCampanha) return mgAlert('Salve os dados básicos primeiro!', 'error');
        document.querySelectorAll('.step-card').forEach(c => c.classList.remove('active'));
        document.getElementById('card_step' + step).classList.add('active');
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        document.getElementById('section_step' + step).classList.add('active');
    }

    function addMetaRow(cod = '1') {
        const container = document.getElementById('metas_container');
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 bg-white p-2 px-3 rounded-pill border shadow-sm meta-item';
        div.innerHTML = `<select class="border-0 bg-transparent fw-bold small flex-grow-1 field-meta-cod" style="outline: none; font-size: 12px; color: var(--saas-text);">
                <option value="1" ${cod==='1'?'selected':''}>1 - POSITIVAÇÃO</option>
                <option value="2" ${cod==='2'?'selected':''}>2 - FATURAMENTO</option>
                <option value="3" ${cod==='3'?'selected':''}>3 - VENDAS</option>
            </select><button class="btn btn-link text-danger p-0" onclick="this.closest('.meta-item').remove()"><i class="bi bi-x-circle-fill"></i></button>`;
        container.appendChild(div);
    }

    async function addProdRow(seq = '', metasArr = []) {
        const tbody = document.getElementById('prods_body');
        const tr = document.createElement('tr');
        tr.className = 'prod-row';
        let metaHtml = '';
        const metaOptions = [{id: '1', label: '1-POS'}, {id: '2', label: '2-FAT'}, {id: '3', label: '3-VEN'}];
        metaOptions.forEach(m => {
            if(selectedMetas.includes(m.id)) {
                const isChecked = metasArr.length === 0 || metasArr.includes(m.id);
                metaHtml += `<div class="form-check form-check-inline"><input class="form-check-input check-meta" type="checkbox" value="${m.id}" ${isChecked?'checked':''}> <label class="form-check-label small">${m.label}</label></div>`;
            }
        });
        tr.innerHTML = `<td><input type="number" class="saas-input py-1 field-prod-seq" value="${seq}" onblur="lookupProduct(this)"></td>
            <td><input type="text" class="saas-input py-1 field-prod-nome" value="" readonly placeholder="..."></td>
            <td>${metaHtml}</td>
            <td class="text-end"><button class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(tr);
        if(seq) lookupProduct(tr.querySelector('.field-prod-seq'));
    }

    async function lookupProduct(input) {
        const seq = input.value;
        const row = input.closest('tr');
        const nameInput = row.querySelector('.field-prod-nome');
        if(!seq) return;
        nameInput.value = 'Buscando...'; nameInput.classList.add('opacity-50');
        try {
            const resp = await fetch(`api/api_campanhas.php?action=get_product&seq=${seq}`);
            const json = await resp.json();
            nameInput.classList.remove('opacity-50');
            if(json.sucesso) nameInput.value = json.nome;
            else { mgAlert('Produto não encontrado!', 'error'); nameInput.value = ''; }
        } catch(e) { nameInput.classList.remove('opacity-50'); nameInput.value = ''; }
    }

    function addPrizeRow(grp = 'G1', pos = '1', desc = '', vlr = '') {
        const tbody = document.getElementById('prizes_body');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><select class="saas-input py-1 field-grp"><option value="G1" ${grp==='G1'?'selected':''}>Grupo G1</option><option value="G2" ${grp==='G2'?'selected':''}>Grupo G2</option><option value="G3" ${grp==='G3'?'selected':''}>Grupo G3</option></select></td>
            <td><select class="saas-input py-1 field-pos"><option value="1" ${pos==='1'?'selected':''}>1º (Ouro)</option><option value="2" ${pos==='2'?'selected':''}>2º (Prata)</option><option value="3" ${pos==='3'?'selected':''}>3º (Bronze)</option></select></td>
            <td><input type="text" class="saas-input py-1 field-desc" value="${desc}"></td>
            <td><input type="number" class="saas-input py-1 field-vlr" value="${vlr}"></td>
            <td class="text-end"><button class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(tr);
    }

    function initDefaults() {
        if(document.getElementById('metas_container').children.length === 0) { addMetaRow('1'); addMetaRow('2'); }
        if(document.getElementById('prizes_body').children.length === 0) {
            addPrizeRow('G1', '1', 'OURO', '2250'); addPrizeRow('G1', '2', 'PRATA', '2100'); addPrizeRow('G1', '3', 'BRONZE', '1950');
            addPrizeRow('G2', '1', 'OURO', '1500'); addPrizeRow('G2', '2', 'PRATA', '1350'); addPrizeRow('G3', '1', 'OURO', '900');
        }
    }
    initDefaults();

    async function saveStep1() {
        selectedMetas = []; document.querySelectorAll('.field-meta-cod').forEach(sel => selectedMetas.push(sel.value));
        const data = { codcampanha: document.getElementById('in_codcampanha').value, campanha: document.getElementById('in_campanha').value, dtainicial: document.getElementById('in_dtainicial').value, dtafinal: document.getElementById('in_dtafinal').value, qtdminmetas: document.getElementById('in_qtdminmetas').value, metas: selectedMetas };
        if(!data.campanha || !data.dtainicial || !data.dtafinal) return mgAlert('Preencha os campos!', 'error');
        setBtnLoading('btn_step1', true);
        try {
            const resp = await fetch('api/api_campanhas.php?action=save_campanha', { method: 'POST', body: JSON.stringify(data) });
            const json = await resp.json();
            if(json.sucesso) { globalCodCampanha = json.codcampanha; document.getElementById('in_codcampanha').value = globalCodCampanha; document.getElementById('display_cod').innerText = globalCodCampanha; document.getElementById('card_step1').classList.add('completed'); mgAlert('Salvo!', 'success'); selectStep(2); if(document.getElementById('prods_body').children.length === 0) addProdRow(); }
            else mgAlert(json.erro, 'error');
        } catch(e) { mgAlert(e.message, 'error'); } finally { setBtnLoading('btn_step1', false); }
    }

    async function saveStep2() {
        const products = [];
        document.querySelectorAll('.prod-row').forEach(row => {
            const seq = row.querySelector('.field-prod-seq').value; const metas = [];
            row.querySelectorAll('.check-meta:checked').forEach(ck => metas.push(ck.value));
            if(seq && metas.length > 0) products.push({ seq, metas });
        });
        if(products.length === 0) return mgAlert('Vincule ao menos um produto!', 'error');
        setBtnLoading('btn_step2', true);
        try {
            const resp = await fetch('api/api_campanhas.php?action=save_produtos', { method: 'POST', body: JSON.stringify({ codcampanha: globalCodCampanha, produtos: products }) });
            const json = await resp.json();
            if(json.sucesso) { document.getElementById('card_step2').classList.add('completed'); mgAlert('Vinculado!', 'success'); selectStep(3); }
            else mgAlert(json.erro, 'error');
        } catch(e) { mgAlert(e.message, 'error'); } finally { setBtnLoading('btn_step2', false); }
    }

    async function saveStep3() {
        const rows = document.querySelectorAll('#prizes_body tr'); const premios = [];
        rows.forEach(row => { premios.push({ codgrupo: row.querySelector('.field-grp').value, posicao: row.querySelector('.field-pos').value, premiodesc: row.querySelector('.field-desc').value, vlrpremio: row.querySelector('.field-vlr').value }); });
        setBtnLoading('btn_step3', true);
        try {
            const resp = await fetch('api/api_campanhas.php?action=save_premios', { method: 'POST', body: JSON.stringify({ codcampanha: globalCodCampanha, premios }) });
            const json = await resp.json();
            if(json.sucesso) { document.getElementById('card_step3').classList.add('completed'); mgAlert('Prêmios salvos!', 'success'); selectStep(4); }
            else mgAlert(json.erro, 'error');
        } catch(e) { mgAlert(e.message, 'error'); } finally { setBtnLoading('btn_step3', false); }
    }

    function log(msg, tipo) {
        const term = document.getElementById('consoleLog'); if(term.innerText.includes('Pronto')) term.innerHTML = '';
        const d = document.createElement('div'); d.innerHTML = `<span class="text-info">></span> ${msg}`;
        if(tipo === 'erro') d.className = 'text-danger'; if(tipo === 'sucesso') d.className = 'text-success';
        term.appendChild(d); term.scrollTop = term.scrollHeight;
    }

    async function iniciarImport() {
        const fileInput = document.getElementById('arquivoInput'); if(!fileInput.files.length) return mgAlert('Selecione a planilha!', 'error');
        setBtnLoading('btn_step4', true); const fd = new FormData(); fd.append('arquivo', fileInput.files[0]);
        try {
            const up = await fetch('upload.php', { method: 'POST', body: fd }); const jsonValue = await up.json();
            const evt = new EventSource(`processors/processa_universal_insert.php?tipo=camp_metarep&arquivo=${jsonValue.arquivo}&fixed_CODCAMPANHA=${globalCodCampanha}`);
            evt.onmessage = (e) => { const d = JSON.parse(e.data); log(d.msg, d.tipo); };
            evt.addEventListener('close', () => { document.getElementById('card_step4').classList.add('completed'); mgAlert('Importação concluída!', 'success'); evt.close(); setBtnLoading('btn_step4', false); });
            evt.onerror = () => { evt.close(); setBtnLoading('btn_step4', false); };
        } catch(e) { log(e.message, 'erro'); setBtnLoading('btn_step4', false); }
    }

    function downloadTemplate() {
        const csvContent = "CODMETA;CODREPRESENTANTE;META;CODGRUPO\n1;1234;5000,00;G1\n2;5678;3000,00;G2";
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a"); link.setAttribute("href", URL.createObjectURL(blob)); link.setAttribute("download", "modelo_metas_campanha.csv");
        link.style.visibility = 'hidden'; document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }
</script>
