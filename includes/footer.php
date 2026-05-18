<?php require_once __DIR__ . '/../helpers/firebase.php'; ?>
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

<style>
  .mg-notif-panel{
    top:76px!important;
    right:24px!important;
    bottom:auto!important;
    transform:translateY(-8px) scale(.98);
  }
  .mg-notif-panel.open{
    transform:translateY(0) scale(1);
  }
  @media(max-width:768px){
    .mg-notif-panel{
      top:66px!important;
      right:10px!important;
      left:10px!important;
      width:auto!important;
      max-height:min(520px,calc(100vh - 160px));
    }
  }
</style>


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
  window.MG_FIREBASE = <?= json_encode(mg_firebase_public_config(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script type="module">
  (async () => {
    const cfg = window.MG_FIREBASE || {};
    const state = {
      enabled: !!cfg.enabled,
      initialized: false,
      initError: '',
      token: '',
      ready: null,
      sdk: null,
      messaging: null,
      registration: null
    };

    window.MGPush = window.MGPush || {};
    window.MGPush.state = state;
    window.MGPush._notifyState = () => window.dispatchEvent(new CustomEvent('mg:push-state', { detail: { ...state } }));

    async function postJson(url, body) {
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body || {})
      });
      const json = await response.json().catch(() => null);
      if (!json || !json.success) {
        throw new Error((json && json.error) || 'Falha na comunicacao com o servidor.');
      }
      return json.data;
    }

    state.ready = (async () => {
      if (!cfg.enabled) {
        state.initError = 'Firebase nao configurado para esta aplicacao.';
        window.MGPush._notifyState();
        return null;
      }

      if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        state.initError = 'Este navegador nao suporta notificacoes push.';
        window.MGPush._notifyState();
        return null;
      }

      const [{ initializeApp }, messagingModule] = await Promise.all([
        import('https://www.gstatic.com/firebasejs/10.13.2/firebase-app.js'),
        import('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging.js')
      ]);

      if (typeof messagingModule.isSupported === 'function') {
        const supported = await messagingModule.isSupported().catch(() => false);
        if (!supported) {
          state.initError = 'Push nao suportado neste navegador.';
          window.MGPush._notifyState();
          return null;
        }
      }

      const app = initializeApp({
        apiKey: cfg.api_key,
        authDomain: cfg.auth_domain || undefined,
        projectId: cfg.project_id,
        storageBucket: cfg.storage_bucket || undefined,
        messagingSenderId: cfg.messaging_sender_id,
        appId: cfg.app_id,
        measurementId: cfg.measurement_id || undefined
      });

      const registration = await navigator.serviceWorker.register(cfg.service_worker_path, {
        scope: cfg.service_worker_scope || '/'
      });

      state.sdk = messagingModule;
      state.registration = registration;
      state.messaging = messagingModule.getMessaging(app);
      state.initialized = true;
      state.initError = '';

      messagingModule.onMessage(state.messaging, (payload) => {
        const title = payload?.notification?.title || payload?.data?.title || 'Notificacao';
        const body = payload?.notification?.body || payload?.data?.body || 'Voce recebeu uma nova notificacao.';
        if (typeof window.showToast === 'function') {
          window.showToast(body, 'info', title);
        }
        window.dispatchEvent(new CustomEvent('mg:notif-received', { detail: payload }));
      });

      window.MGPush._notifyState();
      return state;
    })().catch((error) => {
      state.initError = (error && error.message) ? error.message : 'Falha ao inicializar o Firebase.';
      console.error('Firebase init error:', error);
      window.MGPush._notifyState();
      return null;
    });

    window.MGPush.ensure = async function () {
      const resolved = await state.ready;
      if (!resolved || !state.initialized || !state.messaging || !state.registration || !state.sdk) {
        throw new Error(state.initError || 'Firebase ainda nao foi inicializado.');
      }
      return resolved;
    };

    window.MGPush.syncToken = async function (requestPermission = false) {
      await window.MGPush.ensure();

      let permission = Notification.permission;
      if (requestPermission || permission === 'default') {
        permission = await Notification.requestPermission();
      }

      if (permission !== 'granted') {
        throw new Error('Permissao de notificacoes nao concedida.');
      }

      const token = await state.sdk.getToken(state.messaging, {
        vapidKey: cfg.vapid_key,
        serviceWorkerRegistration: state.registration
      });

      if (!token) {
        throw new Error('Nao foi possivel gerar o token do dispositivo.');
      }

      await postJson(cfg.api_endpoint + '?action=register_token', {
        token,
        platform: 'web',
        user_agent: navigator.userAgent,
        endpoint: window.location.href
      });

      state.token = token;
      window.MGPush._notifyState();
      return token;
    };

    window.MGPush.requestPermission = async function () {
      return window.MGPush.syncToken(true);
    };

    window.MGPush.sendTest = async function () {
      await window.MGPush.ensure();
      if (!state.token) {
        await window.MGPush.syncToken(Notification.permission !== 'granted');
      }
      return postJson(cfg.api_endpoint + '?action=test', {});
    };

    if (cfg.enabled && Notification.permission === 'granted') {
      try {
        await window.MGPush.syncToken(false);
      } catch (error) {
        console.warn('Firebase token sync warning:', error);
      }
    }
  })();
</script>

<!-- Toast Container (Global) -->
<div aria-live="polite" aria-atomic="true" class="position-relative">
  <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;">
  </div>
</div>

<!-- Assistente IA Global -->
<style>
  .mg-notif-fab{display:none!important}
  .mg-ai-widget{position:fixed;right:24px;bottom:24px;z-index:1065;font-family:'Inter',system-ui,-apple-system,sans-serif}
  .mg-ai-launch{display:flex;align-items:center;gap:10px;border:0;background:transparent;color:#fff;padding:0;cursor:pointer}
  .mg-ai-orb{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 0 0 3px rgba(99,102,241,.18),0 0 26px rgba(79,70,229,.55);overflow:hidden}
  .mg-ai-logo{width:72%;height:72%;object-fit:contain;display:block;filter:drop-shadow(0 2px 6px rgba(0,0,0,.18))}
  .mg-ai-pill{border:1px solid rgba(96,165,250,.45);background:#2563eb;color:#fff;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:800;box-shadow:0 10px 24px rgba(37,99,235,.28);max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .mg-ai-panel{position:fixed;right:24px;bottom:96px;width:min(780px,calc(100vw - 48px));height:min(720px,calc(100vh - 130px));background:#0b1020;color:#f8fafc;border:1px solid rgba(148,163,184,.22);border-radius:18px;box-shadow:0 24px 70px rgba(2,6,23,.42);overflow:hidden;display:none;flex-direction:column;z-index:1070}
  .mg-ai-panel.open{display:flex}
  .mg-ai-head{height:60px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:#121827;border-bottom:1px solid rgba(148,163,184,.18)}
  .mg-ai-brand{display:flex;align-items:center;gap:10px;min-width:0}
  .mg-ai-mini{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 0 18px rgba(79,70,229,.55);font-size:13px;font-weight:900;flex:0 0 auto;overflow:hidden}
  .mg-ai-name{font-weight:900;line-height:1.1}
  .mg-ai-role{font-size:12px;color:#94a3b8;margin-top:2px}
  .mg-ai-close{border:0;background:transparent;color:#94a3b8;width:34px;height:34px;border-radius:10px;font-size:22px;line-height:1;cursor:pointer}
  .mg-ai-close:hover{background:rgba(148,163,184,.12);color:#f8fafc}
  .mg-ai-body{flex:1;overflow:auto;padding:22px;display:flex;flex-direction:column;gap:12px;background:#070c17}
  .mg-ai-welcome{margin:auto;text-align:center;max-width:560px;color:#cbd5e1}
  .mg-ai-welcome .mg-ai-mini{margin:0 auto 14px;width:54px;height:54px;font-size:17px}
  .mg-ai-welcome h3{margin:0 0 8px;font-size:20px;font-weight:900;color:#fff}
  .mg-ai-suggestions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:24px}
  .mg-ai-suggest{border:1px solid rgba(148,163,184,.22);background:#121827;color:#cbd5e1;border-radius:10px;padding:10px 12px;font-size:13px;text-align:left;cursor:pointer;min-height:42px}
  .mg-ai-suggest:hover{border-color:#3b82f6;color:#fff}
  .mg-ai-msg{max-width:88%;border:1px solid rgba(148,163,184,.18);border-radius:14px;padding:11px 13px;font-size:14px;line-height:1.55;white-space:pre-wrap}
  .mg-ai-msg.user{align-self:flex-end;background:#2563eb;border-color:#2563eb;color:#fff}
  .mg-ai-msg.bot{align-self:flex-start;background:#111827;color:#e5e7eb}
  .mg-ai-tool-note{align-self:flex-start;color:#94a3b8;font-size:11px;margin-top:-6px}
  .mg-ai-action{align-self:flex-start;border:1px solid rgba(96,165,250,.38);background:#1d4ed8;color:#fff;border-radius:10px;padding:9px 12px;font-size:12px;font-weight:900;cursor:pointer;margin-top:-4px}
  .mg-ai-action:hover{background:#2563eb}
  .mg-ai-compose{border-top:1px solid rgba(148,163,184,.18);padding:16px;background:linear-gradient(180deg,rgba(37,99,235,.08),rgba(79,70,229,.16));display:grid;grid-template-columns:minmax(0,1fr) 44px;gap:10px}
  .mg-ai-input{width:100%;min-height:56px;max-height:120px;resize:vertical;border:1px solid rgba(148,163,184,.28);background:#070c17;color:#f8fafc;border-radius:12px;padding:14px;outline:none}
  .mg-ai-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.18)}
  .mg-ai-send{width:44px;height:44px;align-self:end;border:0;border-radius:12px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer}
  .mg-ai-send:disabled{opacity:.6;cursor:not-allowed}
  @media(max-width:768px){.mg-ai-widget{right:16px;bottom:100px}.mg-ai-pill{display:none}.mg-ai-panel{left:10px;right:10px;bottom:96px;width:auto;height:calc(100vh - 126px)}.mg-ai-suggestions{grid-template-columns:1fr}.mg-ai-msg{max-width:100%}}
</style>

<div class="mg-ai-widget" id="mgAiWidget">
  <button class="mg-ai-launch" type="button" id="mgAiLaunch" aria-label="Abrir Assistente IA">
    <span class="mg-ai-orb"><img class="mg-ai-logo" src="assets/images/logo.png" alt=""></span>
    <span class="mg-ai-pill">Precisa de ajuda no ERP?</span>
  </button>
</div>

<section class="mg-ai-panel" id="mgAiPanel" aria-label="Assistente IA do ERP">
  <div class="mg-ai-head">
    <div class="mg-ai-brand">
      <span class="mg-ai-mini"><img class="mg-ai-logo" src="assets/images/logo.png" alt=""></span>
      <div>
        <div class="mg-ai-name">MegaG AI</div>
        <div class="mg-ai-role">Assistente do ERP</div>
      </div>
    </div>
    <button class="mg-ai-close" type="button" id="mgAiClose" aria-label="Fechar">&times;</button>
  </div>
  <div class="mg-ai-body" id="mgAiBody">
    <div class="mg-ai-welcome" id="mgAiWelcome">
      <span class="mg-ai-mini"><img class="mg-ai-logo" src="assets/images/logo.png" alt=""></span>
      <h3>Sou a MegaG AI</h3>
      <p>Estou aqui para te ajudar a usar o ERP com mais seguranca: encontre telas, tire duvidas de preenchimento, acompanhe processos e resolva pendencias do dia a dia.</p>
      <div class="mg-ai-suggestions" id="mgAiSuggestions">
        <button class="mg-ai-suggest" type="button" data-ai-prompt="Me ajude a entender o que posso fazer nesta tela do ERP."><i class="bi bi-window me-2"></i>O que faco nesta tela?</button>
        <button class="mg-ai-suggest" type="button" data-ai-prompt="Me mostre um passo a passo simples para concluir minha tarefa nesta tela."><i class="bi bi-list-check me-2"></i>Passo a passo</button>
        <button class="mg-ai-suggest" type="button" data-ai-prompt="Estou com dificuldade nesta tela. Quais campos devo conferir primeiro?"><i class="bi bi-search me-2"></i>Conferir campos</button>
        <button class="mg-ai-suggest" type="button" data-ai-prompt="Quais erros comuns podem acontecer neste processo e como resolver?"><i class="bi bi-tools me-2"></i>Erros comuns</button>
      </div>
    </div>
  </div>
  <form class="mg-ai-compose" id="mgAiForm">
    <textarea class="mg-ai-input" id="mgAiInput" rows="1" placeholder="Descreva o que voce precisa..."></textarea>
    <button class="mg-ai-send" type="submit" id="mgAiSend" title="Enviar"><i class="bi bi-arrow-up"></i></button>
  </form>
</section>

<script>
  (() => {
    if (window.__mgAiWidgetLoaded) return;
    window.__mgAiWidgetLoaded = true;

    const API = 'api/ai_chat.php';
    const panel = document.getElementById('mgAiPanel');
    const launch = document.getElementById('mgAiLaunch');
    const close = document.getElementById('mgAiClose');
    const body = document.getElementById('mgAiBody');
    const welcome = document.getElementById('mgAiWelcome');
    const form = document.getElementById('mgAiForm');
    const input = document.getElementById('mgAiInput');
    const send = document.getElementById('mgAiSend');

    function currentModule() {
      const params = new URLSearchParams(window.location.search);
      return params.get('page') || 'geral';
    }

    const suggestionsByModule = {
      inventario_ciclico: [
        ['bi-plus-circle', 'Criar plano', 'Me guie para criar um novo plano de inventario ciclico nesta tela.'],
        ['bi-broadcast', 'Liberar para o app', 'Como confiro e libero um plano de inventario para o app de conferencia?'],
        ['bi-phone', 'Retorno do coletor', 'O que acontece quando o app devolve uma contagem aprovada ou rejeitada?'],
        ['bi-exclamation-triangle', 'Divergencias', 'Como devo analisar divergencias de produto, quantidade ou validade no inventario?']
      ],
      despesas: [
        ['bi-receipt', 'Lancar despesa', 'Me guie para lancar uma despesa corretamente.'],
        ['bi-diagram-3', 'Rateio', 'Como preencher o rateio da despesa sem erro?'],
        ['bi-paperclip', 'Anexos', 'Quais anexos devo conferir antes de enviar uma despesa?'],
        ['bi-hourglass-split', 'Status', 'Como acompanho o status da minha despesa?']
      ],
      despesas_aprovacao: [
        ['bi-check2-circle', 'Aprovar', 'Como devo conferir uma despesa antes de aprovar?'],
        ['bi-x-circle', 'Rejeitar', 'Quando devo rejeitar uma despesa e o que escrever na observacao?'],
        ['bi-person-check', 'Pendencias', 'Por que uma despesa pode nao aparecer para aprovacao?'],
        ['bi-clock-history', 'Historico', 'Como interpretar o historico de aprovacao de uma despesa?']
      ],
      despesas_config: [
        ['bi-shield-check', 'Politicas', 'Me explique como configurar politicas de aprovacao de despesas.'],
        ['bi-people', 'Aprovadores', 'Como cadastrar ou revisar aprovadores por centro de custo?'],
        ['bi-diagram-2', 'Centros de custo', 'Como vincular centros de custo nas regras de despesa?'],
        ['bi-arrow-repeat', 'Regras', 'O que devo revisar quando a aprovacao nao segue o fluxo esperado?']
      ],
      tarefas: [
        ['bi-kanban', 'Organizar tarefas', 'Como organizo minhas tarefas e listas nesta tela?'],
        ['bi-person-plus', 'Responsaveis', 'Como definir responsaveis e participantes em uma tarefa?'],
        ['bi-chat-left-text', 'Comentarios', 'Como usar comentarios para registrar andamento da tarefa?'],
        ['bi-bell', 'Notificacoes', 'Como funcionam as notificacoes de tarefas?']
      ],
      chamados: [
        ['bi-headset', 'Abrir chamado', 'Me guie para abrir um chamado com as informacoes certas.'],
        ['bi-card-checklist', 'Prioridade', 'Como defino prioridade e categoria do chamado?'],
        ['bi-search', 'Acompanhar', 'Como acompanho o andamento de um chamado?'],
        ['bi-paperclip', 'Evidencias', 'Que evidencias devo anexar em um chamado?']
      ],
      dados_visualizar: [
        ['bi-table', 'Consultar dados', 'Como uso esta tela para consultar dados com seguranca?'],
        ['bi-funnel', 'Filtros', 'Como escolher filtros para encontrar o que preciso?'],
        ['bi-download', 'Exportar', 'Como exporto ou aproveito os dados encontrados?'],
        ['bi-search', 'Nao encontrei', 'O que devo conferir quando nao encontro uma informacao?']
      ],
      home: [
        ['bi-grid', 'Mapa do ERP', 'Me explique os principais modulos do ERP e quando usar cada um.'],
        ['bi-search', 'Encontrar tela', 'Quero fazer uma tarefa no ERP, me ajude a encontrar a tela certa.'],
        ['bi-list-check', 'Rotina do dia', 'Quais pontos do ERP devo conferir na minha rotina diaria?'],
        ['bi-question-circle', 'Ajuda geral', 'Me ajude a resolver uma duvida operacional no ERP.']
      ]
    };

    function suggestionGroup(page) {
      if (suggestionsByModule[page]) return suggestionsByModule[page];
      if (page.indexOf('despesa') !== -1) return suggestionsByModule.despesas;
      if (page.indexOf('tarefa') !== -1) return suggestionsByModule.tarefas;
      if (page.indexOf('inventario') !== -1) return suggestionsByModule.inventario_ciclico;
      return suggestionsByModule.home;
    }

    function renderSuggestions() {
      const box = document.getElementById('mgAiSuggestions');
      if (!box) return;
      box.innerHTML = suggestionGroup(currentModule()).map((item) => {
        const icon = item[0];
        const label = item[1];
        const prompt = item[2].replace(/"/g, '&quot;');
        return `<button class="mg-ai-suggest" type="button" data-ai-prompt="${prompt}"><i class="bi ${icon} me-2"></i>${label}</button>`;
      }).join('');
    }

    function addMsg(role, text) {
      if (welcome) welcome.style.display = 'none';
      const div = document.createElement('div');
      div.className = 'mg-ai-msg ' + role;
      div.textContent = text;
      body.appendChild(div);
      body.scrollTop = body.scrollHeight;
      return div;
    }

    async function postJson(payload) {
      const resp = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {})
      });
      const json = await resp.json().catch(() => null);
      if (!json || !json.success) throw new Error((json && json.error) || 'Falha no assistente IA.');
      return json.data;
    }

    async function ask(text) {
      const message = String(text || input.value || '').trim();
      if (!message) return;
      input.value = '';
      addMsg('user', message);
      const pending = addMsg('bot', 'Pensando com o contexto do ERP...');
      send.disabled = true;
      try {
        const data = await postJson({ action: 'chat', module: currentModule(), message });
        pending.textContent = data.answer || 'Sem resposta.';
        if (Array.isArray(data.agent_tools) && data.agent_tools.length) {
          const note = document.createElement('div');
          note.className = 'mg-ai-tool-note';
          const hasDraft = data.agent_tools.includes('preparar_lancamento_despesa');
          note.textContent = hasDraft
            ? 'Rascunho preparado pelo agente. Nenhum lancamento foi confirmado.'
            : ((data.provider_error ? 'Resposta local com consulta ERP: ' : 'Consulta ERP: ') + data.agent_tools.join(', '));
          body.appendChild(note);
          body.scrollTop = body.scrollHeight;
        }
        if (data.agent_draft && data.agent_draft.status === 'pronto_para_revisao') {
          const action = document.createElement('button');
          action.type = 'button';
          action.className = 'mg-ai-action';
          action.textContent = 'Abrir Despesas com este rascunho';
          action.addEventListener('click', () => openExpenseDraft(data.agent_draft));
          body.appendChild(action);
          body.scrollTop = body.scrollHeight;
        }
        if (data.provider && data.model) {
          document.querySelectorAll('.mg-ai-role').forEach((el) => {
            el.textContent = `${data.provider === 'gemini' ? 'Gemini' : 'OpenAI'} · ${data.model}`;
          });
        }
      } catch (e) {
        pending.textContent = e.message || 'Erro ao consultar a IA.';
      } finally {
        send.disabled = false;
        input.focus();
        body.scrollTop = body.scrollHeight;
      }
    }

    function openExpenseDraft(draft) {
      try {
        const params = new URLSearchParams(window.location.search);
        const page = params.get('page') || 'home';
        sessionStorage.setItem('mg_ai_expense_draft', JSON.stringify(draft || {}));
        panel.classList.remove('open');

        if (page === 'despesas' && window.MGDespesaDraft && typeof window.MGDespesaDraft.open === 'function') {
          window.MGDespesaDraft.open(draft);
          return;
        }

        window.location.href = 'index.php?page=despesas&aiDraft=1';
      } catch (e) {
        const msg = e && e.message ? e.message : 'Nao foi possivel abrir o rascunho.';
        addMsg('bot', msg);
      }
    }

    launch.addEventListener('click', () => {
      panel.classList.add('open');
      setTimeout(() => input.focus(), 80);
    });

    close.addEventListener('click', () => panel.classList.remove('open'));

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      ask();
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        ask();
      }
    });

    renderSuggestions();
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-ai-prompt]');
      if (!btn || !panel.contains(btn)) return;
      ask(btn.dataset.aiPrompt || '');
    });
  })();
</script>

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
    const params = new URLSearchParams(window.location.search);
    if (params.get('debug_db_path') !== '1') return;

    window.addEventListener('load', async () => {
      try {
        const resp = await fetch('api/debug_db.php', {
          headers: { Accept: 'application/json' }
        });
        const json = await resp.json();

        if (!resp.ok || !json.success) {
          throw new Error(json.error || 'Nao foi possivel identificar o caminho do db_connect.php.');
        }

        const path = (json.data && json.data.config_path) ? json.data.config_path : '(vazio)';
        window.showToast(path, 'warning', 'db_connect.php');
        alert('db_connect.php: ' + path);
      } catch (e) {
        window.showToast(e.message || 'Falha ao validar db_connect.php.', 'error', 'db_connect.php');
      }
    });
  })();
</script>

<script>
  (() => {
    const API = 'api/notif.php';

    function getUser() {
      const v1 = (window.MG_USER || '').trim();
      if (v1) return v1;
      return '';
    }

    const fab = document.getElementById('mgNotifFab');
    const topNotifBtn = document.getElementById('mgTopNotifBtn');
    const badge = document.getElementById('mgNotifBadge');
    const topBadge = document.getElementById('mgTopNotifBadge');
    const panel = document.getElementById('mgNotifPanel');
    const list = document.getElementById('mgNotifList');
    const pushEnableBtn = document.getElementById('mgPushEnable');
    const pushTestBtn = document.getElementById('mgPushTest');

    function pushEnabled() {
      return !!(window.MG_FIREBASE && window.MG_FIREBASE.enabled);
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
            throw new Error('Firebase ainda nao foi inicializado.');
          }
          await window.MGPush.requestPermission();
          window.showToast('Permissao de push ativada para este navegador.', 'success', 'Push');
        } catch (e) {
          window.showToast(e.message || 'Nao foi possivel ativar o push.', 'error', 'Push');
        }
      });
    }
    if (pushTestBtn) {
      pushTestBtn.addEventListener('click', async () => {
        try {
          if (!window.MGPush || typeof window.MGPush.sendTest !== 'function') {
            throw new Error('Firebase ainda nao foi inicializado.');
          }
          await window.MGPush.sendTest();
          window.showToast('Push de teste enviado. Confira este navegador.', 'success', 'Push');
        } catch (e) {
          window.showToast(e.message || 'Falha ao enviar push de teste.', 'error', 'Push');
        }
      });
    }
    function setNotifBadge(unread) {
      const text = String(unread > 99 ? '99+' : unread);
      [badge, topBadge].forEach((el) => {
        if (!el) return;
        if (unread > 0) {
          el.textContent = text;
          el.style.display = el === topBadge ? 'block' : 'flex';
        } else {
          el.style.display = 'none';
        }
      });
    }

    async function toggleNotifPanel() {
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) await loadNotifs();
    }

    if (fab) fab.addEventListener('click', toggleNotifPanel);
    if (topNotifBtn) topNotifBtn.addEventListener('click', toggleNotifPanel);

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
        setNotifBadge(0);
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

      setNotifBadge(unread);
    }

    // Auto-refresh inteligente (a cada 30s)
    let lastUnreadCount = 0;

    window.addEventListener('mg:notif-received', async () => {
      try {
        await loadNotifs();
      } catch (e) { /* silencioso */ }
    });

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
          setNotifBadge(unread);

          // Dispara um Toast apenas se o número de notificações aumentou (Nova notificação)
          if (unread > lastUnreadCount) {
            window.showToast(`Você tem ${unread - lastUnreadCount} nova(s) notificação(ões).`, 'info', 'Novo Aviso');
          }
        } else {
          setNotifBadge(0);
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
