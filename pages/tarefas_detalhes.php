<?php
// pages/tarefas_detalhes.php
// URL: index.php?page=tarefas_detalhes&task_id=1&space_id=3&list_id=2

$task_id  = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
$space_id = isset($_GET['space_id']) ? (int)$_GET['space_id'] : 0;
$list_id  = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;

if ($task_id <= 0) {
    echo "<div class='container-fluid p-4'><div class='alert alert-danger'>Parâmetro <b>task_id</b> obrigatório.</div></div>";
    return;
}
?>

<style>
    /* ========= Clean SaaS (mesmo padrão do projeto) ========= */
    :root {
        --saas-bg: #f6f8fb;
        --saas-card: #ffffff;
        --saas-border: rgba(17, 24, 39, .10);
        --saas-text: #111827;
        --saas-muted: rgba(17, 24, 39, .60);
        --saas-shadow: 0 12px 30px rgba(17, 24, 39, .08);
        --saas-shadow-soft: 0 10px 30px rgba(17, 24, 39, .06);
        --saas-ring: rgba(13, 110, 253, .12);
        --saas-primary: #0d6efd;
        --saas-danger: #dc3545;
        --saas-success: #198754;
    }

    html[data-theme="dark"] {
        --saas-bg: #1f1f1f;
        --saas-card: rgba(255, 255, 255, .05);
        --saas-border: rgba(255, 255, 255, .10);
        --saas-text: rgba(255, 255, 255, .92);
        --saas-muted: rgba(255, 255, 255, .65);
        --saas-shadow: 0 16px 40px rgba(0, 0, 0, .35);
        --saas-shadow-soft: 0 14px 40px rgba(0, 0, 0, .25);
        --saas-ring: rgba(13, 110, 253, .20);
    }

    /* Fundo e tipografia só da área principal */
    .main-content {
        background:
            radial-gradient(1200px 600px at 15% 10%, rgba(13, 110, 253, .14), transparent 60%),
            radial-gradient(1000px 500px at 85% 25%, rgba(25, 135, 84, .10), transparent 55%),
            var(--saas-bg);
        color: var(--saas-text);
        min-height: 100vh;
    }

    /* Cabeçalho */
    .saas-page-head {
        border: 1px solid var(--saas-border);
        background: linear-gradient(135deg, rgba(13, 110, 253, .10), rgba(13, 110, 253, .04));
        border-radius: 18px;
        box-shadow: var(--saas-shadow-soft);
        padding: 18px 18px;
        overflow: hidden;
        position: relative;
    }

    html[data-theme="dark"] .saas-page-head {
        background: linear-gradient(135deg, rgba(13, 110, 253, .14), rgba(255, 255, 255, .02));
    }

    .saas-page-head:before {
        content: "";
        position: absolute;
        inset: -130px -190px auto auto;
        width: 360px;
        height: 360px;
        background: radial-gradient(circle at 30% 30%, rgba(13, 110, 253, .30), transparent 60%);
        filter: blur(6px);
        transform: rotate(10deg);
        pointer-events: none;
    }

    .saas-title {
        font-weight: 900;
        letter-spacing: -.02em;
        margin: 0;
    }

    .saas-subtitle {
        margin: 6px 0 0;
        color: var(--saas-muted);
        font-size: 14px;
    }

    /* Botões topo */
    .saas-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
    }

    .saas-pill {
        border: 1px solid var(--saas-border);
        background: rgba(255, 255, 255, .55);
        color: var(--saas-muted);
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(10px);
    }

    html[data-theme="dark"] .saas-pill {
        background: rgba(255, 255, 255, .03);
    }

    .saas-pill:hover {
        color: var(--saas-text);
        border-color: rgba(13, 110, 253, .35);
    }

    .btn-saas {
        border: 1px solid rgba(13, 110, 253, .25);
        background: rgba(13, 110, 253, .10);
        color: #0b5ed7;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 900;
    }

    .btn-saas:hover {
        background: rgba(13, 110, 253, .16);
    }

    .btn-saas-danger {
        border: 1px solid rgba(220, 53, 69, .25);
        background: rgba(220, 53, 69, .10);
        color: #b02a37;
    }

    .btn-saas-danger:hover {
        background: rgba(220, 53, 69, .16);
    }

    /* Cards */
    .saas-card {
        background: var(--saas-card) !important;
        border: 1px solid var(--saas-border) !important;
        border-radius: 18px !important;
        box-shadow: var(--saas-shadow) !important;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .saas-card-pad {
        padding: 16px;
    }

    .text-muted {
        color: var(--saas-muted) !important;
    }

    .text-dark {
        color: var(--saas-text) !important;
    }

    /* Form */
    .k-label {
        font-size: 10px;
        letter-spacing: .12em;
        font-weight: 900;
        color: var(--saas-muted);
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .k-input,
    .k-select,
    .k-textarea {
        width: 100%;
        border-radius: 12px;
        border: 1px solid var(--saas-border);
        background: rgba(255, 255, 255, .65);
        padding: 10px 12px;
        outline: none;
        transition: .15s ease;
    }

    html[data-theme="dark"] .k-input,
    html[data-theme="dark"] .k-select,
    html[data-theme="dark"] .k-textarea {
        background: rgba(255, 255, 255, .03);
    }

    .k-input:focus,
    .k-select:focus,
    .k-textarea:focus {
        border-color: rgba(13, 110, 253, .45);
        box-shadow: 0 0 0 6px var(--saas-ring);
    }

    .k-textarea {
        min-height: 120px;
        resize: vertical;
    }

    /* Seções comentários/anexos */
    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        margin-bottom: 12px;
        border-bottom: 1px solid var(--saas-border);
    }

    .section-head h6 {
        margin: 0;
        font-weight: 900;
    }

    .badge-soft {
        border: 1px solid var(--saas-border);
        background: rgba(17, 24, 39, .04);
        color: var(--saas-muted);
        font-weight: 900;
        font-size: 12px;
        border-radius: 999px;
        padding: 4px 10px;
    }

    html[data-theme="dark"] .badge-soft {
        background: rgba(255, 255, 255, .06);
    }

    .item {
        border: 1px solid var(--saas-border);
        border-radius: 14px;
        padding: 12px;
        background: rgba(255, 255, 255, .55);
    }

    html[data-theme="dark"] .item {
        background: rgba(255, 255, 255, .03);
    }

    .item+.item {
        margin-top: 10px;
    }

    .item-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .item-title {
        font-weight: 900;
        margin: 0;
    }

    .item-sub {
        margin: 4px 0 0;
        font-size: 12px;
        color: var(--saas-muted);
    }

    .item-actions {
        display: flex;
        gap: 8px;
    }

    .btn-mini {
        border-radius: 999px;
        padding: 6px 10px;
        font-weight: 900;
        font-size: 12px;
        border: 1px solid var(--saas-border);
        background: rgba(255, 255, 255, .65);
    }

    html[data-theme="dark"] .btn-mini {
        background: rgba(255, 255, 255, .03);
    }

    .btn-mini:hover {
        border-color: rgba(13, 110, 253, .35);
    }

    .btn-mini.danger {
        border-color: rgba(220, 53, 69, .25);
        background: rgba(220, 53, 69, .10);
        color: #b02a37;
    }

    .btn-mini.danger:hover {
        background: rgba(220, 53, 69, .16);
    }

    .hr-soft {
        border-top: 1px solid var(--saas-border);
        margin: 14px 0;
    }

    .alert-soft {
        border-radius: 14px;
        border: 1px solid rgba(220, 53, 69, .20);
        background: rgba(220, 53, 69, .08);
        color: #b02a37;
        padding: 10px 12px;
        font-weight: 800;
        font-size: 13px;
    }

    /* ===== Tags como chips ===== */
    .tags-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--saas-border);
        background: rgba(13, 110, 253, .08);
        color: var(--saas-text);
        font-weight: 800;
        font-size: 12px;
        line-height: 1;
    }

    html[data-theme="dark"] .tag-chip {
        background: rgba(13, 110, 253, .18);
    }

    .tag-chip .dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: rgba(13, 110, 253, .55);
    }

    /* ===== Prioridade com cor ===== */
    .prio-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--saas-border);
        font-weight: 900;
        font-size: 12px;
        line-height: 1;
        user-select: none;
    }

    .prio-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
    }

    /* Tons (light) */
    .prio-low {
        background: rgba(25, 135, 84, .10);
    }

    .prio-med {
        background: rgba(255, 193, 7, .14);
    }

    .prio-high {
        background: rgba(220, 53, 69, .12);
    }

    .prio-low .prio-dot {
        background: rgba(25, 135, 84, .70);
    }

    .prio-med .prio-dot {
        background: rgba(255, 193, 7, .85);
    }

    .prio-high .prio-dot {
        background: rgba(220, 53, 69, .75);
    }

    /* Dark mode */
    html[data-theme="dark"] .prio-low {
        background: rgba(25, 135, 84, .18);
    }

    html[data-theme="dark"] .prio-med {
        background: rgba(255, 193, 7, .20);
    }

    html[data-theme="dark"] .prio-high {
        background: rgba(220, 53, 69, .22);
    }

    /* Borda no select conforme prioridade */
    .select-prio-low {
        box-shadow: 0 0 0 4px rgba(25, 135, 84, .10);
    }

    .select-prio-med {
        box-shadow: 0 0 0 4px rgba(255, 193, 7, .14);
    }

    .select-prio-high {
        box-shadow: 0 0 0 4px rgba(220, 53, 69, .12);
    }

    html[data-theme="dark"] .select-prio-low {
        box-shadow: 0 0 0 4px rgba(25, 135, 84, .16);
    }

    html[data-theme="dark"] .select-prio-med {
        box-shadow: 0 0 0 4px rgba(255, 193, 7, .18);
    }

    html[data-theme="dark"] .select-prio-high {
        box-shadow: 0 0 0 4px rgba(220, 53, 69, .20);
    }
</style>

<div class="container-fluid py-4">
    <div class="saas-page-head mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-lg-7">
                <h3 class="saas-title m-0">Detalhes da Task #<span id="hTaskId"><?= (int)$task_id ?></span></h3>
                <div class="saas-subtitle" id="hSubtitle">Carregando...</div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="saas-actions">
                    <button class="saas-pill" id="btnTheme" type="button">🌙 Tema</button>

                    <a class="saas-pill" id="btnVoltar" href="index.php?page=tarefas<?= $space_id ? '&space_id=' . (int)$space_id : '' ?><?= $list_id ? '&list_id=' . (int)$list_id : '' ?>">← Voltar</a>

                    <button class="btn-saas" id="btnSalvar" type="button">Salvar</button>
                    <button class="btn-saas btn-saas-danger" id="btnExcluir" type="button">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <div id="topMsg" class="d-none"></div>

    <div class="row g-3">
        <!-- FORM PRINCIPAL -->
        <div class="col-12">
            <div class="saas-card saas-card-pad">
                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <div class="k-label">Título</div>
                        <input class="k-input" id="fTitulo" placeholder="Título da task" />
                    </div>
                    <div class="col-12 col-lg-3">
                        <div class="k-label">Status</div>
                        <select class="k-select" id="fStatus">
                            <option value="TODO">TODO</option>
                            <option value="DOING">DOING</option>
                            <option value="DONE">DONE</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="k-label">Prioridade</div>
                        <select class="k-select" id="fPrioridade">
                            <option value="LOW">LOW</option>
                            <option value="MED">MED</option>
                            <option value="HIGH">HIGH</option>
                        </select>
                        <div id="prioBadge" class="prio-badge prio-med">
                            <span class="prio-dot"></span>
                            <span id="prioBadgeText">MED</span>
                        </div>

                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="k-label">Responsável</div>
                        <input class="k-input" id="fResp" placeholder="Ex: Felipe" />
                    </div>
                    <div class="col-12 col-lg-3">
                        <div class="k-label">Entrega (YYYY-MM-DD)</div>
                        <input class="k-input" id="fEntrega" placeholder="2026-02-20" />
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="k-label">Tags</div>
                        <input class="k-input" id="fTags" placeholder="frontend,kanban" />
                        <div id="tagsChips" class="tags-chips"></div>
                    </div>

                    <div class="col-12">
                        <div class="k-label">Descrição</div>
                        <textarea class="k-textarea" id="fDesc" placeholder="Detalhes..."></textarea>
                    </div>

                    <div class="col-12">
                        <div class="text-muted" id="fMeta"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COMENTÁRIOS -->
        <div class="col-12 col-lg-6">
            <div class="saas-card saas-card-pad">
                <div class="section-head">
                    <div>
                        <h6>Comentários</h6>
                        <div class="text-muted" style="font-size:12px">Histórico da task</div>
                    </div>
                    <div class="badge-soft" id="badgeComments">0</div>
                </div>

                <div id="commentsErr" class="d-none"></div>
                <div id="commentsList"></div>

                <div class="hr-soft"></div>

                <div class="k-label">Novo comentário</div>
                <textarea class="k-textarea" id="cTexto" placeholder="Digite um comentário..."></textarea>

                <div class="row g-2 align-items-center mt-2">
                    <div class="col-12 col-md-7">
                        <input class="k-input" id="cUser" placeholder="Seu nome (ex: Felipe)" />
                    </div>
                    <div class="col-12 col-md-5 d-grid">
                        <button class="btn-saas" id="btnAddComment" type="button">Adicionar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ANEXOS -->
        <div class="col-12 col-lg-6">
            <div class="saas-card saas-card-pad">
                <div class="section-head">
                    <div>
                        <h6>Anexos</h6>
                        <div class="text-muted" style="font-size:12px">Arquivos vinculados à task</div>
                    </div>
                    <div class="badge-soft" id="badgeFiles">0</div>
                </div>

                <div id="filesErr" class="d-none"></div>
                <div id="filesList"></div>

                <div class="hr-soft"></div>

                <div class="k-label">Enviar arquivo</div>
                <input class="k-input" type="file" id="upFile" />

                <div class="row g-2 align-items-center mt-2">
                    <div class="col-12 col-md-7">
                        <input class="k-input" id="upUser" placeholder="Seu nome (ex: Felipe)" />
                    </div>
                    <div class="col-12 col-md-5 d-grid">
                        <button class="btn-saas" id="btnUpload" type="button">Enviar</button>
                    </div>
                </div>

                <div class="text-muted mt-2" style="font-size:12px">
                    Dica: após enviar, a lista de anexos atualiza automaticamente.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const API = 'api/tasks.php';

        const taskId = <?= (int)$task_id ?>;
        const spaceId = <?= (int)$space_id ?>;
        const listId = <?= (int)$list_id ?>;

        const $ = (id) => document.getElementById(id);

        function showTopMsg(msg, ok = true) {
            const el = $('topMsg');
            el.className = ok ? 'alert alert-success' : 'alert alert-danger';
            el.textContent = msg;
            el.classList.remove('d-none');
            setTimeout(() => el.classList.add('d-none'), 3000);
        }

        function themeInit() {
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            $('btnTheme').textContent = saved === 'dark' ? '☀️ Tema' : '🌙 Tema';
        }

        function themeToggle() {
            const cur = document.documentElement.getAttribute('data-theme') || 'light';
            const next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            $('btnTheme').textContent = next === 'dark' ? '☀️ Tema' : '🌙 Tema';
        }

        async function apiJson(url, opt) {
            const r = await fetch(url, opt);
            const j = await r.json().catch(() => null);
            if (!j) throw new Error('Resposta inválida do servidor.');
            if (!j.success) throw new Error(j.error || 'Erro.');
            return j.data;
        }

        function norm(v) {
            return (v ?? '').toString();
        }

        function pick(obj, a, b) {
            return obj[a] ?? obj[b];
        }

        async function loadTask() {
            const data = await apiJson(`${API}?entity=tasks&task_id=${taskId}`, {
                method: 'GET'
            });
            if (!data) throw new Error('Task não encontrada.');

            const id = pick(data, 'ID', 'id');
            const titulo = pick(data, 'TITULO', 'titulo') ?? '';
            const desc = pick(data, 'DESCRICAO', 'descricao') ?? '';
            const status = pick(data, 'STATUS', 'status') ?? 'TODO';
            const prio = pick(data, 'PRIORIDADE', 'prioridade') ?? 'MED';
            const resp = pick(data, 'RESPONSAVEL', 'responsavel') ?? '';
            const entrega = pick(data, 'DATA_ENTREGA', 'data_entrega') ?? '';
            const tags = pick(data, 'TAGS', 'tags') ?? '';
            const criadoPor = pick(data, 'CRIADO_POR', 'criado_por') ?? '';
            const criadoEm = pick(data, 'CRIADO_EM', 'criado_em') ?? '';

            $('hSubtitle').textContent = `Editando: ${titulo || '(sem título)'}`;
            $('fTitulo').value = titulo;
            $('fDesc').value = desc;
            $('fStatus').value = status;
            $('fPrioridade').value = prio;
            applyPrioUI(prio);
            $('fResp').value = resp;
            $('fTags').value = tags;
            renderTagsChips(tags);

            // normaliza entrega (pega YYYY-MM-DD)
            const entStr = entrega ? norm(entrega).substring(0, 10) : '';
            $('fEntrega').value = entStr;

            $('fMeta').textContent = (criadoPor || criadoEm) ?
                `Criado por ${criadoPor || '-'} em ${criadoEm || '-'}` :
                '';
        }

        async function saveTask() {
            const payload = {
                titulo: $('fTitulo').value.trim(),
                descricao: $('fDesc').value,
                prioridade: $('fPrioridade').value,
                tags: $('fTags').value.trim(),
                responsavel: $('fResp').value.trim(),
                data_entrega: $('fEntrega').value.trim(),
                user: ($('cUser').value.trim() || $('upUser').value.trim() || $('fResp').value.trim() || 'user')
            };

            await apiJson(`${API}?entity=tasks&task_id=${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                },
                body: JSON.stringify(payload)
            });

            showTopMsg('Salvo com sucesso.', true);
            await loadTask();
            await loadLists();
        }

        async function deleteTask() {
            const u = ($('cUser').value.trim() || $('upUser').value.trim() || $('fResp').value.trim() || 'user');
            if (!confirm(`Excluir task #${taskId}?`)) return;

            await apiJson(`${API}?entity=tasks&task_id=${taskId}&user=${encodeURIComponent(u)}`, {
                method: 'DELETE'
            });
            showTopMsg('Task excluída.', true);

            // volta para kanban
            window.location.href = `index.php?page=tarefas${spaceId ? `&space_id=${spaceId}`:''}${listId ? `&list_id=${listId}`:''}`;
        }

        function renderCommentItem(c) {
            const id = pick(c, 'ID', 'id');
            const comentario = pick(c, 'COMENTARIO', 'comentario') ?? '';
            const criadoPor = pick(c, 'CRIADO_POR', 'criado_por') ?? '';
            const criadoEm = pick(c, 'CRIADO_EM', 'criado_em') ?? '';

            const wrap = document.createElement('div');
            wrap.className = 'item';

            const top = document.createElement('div');
            top.className = 'item-top';

            const left = document.createElement('div');
            const title = document.createElement('p');
            title.className = 'item-title';
            title.textContent = comentario.length > 80 ? (comentario.substring(0, 80) + '…') : comentario;
            const sub = document.createElement('div');
            sub.className = 'item-sub';
            sub.textContent = `por ${criadoPor || '-'} • ${criadoEm || '-'}`;

            left.appendChild(title);
            left.appendChild(sub);

            const actions = document.createElement('div');
            actions.className = 'item-actions';

            const btn = document.createElement('button');
            btn.className = 'btn-mini danger';
            btn.textContent = 'Excluir';
            btn.addEventListener('click', async () => {
                const u = $('cUser').value.trim() || $('upUser').value.trim() || 'user';
                if (!confirm('Excluir comentário?')) return;

                try {
                    await apiJson(`${API}?entity=comments&comment_id=${id}&user=${encodeURIComponent(u)}`, {
                        method: 'DELETE'
                    });
                    showTopMsg('Comentário excluído.', true);
                    await loadComments();
                } catch (e) {
                    showTopMsg(e.message, false);
                }
            });

            actions.appendChild(btn);
            top.appendChild(left);
            top.appendChild(actions);

            wrap.appendChild(top);
            return wrap;
        }

        async function loadComments() {
            $('commentsErr').classList.add('d-none');
            $('commentsErr').innerHTML = '';

            const list = $('commentsList');
            list.innerHTML = '';

            try {
                const rows = await apiJson(`${API}?entity=comments&task_id=${taskId}`, {
                    method: 'GET'
                });
                $('badgeComments').textContent = rows.length;

                if (!rows.length) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted';
                    empty.style.fontSize = '13px';
                    empty.textContent = 'Nenhum comentário ainda.';
                    list.appendChild(empty);
                    return;
                }

                rows.forEach(r => list.appendChild(renderCommentItem(r)));
            } catch (e) {
                $('badgeComments').textContent = '0';
                $('commentsErr').className = 'alert-soft';
                $('commentsErr').textContent = 'Erro: ' + e.message;
                $('commentsErr').classList.remove('d-none');
            }
        }

        async function addComment() {
            const comentario = $('cTexto').value.trim();
            const user = $('cUser').value.trim();

            if (!comentario) return showTopMsg('Digite um comentário.', false);
            if (!user) return showTopMsg('Informe seu nome.', false);

            await apiJson(`${API}?entity=comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                },
                body: JSON.stringify({
                    task_id: taskId,
                    comentario,
                    criado_por: user
                })
            });

            $('cTexto').value = '';
            showTopMsg('Comentário adicionado.', true);
            await loadComments();
        }

        function renderFileItem(f) {
            const id = pick(f, 'ID', 'id');
            const name = pick(f, 'FILE_NAME', 'file_name') ?? 'arquivo';
            const mime = pick(f, 'MIME_TYPE', 'mime_type') ?? '';
            const size = pick(f, 'FILE_SIZE', 'file_size') ?? '';
            const criadoPor = pick(f, 'CRIADO_POR', 'criado_por') ?? '';
            const criadoEm = pick(f, 'CRIADO_EM', 'criado_em') ?? '';

            const wrap = document.createElement('div');
            wrap.className = 'item';

            const top = document.createElement('div');
            top.className = 'item-top';

            const left = document.createElement('div');
            const title = document.createElement('p');
            title.className = 'item-title';
            title.textContent = name;

            const sub = document.createElement('div');
            sub.className = 'item-sub';
            sub.textContent = `${mime || 'arquivo'} • ${size || '-'} bytes • por ${criadoPor || '-'} • ${criadoEm || '-'}`;

            left.appendChild(title);
            left.appendChild(sub);

            const actions = document.createElement('div');
            actions.className = 'item-actions';

            const btnDown = document.createElement('a');
            btnDown.className = 'btn-mini';
            btnDown.textContent = 'Baixar';
            btnDown.href = `${API}?entity=files&action=download&file_id=${id}`;
            btnDown.target = '_blank';

            const btnDel = document.createElement('button');
            btnDel.className = 'btn-mini danger';
            btnDel.textContent = 'Excluir';
            btnDel.addEventListener('click', async () => {
                const u = $('upUser').value.trim() || $('cUser').value.trim() || 'user';
                if (!confirm('Excluir anexo?')) return;

                try {
                    await apiJson(`${API}?entity=files&file_id=${id}&user=${encodeURIComponent(u)}`, {
                        method: 'DELETE'
                    });
                    showTopMsg('Anexo excluído.', true);
                    await loadFiles();
                } catch (e) {
                    showTopMsg(e.message, false);
                }
            });

            actions.appendChild(btnDown);
            actions.appendChild(btnDel);

            top.appendChild(left);
            top.appendChild(actions);

            wrap.appendChild(top);
            return wrap;
        }

        async function loadFiles() {
            $('filesErr').classList.add('d-none');
            $('filesErr').innerHTML = '';

            const list = $('filesList');
            list.innerHTML = '';

            try {
                const rows = await apiJson(`${API}?entity=files&task_id=${taskId}`, {
                    method: 'GET'
                });
                $('badgeFiles').textContent = rows.length;

                if (!rows.length) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted';
                    empty.style.fontSize = '13px';
                    empty.textContent = 'Nenhum anexo ainda.';
                    list.appendChild(empty);
                    return;
                }

                rows.forEach(r => list.appendChild(renderFileItem(r)));
            } catch (e) {
                $('badgeFiles').textContent = '0';
                $('filesErr').className = 'alert-soft';
                $('filesErr').textContent = 'Erro: ' + e.message;
                $('filesErr').classList.remove('d-none');
            }
        }

        async function uploadFile() {
            const file = $('upFile').files && $('upFile').files[0];
            const user = $('upUser').value.trim();

            if (!file) return showTopMsg('Selecione um arquivo.', false);
            if (!user) return showTopMsg('Informe seu nome.', false);

            const fd = new FormData();
            fd.append('task_id', String(taskId));
            fd.append('user', user);
            fd.append('file', file);

            const r = await fetch(`${API}?entity=files&action=upload`, {
                method: 'POST',
                body: fd
            });
            const j = await r.json().catch(() => null);
            if (!j) throw new Error('Resposta inválida do servidor.');
            if (!j.success) throw new Error(j.error || 'Erro no upload.');

            $('upFile').value = '';
            showTopMsg('Arquivo enviado.', true);
            await loadFiles();
        }

        async function loadLists() {
            // só para manter o subtitle/meta mais “vivo”, opcional
        }

        // binds
        $('btnTheme').addEventListener('click', themeToggle);
        $('btnSalvar').addEventListener('click', async () => {
            try {
                await saveTask();
            } catch (e) {
                showTopMsg(e.message, false);
            }
        });
        $('btnExcluir').addEventListener('click', async () => {
            try {
                await deleteTask();
            } catch (e) {
                showTopMsg(e.message, false);
            }
        });
        $('btnAddComment').addEventListener('click', async () => {
            try {
                await addComment();
            } catch (e) {
                showTopMsg(e.message, false);
            }
        });
        $('btnUpload').addEventListener('click', async () => {
            try {
                await uploadFile();
            } catch (e) {
                showTopMsg(e.message, false);
            }
        });
        $('fTags').addEventListener('input', () => {
            renderTagsChips($('fTags').value);
        });

        // init
        (async () => {
            themeInit();
            try {
                await loadTask();
                await loadComments();
                await loadFiles();
            } catch (e) {
                showTopMsg(e.message, false);
            }
        })();
    })();

    function normPrio(v) {
        v = String(v || '').trim().toUpperCase();
        if (v === 'LOW' || v === 'BAIXA' || v === 'BAIXO') return 'LOW';
        if (v === 'HIGH' || v === 'ALTA' || v === 'ALTO') return 'HIGH';
        return 'MED';
    }

    function prioClass(prio) {
        prio = normPrio(prio);
        if (prio === 'LOW') return {
            badge: 'prio-low',
            select: 'select-prio-low',
            text: 'LOW'
        };
        if (prio === 'HIGH') return {
            badge: 'prio-high',
            select: 'select-prio-high',
            text: 'HIGH'
        };
        return {
            badge: 'prio-med',
            select: 'select-prio-med',
            text: 'MED'
        };
    }

    function applyPrioUI(prio) {
        const sel = document.getElementById('fPrioridade'); // <<< AQUI
        const badge = document.getElementById('prioBadge');
        const badgeText = document.getElementById('prioBadgeText');
        if (!sel || !badge || !badgeText) return;

        const c = prioClass(prio);

        badge.classList.remove('prio-low', 'prio-med', 'prio-high');
        badge.classList.add(c.badge);
        badgeText.textContent = c.text;

        sel.classList.remove('select-prio-low', 'select-prio-med', 'select-prio-high');
        sel.classList.add(c.select);
    }

    // Amarra no select (correto)
    (function() {
        const sel = document.getElementById('fPrioridade'); // <<< AQUI
        if (!sel) return;
        sel.addEventListener('change', () => applyPrioUI(sel.value));
    })();

    function parseTags(str) {
        return String(str || '')
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);
    }

    function renderTagsChips(str) {
        const el = document.getElementById('tagsChips');
        if (!el) return;

        const tags = parseTags(str);
        el.innerHTML = '';

        if (tags.length === 0) {
            el.innerHTML = `<span class="text-muted" style="font-size:12px;">Sem tags.</span>`;
            return;
        }

        tags.forEach(t => {
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.innerHTML = `<span class="dot"></span>${escapeHtml(t)}`;
            el.appendChild(chip);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [m]));
    }
</script>