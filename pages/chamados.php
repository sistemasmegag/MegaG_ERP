<?php
// pages/chamados.php
?>


<div class="wrap">
    <div class="saas-page-head">
        <div>
            <h2 class="saas-title"><i class="bi bi-headset"></i> Atendimento / Chamados</h2>
            <p class="saas-subtitle">Gerencie as requisições e incidentes</p>
        </div>
        <div class="actions">
            <button class="saas-btn" onclick="loadChamados()"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
            <button class="saas-btn primary" onclick="openModal('create')"><i class="bi bi-plus-lg"></i> Novo
                Chamado</button>
        </div>
    </div>

    <div class="saas-card">
        <div class="filter-grid">
            <div class="field">
                <label>Status</label>
                <select id="filtroStatus" class="saas-select">
                    <option value="">Todos</option>
                    <option value="ABERTO">Aberto</option>
                    <option value="EM_ATENDIMENTO">Em Atendimento</option>
                    <option value="AGUARDANDO">Aguardando</option>
                    <option value="FECHADO">Fechado</option>
                </select>
            </div>
            <div class="field">
                <label>Prioridade</label>
                <select id="filtroPrioridade" class="saas-select">
                    <option value="">Todas</option>
                    <option value="BAIXA">Baixa</option>
                    <option value="MEDIA">Média</option>
                    <option value="ALTA">Alta</option>
                    <option value="URGENTE">Urgente</option>
                </select>
            </div>
            <div class="field">
                <label>Responsável</label>
                <input type="text" id="filtroResponsavel" class="saas-input" placeholder="Busca...">
            </div>
            <div class="field" style="justify-content: flex-end;">
                <button class="saas-btn" style="width: 100%; justify-content: center;" onclick="loadChamados()">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th>Cód Público</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Solicitante</th>
                        <th>Responsável</th>
                        <th>Data Criação</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="chamadosTbody">
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 40px;">Carregando chamados...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar / Visualizar Chamado -->
<div class="saas-modal-backdrop" id="chamadosModal">
    <div class="saas-modal">
        <div class="saas-modal-header">
            <h3 id="modalTitle">Novo Chamado</h3>
            <button class="saas-btn" style="padding: 4px 8px;" onclick="closeModal()"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="saas-modal-body">
            <input type="hidden" id="formChamadoId">
            <div class="field">
                <label>Título</label>
                <input type="text" id="formTitulo" class="saas-input"
                    placeholder="Informe de forma concisa o problema...">
            </div>
            <div class="field">
                <label>Descrição</label>
                <textarea id="formDescricao" class="saas-textarea" rows="4"
                    placeholder="Detalhes completos da solicitação..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="field">
                    <label>Prioridade</label>
                    <select id="formPrioridade" class="saas-select">
                        <option value="BAIXA">Baixa</option>
                        <option value="MEDIA" selected>Média</option>
                        <option value="ALTA">Alta</option>
                        <option value="URGENTE">Urgente</option>
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select id="formStatus" class="saas-select" disabled>
                        <option value="ABERTO" selected>Aberto</option>
                        <option value="EM_ATENDIMENTO">Em Atendimento</option>
                        <option value="AGUARDANDO">Aguardando Cliente</option>
                        <option value="FECHADO">Fechado</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="field">
                    <label>Categoria</label>
                    <input type="text" id="formCategoria" class="saas-input" placeholder="Ex: Financeiro">
                </div>
                <div class="field">
                    <label>E-mail Solicitante</label>
                    <input type="email" id="formEmail" class="saas-input" placeholder="email@exemplo.com">
                </div>
            </div>
        </div>
        <div class="saas-modal-footer">
            <button class="saas-btn" onclick="closeModal()">Cancelar</button>
            <button class="saas-btn primary" id="btnSalvarChamado" onclick="salvarChamado()">Salvar Chamado</button>
        </div>
    </div>
</div>

<script>
    const API_CHAMADOS = 'api/chamados.php?entity=chamados';

    // Utilitário p/ formatar status
    function renderStatus(status) {
        let classe = 'warning';
        let texto = status || '-';
        if (status === 'FECHADO') classe = 'secondary';
        if (status === 'EM_ATENDIMENTO') classe = 'success';
        if (status === 'ABERTO') classe = 'info';
        return `<span class="saas-badge ${classe}">${texto}</span>`;
    }

    // Utilitário p/ formatar prioridade
    function renderPrioridade(pri) {
        let classe = 'warning';
        let texto = pri || '-';
        if (pri === 'BAIXA') classe = 'secondary';
        if (pri === 'ALTA' || pri === 'URGENTE') classe = 'danger';
        if (pri === 'MEDIA') classe = 'info';
        return `<span class="saas-badge ${classe}">${texto}</span>`;
    }

    async function loadChamados() {
        const tbody = document.getElementById('chamadosTbody');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 40px;">Carregando...</td></tr>';

        const status = document.getElementById('filtroStatus').value;
        const prioridade = document.getElementById('filtroPrioridade').value;
        const texto = document.getElementById('filtroResponsavel').value; // Usado no campo texto provisoriamente

        // Montar query params
        let url = API_CHAMADOS;
        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (prioridade) url += `&prioridade=${encodeURIComponent(prioridade)}`;
        if (texto) url += `&texto=${encodeURIComponent(texto)}`;

        try {
            const res = await fetch(url);
            const json = await res.json();

            if (!res.ok || !json.sucesso) {
                tbody.innerHTML = `<tr><td colspan="8" style="color:red; text-align:center;">Erro: ${json.msg || json.error || 'Erro desconhecido'}</td></tr>`;
                return;
            }

            const dados = json.dados || [];

            if (dados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 40px; color: var(--saas-muted);">Nenhum chamado encontrado.</td></tr>';
                return;
            }

            let html = '';
            for (const c of dados) {
                html += `
                    <tr>
                        <td><strong>${c.COD_PUBLICO || c.ID_CHAMADO}</strong></td>
                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${c.TITULO || '-'}">${c.TITULO || '-'}</td>
                        <td>${renderStatus(c.STATUS)}</td>
                        <td>${renderPrioridade(c.PRIORIDADE)}</td>
                        <td>${c.SOLICITANTE_NOME || c.EMAIL_SOLICITANTE || '—'}</td>
                        <td>${c.RESPONSAVEL_NOME || '—'}</td>
                        <td>${c.CRIADO_EM || '—'}</td>
                        <td style="text-align: right;">
                            <button class="saas-btn" style="padding: 4px 10px;" onclick="verChamado(${c.ID_CHAMADO})">
                                Ver <i class="bi bi-arrow-right-short"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }
            tbody.innerHTML = html;

        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="8" style="color:red; text-align:center;">Falha de comunicação: ${error.message}</td></tr>`;
        }
    }

    function openModal(mode, data = null) {
        document.getElementById('chamadosModal').classList.add('show');
        const isEdit = (mode === 'edit');

        document.getElementById('modalTitle').textContent = isEdit ? 'Detalhes do Chamado' : 'Novo Chamado';
        document.getElementById('formChamadoId').value = isEdit ? data.ID_CHAMADO : '';
        document.getElementById('formTitulo').value = isEdit ? data.TITULO : '';
        document.getElementById('formDescricao').value = isEdit ? data.DESCRICAO : '';
        document.getElementById('formStatus').value = isEdit ? data.STATUS : 'ABERTO';
        document.getElementById('formPrioridade').value = isEdit ? data.PRIORIDADE : 'MEDIA';
        document.getElementById('formCategoria').value = isEdit ? data.CATEGORIA : '';
        document.getElementById('formEmail').value = isEdit ? data.EMAIL_SOLICITANTE : '';

        // Bloqueia campos no modo edição simplificado por enquanto
        document.getElementById('formTitulo').readOnly = isEdit;
        document.getElementById('formDescricao').readOnly = isEdit;
        document.getElementById('formCategoria').readOnly = isEdit;
        document.getElementById('formEmail').readOnly = isEdit;
        document.getElementById('formPrioridade').disabled = isEdit;

        // Habilita status em modo edição
        document.getElementById('formStatus').disabled = !isEdit;

        if (isEdit) {
            document.getElementById('btnSalvarChamado').textContent = 'Atualizar Status';
            document.getElementById('btnSalvarChamado').onclick = atualizarStatusChamado;
        } else {
            document.getElementById('btnSalvarChamado').textContent = 'Criar Chamado';
            document.getElementById('btnSalvarChamado').onclick = salvarChamado;
        }
    }

    function closeModal() {
        document.getElementById('chamadosModal').classList.remove('show');
    }

    async function verChamado(id) {
        try {
            const res = await fetch(`${API_CHAMADOS}&id_chamado=${id}`);
            const json = await res.json();
            if (json.sucesso && json.dados && json.dados.length > 0) {
                openModal('edit', json.dados[0]);
            } else {
                alert("Erro ao buscar detalhes: " + (json.msg || "Chamado não encontrado"));
            }
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    }

    async function atualizarStatusChamado() {
        const id = document.getElementById('formChamadoId').value;
        const status = document.getElementById('formStatus').value;
        const btn = document.getElementById('btnSalvarChamado');

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

        try {
            const body = {
                acao: 'STATUS',
                id_chamado: parseInt(id),
                status: status,
                atualizado_por: 'USUARIO_ATUAL'
            };

            const res = await fetch(API_CHAMADOS, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const json = await res.json();
            if (json.sucesso) {
                closeModal();
                loadChamados();
            } else {
                alert('Erro: ' + json.msg);
            }
        } catch (error) {
            alert('Falha: ' + error.message);
        } finally {
            btn.disabled = false;
        }
    }

    async function salvarChamado() {
        const btn = document.getElementById('btnSalvarChamado');
        const titulo = document.getElementById('formTitulo').value.trim();
        const desc = document.getElementById('formDescricao').value.trim();
        const pri = document.getElementById('formPrioridade').value;
        const cat = document.getElementById('formCategoria').value.trim();
        const email = document.getElementById('formEmail').value.trim();

        if (!titulo) {
            alert('Titulo é obrigatório!');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';

        const body = {
            titulo: titulo,
            descricao: desc,
            prioridade: pri,
            categoria: cat,
            email_solicitante: email,
            tipo_solicitante: 'I',
            criado_por: 'WEB'
        };

        try {
            const res = await fetch(API_CHAMADOS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const json = await res.json();

            if (json.sucesso) {
                closeModal();
                loadChamados();
            } else {
                alert('Erro ao criar: ' + (json.msg || json.error || 'Falha no servidor'));
            }
        } catch (error) {
            alert('Falha na comunicação: ' + error.message);
        } finally {
            btn.disabled = false;
        }
    }

    // Inicialização
    document.addEventListener("DOMContentLoaded", () => {
        loadChamados();
    });
</script>