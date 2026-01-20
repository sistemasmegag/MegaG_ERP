<?php 
// ARQUIVO: pages/tarefas.php
require '../check_session.php'; 
$paginaAtual = 'tarefas'; 
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<style>
/* ===== Clean SaaS (escopado pra Tarefas) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(13,110,253,.04));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(13,110,253,.14), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(13,110,253,.26), transparent 60%);
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

/* Botão principal SaaS */
.saas-primary-btn{
    border-radius: 14px;
    font-weight: 900;
    letter-spacing: .01em;
    box-shadow: 0 10px 18px rgba(13,110,253,.18);
}

/* Layout colunas (kanban) */
.saas-board{
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}
@media (max-width: 992px){
    .saas-board{ grid-template-columns: 1fr; }
}

/* Coluna */
.saas-col{
    background: rgba(255,255,255,.55);
    border: 1px solid var(--saas-border);
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    backdrop-filter: blur(10px);
    overflow:hidden;
    min-height: 420px;
}
html[data-theme="dark"] .saas-col{
    background: rgba(255,255,255,.06);
}
.saas-col-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding: 14px 14px;
    border-bottom: 1px solid var(--saas-border);
}
.saas-col-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
    font-size: 12px;
    margin:0;
    color: var(--saas-text);
}
.saas-dot{
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display:inline-block;
}
.saas-col-body{
    padding: 14px;
}

/* Badge contador */
.saas-count{
    border-radius: 999px;
    padding: 6px 10px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
    color: var(--saas-text);
}
html[data-theme="dark"] .saas-count{
    background: rgba(255,255,255,.06);
}

/* Card tarefa (SaaS) */
.task-card{
    border: 1px solid var(--saas-border);
    border-radius: 16px;
    background: var(--saas-surface);
    box-shadow: var(--saas-shadow-soft);
    overflow:hidden;
    transition: .16s ease;
}
.task-card:hover{
    transform: translateY(-1px);
    box-shadow: var(--saas-shadow);
}
.task-card .task-body{
    padding: 14px;
}
.task-meta{
    display:flex;
    justify-content:space-between;
    gap: 12px;
    margin-bottom: 10px;
}
.task-date{
    font-size: 12px;
    color: var(--saas-muted);
    font-weight: 800;
}
.task-owner{
    font-size: 12px;
    color: rgba(13,110,253,.90);
    font-weight: 900;
}
.task-title{
    margin:0 0 6px;
    font-weight: 900;
    letter-spacing: -.01em;
    color: var(--saas-text);
    font-size: 14px;
}
.task-desc{
    margin:0 0 10px;
    color: var(--saas-muted);
    font-size: 13px;
    line-height: 1.35;
}

/* Ações */
.task-actions{
    display:flex;
    justify-content:flex-end;
    gap: 8px;
    padding-top: 10px;
    border-top: 1px solid var(--saas-border);
}
.task-actions .btn{
    border-radius: 12px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
}
html[data-theme="dark"] .task-actions .btn{
    background: rgba(255,255,255,.06);
}
.task-actions .btn:hover{
    border-color: rgba(13,110,253,.22);
    transform: translateY(-1px);
}

/* Modal SaaS */
#modalTarefa .modal-content{
    border-radius: 18px;
    border: 1px solid var(--saas-border);
    background: var(--saas-surface);
    color: var(--saas-text);
    box-shadow: var(--saas-shadow);
}
#modalTarefa .modal-header{
    border-bottom: 1px solid var(--saas-border);
}
#modalTarefa .modal-footer,
#modalTarefa .modal-body{
    border-top: 0;
}
#modalTarefa .form-label{
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
    color: var(--saas-muted);
}
#modalTarefa .form-control{
    border-radius: 14px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
    color: var(--saas-text);
}
html[data-theme="dark"] #modalTarefa .form-control{
    background: rgba(255,255,255,.06);
}
#modalTarefa .form-control:focus{
    border-color: rgba(13,110,253,.45);
    box-shadow: 0 0 0 .22rem var(--ring);
    background: var(--saas-surface);
}

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }
</style>

<main class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
            <button class="mobile-toggle me-3" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
            <h4 class="m-0 fw-bold text-dark">Tarefas</h4>
        </div>

        <!-- Header SaaS -->
        <div class="saas-head mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
                <div>
                    <h3 class="saas-title">Minha Agenda</h3>
                    <p class="saas-subtitle">Gerencie suas entregas e pendências em um fluxo Kanban.</p>
                </div>
                <button class="btn btn-primary saas-primary-btn" data-bs-toggle="modal" data-bs-target="#modalTarefa">
                    <i class="bi bi-plus-lg me-2"></i> Nova Tarefa
                </button>
            </div>
        </div>

        <!-- Board -->
        <div class="saas-board">
            
            <div class="saas-col">
                <div class="saas-col-head">
                    <h6 class="saas-col-title">
                        <span class="saas-dot" style="background:#6c757d;"></span>
                        A Fazer
                    </h6>
                    <span class="saas-count" id="count-todo">0</span>
                </div>
                <div id="coluna-todo" class="saas-col-body d-flex flex-column gap-3">
                    <!-- cards -->
                </div>
            </div>

            <div class="saas-col">
                <div class="saas-col-head">
                    <h6 class="saas-col-title">
                        <span class="saas-dot" style="background:#ffc107;"></span>
                        Em Andamento
                    </h6>
                    <span class="saas-count" id="count-doing">0</span>
                </div>
                <div id="coluna-doing" class="saas-col-body d-flex flex-column gap-3"></div>
            </div>

            <div class="saas-col">
                <div class="saas-col-head">
                    <h6 class="saas-col-title">
                        <span class="saas-dot" style="background:#198754;"></span>
                        Concluído
                    </h6>
                    <span class="saas-count" id="count-done">0</span>
                </div>
                <div id="coluna-done" class="saas-col-body d-flex flex-column gap-3"></div>
            </div>

        </div>
    </div>
</main>

<div class="modal fade" id="modalTarefa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="letter-spacing:-.01em;">Criar Nova Tarefa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTarefa">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" id="titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea id="descricao" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Entrega</label>
                        <input type="date" id="data" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 saas-primary-btn">Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Configura a URL da API (Voltando uma pasta pois estamos em /pages)
    const API_URL = '../api_tarefas.php';

    async function carregarTarefas() {
        try {
            const resp = await fetch(API_URL);
            const json = await resp.json();
            
            if(!json.sucesso) {
                console.error("Erro API:", json.erro);
                return;
            }

            // Limpa as colunas antes de preencher
            document.getElementById('coluna-todo').innerHTML = '';
            document.getElementById('coluna-doing').innerHTML = '';
            document.getElementById('coluna-done').innerHTML = '';
            
            let cTodo=0, cDoing=0, cDone=0;

            // Distribui os cards nas colunas corretas
            json.dados.forEach(t => {
                const card = criarCardHTML(t);
                if(t.STATUS === 'TODO') { 
                    document.getElementById('coluna-todo').innerHTML += card; 
                    cTodo++;
                } else if(t.STATUS === 'DOING') { 
                    document.getElementById('coluna-doing').innerHTML += card; 
                    cDoing++;
                } else { 
                    document.getElementById('coluna-done').innerHTML += card; 
                    cDone++;
                }
            });

            // Atualiza os contadores do cabeçalho
            document.getElementById('count-todo').innerText = cTodo;
            document.getElementById('count-doing').innerText = cDoing;
            document.getElementById('count-done').innerText = cDone;

        } catch (e) {
            console.error("Erro ao carregar tarefas:", e);
        }
    }

    function criarCardHTML(t) {
        // Lógica para destacar data se estiver atrasada
        const hoje = new Date().toISOString().split('T')[0];
        let corData = 'task-date';
        if(t.DATA_ENTREGA < hoje && t.STATUS !== 'DONE') corData = 'task-date text-danger fw-bold';

        // Botões de ação dependendo do status
        let botoes = '';
        if(t.STATUS === 'TODO') {
            botoes = `<button onclick="mover(${t.ID}, 'DOING')" class="btn btn-sm" title="Iniciar"><i class="bi bi-play-fill text-warning"></i></button>`;
        } else if (t.STATUS === 'DOING') {
            botoes = `
                <button onclick="mover(${t.ID}, 'TODO')" class="btn btn-sm" title="Voltar"><i class="bi bi-arrow-left"></i></button>
                <button onclick="mover(${t.ID}, 'DONE')" class="btn btn-sm" title="Concluir"><i class="bi bi-check-lg text-success"></i></button>
            `;
        } else {
            botoes = `<button onclick="deletar(${t.ID})" class="btn btn-sm" title="Arquivar"><i class="bi bi-trash text-danger"></i></button>`;
        }

        // Formata data BR visualmente
        const dataBR = t.DATA_ENTREGA.split('-').reverse().join('/');

        return `
            <div class="task-card">
                <div class="task-body">
                    <div class="task-meta">
                        <small class="${corData}"><i class="bi bi-calendar-event me-1"></i> ${dataBR}</small>
                        <small class="task-owner">${t.RESPONSAVEL}</small>
                    </div>

                    <h6 class="task-title">${t.TITULO}</h6>
                    <p class="task-desc">${t.DESCRICAO || ''}</p>

                    <div class="task-actions">
                        ${botoes}
                    </div>
                </div>
            </div>
        `;
    }

    async function mover(id, novoStatus) {
        await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify({ id, novoStatus })
        });
        carregarTarefas();
    }

    async function deletar(id) {
        if(!confirm('Remover esta tarefa?')) return;
        await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
        carregarTarefas();
    }

    // Evento de Salvar Nova Tarefa
    document.getElementById('formTarefa').addEventListener('submit', async (e) => {
        e.preventDefault();
        const titulo = document.getElementById('titulo').value;
        const descricao = document.getElementById('descricao').value;
        const data = document.getElementById('data').value;

        const resp = await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify({ titulo, descricao, data })
        });
        
        const json = await resp.json();
        
        if(json.sucesso) {
            // Fecha modal e recarrega
            bootstrap.Modal.getInstance(document.getElementById('modalTarefa')).hide();
            document.getElementById('formTarefa').reset();
            carregarTarefas();
        } else {
            alert('Erro ao salvar: ' + json.erro);
        }
    });

    window.onload = carregarTarefas;
</script>

<?php include '../includes/footer.php'; ?>
