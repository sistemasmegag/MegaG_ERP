(() => {
  const API2 = 'api/tasks.php';
  const STATUS_COLORS = ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444', '#14b8a6', '#f97316', '#6366f1'];
  const byId = (id) => document.getElementById(id);
  const state = {
    tasks: [],
    statuses: [],
    draggingId: null,
    draggingStatus: null,
    filters: {
      priority: 'ALL',
      owner: '',
      q: '',
    },
  };

  function showMsg(el, text, ok = true) {
    if (!el) return;
    if (!text) {
      el.style.display = 'none';
      el.textContent = '';
      return;
    }
    el.style.display = 'block';
    el.className = 'msg ' + (ok ? 'ok' : 'err');
    el.textContent = text;
  }

  function showTop(text, ok = true) {
    showMsg(byId('msgBox'), text, ok);
  }

  function showTaskModal(text, ok = true) {
    showMsg(byId('mMsg'), text, ok);
  }

  function showBoard(text, ok = true) {
    showMsg(byId('dynBoardMsg'), text, ok);
  }

  async function apiGet(url) {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    const j = await r.json();
    if (!j.success) throw new Error(j.error || 'Erro');
    return j.data;
  }

  async function apiSend(url, method, body) {
    const r = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: body ? JSON.stringify(body) : null,
    });
    const j = await r.json();
    if (!j.success) throw new Error(j.error || 'Erro');
    return j.data;
  }

  function currentUser() {
    return (byId('userDefault')?.value || '').trim() || window.CURRENT_TASK_USER || 'web';
  }

  function selectedText(id, fallback) {
    const sel = byId(id);
    if (!sel) return fallback;
    const opt = sel.options[sel.selectedIndex];
    return opt ? opt.textContent : fallback;
  }

  function refreshHero(total = state.tasks.length) {
    const spaceText = selectedText('spaceSelect', 'Nenhum space');
    const listText = selectedText('listSelect', 'Nenhuma list');

    if (byId('heroSpace')) byId('heroSpace').textContent = spaceText || 'Nenhum space';
    if (byId('heroList')) byId('heroList').textContent = listText || 'Nenhuma list';
    if (byId('heroUser')) byId('heroUser').textContent = currentUser();
    if (byId('contextSpaceBadge')) byId('contextSpaceBadge').textContent = spaceText || 'Nenhum';
    if (byId('contextListBadge')) byId('contextListBadge').textContent = listText || 'Nenhuma';
    if (byId('heroTotal')) byId('heroTotal').textContent = String(total);
    if (byId('countTotal')) byId('countTotal').textContent = String(total);
  }

  function cloneById(id) {
    const el = byId(id);
    if (!el || !el.parentNode) return null;
    const clone = el.cloneNode(true);
    el.parentNode.replaceChild(clone, el);
    return clone;
  }

  function normalizeStatus(status, index = 0) {
    return {
      id: status.ID ?? status.id ?? `status-${index}`,
      nome: status.NOME ?? status.nome ?? 'TODO',
      ordem: Number(status.ORDEM ?? status.ordem ?? index + 1),
      cor: status.COR ?? status.cor ?? STATUS_COLORS[index % STATUS_COLORS.length],
    };
  }

  function normalizeTask(task) {
    return {
      id: task.ID ?? task.id,
      titulo: task.TITULO ?? task.titulo ?? '',
      descricao: task.DESCRICAO ?? task.descricao ?? '',
      status: task.STATUS ?? task.status ?? 'TODO',
      prioridade: task.PRIORIDADE ?? task.prioridade ?? 'MED',
      responsavel: task.RESPONSAVEL ?? task.responsavel ?? '',
      data_entrega: task.DATA_ENTREGA ?? task.data_entrega ?? '',
      tags: task.TAGS ?? task.tags ?? '',
      criado_em: task.CRIADO_EM ?? task.criado_em ?? '',
    };
  }

  function ensureDynamicUi() {
    if (!byId('kanbanDynamicStyles')) {
      const style = document.createElement('style');
      style.id = 'kanbanDynamicStyles';
      style.textContent = `
        .kanban-dynamic-hidden { display:none !important; }
        #kanbanSummaryDynamic { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:18px; margin-top:18px; }
        .summary-status-card { padding:18px; min-height:360px; display:flex; flex-direction:column; }
        .summary-status-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px; }
        .summary-status-name { font-size:20px; font-weight:950; letter-spacing:-.02em; }
        .summary-status-sub { color:var(--saas-muted); font-size:12px; line-height:1.6; margin-top:6px; }
        .summary-status-empty { min-height:180px; border:1px dashed rgba(17,24,39,.14); border-radius:18px; display:flex; align-items:center; justify-content:center; text-align:center; color:var(--saas-muted); padding:20px; background:rgba(255,255,255,.42); line-height:1.6; }
        .summary-status-footer { margin-top:auto; padding-top:14px; display:flex; justify-content:flex-end; }
        .task.preview { cursor:default; min-height:200px; }
        .task.preview .ta { display:none; }
        .task.preview-note { margin:0 0 10px; color:var(--saas-muted); font-size:12px; line-height:1.55; }
        .status-chip { display:inline-flex; align-items:center; gap:8px; }
        .status-dot { width:10px; height:10px; border-radius:999px; display:inline-block; }
        .dynamic-board-backdrop { position:fixed; inset:0; display:none; z-index:10000; background:rgba(17,24,39,.58); backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:24px; }
        .dynamic-board-modal { width:min(1720px, calc(100vw - 48px)); height:min(92vh, 980px); background:var(--saas-bg); display:flex; flex-direction:column; border-radius:28px; overflow:hidden; border:1px solid rgba(255,255,255,.16); box-shadow:0 30px 80px rgba(0,0,0,.28); }
        .dynamic-board-head { padding:22px 24px 18px; border-bottom:1px solid var(--saas-border); background:linear-gradient(135deg, rgba(13,110,253,.12), rgba(255,255,255,.48)); display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
        .dynamic-board-head h3 { color:var(--saas-text); }
        .dynamic-board-head .hint { color:var(--saas-muted); }
        .dynamic-board-close { flex:0 0 auto; }
        .dynamic-board-body { flex:1; overflow:auto; padding:18px 22px 26px; }
        .dynamic-board-panel { border:1px solid var(--saas-border); border-radius:20px; background:rgba(255,255,255,.68); box-shadow:0 12px 24px rgba(17,24,39,.05); padding:14px; }
        .dynamic-board-topbar { display:grid; grid-template-columns:minmax(280px, 1.1fr) minmax(320px, 1.4fr); gap:16px; margin-bottom:16px; }
        .dynamic-board-create-title { margin:0 0 10px; font-size:12px; font-weight:900; letter-spacing:.18em; text-transform:uppercase; color:var(--saas-muted); }
        .dynamic-board-toolbar { display:grid; grid-template-columns:minmax(180px, 1fr) 92px auto; gap:10px; align-items:center; }
        .dynamic-board-toolbar input, .dynamic-board-toolbar select, .dynamic-board-filtersbar input, .dynamic-board-filtersbar select { min-height:44px; padding:10px 14px; border-radius:16px; border:1px solid var(--saas-border); background:rgba(255,255,255,.92); color:var(--saas-text); }
        .dynamic-board-toolbar .btnx { justify-content:center; }
        .dynamic-board-filtersbar { display:grid; grid-template-columns:180px 220px minmax(220px, 1fr); gap:10px; align-items:center; }
        .dynamic-board-filter-title { margin:0 0 10px; font-size:12px; font-weight:900; letter-spacing:.18em; text-transform:uppercase; color:var(--saas-muted); }
        .dynamic-board-filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:14px 0 16px; }
        .dynamic-board-filter-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--saas-border); border-radius:999px; background:rgba(255,255,255,.82); color:var(--saas-muted); font-size:12px; font-weight:800; }
        .dynamic-board-stats { display:flex; gap:10px; flex-wrap:wrap; margin:0 0 16px; }
        .dynamic-board-stat { display:flex; flex-direction:column; min-width:132px; padding:12px 14px; border-radius:16px; border:1px solid var(--saas-border); background:rgba(255,255,255,.72); }
        .dynamic-board-stat span { font-size:11px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:var(--saas-muted); }
        .dynamic-board-stat strong { margin-top:6px; font-size:22px; line-height:1; color:var(--saas-text); }
        #kanbanBoardDynamic { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:18px; align-items:start; }
        #kanbanBoardDynamic .kcol { min-height:620px; }
        .board-status-card { overflow:hidden; }
        .board-status-head { padding:16px 16px 12px; border-bottom:1px solid var(--saas-border); }
        .board-status-head .khead { margin-bottom:6px; }
        .board-status-head .ksub { margin:0; }
        .board-status-body { padding:16px; }
        .board-dropzone { min-height:260px; }
        .board-dropzone.is-over { outline:2px dashed rgba(13,110,253,.35); outline-offset:6px; background:rgba(13,110,253,.05); }
        @media (max-width:1050px) {
          .dynamic-board-backdrop { padding:12px; }
          .dynamic-board-modal { width:calc(100vw - 24px); height:calc(100vh - 24px); border-radius:22px; }
          .dynamic-board-head { flex-direction:column; }
          .dynamic-board-topbar { grid-template-columns:1fr; }
          .dynamic-board-toolbar, .dynamic-board-filtersbar { grid-template-columns:1fr; }
        }
      `;
      document.head.appendChild(style);
    }

    const legacyKanban = document.querySelector('.kanban');
    if (legacyKanban && !byId('kanbanSummaryDynamic')) {
      legacyKanban.classList.add('kanban-dynamic-hidden');
      const host = document.createElement('div');
      host.id = 'kanbanSummaryDynamic';
      legacyKanban.parentNode.insertBefore(host, legacyKanban);
    } else if (legacyKanban) {
      legacyKanban.classList.add('kanban-dynamic-hidden');
    }

    if (!byId('dynamicBoardBackdrop')) {
      const wrap = document.createElement('div');
      wrap.innerHTML = `
        <div class="dynamic-board-backdrop" id="dynamicBoardBackdrop">
          <div class="dynamic-board-modal">
            <div class="dynamic-board-head">
              <div>
                <h3 id="dynBoardTitle" style="margin:0;font-size:20px;font-weight:950;">Quadro completo da list</h3>
                <div id="dynBoardHint" class="hint" style="margin-top:6px;">Todos os status e todas as tasks da list selecionada.</div>
              </div>
              <button class="btnx dynamic-board-close" id="dynBtnCloseBoard">Fechar</button>
            </div>
            <div class="dynamic-board-body">
              <div id="dynBoardMsg" class="msg"></div>
              <div class="dynamic-board-topbar">
                <div class="dynamic-board-panel">
                  <p class="dynamic-board-create-title">Novo status</p>
                  <div class="dynamic-board-toolbar">
                    <input id="dynStatusNome" placeholder="Nome do novo status" />
                    <input id="dynStatusOrdem" type="number" value="0" placeholder="Ordem" />
                    <button class="btnx primary" id="dynBtnCreateStatus">Criar status</button>
                  </div>
                </div>
                <div class="dynamic-board-panel">
                  <p class="dynamic-board-filter-title">Filtros rapidos</p>
                  <div class="dynamic-board-filtersbar">
                    <select id="dynFilterPriority">
                      <option value="ALL">Todas prioridades</option>
                      <option value="URGENT">Urgente</option>
                      <option value="HIGH">Alta</option>
                      <option value="MED">Media</option>
                      <option value="LOW">Baixa</option>
                    </select>
                    <input id="dynFilterOwner" placeholder="Responsavel" />
                    <input id="dynFilterQ" placeholder="Buscar por titulo, tag ou dono" />
                  </div>
                </div>
              </div>
              <div class="dynamic-board-stats" id="dynBoardStats"></div>
              <div class="dynamic-board-filters" id="dynFilterSummary"></div>
              <div id="kanbanBoardDynamic"></div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(wrap.firstElementChild);
    }
  }

  function getStatuses() {
    const list = state.statuses.map(normalizeStatus);
    const known = new Set(list.map((item) => String(item.nome).toUpperCase()));
    state.tasks.map(normalizeTask).forEach((task, index) => {
      const key = String(task.status || '').toUpperCase();
      if (!key || known.has(key)) return;
      known.add(key);
      list.push(normalizeStatus({ nome: task.status, ordem: 100 + index }, list.length));
    });
    return list.sort((a, b) => a.ordem - b.ordem || String(a.nome).localeCompare(String(b.nome)));
  }

  function taskScore(task) {
    const prioMap = { LOW: 1, MED: 2, HIGH: 3, URGENT: 4 };
    const prio = prioMap[String(task.prioridade || 'MED').toUpperCase()] || 2;
    let due = 0;
    if (task.data_entrega) {
      const dt = new Date(String(task.data_entrega).substring(0, 10) + 'T00:00:00');
      if (!Number.isNaN(dt.getTime())) {
        const base = new Date();
        const today = new Date(base.getFullYear(), base.getMonth(), base.getDate());
        const diff = Math.ceil((dt - today) / 86400000);
        if (diff < 0) due = 120;
        else if (diff === 0) due = 80;
        else if (diff <= 2) due = 40;
      }
    }
    return (prio * 100) + due;
  }

  function buildBuckets() {
    const statuses = getStatuses();
    const buckets = {};
    statuses.forEach((status) => { buckets[status.nome] = []; });
    getFilteredTasks().forEach((task) => {
      const key = buckets[task.status] ? task.status : (statuses[0]?.nome || 'TODO');
      buckets[key] = buckets[key] || [];
      buckets[key].push(task);
    });
    Object.values(buckets).forEach((list) => list.sort((a, b) => taskScore(b) - taskScore(a)));
    return { statuses, buckets };
  }

  function getFilteredTasks() {
    return state.tasks.map(normalizeTask).filter((task) => {
      if (state.filters.priority !== 'ALL' && String(task.prioridade || '').toUpperCase() !== state.filters.priority) {
        return false;
      }

      if (state.filters.owner) {
        const owner = String(task.responsavel || '').toUpperCase();
        if (!owner.includes(state.filters.owner)) return false;
      }

      if (state.filters.q) {
        const q = state.filters.q;
        const hay = `${task.titulo} ${task.tags} ${task.responsavel}`.toUpperCase();
        if (!hay.includes(q)) return false;
      }

      return true;
    });
  }

  function fillSummaryMetrics(statuses, buckets) {
    const host = byId('summaryMetrics');
    if (!host) return;
    host.innerHTML = '';

    const total = document.createElement('div');
    total.className = 'metric-card total';
    total.innerHTML = `<span>Total</span><strong id="countTotal">${state.tasks.length}</strong>`;
    host.appendChild(total);

    statuses.slice(0, 3).forEach((status, index) => {
      const card = document.createElement('div');
      card.className = `metric-card ${index === 0 ? 'todo' : index === 1 ? 'doing' : 'done'}`;
      card.innerHTML = `<span>${status.nome}</span><strong>${(buckets[status.nome] || []).length}</strong>`;
      host.appendChild(card);
    });
  }

  function chip(text) {
    const span = document.createElement('span');
    span.className = 'chip';
    span.textContent = text;
    return span;
  }

  function taskDetailsHref(taskId) {
    return (
      `index.php?page=tarefas_detalhes` +
      `&task_id=${encodeURIComponent(taskId)}` +
      `&space_id=${encodeURIComponent(byId('spaceSelect')?.value || '')}` +
      `&list_id=${encodeURIComponent(byId('listSelect')?.value || '')}`
    );
  }

  function buildTaskCard(task, mode = 'board') {
    const compact = mode === 'preview';
    const statuses = getStatuses();
    const status = task.status || statuses[0]?.nome || 'TODO';
    const prioClass = String(task.prioridade || 'med').toLowerCase();

    const card = document.createElement('div');
    card.className = `task prio-${prioClass}${compact ? ' preview' : ''}`;
    card.dataset.taskId = String(task.id);
    card.dataset.status = status;
    card.draggable = !compact;

    if (!compact) {
      card.addEventListener('dragstart', (ev) => {
        state.draggingId = task.id;
        state.draggingStatus = status;
        card.classList.add('is-dragging');
        if (ev.dataTransfer) {
          ev.dataTransfer.effectAllowed = 'move';
          ev.dataTransfer.setData('text/plain', String(task.id));
        }
      });
      card.addEventListener('dragend', () => {
        card.classList.remove('is-dragging');
        clearBoardDropzones();
        state.draggingId = null;
        state.draggingStatus = null;
      });
    }

    const top = document.createElement('div');
    top.className = 'task-top';
    top.innerHTML = `
      <div>
        <div class="task-id">Task #${task.id}</div>
        <p class="tt">${task.titulo || '(sem titulo)'}</p>
      </div>
      <span class="task-badge ${prioClass}">${task.prioridade || 'MED'}</span>
    `;
    card.appendChild(top);

    if (compact) {
      const note = document.createElement('p');
      note.className = 'task-preview-note';
      note.textContent = taskScore(task) >= 300
        ? 'Item mais sensivel deste status por prioridade ou vencimento.'
        : 'Item em destaque para acompanhamento rapido deste status.';
      card.appendChild(note);
    }

    const meta = document.createElement('div');
    meta.className = 'meta';
    if (task.prioridade) meta.appendChild(chip(`PRIO: ${task.prioridade}`));
    if (task.responsavel) meta.appendChild(chip(`RESP: ${task.responsavel}`));
    if (task.data_entrega) meta.appendChild(chip(`ENT: ${task.data_entrega}`));
    if (task.tags) meta.appendChild(chip(`TAGS: ${task.tags}`));
    if (task.criado_em) meta.appendChild(chip(`CRIADO: ${task.criado_em}`));
    card.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'ta';

    if (compact) {
      const link = document.createElement('a');
      link.className = 'btnx';
      link.textContent = 'Abrir detalhe';
      link.href = taskDetailsHref(task.id);
      link.style.textDecoration = 'none';
      link.style.display = 'inline-flex';
      link.style.alignItems = 'center';
      actions.appendChild(link);
      card.appendChild(actions);
      return card;
    }

    const select = document.createElement('select');
    select.className = 'select-mini';
    statuses.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = item.nome;
      opt.textContent = item.nome;
      if (item.nome === status) opt.selected = true;
      select.appendChild(opt);
    });
    select.addEventListener('change', async () => {
      try {
        await moveTask(task.id, select.value);
      } catch (e) {
        showTop(e.message, false);
      }
    });

    const edit = document.createElement('a');
    edit.className = 'btnx';
    edit.textContent = 'Editar';
    edit.href = taskDetailsHref(task.id);
    edit.style.textDecoration = 'none';
    edit.style.display = 'inline-flex';
    edit.style.alignItems = 'center';

    const remove = document.createElement('button');
    remove.className = 'btnx danger';
    remove.textContent = 'Excluir';
    remove.addEventListener('click', async () => {
      if (!confirm(`Excluir task #${task.id}?`)) return;
      try {
        await fetch(`${API2}?entity=tasks&task_id=${task.id}&user=${encodeURIComponent(currentUser())}`, {
          method: 'DELETE',
        }).then((r) => r.json()).then((j) => {
          if (!j.success) throw new Error(j.error || 'Erro');
        });
        showTop('Task excluida.', true);
        await loadBoardData();
      } catch (e) {
        showTop(e.message, false);
      }
    });

    actions.appendChild(select);
    actions.appendChild(edit);
    actions.appendChild(remove);
    card.appendChild(actions);
    return card;
  }

  function renderSummaryBoard() {
    const host = byId('kanbanSummaryDynamic');
    if (!host) return;
    const { statuses, buckets } = buildBuckets();
    fillSummaryMetrics(statuses, buckets);
    refreshHero();
    host.innerHTML = '';

    statuses.forEach((status, index) => {
      const tasks = buckets[status.nome] || [];
      const topTask = tasks[0] || null;
      const card = document.createElement('div');
      card.className = 'saas-card';

      const shell = document.createElement('div');
      shell.className = 'summary-status-card';
      shell.innerHTML = `
        <div class="summary-status-top">
          <div>
            <div class="summary-status-name">
              <span class="status-chip"><span class="status-dot" style="background:${status.cor || STATUS_COLORS[index % STATUS_COLORS.length]}"></span>${status.nome}</span>
            </div>
            <div class="summary-status-sub">${tasks.length} task(s) neste status.</div>
          </div>
          <div class="pill">${tasks.length}</div>
        </div>
      `;

      if (topTask) {
        shell.appendChild(buildTaskCard(topTask, 'preview'));
      } else {
        const empty = document.createElement('div');
        empty.className = 'summary-status-empty';
        empty.textContent = 'Sem tasks neste status por enquanto.';
        shell.appendChild(empty);
      }

      const footer = document.createElement('div');
      footer.className = 'summary-status-footer';
      const btn = document.createElement('button');
      btn.className = 'btnx';
      btn.textContent = 'Ver mais';
      btn.addEventListener('click', () => openBoardModal(status.nome));
      footer.appendChild(btn);
      shell.appendChild(footer);

      card.appendChild(shell);
      host.appendChild(card);
    });
  }

  function renderFilterSummary() {
    const host = byId('dynFilterSummary');
    if (!host) return;
    host.innerHTML = '';

    const chips = [];
    chips.push(`Exibindo ${getFilteredTasks().length} de ${state.tasks.length} tasks`);
    if (state.filters.priority !== 'ALL') chips.push(`Prioridade: ${state.filters.priority}`);
    if (state.filters.owner) chips.push(`Responsavel: ${state.filters.owner}`);
    if (state.filters.q) chips.push(`Busca: ${state.filters.q}`);

    chips.forEach((text) => {
      const chip = document.createElement('div');
      chip.className = 'dynamic-board-filter-chip';
      chip.textContent = text;
      host.appendChild(chip);
    });
  }

  function renderBoardStats(statuses, buckets) {
    const host = byId('dynBoardStats');
    if (!host) return;
    host.innerHTML = '';

    const filteredTasks = getFilteredTasks();
    const overdue = filteredTasks.filter((task) => {
      if (!task.data_entrega) return false;
      const dt = new Date(String(task.data_entrega).substring(0, 10) + 'T00:00:00');
      if (Number.isNaN(dt.getTime())) return false;
      const base = new Date();
      const today = new Date(base.getFullYear(), base.getMonth(), base.getDate());
      return dt < today;
    }).length;

    const stats = [
      { label: 'Tasks exibidas', value: filteredTasks.length },
      { label: 'Status ativos', value: statuses.length },
      { label: 'Atrasadas', value: overdue },
      { label: 'Maior coluna', value: Math.max(0, ...statuses.map((status) => (buckets[status.nome] || []).length)) },
    ];

    stats.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'dynamic-board-stat';
      card.innerHTML = `<span>${item.label}</span><strong>${item.value}</strong>`;
      host.appendChild(card);
    });
  }

  function clearBoardDropzones() {
    document.querySelectorAll('.board-dropzone.is-over').forEach((el) => el.classList.remove('is-over'));
  }

  function bindBoardDropzones() {
    const board = byId('kanbanBoardDynamic');
    if (!board) return;
    board.querySelectorAll('.board-dropzone').forEach((zone) => {
      if (zone.dataset.bound === '1') return;
      zone.dataset.bound = '1';
      zone.addEventListener('dragover', (ev) => {
        ev.preventDefault();
        zone.classList.add('is-over');
      });
      zone.addEventListener('dragenter', (ev) => {
        ev.preventDefault();
        zone.classList.add('is-over');
      });
      zone.addEventListener('dragleave', (ev) => {
        if (!zone.contains(ev.relatedTarget)) zone.classList.remove('is-over');
      });
      zone.addEventListener('drop', async (ev) => {
        ev.preventDefault();
        const nextStatus = zone.dataset.status || '';
        clearBoardDropzones();
        if (!state.draggingId || !nextStatus || nextStatus === state.draggingStatus) return;
        try {
          await moveTask(state.draggingId, nextStatus);
        } catch (e) {
          showBoard(e.message, false);
        } finally {
          state.draggingId = null;
          state.draggingStatus = null;
        }
      });
    });
  }

  function openBoardModal(focusStatus = null) {
    ensureDynamicUi();
    renderFullBoard(focusStatus);
    const backdrop = byId('dynamicBoardBackdrop');
    if (!backdrop) {
      showTop('Nao foi possivel abrir o quadro completo agora.', false);
      return;
    }
    backdrop.style.display = 'flex';
    showBoard('');
  }

  function closeBoardModal() {
    if (byId('dynamicBoardBackdrop')) byId('dynamicBoardBackdrop').style.display = 'none';
  }

  function renderFullBoard(focusStatus = null) {
    const board = byId('kanbanBoardDynamic');
    const title = byId('dynBoardTitle');
    const hint = byId('dynBoardHint');
    if (!board || !title || !hint) return;
    const { statuses, buckets } = buildBuckets();
    board.innerHTML = '';
    title.textContent = `Quadro completo: ${selectedText('listSelect', 'Nenhuma list')}`;
    hint.textContent = `${getFilteredTasks().length} task(s) distribuidas em ${statuses.length} status.`;
    renderBoardStats(statuses, buckets);
    renderFilterSummary();

    statuses.forEach((status, index) => {
      const tasks = buckets[status.nome] || [];
      const wrapper = document.createElement('div');
      wrapper.className = 'saas-card board-status-card';
      wrapper.dataset.statusKey = status.nome;

      const head = document.createElement('div');
      head.className = 'board-status-head';
      head.style.background = `linear-gradient(135deg, ${status.cor || STATUS_COLORS[index % STATUS_COLORS.length]}22, rgba(255,255,255,.75))`;
      head.innerHTML = `
        <div class="khead">
          <div class="kname"><span class="status-chip"><span class="status-dot" style="background:${status.cor || STATUS_COLORS[index % STATUS_COLORS.length]}"></span>${status.nome}</span></div>
          <div class="pill">${tasks.length}</div>
        </div>
        <div class="ksub">Todos os cards deste status ficam aqui no quadro completo.</div>
      `;

      const body = document.createElement('div');
      body.className = 'board-status-body';
      const zone = document.createElement('div');
      zone.className = 'dropzone board-dropzone';
      zone.dataset.status = status.nome;

      if (!tasks.length) {
        const empty = document.createElement('div');
        empty.className = 'empty-col';
        empty.textContent = 'Nenhuma task neste status ainda.';
        zone.appendChild(empty);
      } else {
        tasks.forEach((task) => zone.appendChild(buildTaskCard(task, 'board')));
      }

      body.appendChild(zone);
      wrapper.appendChild(head);
      wrapper.appendChild(body);
      board.appendChild(wrapper);
    });

    bindBoardDropzones();

    if (focusStatus) {
      const target = board.querySelector(`[data-status-key="${CSS.escape(focusStatus)}"]`);
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
  }

  function fillTaskStatusOptions(selected = null) {
    const sel = byId('mStatus');
    if (!sel) return;
    const statuses = getStatuses();
    sel.innerHTML = '';
    statuses.forEach((status, index) => {
      const opt = document.createElement('option');
      opt.value = status.nome;
      opt.textContent = status.nome;
      if ((selected && status.nome === selected) || (!selected && index === 0)) opt.selected = true;
      sel.appendChild(opt);
    });
  }

  function openTaskModal() {
    closeBoardModal();
    const taskBackdrop = byId('backdrop');
    if (!taskBackdrop) {
      showTop('Nao foi possivel abrir o editor da task agora.', false);
      return;
    }
    taskBackdrop.style.display = 'flex';
    showTaskModal('');
  }

  function closeTaskModal() {
    byId('backdrop').style.display = 'none';
  }

  function openNewModal() {
    const listId = parseInt(byId('listSelect')?.value || '0', 10);
    if (!listId) {
      showTop('Selecione uma list antes de criar uma task.', false);
      return;
    }
    byId('mTitle').textContent = 'Nova Task';
    byId('mHint').textContent = 'A task sera criada na list selecionada.';
    byId('btnDelete').style.display = 'none';
    byId('mTaskId').value = '';
    byId('mTitulo').value = '';
    byId('mDescricao').value = '';
    fillTaskStatusOptions(getStatuses()[0]?.nome || 'TODO');
    byId('mPrioridade').value = 'MED';
    byId('mResponsavel').value = currentUser();
    byId('mEntrega').value = '';
    byId('mTags').value = '';
    openTaskModal();
  }

  function openEditModal(task) {
    byId('mTitle').textContent = `Editar Task #${task.id}`;
    byId('mHint').textContent = 'Salvar atualiza a task e reaplica o status selecionado.';
    byId('btnDelete').style.display = 'inline-flex';
    byId('mTaskId').value = task.id;
    byId('mTitulo').value = task.titulo || '';
    byId('mDescricao').value = task.descricao || '';
    fillTaskStatusOptions(task.status || 'TODO');
    byId('mPrioridade').value = task.prioridade || 'MED';
    byId('mResponsavel').value = task.responsavel || '';
    byId('mEntrega').value = task.data_entrega || '';
    byId('mTags').value = task.tags || '';
    openTaskModal();
  }

  async function saveTask() {
    const list_id = parseInt(byId('listSelect')?.value || '0', 10);
    if (!list_id) return showTaskModal('Selecione uma list.', false);

    const task_id = (byId('mTaskId')?.value || '').trim();
    const titulo = (byId('mTitulo')?.value || '').trim();
    const descricao = byId('mDescricao')?.value || null;
    const status = byId('mStatus')?.value || 'TODO';
    const prioridade = byId('mPrioridade')?.value || 'MED';
    const responsavel = (byId('mResponsavel')?.value || '').trim() || null;
    const data_entrega = (byId('mEntrega')?.value || '').trim() || null;
    const tags = (byId('mTags')?.value || '').trim() || null;

    if (!titulo) return showTaskModal('Informe o titulo.', false);

    try {
      if (!task_id) {
        const r = await apiSend(`${API2}?entity=tasks`, 'POST', {
          list_id,
          titulo,
          descricao,
          status,
          prioridade,
          tags,
          responsavel,
          data_entrega,
          criado_por: currentUser(),
        });
        showTop(`Task criada (id=${r.id}).`, true);
        closeTaskModal();
        await loadBoardData();
        return;
      }

      await apiSend(`${API2}?entity=tasks&task_id=${encodeURIComponent(task_id)}`, 'PUT', {
        titulo,
        descricao,
        prioridade,
        tags,
        responsavel,
        data_entrega,
        user: currentUser(),
      });

      await apiSend(`${API2}?entity=tasks&action=move`, 'PATCH', {
        task_id: parseInt(task_id, 10),
        status,
        user: currentUser(),
      });

      showTop('Task atualizada.', true);
      closeTaskModal();
      await loadBoardData();
    } catch (e) {
      showTaskModal(e.message, false);
    }
  }

  async function deleteTaskFromModal() {
    const task_id = (byId('mTaskId')?.value || '').trim();
    if (!task_id) return;
    if (!confirm(`Excluir task #${task_id}?`)) return;
    try {
      await fetch(`${API2}?entity=tasks&task_id=${encodeURIComponent(task_id)}&user=${encodeURIComponent(currentUser())}`, {
        method: 'DELETE',
      }).then((r) => r.json()).then((j) => {
        if (!j.success) throw new Error(j.error || 'Erro');
      });
      showTop('Task excluida.', true);
      closeTaskModal();
      await loadBoardData();
    } catch (e) {
      showTaskModal(e.message, false);
    }
  }

  async function moveTask(taskId, status) {
    await apiSend(`${API2}?entity=tasks&action=move`, 'PATCH', {
      task_id: taskId,
      status,
      user: currentUser(),
    });
    await loadBoardData();
    showTop('Status atualizado.', true);
  }

  async function loadStatuses(listId) {
    state.statuses = await apiGet(`${API2}?entity=statuses&list_id=${listId}&user=${encodeURIComponent(currentUser())}`);
  }

  async function loadTasks(listId) {
    state.tasks = await apiGet(`${API2}?entity=tasks&list_id=${listId}&user=${encodeURIComponent(currentUser())}`);
  }

  async function loadBoardData() {
    const listId = parseInt(byId('listSelect')?.value || '0', 10);
    if (!listId) {
      state.tasks = [];
      state.statuses = [];
      renderSummaryBoard();
      return;
    }
    await Promise.all([loadStatuses(listId), loadTasks(listId)]);
    renderSummaryBoard();
    if (byId('dynamicBoardBackdrop')?.style.display === 'flex') renderFullBoard();
  }

  async function loadLists() {
    const spaceId = parseInt(byId('spaceSelect')?.value || '0', 10);
    const sel = byId('listSelect');
    sel.innerHTML = '';

    if (!spaceId) {
      sel.innerHTML = '<option value="">Selecione um space</option>';
      state.tasks = [];
      state.statuses = [];
      renderSummaryBoard();
      return;
    }

    const lists = await apiGet(`${API2}?entity=lists&space_id=${spaceId}&user=${encodeURIComponent(currentUser())}`);
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = lists.length ? 'Selecione...' : 'Sem lists (crie uma)';
    sel.appendChild(opt0);

    lists.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = item.ID ?? item.id;
      opt.textContent = `${item.NOME ?? item.nome} (#${item.ID ?? item.id})`;
      sel.appendChild(opt);
    });

    if (lists.length) {
      sel.value = String(lists[0].ID ?? lists[0].id);
      await loadBoardData();
    } else {
      state.tasks = [];
      state.statuses = [];
      renderSummaryBoard();
    }
    refreshHero();
  }

  async function loadSpaces() {
    const sel = byId('spaceSelect');
    const spaces = await apiGet(`${API2}?entity=spaces&only_active=S&user=${encodeURIComponent(currentUser())}`);
    sel.innerHTML = '';

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = spaces.length ? 'Selecione...' : 'Sem spaces (crie um)';
    sel.appendChild(opt0);

    spaces.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = item.ID ?? item.id;
      opt.textContent = `${item.NOME ?? item.nome} (#${item.ID ?? item.id})`;
      sel.appendChild(opt);
    });

    if (spaces.length) {
      sel.value = String(spaces[0].ID ?? spaces[0].id);
      await loadLists();
    } else {
      byId('listSelect').innerHTML = '<option value="">Sem lists</option>';
      state.tasks = [];
      state.statuses = [];
      renderSummaryBoard();
    }
    refreshHero();
  }

  async function createSpace() {
    const nome = (byId('spaceNome')?.value || '').trim();
    const criado_por = (byId('spaceCriadoPor')?.value || '').trim();
    if (!nome) return showTop('Informe nome do Space.', false);
    if (!criado_por) return showTop('Informe Criado por.', false);
    const r = await apiSend(`${API2}?entity=spaces`, 'POST', { nome, criado_por });
    showTop(`Space criado (id=${r.id}).`, true);
    byId('spaceNome').value = '';
    await loadSpaces();
  }

  async function createList() {
    const space_id = parseInt(byId('spaceSelect')?.value || '0', 10);
    const nome = (byId('listNome')?.value || '').trim();
    const ordem = parseInt(byId('listOrdem')?.value || '0', 10);
    const criado_por = (byId('listCriadoPor')?.value || '').trim();
    const participantes = (byId('listParticipantes')?.value || '').trim();
    if (!space_id) return showTop('Selecione um Space.', false);
    if (!nome) return showTop('Informe nome da List.', false);
    if (!criado_por) return showTop('Informe Criado por.', false);
    const r = await apiSend(`${API2}?entity=lists`, 'POST', {
      space_id,
      nome,
      ordem,
      criado_por,
      participantes,
    });
    showTop(`List criada (id=${r.id}).`, true);
    byId('listNome').value = '';
    byId('listParticipantes').value = '';
    await loadLists();
  }

  async function createStatus() {
    const list_id = parseInt(byId('listSelect')?.value || '0', 10);
    const nome = (byId('dynStatusNome')?.value || '').trim();
    const ordem = parseInt(byId('dynStatusOrdem')?.value || '0', 10);
    if (!list_id) return showBoard('Selecione uma list antes de criar status.', false);
    if (!nome) return showBoard('Informe o nome do novo status.', false);
    try {
      await apiSend(`${API2}?entity=statuses`, 'POST', {
        list_id,
        nome,
        ordem,
        criado_por: currentUser(),
      });
      byId('dynStatusNome').value = '';
      byId('dynStatusOrdem').value = '0';
      showBoard('Novo status criado com sucesso.', true);
      await loadBoardData();
      openBoardModal(nome);
    } catch (e) {
      showBoard(e.message, false);
    }
  }

  function rebindControls() {
    const spaceSelect = cloneById('spaceSelect');
    const listSelect = cloneById('listSelect');
    const btnReload = cloneById('btnReload');
    const btnNewTask = cloneById('btnNewTask');
    const btnCreateSpace = cloneById('btnCreateSpace');
    const btnCreateList = cloneById('btnCreateList');
    const btnSave = cloneById('btnSave');
    const btnDelete = cloneById('btnDelete');
    const btnClose = cloneById('btnClose');

    if (spaceSelect) {
      spaceSelect.addEventListener('change', async () => {
        try {
          showTop('');
          await loadLists();
        } catch (e) {
          showTop(e.message, false);
        }
      });
    }

    if (listSelect) {
      listSelect.addEventListener('change', async () => {
        try {
          showTop('');
          await loadBoardData();
        } catch (e) {
          showTop(e.message, false);
        }
      });
    }

    if (btnReload) btnReload.addEventListener('click', async () => {
      try {
        showTop('');
        await loadSpaces();
      } catch (e) {
        showTop(e.message, false);
      }
    });
    if (btnNewTask) btnNewTask.addEventListener('click', openNewModal);
    if (btnCreateSpace) btnCreateSpace.addEventListener('click', async () => {
      try { await createSpace(); } catch (e) { showTop(e.message, false); }
    });
    if (btnCreateList) btnCreateList.addEventListener('click', async () => {
      try { await createList(); } catch (e) { showTop(e.message, false); }
    });
    if (btnSave) btnSave.addEventListener('click', saveTask);
    if (btnDelete) btnDelete.addEventListener('click', deleteTaskFromModal);
    if (btnClose) btnClose.addEventListener('click', closeTaskModal);
    if (byId('dynBtnCloseBoard')) byId('dynBtnCloseBoard').addEventListener('click', closeBoardModal);
    if (byId('dynBtnCreateStatus')) byId('dynBtnCreateStatus').addEventListener('click', createStatus);
    if (byId('dynamicBoardBackdrop')) byId('dynamicBoardBackdrop').addEventListener('click', (ev) => {
      if (ev.target?.id === 'dynamicBoardBackdrop') closeBoardModal();
    });
    if (byId('dynFilterPriority')) byId('dynFilterPriority').addEventListener('change', () => {
      state.filters.priority = byId('dynFilterPriority').value || 'ALL';
      renderFullBoard();
    });
    if (byId('dynFilterOwner')) byId('dynFilterOwner').addEventListener('input', () => {
      state.filters.owner = (byId('dynFilterOwner').value || '').trim().toUpperCase();
      renderFullBoard();
    });
    if (byId('dynFilterQ')) byId('dynFilterQ').addEventListener('input', () => {
      state.filters.q = (byId('dynFilterQ').value || '').trim().toUpperCase();
      renderFullBoard();
    });
  }

  (async () => {
    try {
      ensureDynamicUi();
      rebindControls();
      document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') {
          closeBoardModal();
          closeTaskModal();
        }
      });
      if (byId('userDefault')) {
        byId('userDefault').value = byId('userDefault').value || window.CURRENT_TASK_USER || '';
        byId('userDefault').addEventListener('input', () => refreshHero());
      }
      if (byId('spaceCriadoPor')) byId('spaceCriadoPor').value = byId('spaceCriadoPor').value || currentUser();
      if (byId('listCriadoPor')) byId('listCriadoPor').value = byId('listCriadoPor').value || currentUser();
      await loadSpaces();
    } catch (e) {
      showTop(e.message, false);
    }
  })();
})();
