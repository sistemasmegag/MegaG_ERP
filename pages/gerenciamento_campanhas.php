<?php
$paginaAtual = 'gerenciamento_campanhas';
?>

<style>
/* Gerenciamento Style */
.camp-card-list {
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    overflow: hidden;
}
.table-camp thead th {
    background: rgba(var(--saas-surface-rgb), 0.5);
    padding: 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--saas-muted);
    border-bottom: 2px solid var(--saas-border);
}
.table-camp tbody td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--saas-border);
}
.btn-action {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    border: 1px solid var(--saas-border);
    background: var(--saas-surface);
    color: var(--saas-text);
}
.btn-action:hover {
    background: var(--saas-info);
    color: white;
    border-color: var(--saas-info);
    transform: translateY(-2px);
}
.badge-status {
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 10px;
    text-transform: uppercase;
}
.badge-active { background: rgba(25, 135, 84, 0.1); color: #198754; }
.badge-inactive { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

/* Modal Edit Customization */
.modal-saas { border-radius: 20px; overflow: hidden; border: none; }
.modal-saas .modal-header { border-bottom: 1px solid var(--saas-border); padding: 20px 30px; }
.modal-saas .modal-body { padding: 30px; background: var(--saas-bg); }
</style>

<div class="container-fluid pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-black mb-1">Gerenciamento de Campanhas</h3>
            <p class="text-muted small">Visualize, edite e acompanhe o status de todas as campanhas lançadas.</p>
        </div>
        <a href="index.php?page=lancamento_campanhas" class="btn btn-info text-white fw-bold px-4 rounded-3 shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> NOVO LANÇAMENTO
        </a>
    </div>

    <div class="camp-card-list">
        <div class="p-3 border-bottom d-flex gap-3">
            <div class="flex-grow-1"><input type="text" class="saas-input" id="searchCamp" placeholder="Procurar por nome ou código..." onkeyup="filterTable()"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-camp mb-0" id="tableCamp">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Campanha</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Metas</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="camp_list_body">
                    <tr><td colspan="7" class="text-center p-5"><div class="spinner-border text-info"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edição -->
<div class="modal fade" id="modalEditCamp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-saas">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold">Editar Campanha</h5>
                    <p class="small text-muted mb-0">ID: <span id="edit_id_display">--</span> | <span id="edit_name_display">--</span></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="edit_modal_content">
                <!-- Conteúdo será carregado aqui -->
                <div class="text-center p-5"><div class="spinner-border text-info"></div><p class="mt-2 text-muted">Carregando dados da campanha...</p></div>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadCampanhas() {
        try {
            const r = await fetch('api/api_campanhas.php?action=list_campanhas');
            const j = await r.json();
            const body = document.getElementById('camp_list_body');
            if(j.sucesso) {
                body.innerHTML = '';
                if(j.campanhas.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center p-5 text-muted">Nenhuma campanha encontrada.</td></tr>';
                    return;
                }
                j.campanhas.forEach(c => {
                    const dtIni = c.DTAINICIAL ? c.DTAINICIAL.split(' ')[0].split('-').reverse().join('/') : '--';
                    const dtFim = c.DTAFINAL ? c.DTAFINAL.split(' ')[0].split('-').reverse().join('/') : '--';
                    const statusClass = c.STATUS === 'A' ? 'badge-active' : 'badge-inactive';
                    const statusText = c.STATUS === 'A' ? 'Ativa' : 'Inativa';
                    
                    body.innerHTML += `
                        <tr>
                            <td class="fw-bold text-info">${c.CODCAMPANHA}</td>
                            <td><div class="fw-bold">${c.CAMPANHA}</div><small class="text-muted">Incluído por: ${c.USUINCLUSAO}</small></td>
                            <td>${dtIni}</td>
                            <td>${dtFim}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${c.QTDMINMETAS || 0}</span></td>
                            <td class="text-center"><span class="badge-status ${statusClass}">${statusText}</span></td>
                            <td class="text-end">
                                <button class="btn-action" title="Editar Campanha" onclick="openEditModal(${c.CODCAMPANHA})"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn-action" title="Log de Atividades"><i class="bi bi-clock-history"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
        } catch(e) { mgAlert('Erro ao carregar lista de campanhas', 'error'); }
    }
    loadCampanhas();

    function filterTable() {
        let input = document.getElementById("searchCamp");
        let filter = input.value.toUpperCase();
        let table = document.getElementById("tableCamp");
        let tr = table.getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            let found = false;
            let tds = tr[i].getElementsByTagName("td");
            for(let j=0; j<tds.length; j++) {
                if (tds[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }

    async function openEditModal(cod) {
        document.getElementById('edit_id_display').innerText = cod;
        const myModal = new bootstrap.Modal(document.getElementById('modalEditCamp'));
        myModal.show();
        
        // Carrega o conteúdo do editor via AJAX para dentro do modal
        try {
            const resp = await fetch('index.php?page=lancamento_campanhas&embed=true&codcampanha=' + cod);
            const html = await resp.text();
            
            const content = document.getElementById('edit_modal_content');
            content.innerHTML = html;

            // Executa scripts manualmente que vieram no AJAX
            const scripts = content.querySelectorAll("script");
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            
            // Aguarda um pequeno delay para os scripts iniciarem e carrega os dados
            setTimeout(() => { if(typeof loadEditData === 'function') loadEditData(cod); }, 500);
        } catch(e) { mgAlert('Erro ao carregar editor', 'error'); }
    }
</script>
