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
                    class="bi bi-diagram-3 me-1"></i> Centros de Custo (Aprovadores)</button>
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
                        <th style="width:80px;">Ações</th>
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



    </div>
</main>


<!-- Modal Genérico de Inserção -->
<div class="modal fade" id="modalConfig" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px; border:1px solid var(--saas-border); overflow:hidden;">
            <div class="modal-header border-bottom-0 pb-0 mt-3 mx-2">
                <h5 class="modal-title fw-bold" id="modalConfigTitle">Novo Cadastro</h5>
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

<script>
    // Variável para saber qual form está aberto
    let modalMode = '';

    function switchTab(btn, tabId) {
        document.querySelectorAll('.saas-nav-link').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.settings-card').forEach(el => el.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    function modalItem(tipo) {
        modalMode = tipo;
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
            <div class="mb-3">
                <label class="saas-label">Grupo</label>
                <select class="saas-select" id="fPolGrupo"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Centro de Custo</label>
                <select class="saas-select" id="fPolCc"></select>
            </div>
            <div class="mb-3">
                <label class="saas-label">Descrição da Regra</label>
                <input type="text" class="saas-input" id="fPolDesc" placeholder="Ex: Aprovação Nível 1">
            </div>
            <div class="mb-3">
                <label class="saas-label">Nível de Aprovação</label>
                <input type="number" class="saas-input" id="fPolNivel" value="1">
            </div>`;
            new bootstrap.Modal('#modalConfig').show();
            carregarDomDinamico('fPolCc', 'fPolGrupo');
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
                        let cfSeq = c.SEQCENTRORESULTADO || c.seqcentroresultado;
                        let cfNome = (c.NOME || c.nome || c.DESCRICAO || c.descricao || '').trim();
                        selCc.innerHTML += `<option value="${cfCod}|${cfSeq}">${cfCod} | ${cfNome}</option>`;
                    });
                    new TomSelect('#' + idCc, { create: false, placeholder: 'Pesquise o centro de custo...' });
                 }

                 // Grupos
                 if(idGrupo) {
                    let selG = document.getElementById(idGrupo);
                    if (selG.tomselect) selG.tomselect.destroy();
                    selG.innerHTML = '<option value="">Selecione...</option>';
                    json.dados.groups?.forEach(g => { // Tentando 'groups' e 'grupos'
                        let gId = g.CODGRUPO || g.codgrupo;
                        let gNome = g.NOMEGRUPO || g.nomegrupo;
                        selG.innerHTML += `<option value="${gId}">${gNome}</option>`;
                    });
                    // Fallback se o backend devolveu como 'grupos'
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

    async function salvarConfig() {
        let payload = {};
        
        if (modalMode === 'Categoria') {
            payload = {
                action: 'add_tipo',
                descricao: document.getElementById('fDescCateg').value
            };
        } else if (modalMode === 'Grupo') {
            payload = {
                action: 'add_grupo',
                nome: document.getElementById('fNomeGrupo').value
            };
        } else if (modalMode === 'Politica') {
            payload = {
                action: 'add_politica',
                codgrupo: document.getElementById('fPolGrupo').value,
                centro_custo: document.getElementById('fPolCc').value,
                descricao: document.getElementById('fPolDesc').value,
                nivel: document.getElementById('fPolNivel').value
            };
        } else if (modalMode === 'Aprovador') {
            payload = {
                action: 'add_aprovador',
                centro_custo: document.getElementById('fCcAprov').value,
                gestor: document.getElementById('fGestorAprov').value,
                codgrupo: document.getElementById('fGrupoAprov').value
            };
        }
        
        try {
            let res = await fetch('api/api_despesas_config.php', {
                method: 'POST', body: JSON.stringify(payload)
            });
            let json = await res.json();
            if (json.sucesso) {
                bootstrap.Modal.getInstance(document.getElementById('modalConfig')).hide();
                carregarTabelas();
            } else {
                alert("Erro: " + json.erro);
            }
        } catch(e) { alert("Erro de rede."); }
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
            let r = await fetch('api/api_despesas_config.php', { method: 'POST', body: JSON.stringify({action: 'list_grupos'}) });
            let j = await r.json();
            if (j.sucesso) {
                let tb = document.querySelector('#tableGrupos tbody');
                tb.innerHTML = j.dados?.map(g => `<tr>
                    <td class="text-muted fw-bold">${g.CODGRUPO}</td>
                    <td class="fw-bold">${g.NOMEGRUPO}</td>
                    <td><button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_grupo', ${g.CODGRUPO})"><i class="bi bi-trash"></i></button></td>
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
                    <td><div class="fw-bold">${p.CENTROCUSTO}</div><div class="small text-muted">${p.NOME_CC}</div></td>
                    <td>${p.DESCRICAO}</td>
                    <td><span class="badge bg-light text-dark">Nível ${p.NIVEL_APROVACAO}</span></td>
                    <td><button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_politica', ${p.CODPOLITICA})"><i class="bi bi-trash"></i></button></td>
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
                    <td><div class="fw-bold">${a.CENTROCUSTO}</div><div class="small text-muted">${a.SEQCENTRORESULTADO}</div></td>
                    <td><div class="fw-bold">${a.GESTOR}</div></td>
                    <td><span class="badge bg-primary" style="font-size:10px;">${a.NOMEGRUPO || '-'}</span></td>
                    <td class="text-muted">${a.DATA_VINCULO || '-'}</td>
                    <td><button class="btn btn-sm btn-light p-1 text-danger" onclick="deletarItem('del_aprovador', null, '${a.GESTOR}')"><i class="bi bi-x"></i></button></td>
                </tr>`).join('') || '';
            }
        } catch(e) {}
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
        carregarTabelas();
    });
</script>