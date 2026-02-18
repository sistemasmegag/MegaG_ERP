<?php
?>
<!doctype html>
<html lang="pt-br" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>MegaG — Tarefas</title>

    <!-- SEU CSS PADRÃO (como você mandou) -->
    <style>
        /* ========= Clean SaaS ========= */
        :root {
            --saas-bg: #f6f8fb;
            --saas-card: #ffffff;
            --saas-border: rgba(17, 24, 39, .10);
            --saas-text: #111827;
            --saas-muted: rgba(17, 24, 39, .60);
            --saas-shadow: 0 12px 30px rgba(17, 24, 39, .08);
            --saas-shadow-soft: 0 10px 30px rgba(17, 24, 39, .06);
            --saas-ring: rgba(13, 110, 253, .12);
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

        /* Botão tema */
        .saas-theme-toggle {
            border: 1px solid var(--saas-border);
            background: transparent;
            color: var(--saas-muted);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .saas-theme-toggle:hover {
            color: var(--saas-text);
            border-color: rgba(13, 110, 253, .35);
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

        .quick-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .quick-card:hover .saas-card {
            transform: translateY(-2px);
            transition: transform .18s ease;
            box-shadow: 0 18px 44px rgba(17, 24, 39, .10) !important;
        }

        .quick-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-card:hover .quick-icon {
            transform: scale(1.1);
            transition: 0.2s ease;
        }

        .text-muted {
            color: var(--saas-muted) !important;
        }

        .text-dark {
            color: var(--saas-text) !important;
        }

        /* ===== QUICK CARDS - DARK MODE ===== */
        [data-bs-theme="dark"] .saas-card {
            background-color: #1e1e2d;
            border: 1px solid #2b2b40;
        }

        [data-bs-theme="dark"] .saas-card h5 {
            color: #ffffff;
        }

        [data-bs-theme="dark"] .saas-card .text-muted {
            color: #b5b5c3 !important;
        }

        /* seta da direita */
        [data-bs-theme="dark"] .saas-card .bi-arrow-right {
            color: #8a8aa3 !important;
        }

        /* ícone com fundo suave */
        [data-bs-theme="dark"] .quick-icon {
            background-color: rgba(255, 255, 255, 0.06) !important;
        }

        /* hover */
        .quick-card:hover .saas-card {
            transform: translateY(-2px);
            transition: 0.2s ease;
        }
    </style>

    <!-- CSS do módulo Tarefas (somente o que falta) -->
    <style>
        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 18px 18px 28px;
        }

        .head-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btnx {
            border: 1px solid var(--saas-border);
            background: rgba(255, 255, 255, .20);
            color: var(--saas-text);
            border-radius: 999px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        html[data-theme="dark"] .btnx {
            background: rgba(255, 255, 255, .06);
        }

        .btnx:hover {
            border-color: rgba(13, 110, 253, .35);
        }

        .btnx.primary {
            background: rgba(13, 110, 253, .12);
            border-color: rgba(13, 110, 253, .25);
            color: #0b5ed7;
        }

        html[data-theme="dark"] .btnx.primary {
            color: rgba(255, 255, 255, .92);
        }

        .btnx.danger {
            background: rgba(220, 38, 38, .10);
            border-color: rgba(220, 38, 38, .24);
            color: #b42318;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 14px;
            margin-top: 14px;
        }

        .card-body {
            padding: 16px;
        }

        .card-title {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .field {
            flex: 1;
            min-width: 220px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .08em;
            color: var(--saas-muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid var(--saas-border);
            background: rgba(255, 255, 255, .75);
            color: var(--saas-text);
            outline: none;
        }

        html[data-theme="dark"] input,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea {
            background: rgba(255, 255, 255, .06);
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .hr {
            height: 1px;
            background: rgba(17, 24, 39, .10);
            margin: 12px 0;
        }

        html[data-theme="dark"] .hr {
            background: rgba(255, 255, 255, .10);
        }

        .msg {
            margin-top: 10px;
            font-weight: 900;
            font-size: 13px;
            display: none;
        }

        .msg.ok {
            color: #16a34a;
            display: block;
        }

        .msg.err {
            color: #dc2626;
            display: block;
        }

        /* Kanban */
        .kanban {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .kcol {
            padding: 14px;
        }

        .khead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .kname {
            font-weight: 950;
            letter-spacing: -.01em;
        }

        .pill {
            border: 1px solid var(--saas-border);
            background: rgba(17, 24, 39, .06);
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 12px;
            color: var(--saas-text);
        }

        html[data-theme="dark"] .pill {
            background: rgba(255, 255, 255, .06);
        }

        .ksub {
            color: var(--saas-muted);
            font-size: 12px;
            margin-bottom: 10px;
        }

        .task {
            border: 1px solid var(--saas-border);
            background: rgba(255, 255, 255, .80);
            border-radius: 16px;
            padding: 12px;
            box-shadow: var(--saas-shadow-soft);
            margin-bottom: 10px;
        }

        html[data-theme="dark"] .task {
            background: rgba(255, 255, 255, .06);
        }

        .tt {
            font-weight: 950;
            font-size: 13px;
            margin: 0;
        }

        .meta {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            color: var(--saas-muted);
            font-size: 12px;
        }

        .chip {
            border: 1px solid var(--saas-border);
            background: rgba(17, 24, 39, .06);
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 900;
            color: var(--saas-text);
        }

        html[data-theme="dark"] .chip {
            background: rgba(255, 255, 255, .06);
        }

        .ta {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .select-mini {
            max-width: 160px;
        }

        /* Modal */
        .backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, .46);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 9999;
        }

        .modal {
            width: min(900px, 96vw);
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .92);
            border-radius: 18px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        html[data-theme="dark"] .modal {
            background: rgba(30, 30, 30, .94);
        }

        .mhead {
            padding: 14px 16px;
            border-bottom: 1px solid var(--saas-border);
            background: linear-gradient(135deg, rgba(13, 110, 253, .10), rgba(13, 110, 253, .04));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        html[data-theme="dark"] .mhead {
            background: linear-gradient(135deg, rgba(13, 110, 253, .14), rgba(255, 255, 255, .02));
        }

        .mhead h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 950;
        }

        .mbody {
            padding: 16px;
        }

        .mfoot {
            padding: 12px 16px;
            border-top: 1px solid var(--saas-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .hint {
            color: var(--saas-muted);
            font-size: 12px;
            margin-top: 8px;
        }

        @media (max-width: 1050px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .kanban {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="wrap">

            <!-- Header padrão -->
            <div class="saas-page-head">
                <div class="head-row">
                    <div>
                        <h2 class="saas-title">Tarefas</h2>
                        <p class="saas-subtitle">Organize por Space e List — visão Kanban</p>
                    </div>
                    <div class="actions">
                        <button class="saas-theme-toggle" id="btnTheme">🌙 <span id="themeLabel">Dark</span></button>
                        <button class="btnx" id="btnReload">↻ Recarregar</button>
                        <button class="btnx primary" id="btnNewTask">＋ Nova Task</button>
                    </div>
                </div>
            </div>

            <!-- Config + ações -->
            <div class="grid">

                <div class="saas-card">
                    <div class="card-body">
                        <div class="card-title">Contexto</div>

                        <div class="row">
                            <div class="field">
                                <label>Space</label>
                                <select id="spaceSelect"></select>
                            </div>
                            <div class="field">
                                <label>List</label>
                                <select id="listSelect"></select>
                            </div>
                            <div class="field">
                                <label>Usuário</label>
                                <input id="userDefault" placeholder="Ex: Felipe" />
                            </div>
                        </div>

                        <div id="msgBox" class="msg"></div>

                        <div class="hr"></div>

                        <div class="row">
                            <div class="field">
                                <label>Novo Space</label>
                                <input id="spaceNome" placeholder="Ex: TI" />
                            </div>
                            <div class="field">
                                <label>Criado por</label>
                                <input id="spaceCriadoPor" placeholder="Ex: Felipe" />
                            </div>
                            <div class="field" style="flex:0;min-width:220px">
                                <button class="btnx primary" id="btnCreateSpace" style="width:100%; justify-content:center;">Criar Space</button>
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="row">
                            <div class="field">
                                <label>Nova List (no Space selecionado)</label>
                                <input id="listNome" placeholder="Ex: Desenvolvimento" />
                            </div>
                            <div class="field" style="max-width:160px">
                                <label>Ordem</label>
                                <input id="listOrdem" type="number" value="0" />
                            </div>
                            <div class="field">
                                <label>Criado por</label>
                                <input id="listCriadoPor" placeholder="Ex: Felipe" />
                            </div>
                            <div class="field" style="flex:0;min-width:220px">
                                <button class="btnx primary" id="btnCreateList" style="width:100%; justify-content:center;">Criar List</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="saas-card">
                    <div class="card-body">
                        <div class="card-title">Resumo da List</div>

                        <div class="row">
                            <div class="field">
                                <label>TODO</label>
                                <div class="pill" id="countTodo">0</div>
                            </div>
                            <div class="field">
                                <label>DOING</label>
                                <div class="pill" id="countDoing">0</div>
                            </div>
                            <div class="field">
                                <label>DONE</label>
                                <div class="pill" id="countDone">0</div>
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="hint">
                            • Mude o status pelo seletor no card<br>
                            • “Nova Task” cria na List selecionada<br>
                            • Tema (dark/light) igual às outras páginas
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanban -->
            <div class="kanban">

                <div class="saas-card">
                    <div class="kcol">
                        <div class="khead">
                            <div class="kname">TODO</div>
                            <div class="pill" id="pillTodo">0</div>
                        </div>
                        <div class="ksub">A fazer</div>
                        <div id="colTODO"></div>
                    </div>
                </div>

                <div class="saas-card">
                    <div class="kcol">
                        <div class="khead">
                            <div class="kname">DOING</div>
                            <div class="pill" id="pillDoing">0</div>
                        </div>
                        <div class="ksub">Em andamento</div>
                        <div id="colDOING"></div>
                    </div>
                </div>

                <div class="saas-card">
                    <div class="kcol">
                        <div class="khead">
                            <div class="kname">DONE</div>
                            <div class="pill" id="pillDone">0</div>
                        </div>
                        <div class="ksub">Concluídas</div>
                        <div id="colDONE"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="backdrop" id="backdrop">
        <div class="modal">
            <div class="mhead">
                <h3 id="mTitle">Nova Task</h3>
                <button class="btnx" id="btnClose">Fechar</button>
            </div>
            <div class="mbody">
                <div class="row">
                    <div class="field" style="flex:2">
                        <label>Título</label>
                        <input id="mTitulo" placeholder="Ex: Criar tela Kanban" />
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select id="mStatus">
                            <option value="TODO">TODO</option>
                            <option value="DOING">DOING</option>
                            <option value="DONE">DONE</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Prioridade</label>
                        <select id="mPrioridade">
                            <option value="LOW">LOW</option>
                            <option value="MED" selected>MED</option>
                            <option value="HIGH">HIGH</option>
                            <option value="URGENT">URGENT</option>
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top:12px">
                    <div class="field">
                        <label>Responsável</label>
                        <input id="mResponsavel" placeholder="Ex: Felipe" />
                    </div>
                    <div class="field">
                        <label>Entrega (YYYY-MM-DD)</label>
                        <input id="mEntrega" placeholder="2026-02-20" />
                    </div>
                    <div class="field">
                        <label>Tags</label>
                        <input id="mTags" placeholder="frontend,kanban" />
                    </div>
                </div>

                <div style="margin-top:12px">
                    <label>Descrição</label>
                    <textarea id="mDescricao" placeholder="Detalhes..."></textarea>
                </div>

                <div class="hint" id="mHint"></div>
                <div id="mMsg" class="msg"></div>

                <input type="hidden" id="mTaskId" value="">
            </div>
            <div class="mfoot">
                <button class="btnx danger" id="btnDelete" style="display:none">Excluir</button>
                <button class="btnx primary" id="btnSave">Salvar</button>
            </div>
        </div>
    </div>

    <script>
        const API = '/importador/api/tasks.php';
        const $ = (id) => document.getElementById(id);

        function showMsg(text, ok = true) {
            const el = $('msgBox');
            if (!text) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }
            el.style.display = 'block';
            el.className = 'msg ' + (ok ? 'ok' : 'err');
            el.textContent = text;
        }

        function showModalMsg(text, ok = true) {
            const el = $('mMsg');
            if (!text) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }
            el.style.display = 'block';
            el.className = 'msg ' + (ok ? 'ok' : 'err');
            el.textContent = text;
        }

        async function apiGet(url) {
            const r = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const j = await r.json();
            if (!j.success) throw new Error(j.error || 'Erro');
            return j.data;
        }
        async function apiSend(url, method, body) {
            const r = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: body ? JSON.stringify(body) : null
            });
            const j = await r.json();
            if (!j.success) throw new Error(j.error || 'Erro');
            return j.data;
        }

        function user() {
            return ($('userDefault').value || '').trim() || 'web';
        }

        // Theme toggle (compatível com seu data-theme)
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            $('themeLabel').textContent = theme === 'dark' ? 'Light' : 'Dark';
            $('btnTheme').innerHTML = (theme === 'dark' ? '☀️' : '🌙') + ' <span id="themeLabel">' + (theme === 'dark' ? 'Light' : 'Dark') + '</span>';
            localStorage.setItem('megag_theme', theme);
        }

        // Loaders
        async function loadSpaces() {
            const spaces = await apiGet(`${API}?entity=spaces&only_active=S`);
            const sel = $('spaceSelect');
            sel.innerHTML = '';

            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = spaces.length ? 'Selecione...' : 'Sem spaces (crie um)';
            sel.appendChild(opt0);

            spaces.forEach(s => {
                const id = s.ID ?? s.id;
                const nome = s.NOME ?? s.nome;
                const o = document.createElement('option');
                o.value = id;
                o.textContent = `${nome} (#${id})`;
                sel.appendChild(o);
            });

            if (spaces.length) {
                sel.value = (spaces[0].ID ?? spaces[0].id);
                await loadLists();
            } else {
                $('listSelect').innerHTML = '<option value="">Sem lists</option>';
                renderKanban([]);
            }
        }

        async function loadLists() {
            const spaceId = parseInt($('spaceSelect').value || '0', 10);
            const sel = $('listSelect');
            sel.innerHTML = '';

            if (!spaceId) {
                sel.innerHTML = '<option value="">Selecione um space</option>';
                renderKanban([]);
                return;
            }

            const lists = await apiGet(`${API}?entity=lists&space_id=${spaceId}`);

            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = lists.length ? 'Selecione...' : 'Sem lists (crie uma)';
            sel.appendChild(opt0);

            lists.forEach(l => {
                const id = l.ID ?? l.id;
                const nome = l.NOME ?? l.nome;
                const o = document.createElement('option');
                o.value = id;
                o.textContent = `${nome} (#${id})`;
                sel.appendChild(o);
            });

            if (lists.length) {
                sel.value = (lists[0].ID ?? lists[0].id);
                await loadTasks();
            } else {
                renderKanban([]);
            }
        }

        async function loadTasks() {
            const listId = parseInt($('listSelect').value || '0', 10);
            if (!listId) {
                renderKanban([]);
                return;
            }
            const tasks = await apiGet(`${API}?entity=tasks&list_id=${listId}`);
            renderKanban(tasks || []);
        }

        function renderKanban(tasks) {
            const by = {
                TODO: [],
                DOING: [],
                DONE: []
            };
            tasks.forEach(t => (by[(t.STATUS ?? t.status ?? 'TODO')] || by.TODO).push(t));

            $('pillTodo').textContent = by.TODO.length;
            $('pillDoing').textContent = by.DOING.length;
            $('pillDone').textContent = by.DONE.length;

            $('countTodo').textContent = by.TODO.length;
            $('countDoing').textContent = by.DOING.length;
            $('countDone').textContent = by.DONE.length;

            paint('colTODO', by.TODO);
            paint('colDOING', by.DOING);
            paint('colDONE', by.DONE);
        }

        function paint(id, tasks) {
            const el = $(id);
            el.innerHTML = '';
            tasks.forEach(t => el.appendChild(card(t)));
        }

        function chip(text) {
            const s = document.createElement('span');
            s.className = 'chip';
            s.textContent = text;
            return s;
        }

        function card(t) {
            const id = t.ID ?? t.id;
            const titulo = t.TITULO ?? t.titulo ?? '';
            const prioridade = t.PRIORIDADE ?? t.prioridade ?? '';
            const status = t.STATUS ?? t.status ?? 'TODO';
            const resp = t.RESPONSAVEL ?? t.responsavel ?? '';
            const entrega = t.DATA_ENTREGA ?? t.data_entrega ?? '';
            const tags = t.TAGS ?? t.tags ?? '';
            const criadoEm = t.CRIADO_EM ?? t.criado_em ?? '';

            const el = document.createElement('div');
            el.className = 'task';

            const tt = document.createElement('p');
            tt.className = 'tt';
            tt.textContent = `#${id} — ${titulo}`;
            el.appendChild(tt);

            const meta = document.createElement('div');
            meta.className = 'meta';
            if (prioridade) meta.appendChild(chip(`PRIO: ${prioridade}`));
            if (resp) meta.appendChild(chip(`RESP: ${resp}`));
            if (entrega) meta.appendChild(chip(`ENT: ${entrega}`));
            if (tags) meta.appendChild(chip(`TAGS: ${tags}`));
            if (criadoEm) meta.appendChild(chip(`CRIADO: ${criadoEm}`));
            el.appendChild(meta);

            const ta = document.createElement('div');
            ta.className = 'ta';

            const sel = document.createElement('select');
            sel.className = 'select-mini';
            ['TODO', 'DOING', 'DONE'].forEach(v => {
                const o = document.createElement('option');
                o.value = v;
                o.textContent = v;
                if (v === status) o.selected = true;
                sel.appendChild(o);
            });
            sel.addEventListener('change', async () => {
                try {
                    await apiSend(`${API}?entity=tasks&action=move`, 'PATCH', {
                        task_id: id,
                        status: sel.value,
                        user: user()
                    });
                    showMsg('Status atualizado.', true);
                    await loadTasks();
                } catch (e) {
                    showMsg(e.message, false);
                }
            });

            const btnE = document.createElement('a');
            btnE.className = 'btnx';
            btnE.textContent = 'Editar';

            const spaceId = $('spaceSelect').value || '';
            const listId = $('listSelect').value || '';

            btnE.href =
                `/importador/index.php?page=tarefas_detalhes` +
                `&task_id=${encodeURIComponent(id)}` +
                `&space_id=${encodeURIComponent(spaceId)}` +
                `&list_id=${encodeURIComponent(listId)}`;

            btnE.style.textDecoration = 'none';
            btnE.style.display = 'inline-flex';
            btnE.style.alignItems = 'center';

            const btnD = document.createElement('button');
            btnD.className = 'btnx danger';
            btnD.textContent = 'Excluir';
            btnD.addEventListener('click', async () => {
                if (!confirm(`Excluir task #${id}?`)) return;
                try {
                    await fetch(`${API}?entity=tasks&task_id=${id}&user=${encodeURIComponent(user())}`, {
                            method: 'DELETE'
                        })
                        .then(r => r.json()).then(j => {
                            if (!j.success) throw new Error(j.error || 'Erro');
                        });
                    showMsg('Task excluída.', true);
                    await loadTasks();
                } catch (e) {
                    showMsg(e.message, false);
                }
            });

            ta.appendChild(sel);
            ta.appendChild(btnE);
            ta.appendChild(btnD);
            el.appendChild(ta);

            return el;
        }

        // Create space/list
        async function createSpace() {
            const nome = ($('spaceNome').value || '').trim();
            const criado_por = ($('spaceCriadoPor').value || '').trim();
            if (!nome) return showMsg('Informe nome do Space.', false);
            if (!criado_por) return showMsg('Informe Criado por.', false);

            const r = await apiSend(`${API}?entity=spaces`, 'POST', {
                nome,
                criado_por
            });
            showMsg(`Space criado (id=${r.id}).`, true);
            $('spaceNome').value = '';
            await loadSpaces();
        }

        async function createList() {
            const space_id = parseInt($('spaceSelect').value || '0', 10);
            const nome = ($('listNome').value || '').trim();
            const ordem = parseInt($('listOrdem').value || '0', 10);
            const criado_por = ($('listCriadoPor').value || '').trim();

            if (!space_id) return showMsg('Selecione um Space.', false);
            if (!nome) return showMsg('Informe nome da List.', false);
            if (!criado_por) return showMsg('Informe Criado por.', false);

            const r = await apiSend(`${API}?entity=lists`, 'POST', {
                space_id,
                nome,
                ordem,
                criado_por
            });
            showMsg(`List criada (id=${r.id}).`, true);
            $('listNome').value = '';
            await loadLists();
        }

        // Modal
        function openModal() {
            $('backdrop').style.display = 'flex';
            showModalMsg('');
        }

        function closeModal() {
            $('backdrop').style.display = 'none';
        }

        function openNew() {
            $('mTitle').textContent = 'Nova Task';
            $('mHint').textContent = 'A task será criada na List selecionada.';
            $('btnDelete').style.display = 'none';
            $('mTaskId').value = '';
            $('mTitulo').value = '';
            $('mDescricao').value = '';
            $('mStatus').value = 'TODO';
            $('mPrioridade').value = 'MED';
            $('mResponsavel').value = '';
            $('mEntrega').value = '';
            $('mTags').value = '';
            openModal();
        }

        function openEdit(d) {
            $('mTitle').textContent = `Editar Task #${d.id}`;
            $('mHint').textContent = 'Salvar atualiza campos e aplica status selecionado.';
            $('btnDelete').style.display = 'inline-flex';
            $('mTaskId').value = d.id;
            $('mTitulo').value = d.titulo || '';
            $('mDescricao').value = d.descricao || '';
            $('mStatus').value = d.status || 'TODO';
            $('mPrioridade').value = d.prioridade || 'MED';
            $('mResponsavel').value = d.responsavel || '';
            $('mEntrega').value = d.data_entrega || '';
            $('mTags').value = d.tags || '';
            openModal();
        }

        async function saveTask() {
            const list_id = parseInt($('listSelect').value || '0', 10);
            if (!list_id) return showModalMsg('Selecione uma List.', false);

            const task_id = ($('mTaskId').value || '').trim();
            const titulo = ($('mTitulo').value || '').trim();
            const descricao = $('mDescricao').value || null;
            const status = $('mStatus').value;
            const prioridade = $('mPrioridade').value;
            const responsavel = ($('mResponsavel').value || '').trim() || null;
            const data_entrega = ($('mEntrega').value || '').trim() || null;
            const tags = ($('mTags').value || '').trim() || null;

            if (!titulo) return showModalMsg('Informe Título.', false);

            try {
                if (!task_id) {
                    const r = await apiSend(`${API}?entity=tasks`, 'POST', {
                        list_id,
                        titulo,
                        descricao,
                        status,
                        prioridade,
                        tags,
                        responsavel,
                        data_entrega,
                        criado_por: user()
                    });
                    showMsg(`Task criada (id=${r.id}).`, true);
                    closeModal();
                    await loadTasks();
                    return;
                }

                await apiSend(`${API}?entity=tasks&task_id=${encodeURIComponent(task_id)}`, 'PUT', {
                    titulo,
                    descricao,
                    prioridade,
                    tags,
                    responsavel,
                    data_entrega,
                    user: user()
                });

                await apiSend(`${API}?entity=tasks&action=move`, 'PATCH', {
                    task_id: parseInt(task_id, 10),
                    status,
                    user: user()
                });

                showMsg('Task atualizada.', true);
                closeModal();
                await loadTasks();
            } catch (e) {
                showModalMsg(e.message, false);
            }
        }

        async function delTask() {
            const task_id = ($('mTaskId').value || '').trim();
            if (!task_id) return;
            if (!confirm(`Excluir task #${task_id}?`)) return;

            try {
                await fetch(`${API}?entity=tasks&task_id=${encodeURIComponent(task_id)}&user=${encodeURIComponent(user())}`, {
                        method: 'DELETE'
                    })
                    .then(r => r.json()).then(j => {
                        if (!j.success) throw new Error(j.error || 'Erro');
                    });

                showMsg('Task excluída.', true);
                closeModal();
                await loadTasks();
            } catch (e) {
                showModalMsg(e.message, false);
            }
        }

        // Events
        $('btnTheme').addEventListener('click', () => {
            const cur = document.documentElement.getAttribute('data-theme') || 'light';
            setTheme(cur === 'dark' ? 'light' : 'dark');
        });

        $('btnReload').addEventListener('click', async () => {
            try {
                showMsg('');
                await loadSpaces();
            } catch (e) {
                showMsg(e.message, false);
            }
        });
        $('spaceSelect').addEventListener('change', async () => {
            try {
                showMsg('');
                await loadLists();
            } catch (e) {
                showMsg(e.message, false);
            }
        });
        $('listSelect').addEventListener('change', async () => {
            try {
                showMsg('');
                await loadTasks();
            } catch (e) {
                showMsg(e.message, false);
            }
        });

        $('btnCreateSpace').addEventListener('click', async () => {
            try {
                await createSpace();
            } catch (e) {
                showMsg(e.message, false);
            }
        });
        $('btnCreateList').addEventListener('click', async () => {
            try {
                await createList();
            } catch (e) {
                showMsg(e.message, false);
            }
        });

        $('btnNewTask').addEventListener('click', () => {
            const spaceId = $('spaceSelect').value || '';
            const listId = $('listSelect').value || '';
            location.href =
                `/importador/index.php?page=tarefas_criar_tasks` +
                `&space_id=${encodeURIComponent(spaceId)}` +
                `&list_id=${encodeURIComponent(listId)}`;
        });
        $('btnSave').addEventListener('click', saveTask);
        $('btnDelete').addEventListener('click', delTask);

        // Init
        (async () => {
            try {
                const savedTheme = localStorage.getItem('megag_theme');
                setTheme(savedTheme || 'light');
                $('userDefault').value = $('userDefault').value || 'Felipe';
                await loadSpaces();
            } catch (e) {
                showMsg(e.message, false);
            }
        })();
    </script>

</body>

</html>