<?php
$space_id = isset($_GET['space_id']) ? (int)$_GET['space_id'] : 0;
$list_id  = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;
?>
<!doctype html>
<html lang="pt-br" data-theme="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>MegaG — Criar Task</title>

  <!-- CSS padrão do seu projeto -->
  <style>
    :root {
      --saas-bg: #f6f8fb;
      --saas-card: #ffffff;
      --saas-border: rgba(17, 24, 39, .10);
      --saas-text: #111827;
      --saas-muted: rgba(17, 24, 39, .60);
      --saas-shadow: 0 12px 30px rgba(17, 24, 39, .08);
      --saas-shadow-soft: 0 10px 30px rgba(17, 24, 39, .06);
    }

    html[data-theme="dark"] {
      --saas-bg: #1f1f1f;
      --saas-card: rgba(255, 255, 255, .05);
      --saas-border: rgba(255, 255, 255, .10);
      --saas-text: rgba(255, 255, 255, .92);
      --saas-muted: rgba(255, 255, 255, .65);
      --saas-shadow: 0 16px 40px rgba(0, 0, 0, .35);
      --saas-shadow-soft: 0 14px 40px rgba(0, 0, 0, .25);
    }

    .main-content {
      background:
        radial-gradient(1200px 600px at 15% 10%, rgba(13, 110, 253, .14), transparent 60%),
        radial-gradient(1000px 500px at 85% 25%, rgba(25, 135, 84, .10), transparent 55%),
        var(--saas-bg);
      color: var(--saas-text);
      min-height: 100vh;
    }

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

    .saas-card {
      background: var(--saas-card) !important;
      border: 1px solid var(--saas-border) !important;
      border-radius: 18px !important;
      box-shadow: var(--saas-shadow) !important;
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

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
      cursor: pointer;
    }

    .saas-theme-toggle:hover {
      color: var(--saas-text);
      border-color: rgba(13, 110, 253, .35);
    }

    .wrap {
      max-width: 1440px;
      margin: 0 auto;
      padding: 28px 24px 40px;
    }

    .head-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      flex-wrap: wrap;
    }

    .hero-copy {
      display: flex;
      flex-direction: column;
      gap: 14px;
      position: relative;
      z-index: 1;
    }

    .hero-stats {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .hero-stat {
      min-width: 150px;
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid rgba(13, 110, 253, .14);
      background: rgba(255, 255, 255, .42);
      backdrop-filter: blur(8px);
    }

    html[data-theme="dark"] .hero-stat {
      background: rgba(255, 255, 255, .06);
      border-color: rgba(255, 255, 255, .08);
    }

    .hero-stat-label {
      font-size: 10px;
      font-weight: 900;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--saas-muted);
    }

    .hero-stat-value {
      margin-top: 6px;
      font-size: 24px;
      font-weight: 950;
      letter-spacing: -.03em;
      color: var(--saas-text);
      line-height: 1;
    }

    .hero-stat-sub {
      margin-top: 4px;
      font-size: 12px;
      color: var(--saas-muted);
    }

    .actions {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .btnx {
      border: 1px solid var(--saas-border);
      background: rgba(255, 255, 255, .52);
      color: var(--saas-text);
      border-radius: 999px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 900;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      box-shadow: 0 10px 24px rgba(17, 24, 39, .06);
    }

    html[data-theme="dark"] .btnx {
      background: rgba(255, 255, 255, .06);
    }

    .btnx:hover {
      border-color: rgba(13, 110, 253, .35);
    }

    .btnx.primary {
      background: linear-gradient(135deg, rgba(13, 110, 253, .18), rgba(13, 110, 253, .08));
      border-color: rgba(13, 110, 253, .25);
      color: #0b5ed7;
    }

    html[data-theme="dark"] .btnx.primary {
      color: rgba(255, 255, 255, .92);
    }

    .card-body {
      padding: 22px;
    }

    .layout-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(320px, .75fr);
      gap: 18px;
      margin-top: 18px;
      align-items: start;
    }

    .row {
      display: flex;
      gap: 14px;
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
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid var(--saas-border);
      background: rgba(255, 255, 255, .84);
      color: var(--saas-text);
      outline: none;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, .35);
    }

    html[data-theme="dark"] input,
    html[data-theme="dark"] select,
    html[data-theme="dark"] textarea {
      background: rgba(255, 255, 255, .06);
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: rgba(13, 110, 253, .34);
      box-shadow: 0 0 0 4px rgba(13, 110, 253, .12);
    }

    textarea {
      min-height: 160px;
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

    .hint {
      color: var(--saas-muted);
      font-size: 12px;
      margin-top: 8px;
      line-height: 1.8;
    }

    .section-block {
      border: 1px solid rgba(17, 24, 39, .08);
      background: linear-gradient(180deg, rgba(255, 255, 255, .72), rgba(255, 255, 255, .48));
      border-radius: 18px;
      padding: 18px;
    }

    html[data-theme="dark"] .section-block {
      background: rgba(255, 255, 255, .04);
      border-color: rgba(255, 255, 255, .06);
    }

    .section-head {
      margin-bottom: 14px;
    }

    .section-kicker {
      font-size: 10px;
      font-weight: 900;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--saas-muted);
      margin-bottom: 4px;
    }

    .section-title {
      font-size: 20px;
      font-weight: 950;
      letter-spacing: -.02em;
      color: var(--saas-text);
      margin: 0;
    }

    .section-note {
      font-size: 13px;
      color: var(--saas-muted);
      margin-top: 4px;
    }

    .side-stack {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .summary-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .summary-item {
      padding: 14px;
      border-radius: 16px;
      border: 1px solid rgba(17, 24, 39, .08);
      background: rgba(255, 255, 255, .62);
    }

    html[data-theme="dark"] .summary-item {
      background: rgba(255, 255, 255, .04);
      border-color: rgba(255, 255, 255, .08);
    }

    .summary-label {
      font-size: 10px;
      font-weight: 900;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--saas-muted);
    }

    .summary-value {
      margin-top: 6px;
      font-size: 16px;
      font-weight: 950;
      color: var(--saas-text);
      line-height: 1.35;
    }

    .summary-help {
      font-size: 12px;
      color: var(--saas-muted);
      margin-top: 6px;
      line-height: 1.6;
    }

    .priority-preview {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(17, 24, 39, .08);
      background: rgba(255, 255, 255, .66);
      font-size: 12px;
      font-weight: 900;
      color: var(--saas-text);
    }

    .priority-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #f59e0b;
    }

    .priority-dot.low {
      background: #60a5fa;
    }

    .priority-dot.med {
      background: #f59e0b;
    }

    .priority-dot.high {
      background: #f97316;
    }

    .priority-dot.urgent {
      background: #ef4444;
    }

    /* Autocomplete bonito */
    .ac-drop {
      position: absolute;
      left: 0;
      right: 0;
      top: 70px;
      background: var(--saas-card);
      border: 1px solid var(--saas-border);
      border-radius: 14px;
      box-shadow: var(--saas-shadow);
      padding: 6px;
      max-height: 280px;
      overflow: auto;
      z-index: 9999;
    }

    .ac-item {
      padding: 10px 10px;
      border-radius: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      user-select: none;
    }

    .ac-item:hover {
      background: rgba(13, 110, 253, .10);
    }

    .ac-item.active {
      background: rgba(13, 110, 253, .16);
    }

    .ac-name {
      font-weight: 900;
      font-size: 13px;
    }

    .ac-login {
      font-weight: 900;
      font-size: 12px;
      color: var(--saas-muted);
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid var(--saas-border);
    }

    @media (max-width: 1050px) {
      .layout-grid {
        grid-template-columns: 1fr;
      }

      .wrap {
        padding: 20px 16px 28px;
      }
    }
  </style>
</head>

<body>
  <div class="main-content">
    <div class="wrap">

      <div class="saas-page-head">
        <div class="head-row">
          <div class="hero-copy">
            <h2 class="saas-title">Criar Task</h2>
            <p class="saas-subtitle">Monte a task com mais contexto antes de publicar no fluxo. A ideia aqui é reduzir ruído e deixar a criação bem mais guiada.</p>
            <div class="hero-stats">
              <div class="hero-stat">
                <div class="hero-stat-label">Space</div>
                <div class="hero-stat-value" id="heroSpace">--</div>
                <div class="hero-stat-sub">contexto atual</div>
              </div>
              <div class="hero-stat">
                <div class="hero-stat-label">List</div>
                <div class="hero-stat-value" id="heroList">--</div>
                <div class="hero-stat-sub">destino da task</div>
              </div>
              <div class="hero-stat">
                <div class="hero-stat-label">Prioridade</div>
                <div class="hero-stat-value" id="heroPrio">MED</div>
                <div class="hero-stat-sub">nível atual selecionado</div>
              </div>
            </div>
          </div>
          <div class="actions">
            <button class="saas-theme-toggle" id="btnTheme">🌙 <span id="themeLabel">Dark</span></button>
            <a class="btnx" id="btnBack" href="/importador/tarefas.php">← Voltar</a>
            <button class="btnx primary" id="btnCreate">Criar</button>
          </div>
        </div>
      </div>

      <div class="layout-grid">
      <div class="saas-card">
        <div class="card-body">
          <div id="msgBox" class="msg"></div>

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
              <label>Criado por</label>
              <input id="fCriadoPor" placeholder="Ex: Felipe" />
            </div>
          </div>

          <div class="hr"></div>

          <div class="row">
            <div class="field" style="flex:2">
              <label>Título</label>
              <input id="fTitulo" placeholder="Ex: Criar tela Kanban" />
            </div>
            <div class="field">
              <label>Status</label>
              <select id="fStatus">
                <option value="TODO">TODO</option>
                <option value="DOING">DOING</option>
                <option value="DONE">DONE</option>
              </select>
            </div>
            <div class="field">
              <label>Prioridade</label>
              <select id="fPrioridade">
                <option value="LOW">LOW</option>
                <option value="MED" selected>MED</option>
                <option value="HIGH">HIGH</option>
                <option value="URGENT">URGENT</option>
              </select>
              <div class="priority-preview">
                <span class="priority-dot med" id="priorityDot"></span>
                <span id="priorityText">Prioridade média selecionada</span>
              </div>
            </div>
          </div>

          <div class="hr"></div>

          <div class="row">
            <div class="field" style="position:relative">
              <label>Responsável</label>

              <input id="fResponsavel" placeholder="Digite para buscar..." autocomplete="off" />
              <input type="hidden" id="fResponsavelLogin" />

              <div id="respDrop" class="ac-drop" style="display:none"></div>
              <div class="hint" id="respHint" style="margin-top:6px"></div>
            </div>
            <div class="field">
              <label>Entrega (YYYY-MM-DD)</label>
              <input id="fEntrega" placeholder="2026-02-20" />
            </div>
            <div class="field">
              <label>Tags</label>
              <input id="fTags" placeholder="frontend,kanban" />
            </div>
          </div>

          <div class="hr"></div>

          <div>
            <label>Descrição</label>
            <textarea id="fDescricao" placeholder="Detalhes..."></textarea>
          </div>

          <div class="hint">
            Dica: depois de criar, você será redirecionado para a página de detalhes.
          </div>

        </div>
      </div>

      <div class="side-stack">
        <div class="saas-card">
          <div class="card-body">
            <div class="section-kicker">Resumo</div>
            <h3 class="section-title">Prévia da publicação</h3>
            <div class="section-note">Uma leitura rápida do que será criado antes de enviar.</div>

            <div class="summary-list" style="margin-top:16px;">
              <div class="summary-item">
                <div class="summary-label">Título</div>
                <div class="summary-value" id="previewTitulo">Aguardando título...</div>
                <div class="summary-help">Use um nome curto e claro para facilitar leitura no kanban.</div>
              </div>
              <div class="summary-item">
                <div class="summary-label">Responsável</div>
                <div class="summary-value" id="previewResponsavel">Não definido</div>
                <div class="summary-help">O autocomplete grava o login do responsável no backend.</div>
              </div>
              <div class="summary-item">
                <div class="summary-label">Entrega</div>
                <div class="summary-value" id="previewEntrega">Sem prazo</div>
                <div class="summary-help">Definir prazo ajuda a priorizar melhor no fluxo.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="saas-card">
          <div class="card-body">
            <div class="section-kicker">Guia rápido</div>
            <h3 class="section-title">Boas práticas</h3>
            <div class="hint" style="margin-top:14px;">
              â€¢ Escolha primeiro o Space e a List corretos<br>
              â€¢ Use título curto, mas objetivo<br>
              â€¢ Informe responsável quando a task já tiver dono<br>
              â€¢ Depois de criar, você será levado direto para os detalhes
            </div>
          </div>
        </div>
      </div>
      </div>

    </div>
  </div>

  <script>
    const API = '/importador/api/tasks.php';
    const PRE_SPACE_ID = <?= (int)$space_id ?>;
    const PRE_LIST_ID = <?= (int)$list_id ?>;

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

    function setTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      $('btnTheme').innerHTML = (theme === 'dark' ? '☀️' : '🌙') + ' <span id="themeLabel">' + (theme === 'dark' ? 'Light' : 'Dark') + '</span>';
      localStorage.setItem('megag_theme', theme);
    }

    function updateHeaderContext() {
      const spaceText = $('spaceSelect').selectedOptions[0]?.textContent || '--';
      const listText = $('listSelect').selectedOptions[0]?.textContent || '--';
      $('heroSpace').textContent = spaceText && spaceText !== 'Selecione...' ? spaceText.replace(/\s*\(#\d+\)\s*$/, '') : '--';
      $('heroList').textContent = listText && listText !== 'Selecione...' ? listText.replace(/\s*\(#\d+\)\s*$/, '') : '--';
    }

    function updatePriorityPreview() {
      const value = ($('fPrioridade').value || 'MED').toLowerCase();
      const map = {
        low: 'Prioridade baixa selecionada',
        med: 'Prioridade média selecionada',
        high: 'Prioridade alta selecionada',
        urgent: 'Prioridade urgente selecionada'
      };
      $('heroPrio').textContent = (value || 'med').toUpperCase();
      $('priorityDot').className = `priority-dot ${value}`;
      $('priorityText').textContent = map[value] || map.med;
    }

    function updateFormPreview() {
      const titulo = ($('fTitulo').value || '').trim();
      const responsavel = ($('fResponsavel').value || '').trim();
      const entrega = ($('fEntrega').value || '').trim();

      $('previewTitulo').textContent = titulo || 'Aguardando título...';
      $('previewResponsavel').textContent = responsavel || 'Não definido';
      $('previewEntrega').textContent = entrega || 'Sem prazo';
      updatePriorityPreview();
      updateHeaderContext();
    }

    async function loadSpaces() {
      const spaces = await apiGet(`${API}?entity=spaces&only_active=S`);
      const sel = $('spaceSelect');
      sel.innerHTML = '';

      const opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = spaces.length ? 'Selecione...' : 'Sem spaces';
      sel.appendChild(opt0);

      spaces.forEach(s => {
        const id = s.ID ?? s.id;
        const nome = s.NOME ?? s.nome;
        const o = document.createElement('option');
        o.value = id;
        o.textContent = `${nome} (#${id})`;
        sel.appendChild(o);
      });

      if (PRE_SPACE_ID) sel.value = String(PRE_SPACE_ID);
      else if (spaces.length) sel.value = String(spaces[0].ID ?? spaces[0].id);

      await loadLists();
      updateHeaderContext();
    }

    async function loadLists() {
      const spaceId = parseInt($('spaceSelect').value || '0', 10);
      const sel = $('listSelect');
      sel.innerHTML = '';

      if (!spaceId) {
        sel.innerHTML = '<option value="">Selecione um space</option>';
        return;
      }

      const lists = await apiGet(`${API}?entity=lists&space_id=${spaceId}`);

      const opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = lists.length ? 'Selecione...' : 'Sem lists';
      sel.appendChild(opt0);

      lists.forEach(l => {
        const id = l.ID ?? l.id;
        const nome = l.NOME ?? l.nome;
        const o = document.createElement('option');
        o.value = id;
        o.textContent = `${nome} (#${id})`;
        sel.appendChild(o);
      });

      if (PRE_LIST_ID) sel.value = String(PRE_LIST_ID);
      else if (lists.length) sel.value = String(lists[0].ID ?? lists[0].id);

      updateHeaderContext();
    }

    async function createTask() {
      const list_id = parseInt($('listSelect').value || '0', 10);
      const criado_por = ($('fCriadoPor').value || '').trim();
      const titulo = ($('fTitulo').value || '').trim();

      if (!list_id) return showMsg('Selecione uma List.', false);
      if (!criado_por) return showMsg('Informe "Criado por".', false);
      if (!titulo) return showMsg('Informe o título.', false);

      const body = {
        list_id,
        titulo,
        descricao: $('fDescricao').value || null,
        status: $('fStatus').value,
        prioridade: $('fPrioridade').value,
        tags: ($('fTags').value || '').trim() || null,
        responsavel: ($('fResponsavelLogin').value || '').trim() || null,
        data_entrega: ($('fEntrega').value || '').trim() || null,
        criado_por
      };

      try {
        const r = await apiSend(`${API}?entity=tasks`, 'POST', body);
        showMsg(`Task criada (id=${r.id}). Redirecionando...`, true);

        const spaceId = parseInt($('spaceSelect').value || '0', 10);
        const qs = new URLSearchParams();
        qs.set('task_id', r.id);
        if (spaceId) qs.set('space_id', spaceId);
        if (list_id) qs.set('list_id', list_id);

        location.href = `/importador/index.php?page=tarefas_detalhes&${qs.toString()}`;
      } catch (e) {
        showMsg(e.message, false);
      }
    }

    $('btnTheme').addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme') || 'light';
      setTheme(cur === 'dark' ? 'light' : 'dark');
    });

    $('spaceSelect').addEventListener('change', async () => {
      await loadLists();
      updateFormPreview();
    });
    $('btnCreate').addEventListener('click', createTask);
    $('listSelect').addEventListener('change', updateFormPreview);
    $('fPrioridade').addEventListener('change', updateFormPreview);
    $('fTitulo').addEventListener('input', updateFormPreview);
    $('fResponsavel').addEventListener('input', updateFormPreview);
    $('fEntrega').addEventListener('input', updateFormPreview);

    (async () => {
      const savedTheme = localStorage.getItem('megag_theme');
      setTheme(savedTheme || 'light');

      // botão voltar preservando contexto
      const qs = new URLSearchParams();
      qs.set('page', 'tarefas');
      if (PRE_SPACE_ID) qs.set('space_id', PRE_SPACE_ID);
      if (PRE_LIST_ID) qs.set('list_id', PRE_LIST_ID);
      $('btnBack').href = '/importador/index.php?' + qs.toString();

      $('fCriadoPor').value = 'Felipe'; // ajuste depois pra sessão
      await loadSpaces();
      updateFormPreview();
    })();

    // =============================
    // AUTOCOMPLETE RESPONSÁVEL (BONITO)
    // =============================
    (function initResponsavelAutocomplete() {
      const inp = document.getElementById('fResponsavel');
      const hid = document.getElementById('fResponsavelLogin');
      const drop = document.getElementById('respDrop');
      const hint = document.getElementById('respHint');

      if (!inp || !hid || !drop || !hint) {
        console.warn('Autocomplete: elementos não encontrados');
        return;
      }

      let timer = null;
      let items = [];
      let activeIndex = -1;

      function closeDrop() {
        drop.style.display = 'none';
        drop.innerHTML = '';
        items = [];
        activeIndex = -1;
      }

      function openDrop() {
        if (!drop.innerHTML.trim()) return;
        drop.style.display = 'block';
      }

      function setActive(idx) {
        activeIndex = idx;
        const els = drop.querySelectorAll('.ac-item');
        els.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        if (els[activeIndex]) {
          els[activeIndex].scrollIntoView({
            block: 'nearest'
          });
        }
      }

      function selectItem(u) {
        // ✅ o input visível pode mostrar "Nome (LOGINID)" ou só nome, você escolhe
        inp.value = `${u.nome} (${u.loginid})`;
        hid.value = u.loginid; // ✅ esse é o que vai para o backend
        closeDrop();
        hint.textContent = `Selecionado: ${u.loginid}`;
        updateFormPreview();
      }

      function renderDrop(list) {
        drop.innerHTML = '';
        items = list.slice(0);

        if (!items.length) {
          closeDrop();
          hint.textContent = 'Nenhum usuário encontrado.';
          return;
        }

        items.forEach((u, idx) => {
          const div = document.createElement('div');
          div.className = 'ac-item';
          div.innerHTML = `
        <div class="ac-name">${escapeHtml(u.nome)}</div>
        <div class="ac-login">${escapeHtml(u.loginid)}</div>
      `;
          div.addEventListener('mousedown', (e) => {
            // mousedown pra não perder foco antes do click
            e.preventDefault();
            selectItem(u);
          });
          drop.appendChild(div);
        });

        hint.textContent = 'Use ↑ ↓ e Enter para selecionar.';
        openDrop();
        setActive(0);
      }

      async function fetchUsers(q) {
        const users = await apiGet(`${API}?entity=users&q=${encodeURIComponent(q)}&limit=15`);
        return users.map(u => ({
          nome: (u.NOME ?? u.nome ?? '').toString(),
          loginid: (u.LOGINID ?? u.loginid ?? '').toString()
        })).filter(u => u.loginid);
      }

      function clearSelectionIfTyping() {
        // se o usuário começar a digitar depois de selecionar, limpamos o hidden
        hid.value = '';
      }

      inp.addEventListener('input', () => {
        const q = (inp.value || '').trim();
        clearTimeout(timer);
        clearSelectionIfTyping();

        if (q.length < 2) {
          hint.textContent = '';
          closeDrop();
          return;
        }

        timer = setTimeout(async () => {
          try {
            const list = await fetchUsers(q);
            renderDrop(list);
          } catch (e) {
            console.error(e);
            hint.textContent = 'Falha ao buscar usuários.';
            closeDrop();
          }
        }, 250);
      });

      inp.addEventListener('keydown', (e) => {
        if (drop.style.display !== 'block') return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          setActive(Math.min(activeIndex + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          setActive(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (items[activeIndex]) selectItem(items[activeIndex]);
        } else if (e.key === 'Escape') {
          e.preventDefault();
          closeDrop();
        }
      });

      // fecha ao clicar fora
      document.addEventListener('mousedown', (e) => {
        if (e.target === inp) return;
        if (drop.contains(e.target)) return;
        closeDrop();
      });

      // util simples pra evitar XSS no innerHTML
      function escapeHtml(s) {
        return String(s)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", "&#039;");
      }

      console.log('Autocomplete bonito OK ✅');
    })();
  </script>

</body>

</html>
