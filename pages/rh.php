<?php
// pages/rh.php
?>
<div class="wrap">
    <div class="saas-page-head"
        style="background: linear-gradient(135deg, rgba(236, 72, 153, .10), rgba(236, 72, 153, .04)); border-color: rgba(236, 72, 153, 0.2);">
        <div>
            <h2 class="saas-title" style="color: #be185d;"><i class="bi bi-person-hearts"></i> Departamento Pessoal (RH)
            </h2>
            <p class="saas-subtitle">Solicitações de Férias, Atestados, Holerites e Mural de Avisos</p>
        </div>
        <div class="actions">
            <button class="saas-btn" onclick="initRH()"><i class="bi bi-arrow-clockwise"></i> Atualizar Dados</button>
            <button class="saas-btn primary" onclick="openRhModal()"
                style="background: #be185d; border-color: #be185d; color: white;">
                <i class="bi bi-plus-lg"></i> Nova Solicitação
            </button>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- Esquerda: Minhas Solicitações -->
        <div class="col-lg-8">
            <div class="saas-card h-100 p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0"><i class="bi bi-inbox-fill text-muted me-2"></i> Minhas Solicitações</h5>
                </div>
                <div class="p-4" style="min-height: 400px;">
                    <div class="table-responsive">
                        <table class="saas-table text-nowrap">
                            <thead>
                                <tr>
                                    <th>Cód.</th>
                                    <th>Tipo</th>
                                    <th>Solicitante</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="rhSolicitacoesBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm"></div> Carregando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Direita: Mural de Avisos -->
        <div class="col-lg-4">
            <div class="saas-card h-100 p-0" style="background: rgba(236, 72, 153, .02);">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0"><i class="bi bi-megaphone-fill text-danger me-2"></i> Mural de Avisos</h5>
                </div>
                <div class="p-3" id="rhAvisosList" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                    <!-- Avisos renderizados aqui -->
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Nova Solicitação -->
<div class="saas-modal-backdrop" id="rhModal">
    <div class="saas-modal" style="max-width: 600px;">
        <div class="saas-modal-header"
            style="background: linear-gradient(135deg, rgba(236, 72, 153, .10), transparent);">
            <h3 id="rhModalTitle"><i class="bi bi-envelope-paper"></i> Enviar Solicitação ao RH</h3>
            <button class="saas-btn" style="padding: 4px 8px;" onclick="closeRhModal()"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="saas-modal-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="saas-kicker d-block mb-1">Tipo de Solicitação</label>
                    <select id="formRhTipo" class="saas-select">
                        <option value="FERIAS">Férias / Desconto Banco de Horas</option>
                        <option value="ATESTADO">Envio de Atestado / Receita</option>
                        <option value="HOLERITE">2ª Via de Holerite</option>
                        <option value="BENEFICIO">Dúvida sobre Benefícios</option>
                        <option value="OUTROS" selected>Outros / Geral</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="saas-kicker d-block mb-1">Descrição / Detalhes</label>
                    <textarea id="formRhDesc" class="saas-textarea" rows="5"
                        placeholder="Explique os detalhes para o setor de departamento pessoal..."></textarea>
                </div>
            </div>
            <div class="mt-3 p-3 rounded"
                style="background: rgba(17,24,39,.04); font-size: 0.85rem; color: var(--saas-muted);">
                <i class="bi bi-info-circle"></i> As solicitações são encaminhadas diretamente para a caixa de entrada
                do RH e o status será atualizado aqui no seu painel.
            </div>
        </div>
        <div class="saas-modal-footer">
            <button class="saas-btn" onclick="closeRhModal()">Cancelar</button>
            <button class="saas-btn primary" style="background: #be185d; border-color: #be185d; color: white;"
                id="btnSalvarSolicitacao" onclick="enviarSolicitacao()"><i class="bi bi-send"></i> Enviar
                Solicitação</button>
        </div>
    </div>
</div>

<style>
    /* Estilos Especiais RH */
    .rh-aviso-card {
        background: var(--saas-card);
        border: 1px solid var(--saas-border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, .02);
    }

    .rh-aviso-card.importante {
        border-left: 4px solid #dc3545;
    }

    .rh-aviso-title {
        font-weight: 800;
        font-size: 1rem;
        margin-bottom: 6px;
        color: var(--saas-text);
    }

    .rh-aviso-msg {
        color: var(--saas-muted);
        font-size: 0.9rem;
        line-height: 1.4;
        margin-bottom: 10px;
    }

    .rh-tipo-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 6px;
        background: rgba(17, 24, 39, .05);
    }

    html[data-theme="dark"] .rh-tipo-badge {
        background: rgba(255, 255, 255, .1);
    }
</style>

<script>
    const API_RH = 'api/rh.php';

    async function initRH() {
        loadAvisos();
        loadSolicitacoes();
    }

    // Carrega Mural
    async function loadAvisos() {
        try {
            const res = await fetch(API_RH + '?acao=AVISOS');
            const json = await res.json();
            const el = document.getElementById('rhAvisosList');

            if (json.sucesso) {
                if (json.dados.length === 0) {
                    el.innerHTML = '<div class="text-center py-4 text-muted">Ainda não há avisos.</div>';
                    return;
                }

                el.innerHTML = '';
                json.dados.forEach(aviso => {
                    const dt = aviso.DATA.split('-').reverse().join('/');
                    const bge = aviso.IMPORTANTE ? 'importante' : '';
                    const icon = aviso.IMPORTANTE ? '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>' : '';

                    el.innerHTML += `
                        <div class="rh-aviso-card ${bge}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="saas-kicker d-block" style="font-size: 0.65rem;"><i class="bi bi-calendar-event"></i> ${dt}</span>
                            </div>
                            <div class="rh-aviso-title">${icon}${aviso.TITULO}</div>
                            <div class="rh-aviso-msg">${aviso.MENSAGEM}</div>
                            <div class="text-end" style="font-size: 0.70rem; color: var(--saas-muted);">Postado pelo Adm</div>
                        </div>
                    `;
                });
            }
        } catch (e) { console.error('Erro Mural:', e); }
    }

    // Carrega Tabela
    async function loadSolicitacoes() {
        try {
            const res = await fetch(API_RH + '?acao=MINHAS_SOLICITACOES');
            const json = await res.json();
            const tbody = document.getElementById('rhSolicitacoesBody');

            if (json.sucesso) {
                if (json.dados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Você não possui solicitações ativas.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                json.dados.forEach(req => {
                    const dt = req.DATA_CRIACAO.split('-').reverse().join('/');

                    let bgStatus = 'secondary';
                    if (req.STATUS === 'APROVADO' || req.STATUS === 'CONCLUIDO') bgStatus = 'success';
                    if (req.STATUS === 'PENDENTE') bgStatus = 'warning';
                    if (req.STATUS === 'RECUSADO') bgStatus = 'danger';

                    let iconTipo = 'bi-file-text';
                    if (req.TIPO === 'FERIAS') iconTipo = 'bi-airplane';
                    if (req.TIPO === 'ATESTADO') iconTipo = 'bi-bandaid';
                    if (req.TIPO === 'HOLERITE') iconTipo = 'bi-cash-coin';

                    tbody.innerHTML += `
                        <tr>
                            <td class="fw-bold text-muted">#${req.ID}</td>
                            <td>
                                <div class="rh-tipo-badge">
                                    <i class="bi ${iconTipo}"></i> ${req.TIPO}
                                </div>
                                <div class="text-muted text-truncate ms-1 mt-1" style="font-size: 0.8rem; max-width: 300px;">
                                    ${req.DESCRICAO}
                                </div>
                            </td>
                            <td><span class="fw-bold">${req.SOLICITANTE}</span></td>
                            <td>${dt}</td>
                            <td><span class="saas-badge ${bgStatus}">${req.STATUS}</span></td>
                        </tr>
                    `;
                });
            }
        } catch (e) { console.error('Erro Table:', e); }
    }

    // Modal
    function openRhModal() {
        document.getElementById('rhModal').classList.add('show');
        document.getElementById('formRhTipo').value = 'OUTROS';
        document.getElementById('formRhDesc').value = '';
    }

    function closeRhModal() {
        document.getElementById('rhModal').classList.remove('show');
    }

    async function enviarSolicitacao() {
        const tipo = document.getElementById('formRhTipo').value;
        const desc = document.getElementById('formRhDesc').value;
        if (!desc) { alert('Você precisa escrever uma descrição.'); return; }

        const btn = document.getElementById('btnSalvarSolicitacao');
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Enviando...';

        try {
            const res = await fetch(API_RH, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo, descricao: desc })
            });
            const j = await res.json();
            if (j.sucesso) {
                closeRhModal();
                loadSolicitacoes(); // Recarrega table
            } else {
                alert('Erro: ' + j.msg);
            }
        } catch (e) {
            alert('Falha interna: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Enviar Solicitação';
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initRH();
    });
</script>