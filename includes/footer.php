<?php require_once __DIR__ . '/../helpers/onesignal.php'; ?>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

<!-- css para o bagde de notificação e painel de notificações, inspirado no estilo do Bootstrap 5 e adaptado para um visual moderno e clean, com suporte a temas claro e escuro -->


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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      toggleMenu(true);
    }
  });

  // Se o overlay já estiver visível por algum motivo, garante estado consistente
  window.addEventListener('load', function () {
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
    <div class="modal-content rounded-4"
      style="border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text);">
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
      <button class="mg-notif-btn" id="mgPushEnable" type="button" style="display:none;">Ativar push</button>
      <button class="mg-notif-btn" id="mgPushTest" type="button" style="display:none;">Teste push</button>
      <button class="mg-notif-btn" id="mgNotifReadAll" type="button">Ler todas</button>
      <button class="mg-notif-btn" id="mgNotifClose" type="button">Fechar</button>
    </div>
  </div>
  <div class="mg-notif-list" id="mgNotifList">
    <div style="opacity:.7;font-size:12px;">Carregando...</div>
  </div>
</div>


<script>
  window.mostrarModalPermissao = function (msg) {
    const elMsg = document.getElementById('modalPermissaoMsg');
    if (elMsg) elMsg.textContent = msg || 'Você não possui permissão para acessar esta página.';
    const modalEl = document.getElementById('modalPermissao');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  };

  // Intercepta cliques nos links protegidos da sidebar
  document.addEventListener('click', function (e) {
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
  window.MG_ONESIGNAL = <?= json_encode(mg_onesignal_public_config(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php $mgOneSignal = mg_onesignal_public_config(); ?>
<?php if (!empty($mgOneSignal['enabled'])): ?>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  window.MGPush = window.MGPush || {};

  window.OneSignalDeferred.push(async function (OneSignal) {
    const cfg = window.MG_ONESIGNAL || {};
    if (!cfg.enabled || !cfg.app_id) return;

    await OneSignal.init({
      appId: cfg.app_id,
      safari_web_id: cfg.safari_web_id || undefined,
      allowLocalhostAsSecureOrigin: ['localhost', '127.0.0.1'].includes(window.location.hostname),
      serviceWorkerPath: cfg.service_worker_path || '/OneSignalSDKWorker.js',
      serviceWorkerUpdaterPath: cfg.service_worker_updater_path || '/OneSignalSDKUpdaterWorker.js',
      notifyButton: { enable: false },
    });

    if (window.MG_USER) {
      await OneSignal.login(String(window.MG_USER));
    }

    window.MGPush.ensure = async function () {
      if (window.MG_USER) {
        await OneSignal.login(String(window.MG_USER));
      }
      return OneSignal;
    };

    window.MGPush.requestPermission = async function () {
      await window.MGPush.ensure();
      return OneSignal.Notifications.requestPermission();
    };
  });
</script>
<?php endif; ?>

<!-- Toast Container (Global) -->
<div aria-live="polite" aria-atomic="true" class="position-relative">
  <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;">
  </div>
</div>

<script>
  // ==========================================
  // Sistema Global de Toasts (Clean SaaS)
  // ==========================================
  window.showToast = function (message, type = 'success', title = 'Notificação') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    let bgClass = 'bg-white text-dark';
    let icon = 'bi-info-circle text-primary';
    let borderClass = 'border-primary';

    if (type === 'success') { icon = 'bi-check-circle-fill text-success'; borderClass = 'border-success'; }
    if (type === 'error') { icon = 'bi-x-circle-fill text-danger'; borderClass = 'border-danger'; }
    if (type === 'warning') { icon = 'bi-exclamation-triangle-fill text-warning'; borderClass = 'border-warning'; }

    const toastEl = document.createElement('div');
    toastEl.className = `toast saas-card ${borderClass} mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.style.borderLeftWidth = '4px';

    toastEl.innerHTML = `
      <div class="toast-header border-0 bg-transparent">
        <i class="bi ${icon} me-2" style="font-size: 1.1rem;"></i>
        <strong class="me-auto">${title}</strong>
        <small class="text-muted">agora</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body pt-0 pb-3 text-muted">
        ${message}
      </div>
    `;

    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
      toastEl.remove();
    });
  };
</script>

<!-- Script para controle do painel de notificações -->
<script>
  (() => {
    const API = 'api/notif.php';

    function getUser() {
      const v1 = (window.MG_USER || '').trim();
      if (v1) return v1;
      return '';
    }

    const fab = document.getElementById('mgNotifFab');
    const badge = document.getElementById('mgNotifBadge');
    const panel = document.getElementById('mgNotifPanel');
    const list = document.getElementById('mgNotifList');
    const pushEnableBtn = document.getElementById('mgPushEnable');
    const pushTestBtn = document.getElementById('mgPushTest');

    function pushEnabled() {
      return !!(window.MG_ONESIGNAL && window.MG_ONESIGNAL.enabled);
    }

    function syncPushButtons() {
      if (!pushEnableBtn || !pushTestBtn) return;
      if (!pushEnabled()) {
        pushEnableBtn.style.display = 'none';
        pushTestBtn.style.display = 'none';
        return;
      }
      pushEnableBtn.style.display = 'inline-flex';
      pushTestBtn.style.display = 'inline-flex';
    }

    syncPushButtons();

    document.getElementById('mgNotifClose').addEventListener('click', () => panel.classList.remove('open'));
    if (pushEnableBtn) {
      pushEnableBtn.addEventListener('click', async () => {
        try {
          if (!window.MGPush || typeof window.MGPush.requestPermission !== 'function') {
            throw new Error('OneSignal ainda nao foi inicializado.');
          }
          await window.MGPush.requestPermission();
          window.showToast('Permissao de push solicitada ao navegador.', 'success', 'Push');
        } catch (e) {
          window.showToast(e.message || 'Nao foi possivel ativar o push.', 'error', 'Push');
        }
      });
    }
    if (pushTestBtn) {
      pushTestBtn.addEventListener('click', async () => {
        try {
          const r = await fetch('api/onesignal.php?action=test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
          });
          const j = await r.json().catch(() => null);
          if (!j || !j.success) {
            throw new Error((j && j.error) || 'Falha ao enviar push de teste.');
          }
          window.showToast('Push de teste enviado. Confira o navegador/dispositivo inscrito.', 'success', 'Push');
        } catch (e) {
          window.showToast(e.message || 'Falha ao enviar push de teste.', 'error', 'Push');
        }
      });
    }
    fab.addEventListener('click', async () => {
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) await loadNotifs();
    });

    document.getElementById('mgNotifReadAll').addEventListener('click', async () => {
      const u = getUser();
      if (!u) return alert('Defina o usuário para ler notificações.');
      try {
        await apiJson(`${API}?action=read_all&usuario=${encodeURIComponent(u)}`, { method: 'PATCH' });
        window.showToast('Todas as notificações marcadas como lidas.', 'success', 'Pronto!');
        await loadNotifs();
      } catch (e) {
        window.showToast(e.message, 'error', 'Erro');
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
      const titulo = pick(n, 'TITULO', 'titulo') || 'Notificação';
      const msg = pick(n, 'MENSAGEM', 'mensagem') || '';
      const tipo = pick(n, 'TIPO', 'tipo') || '';
      const lida = (pick(n, 'LIDA', 'lida') || 'N') === 'S';
      const criadoEm = pick(n, 'CRIADO_EM', 'criado_em') || '';
      const sender = pick(n, 'SENDER', 'sender') || 'Sistema';
      const link = pick(n, 'LINK', 'link') || '';

      const div = document.createElement('div');
      div.className = 'mg-notif-item' + (lida ? '' : ' unread');
      div.style.padding = '14px';
      div.style.borderBottom = '1px solid rgba(17,24,39,.06)';
      div.style.background = lida ? 'transparent' : 'rgba(13,110,253,.02)';
      div.style.borderLeft = lida ? '3px solid transparent' : '3px solid #0d6efd';

      // Header do Card
      const header = document.createElement('div');
      header.style.display = 'flex';
      header.style.justifyContent = 'space-between';
      header.style.marginBottom = '6px';

      let badgeClass = 'secondary';
      if (tipo === 'CHAMADO') badgeClass = 'danger';
      if (tipo === 'RH') badgeClass = 'success';
      if (tipo === 'CRM') badgeClass = 'primary';
      if (tipo === 'SISTEMA') badgeClass = 'info';

      header.innerHTML = `
        <span class="saas-badge ${badgeClass}" style="font-size: 0.65rem; padding: 2px 6px;">${tipo}</span>
        <span style="font-size: 0.70rem; color: var(--saas-muted);"><i class="bi bi-clock"></i> ${criadoEm}</span>
      `;
      div.appendChild(header);

      // Título e Remetente
      const t = document.createElement('div');
      t.style.fontWeight = '900';
      t.style.fontSize = '0.9rem';
      t.style.color = 'var(--saas-text)';
      t.innerHTML = `<span style="opacity:0.6;font-weight:700;">@${sender}</span> ${titulo}`;
      div.appendChild(t);

      // Mensagem (Comentário)
      if (msg) {
        const m = document.createElement('div');
        m.style.fontSize = '0.85rem';
        m.style.color = 'var(--saas-muted)';
        m.style.marginTop = '4px';
        m.style.lineHeight = '1.4';
        m.textContent = msg;
        div.appendChild(m);
      }

      // Rodapé (Ações)
      const meta = document.createElement('div');
      meta.style.display = 'flex';
      meta.style.justifyContent = 'space-between';
      meta.style.marginTop = '10px';

      const right = document.createElement('div');
      right.style.display = 'flex';
      right.style.gap = '8px';

      if (link) {
        const a = document.createElement('a');
        a.href = link;
        a.className = 'saas-btn primary';
        a.style.padding = '2px 8px';
        a.style.fontSize = '0.75rem';
        a.innerHTML = 'Ver <i class="bi bi-arrow-right-short"></i>';
        right.appendChild(a);
      }

      if (!lida) {
        const btn = document.createElement('button');
        btn.className = 'saas-btn';
        btn.style.padding = '2px 8px';
        btn.style.fontSize = '0.75rem';
        btn.textContent = 'Marcar lida';
        btn.addEventListener('click', async () => {
          try {
            await apiJson(`${API}?action=read&id=${encodeURIComponent(id)}`, { method: 'PATCH' });
            await loadNotifs();
          } catch (e) {
            alert(e.message);
          }
        });
        right.appendChild(btn);
      }

      meta.appendChild(right);
      div.appendChild(meta);

      return div;
    }

    async function loadNotifs() {
      const u = getUser();
      if (!u) {
        list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--saas-muted); font-size: 0.85rem;">Usuário não autenticado.</div>`;
        badge.style.display = 'none';
        return;
      }

      const rows = await apiJson(`${API}?usuario=${encodeURIComponent(u)}`, { method: 'GET' }) || [];
      list.innerHTML = '';

      let unread = 0;
      rows.forEach(r => {
        const lida = (pick(r, 'LIDA', 'lida') || 'N') === 'S';
        if (!lida) unread++;
        list.appendChild(renderItem(r));
      });

      if (!rows.length) {
        list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--saas-muted); font-size: 0.85rem;"><i class="bi bi-inbox fs-2 d-block opacity-50 mb-2"></i>Você está atualizado!<br>Nenhuma notificação por aqui.</div>`;
      }

      if (unread > 0) {
        badge.textContent = String(unread > 99 ? '99+' : unread);
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    // Auto-refresh inteligente (a cada 30s)
    let lastUnreadCount = 0;

    setInterval(async () => {
      try {
        const u = getUser();
        if (!u) return;
        const rows = await apiJson(`${API}?usuario=${encodeURIComponent(u)}`, { method: 'GET' }) || [];
        let unread = 0;
        rows.forEach(r => {
          if ((pick(r, 'LIDA', 'lida') || 'N') !== 'S') unread++;
        });

        if (unread > 0) {
          badge.textContent = String(unread > 99 ? '99+' : unread);
          badge.style.display = 'flex';

          // Dispara um Toast apenas se o número de notificações aumentou (Nova notificação)
          if (unread > lastUnreadCount) {
            window.showToast(`Você tem ${unread - lastUnreadCount} nova(s) notificação(ões).`, 'info', 'Novo Aviso');
          }
        } else {
          badge.style.display = 'none';
        }

        lastUnreadCount = unread;

        // se o painel estiver aberto, atualiza visualmente a lista
        if (panel.classList.contains('open')) await loadNotifs();
      } catch (e) { /* silencioso */ }
    }, 30000);

    // Inicia a primeira carga "silenciosa" pra preencher a bolinha (badge)
    setTimeout(loadNotifs, 1500);

  })();
</script>

</body>

</html>
