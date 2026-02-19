</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

<!-- css para o bagde de notificação e painel de notificações, inspirado no estilo do Bootstrap 5 e adaptado para um visual moderno e clean, com suporte a temas claro e escuro -->
<style>
  .mg-notif-fab {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 9999;
    width: 56px;
    height: 56px;
    border-radius: 18px;
    border: 1px solid rgba(17, 24, 39, .12);
    background: rgba(255, 255, 255, .85);
    backdrop-filter: blur(10px);
    box-shadow: 0 16px 40px rgba(17, 24, 39, .18);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    user-select: none;
  }

  html[data-theme="dark"] .mg-notif-fab {
    background: rgba(255, 255, 255, .06);
    border-color: rgba(255, 255, 255, .12);
    box-shadow: 0 18px 44px rgba(0, 0, 0, .35);
  }

  .mg-notif-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    background: #dc3545;
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, .9);
  }

  html[data-theme="dark"] .mg-notif-badge {
    border-color: rgba(0, 0, 0, .35);
  }

  .mg-notif-panel {
    position: fixed;
    right: 22px;
    bottom: 90px;
    width: 360px;
    max-width: calc(100vw - 44px);
    max-height: 60vh;
    overflow: hidden;
    z-index: 9999;
    border-radius: 18px;
    border: 1px solid rgba(17, 24, 39, .12);
    background: rgba(255, 255, 255, .92);
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 44px rgba(17, 24, 39, .18);
    display: none;
  }

  html[data-theme="dark"] .mg-notif-panel {
    background: rgba(30, 30, 45, .92);
    border-color: rgba(255, 255, 255, .10);
    box-shadow: 0 18px 44px rgba(0, 0, 0, .45);
  }

  .mg-notif-panel.open {
    display: block;
  }

  .mg-notif-head {
    padding: 12px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(17, 24, 39, .10);
  }

  html[data-theme="dark"] .mg-notif-head {
    border-bottom-color: rgba(255, 255, 255, .10);
  }

  .mg-notif-title {
    font-weight: 900;
    margin: 0;
    font-size: 14px;
  }

  .mg-notif-actions {
    display: flex;
    gap: 8px;
  }

  .mg-notif-btn {
    border-radius: 999px;
    padding: 6px 10px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid rgba(17, 24, 39, .12);
    background: rgba(255, 255, 255, .65);
  }

  html[data-theme="dark"] .mg-notif-btn {
    background: rgba(255, 255, 255, .06);
    border-color: rgba(255, 255, 255, .10);
    color: rgba(255, 255, 255, .90);
  }

  .mg-notif-list {
    padding: 10px;
    overflow: auto;
    max-height: calc(60vh - 56px);
  }

  .mg-notif-item {
    border: 1px solid rgba(17, 24, 39, .10);
    border-radius: 14px;
    background: rgba(255, 255, 255, .65);
    padding: 10px;
  }

  html[data-theme="dark"] .mg-notif-item {
    background: rgba(255, 255, 255, .05);
    border-color: rgba(255, 255, 255, .10);
  }

  .mg-notif-item+.mg-notif-item {
    margin-top: 10px;
  }

  .mg-notif-item .t {
    font-weight: 900;
    margin: 0;
    font-size: 13px;
  }

  .mg-notif-item .m {
    margin: 6px 0 0;
    font-size: 12px;
    opacity: .85;
  }

  .mg-notif-item .meta {
    margin-top: 8px;
    font-size: 11px;
    opacity: .65;
    display: flex;
    justify-content: space-between;
    gap: 10px;
  }

  .mg-notif-item.unread {
    box-shadow: 0 0 0 5px rgba(13, 110, 253, .10);
    border-color: rgba(13, 110, 253, .25);
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  function toggleMenu(forceClose = false) {
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !overlay) return;

    if (forceClose) {
      sidebar.classList.remove('show');
    } else {
      sidebar.classList.toggle('show');
    }

    const isOpen = sidebar.classList.contains('show');

    // Overlay
    overlay.style.display = isOpen ? 'block' : 'none';

    // UX mobile: evita scroll do conteúdo quando menu estiver aberto
    document.body.style.overflow = isOpen ? 'hidden' : '';
  }

  // Fecha menu com ESC (Clean SaaS UX)
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      toggleMenu(true);
    }
  });

  // Se o overlay já estiver visível por algum motivo, garante estado consistente
  window.addEventListener('load', function() {
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;

    const isOpen = sidebar.classList.contains('show');
    overlay.style.display = isOpen ? 'block' : 'none';
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });
</script>

<!-- Modal de Permissão (Global) -->
<div class="modal fade" id="modalPermissao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4" style="border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text);">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold text-danger">
          <i class="bi bi-shield-lock-fill me-2"></i> Acesso negado
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body text-muted" id="modalPermissaoMsg">
        Você não possui permissão para realizar esta ação.
      </div>

      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
          Entendi
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Notificações (Global) -->
<div class="mg-notif-fab" id="mgNotifFab" title="Notificações">
  <span style="font-size:20px;">🔔</span>
  <div class="mg-notif-badge" id="mgNotifBadge" style="display:none;">0</div>
</div>

<div class="mg-notif-panel" id="mgNotifPanel">
  <div class="mg-notif-head">
    <p class="mg-notif-title">Notificações</p>
    <div class="mg-notif-actions">
      <button class="mg-notif-btn" id="mgNotifReadAll" type="button">Ler todas</button>
      <button class="mg-notif-btn" id="mgNotifClose" type="button">Fechar</button>
    </div>
  </div>
  <div class="mg-notif-list" id="mgNotifList">
    <div style="opacity:.7;font-size:12px;">Carregando...</div>
  </div>
</div>


<script>
  window.mostrarModalPermissao = function(msg) {
    const elMsg = document.getElementById('modalPermissaoMsg');
    if (elMsg) elMsg.textContent = msg || 'Você não possui permissão para acessar esta página.';
    const modalEl = document.getElementById('modalPermissao');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  };

  // Intercepta cliques nos links protegidos da sidebar
  document.addEventListener('click', function(e) {
    const a = e.target.closest('a[data-allowed]');
    if (!a) return;

    const allowed = a.dataset.allowed === '0';
    if (!allowed) {
      e.preventDefault();
      e.stopPropagation();
      window.mostrarModalPermissao(a.dataset.deniedMsg || 'Você não tem permissão para acessar este módulo.');
    }
  });
</script>

<script>
  // ✅ usuário logado vindo do PHP (ajuste a variável conforme seu bootstrap)
  window.MG_USER = <?= json_encode($_SESSION['loginid'] ?? $_SESSION['usuario'] ?? $_SESSION['user'] ?? '') ?>;
</script>

<!-- Script para controle do painel de notificações -->
<script>
  (() => {
    const API = 'api/tasks.php';

    // Ajuste: de onde vem o usuário? (padrão atual: input livre)
    // Se você tiver usuário em sessão, troca aqui:
    function getUser() {
      const v1 = (window.MG_USER || '').trim();
      if (v1) return v1;

      const v2 = (localStorage.getItem('mg_user') || '').trim();
      if (v2) return v2;

      // fallback: tenta ler o login exibido na sidebar/topbar
      const el = document.querySelector('[data-loginid]'); // se você tiver atributo
      if (el && el.dataset.loginid) return String(el.dataset.loginid).trim();

      // fallback bem “na marra”: procura um texto tipo FELIPEG na área do usuário
      const possible = document.querySelector('.sidebar .user-name, .sidebar .profile-name, .mg-user-login, .user-login');
      if (possible) {
        const t = (possible.textContent || '').trim();
        if (t) return t;
      }

      return '';
    }

    const fab = document.getElementById('mgNotifFab');
    const badge = document.getElementById('mgNotifBadge');
    const panel = document.getElementById('mgNotifPanel');
    const list = document.getElementById('mgNotifList');

    document.getElementById('mgNotifClose').addEventListener('click', () => panel.classList.remove('open'));
    fab.addEventListener('click', async () => {
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) await loadNotifs();
    });

    document.getElementById('mgNotifReadAll').addEventListener('click', async () => {
      const u = getUser();
      if (!u) return alert('Defina o usuário (ex: em sessão) para ler notificações.');
      try {
        await apiJson(`${API}?entity=notif&action=read_all&usuario=${encodeURIComponent(u)}`, {
          method: 'PATCH'
        });
        await loadNotifs();
      } catch (e) {
        alert(e.message);
      }
    });

    async function apiJson(url, opt) {
      const r = await fetch(url, opt);
      const j = await r.json().catch(() => null);
      if (!j) throw new Error('Resposta inválida do servidor.');
      if (!j.success) throw new Error(j.error || 'Erro.');
      return j.data;
    }

    function pick(o, a, b) {
      return o?.[a] ?? o?.[b];
    }

    function renderItem(n) {
      const id = pick(n, 'ID', 'id');
      const titulo = pick(n, 'TITULO', 'titulo') || pick(n, 'TITULO_NOTIF', 'titulo_notif') || pick(n, 'TITULO', 'TITULO') || '';
      const msg = pick(n, 'MENSAGEM', 'mensagem') || '';
      const tipo = pick(n, 'TIPO', 'tipo') || '';
      const taskId = pick(n, 'TASK_ID', 'task_id') || '';
      const lida = (pick(n, 'LIDA', 'lida') || 'N') === 'S';
      const criadoEm = pick(n, 'CRIADO_EM', 'criado_em') || '';

      const div = document.createElement('div');
      div.className = 'mg-notif-item' + (lida ? '' : ' unread');

      const t = document.createElement('p');
      t.className = 't';
      t.textContent = (tipo ? `[${tipo}] ` : '') + (titulo || 'Notificação');
      div.appendChild(t);

      if (msg) {
        const m = document.createElement('div');
        m.className = 'm';
        m.textContent = msg;
        div.appendChild(m);
      }

      const meta = document.createElement('div');
      meta.className = 'meta';

      const left = document.createElement('span');
      left.textContent = criadoEm ? String(criadoEm) : '';
      meta.appendChild(left);

      const right = document.createElement('span');
      right.style.display = 'flex';
      right.style.gap = '8px';

      if (taskId) {
        const a = document.createElement('a');
        a.href = `index.php?page=tarefas_detalhes&task_id=${encodeURIComponent(taskId)}`;
        a.textContent = 'Abrir task';
        a.style.fontWeight = '900';
        a.style.textDecoration = 'none';
        right.appendChild(a);
      }

      const btn = document.createElement('button');
      btn.className = 'mg-notif-btn';
      btn.textContent = lida ? 'OK' : 'Marcar lida';
      btn.disabled = lida;
      btn.addEventListener('click', async () => {
        try {
          await apiJson(`${API}?entity=notif&action=read&id=${encodeURIComponent(id)}`, {
            method: 'PATCH'
          });
          await loadNotifs();
        } catch (e) {
          alert(e.message);
        }
      });
      right.appendChild(btn);

      meta.appendChild(right);
      div.appendChild(meta);

      return div;
    }

    async function loadNotifs() {
      const u = getUser();
      if (!u) {
        list.innerHTML = `<div style="opacity:.75;font-size:12px;">Sem usuário definido. Configure usuário em sessão/localStorage para ver notificações.</div>`;
        badge.style.display = 'none';
        return;
      }

      const rows = await apiJson(`${API}?entity=notif&usuario=${encodeURIComponent(u)}`, {
        method: 'GET'
      }) || [];
      list.innerHTML = '';

      let unread = 0;
      rows.forEach(r => {
        const lida = (pick(r, 'LIDA', 'lida') || 'N') === 'S';
        if (!lida) unread++;
        list.appendChild(renderItem(r));
      });

      if (!rows.length) {
        list.innerHTML = `<div style="opacity:.75;font-size:12px;">Nenhuma notificação.</div>`;
      }

      if (unread > 0) {
        badge.textContent = String(unread);
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    // auto-refresh leve (a cada 20s) sem abrir painel
    setInterval(async () => {
      try {
        const u = getUser();
        if (!u) return;
        const rows = await apiJson(`${API}?entity=notif&usuario=${encodeURIComponent(u)}`, {
          method: 'GET'
        }) || [];
        let unread = 0;
        rows.forEach(r => {
          if ((pick(r, 'LIDA', 'lida') || 'N') !== 'S') unread++;
        });

        if (unread > 0) {
          badge.textContent = String(unread);
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }

        // se o painel estiver aberto, atualiza lista
        if (panel.classList.contains('open')) await loadNotifs();
      } catch (e) {
        /* silencioso */ }
    }, 20000);
  })();
</script>

</body>

</html>