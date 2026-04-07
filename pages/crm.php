<?php
// pages/crm.php
?>
<div class="wrap">
    <div class="saas-page-head">
        <div>
            <h2 class="saas-title"><i class="bi bi-funnel-fill"></i> CRM / Comercial</h2>
            <p class="saas-subtitle">Gestão de Leads, Negociações e Funil de Vendas</p>
        </div>
        <div class="actions">
            <button class="saas-btn" onclick="loadLeads()"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
            <button class="saas-btn primary" onclick="openCrmModal('create')"><i class="bi bi-plus-lg"></i> Novo
                Lead</button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="saas-card mb-4" style="padding: 16px;">
        <div class="row g-3 config-panel">
            <div class="col-md-3">
                <label class="saas-kicker d-block mb-1">Responsável</label>
                <select id="filtroResponsavel" class="saas-select">
                    <option value="">Todos da equipe</option>
                    <option value="MEUS">Somente Meus Leads</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="saas-kicker d-block mb-1">Buscar Empresa / Contato</label>
                <input type="text" id="filtroTexto" class="saas-input" placeholder="Digite para filtrar...">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="saas-btn w-100" onclick="loadLeads()">
                    <i class="bi bi-search"></i> Filtrar Funil
                </button>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="row g-3" id="crmKanbanBoard">
        <!-- Coluna: Leads -->
        <div class="col-lg-3 col-md-6">
            <div class="saas-card h-100 p-0" style="background: rgba(17,24,39,.02);">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white"
                    style="border-radius: 18px 18px 0 0;">
                    <h6 class="m-0 fw-bold"><i class="bi bi-inbox text-primary me-2"></i> Novos Leads</h6>
                    <span class="saas-badge info" id="count_LEAD">0</span>
                </div>
                <div class="p-3 kanban-column" id="col_LEAD" data-status="LEAD"
                    style="min-height: 400px; overflow-y: auto;">
                    <!-- Cards de Leads -->
                </div>
            </div>
        </div>

        <!-- Coluna: Contato Feito -->
        <div class="col-lg-3 col-md-6">
            <div class="saas-card h-100 p-0" style="background: rgba(17,24,39,.02);">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white"
                    style="border-radius: 18px 18px 0 0;">
                    <h6 class="m-0 fw-bold"><i class="bi bi-telephone text-warning me-2"></i> Contato / Negociação</h6>
                    <span class="saas-badge warning" id="count_CONTATO">0</span>
                </div>
                <div class="p-3 kanban-column" id="col_CONTATO" data-status="CONTATO"
                    style="min-height: 400px; overflow-y: auto;">
                    <!-- Cards -->
                </div>
            </div>
        </div>

        <!-- Coluna: Proposta -->
        <div class="col-lg-3 col-md-6">
            <div class="saas-card h-100 p-0" style="background: rgba(17,24,39,.02);">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white"
                    style="border-radius: 18px 18px 0 0;">
                    <h6 class="m-0 fw-bold"><i class="bi bi-file-earmark-text text-secondary me-2"></i> Proposta Enviada
                    </h6>
                    <span class="saas-badge secondary" id="count_PROPOSTA">0</span>
                </div>
                <div class="p-3 kanban-column" id="col_PROPOSTA" data-status="PROPOSTA"
                    style="min-height: 400px; overflow-y: auto;">
                    <!-- Cards -->
                </div>
            </div>
        </div>

        <!-- Coluna: Ganho / Perdido -->
        <div class="col-lg-3 col-md-6">
            <div class="saas-card h-100 p-0" style="background: rgba(17,24,39,.02);">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white"
                    style="border-radius: 18px 18px 0 0;">
                    <h6 class="m-0 fw-bold"><i class="bi bi-trophy text-success me-2"></i> Ganho / Fechado</h6>
                    <span class="saas-badge success" id="count_GANHO">0</span>
                </div>
                <div class="p-3 kanban-column mb-3" id="col_GANHO" data-status="GANHO"
                    style="min-height: 180px; overflow-y: auto;">
                    <!-- Cards Ganho -->
                </div>

                <div class="p-3 border-top border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 fw-bold"><i class="bi bi-x-circle text-danger me-2"></i> Perdido</h6>
                    <span class="saas-badge danger" id="count_PERDIDO">0</span>
                </div>
                <div class="p-3 kanban-column" id="col_PERDIDO" data-status="PERDIDO"
                    style="min-height: 180px; overflow-y: auto;">
                    <!-- Cards Perdido -->
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Criar / Editar Lead -->
<div class="saas-modal-backdrop" id="crmModal">
    <div class="saas-modal">
        <div class="saas-modal-header">
            <h3 id="crmModalTitle">Novo Lead / Negócio</h3>
            <button class="saas-btn" style="padding: 4px 8px;" onclick="closeCrmModal()"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="saas-modal-body">
            <input type="hidden" id="formLeadId">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="saas-kicker d-block mb-1">Nome do Contato / Oportunidade</label>
                    <input type="text" id="formLeadNome" class="saas-input" placeholder="Ex: João da Silva">
                </div>
                <div class="col-md-6">
                    <label class="saas-kicker d-block mb-1">Empresa</label>
                    <input type="text" id="formLeadEmpresa" class="saas-input" placeholder="Ex: MegaG Logística">
                </div>
                <div class="col-md-6">
                    <label class="saas-kicker d-block mb-1">Valor Estimado (R$)</label>
                    <input type="number" id="formLeadValor" class="saas-input" placeholder="0.00">
                </div>
                <div class="col-md-6">
                    <label class="saas-kicker d-block mb-1">Etapa do Funil</label>
                    <select id="formLeadStatus" class="saas-select">
                        <option value="LEAD" selected>Novo Lead</option>
                        <option value="CONTATO">Contato/Negociação</option>
                        <option value="PROPOSTA">Proposta</option>
                        <option value="GANHO">Ganho/Vendido</option>
                        <option value="PERDIDO">Perdido</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="saas-kicker d-block mb-1">Prioridade</label>
                    <select id="formLeadPrioridade" class="saas-select">
                        <option value="BAIXA">Baixa</option>
                        <option value="MEDIA" selected>Média</option>
                        <option value="ALTA">Alta</option>
                        <option value="URGENTE">Urgente</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="saas-modal-footer">
            <button class="saas-btn danger d-none" id="btnDeleteLead" onclick="deleteLead()">Excluir</button>
            <button class="saas-btn" onclick="closeCrmModal()">Cancelar</button>
            <button class="saas-btn primary" id="btnSalvarLead" onclick="salvarLead()">Salvar Oportunidade</button>
        </div>
    </div>
</div>

<style>
    /* Estilos Especiais do Kanban CRM */
    .crm-card {
        background: #fff;
        border: 1px solid var(--saas-border);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, .02);
        cursor: grab;
        transition: transform 0.2s, box-shadow 0.2s;
        user-select: none;
    }

    html[data-theme="dark"] .crm-card {
        background: rgba(255, 255, 255, .05);
    }

    .crm-card:active {
        cursor: grabbing;
    }

    .crm-card:hover {
        box-shadow: 0 10px 20px rgba(0, 0, 0, .06);
        transform: translateY(-2px);
    }

    .crm-empresa {
        font-size: 0.8rem;
        color: var(--saas-muted);
        font-weight: 700;
        text-transform: uppercase;
    }

    .crm-nome {
        font-size: 0.95rem;
        font-weight: 800;
        margin: 4px 0 8px 0;
        color: var(--saas-text);
    }

    .crm-valor {
        font-size: 0.85rem;
        font-weight: 900;
        color: #16a34a;
        background: rgba(22, 163, 74, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-block;
    }

    .crm-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        border-top: 1px dashed var(--saas-border);
        padding-top: 10px;
    }

    .kanban-column.drag-over {
        background: rgba(13, 110, 253, 0.05);
        border: 2px dashed rgba(13, 110, 253, 0.4);
        border-radius: 12px;
    }
</style>

<script>
    const API_CRM = 'api/crm.php';
    let draggedLeadId = null;

    // Utilitário Monetário
    function formatMoney(val) {
        let num = parseFloat(val) || 0;
        return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    // Inicializa a tela com os Leads
    async function loadLeads() {
        // Limpar colunas
        ['LEAD', 'CONTATO', 'PROPOSTA', 'GANHO', 'PERDIDO'].forEach(col => {
            document.getElementById('col_' + col).innerHTML = '';
            document.getElementById('count_' + col).textContent = '0';
        });

        try {
            const res = await fetch(API_CRM);
            const json = await res.json();

            if (!json.sucesso) {
                alert('Erro ao carregar CRM: ' + json.msg);
                return;
            }

            const leads = json.dados || [];
            const contadores = { LEAD: 0, CONTATO: 0, PROPOSTA: 0, GANHO: 0, PERDIDO: 0 };

            leads.forEach(lead => {
                const col = lead.STATUS || 'LEAD';
                if (contadores[col] !== undefined) {
                    contadores[col]++;
                    document.getElementById('col_' + col).appendChild(createLeadCard(lead));
                }
            });

            // Atualiza Contadores
            for (const [col, qtd] of Object.entries(contadores)) {
                document.getElementById('count_' + col).textContent = qtd;
            }

            setupDragAndDrop();

        } catch (e) {
            console.error(e);
        }
    }

    // Cria o HTML do Card de Lead
    function createLeadCard(lead) {
        const div = document.createElement('div');
        div.className = 'crm-card';
        div.draggable = true;
        div.dataset.id = lead.ID;

        let priorityClass = 'info';
        if (lead.PRIORIDADE === 'ALTA' || lead.PRIORIDADE === 'URGENTE') priorityClass = 'danger';
        if (lead.PRIORIDADE === 'BAIXA') priorityClass = 'secondary';
        if (lead.PRIORIDADE === 'MEDIA') priorityClass = 'warning';

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="crm-empresa">${lead.EMPRESA || 'Sem Empresa'}</div>
                <span class="saas-badge ${priorityClass} px-2 py-0" style="font-size: 0.65rem;">${lead.PRIORIDADE || 'MEDIA'}</span>
            </div>
            <div class="crm-nome">${lead.NOME}</div>
            <div class="crm-valor">${formatMoney(lead.VALOR)}</div>
            
            <div class="crm-footer">
                <div style="font-size: 0.75rem; color: var(--saas-muted);">
                    <i class="bi bi-person"></i> ${lead.RESPONSAVEL || 'N/A'}
                </div>
                <button class="saas-btn" style="padding: 2px 6px; font-size: 0.75rem;" onclick="editLead(${encodeURIComponent(JSON.stringify(lead))})">
                    <i class="bi bi-pencil"></i> Editar
                </button>
            </div>
        `;

        div.addEventListener('dragstart', (e) => {
            draggedLeadId = lead.ID;
            e.dataTransfer.effectAllowed = 'move';
            div.style.opacity = '0.5';
        });

        div.addEventListener('dragend', () => {
            div.style.opacity = '1';
            draggedLeadId = null;
        });

        return div;
    }

    // Configuração de Drag & Drop no Kanban
    function setupDragAndDrop() {
        const columns = document.querySelectorAll('.kanban-column');

        columns.forEach(col => {
            col.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                col.classList.add('drag-over');
            });

            col.addEventListener('dragleave', () => {
                col.classList.remove('drag-over');
            });

            col.addEventListener('drop', async (e) => {
                e.preventDefault();
                col.classList.remove('drag-over');

                const newStatus = col.dataset.status;
                if (!newStatus || !draggedLeadId) return;

                // Move visualmente
                const card = document.querySelector(`.crm-card[data-id="${draggedLeadId}"]`);
                if (card && card.parentElement !== col) {
                    col.appendChild(card);
                    atualizarContadoresLocais();

                    // Salva no backend
                    try {
                        const res = await fetch(API_CRM, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ acao: 'STATUS', id_lead: draggedLeadId, status: newStatus })
                        });
                        const j = await res.json();
                        if (!j.sucesso) alert(j.msg);
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        });
    }

    function atualizarContadoresLocais() {
        ['LEAD', 'CONTATO', 'PROPOSTA', 'GANHO', 'PERDIDO'].forEach(col => {
            const count = document.getElementById('col_' + col).querySelectorAll('.crm-card').length;
            document.getElementById('count_' + col).textContent = count;
        });
    }

    // ----- Modais -----
    function openCrmModal(mode) {
        document.getElementById('crmModal').classList.add('show');
        if (mode === 'create') {
            document.getElementById('crmModalTitle').textContent = 'Novo Lead / Negócio';
            document.getElementById('formLeadId').value = '';
            document.getElementById('formLeadNome').value = '';
            document.getElementById('formLeadEmpresa').value = '';
            document.getElementById('formLeadValor').value = '';
            document.getElementById('formLeadStatus').value = 'LEAD';
            document.getElementById('formLeadPrioridade').value = 'MEDIA';
            document.getElementById('btnDeleteLead').classList.add('d-none');
        }
    }

    function closeCrmModal() {
        document.getElementById('crmModal').classList.remove('show');
    }

    function editLead(leadObj) {
        if (typeof leadObj === 'string') leadObj = JSON.parse(decodeURIComponent(leadObj));

        openCrmModal('edit');
        document.getElementById('crmModalTitle').textContent = 'Editar Oportunidade';
        document.getElementById('formLeadId').value = leadObj.ID;
        document.getElementById('formLeadNome').value = leadObj.NOME;
        document.getElementById('formLeadEmpresa').value = leadObj.EMPRESA;
        document.getElementById('formLeadValor').value = leadObj.VALOR;
        document.getElementById('formLeadStatus').value = leadObj.STATUS;
        document.getElementById('formLeadPrioridade').value = leadObj.PRIORIDADE;

        document.getElementById('btnDeleteLead').classList.remove('d-none');
    }

    async function salvarLead() {
        const id = document.getElementById('formLeadId').value;
        const nome = document.getElementById('formLeadNome').value;
        const btn = document.getElementById('btnSalvarLead');

        if (!nome) { alert('O nome do contato/negócio é obrigatório!'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass"></i> Salvando...';

        try {
            const res = await fetch(API_CRM, {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: id ? 'EDIT' : 'CREATE',
                    id_lead: id,
                    nome: nome,
                    empresa: document.getElementById('formLeadEmpresa').value,
                    valor: document.getElementById('formLeadValor').value,
                    status: document.getElementById('formLeadStatus').value,
                    prioridade: document.getElementById('formLeadPrioridade').value
                })
            });
            const j = await res.json();
            if (j.sucesso) {
                closeCrmModal();
                loadLeads();
            } else {
                alert('Erro: ' + j.msg);
            }
        } catch (e) {
            alert('Falha: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Oportunidade';
        }
    }

    async function deleteLead() {
        if (!confirm('Tem certeza que deseja excluir esta oportunidade? Essa ação não pode ser desfeita.')) return;

        const id = document.getElementById('formLeadId').value;
        try {
            const res = await fetch(API_CRM + '?id_lead=' + id, { method: 'DELETE' });
            const j = await res.json();
            if (j.sucesso) {
                closeCrmModal();
                loadLeads();
            } else {
                alert('Erro: ' + j.msg);
            }
        } catch (e) {
            alert('Falha: ' + e.message);
        }
    }

    // Init
    document.addEventListener("DOMContentLoaded", () => {
        loadLeads();
    });
</script>