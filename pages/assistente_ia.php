<?php
require_once __DIR__ . '/../routes/check_session.php';
$paginaAtual = 'assistente_ia';
?>

<style>
  .ai-page-head{border:1px solid var(--saas-border);background:var(--saas-surface);border-radius:18px;box-shadow:var(--saas-shadow-soft);padding:18px 20px;margin-bottom:16px}
  .ai-title{font-weight:900;color:var(--saas-text);letter-spacing:0;margin:0}
  .ai-sub{color:var(--saas-muted);font-size:13px;margin:5px 0 0}
  .ai-shell{display:grid;grid-template-columns:minmax(260px,340px) minmax(0,1fr);gap:16px;align-items:stretch;height:calc(100vh - 190px);min-height:560px}
  .ai-panel{border:1px solid var(--saas-border);background:var(--saas-surface);border-radius:16px;box-shadow:var(--saas-shadow-soft);overflow:hidden}
  .ai-side{padding:16px;display:flex;flex-direction:column;gap:12px}
  .ai-kicker{font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--saas-muted)}
  .ai-select,.ai-input{width:100%;border:1px solid var(--saas-border);background:transparent;color:var(--saas-text);border-radius:12px;padding:10px 12px;outline:none}
  .ai-select:focus,.ai-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--ring)}
  .ai-chip{border:1px solid var(--saas-border);background:rgba(13,110,253,.04);color:var(--saas-text);border-radius:12px;padding:10px 12px;text-align:left;font-size:13px;font-weight:700;cursor:pointer}
  .ai-chip:hover{border-color:var(--accent);color:var(--accent)}
  .ai-chat{display:flex;flex-direction:column;height:100%}
  .ai-messages{flex:1;overflow:auto;padding:18px;display:flex;flex-direction:column;gap:12px}
  .ai-msg{max-width:min(820px,92%);border:1px solid var(--saas-border);border-radius:16px;padding:12px 14px;font-size:14px;line-height:1.55;white-space:pre-wrap}
  .ai-msg.user{align-self:flex-end;background:var(--accent);color:#fff;border-color:var(--accent)}
  .ai-msg.bot{align-self:flex-start;background:rgba(17,24,39,.025);color:var(--saas-text)}
  .ai-msg.meta{align-self:center;max-width:720px;text-align:center;color:var(--saas-muted);background:transparent;border-style:dashed;font-size:13px}
  .ai-compose{border-top:1px solid var(--saas-border);padding:14px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end}
  .ai-input{min-height:48px;max-height:130px;resize:vertical}
  .ai-send{height:48px;border:0;background:var(--accent);color:white;border-radius:12px;padding:0 18px;font-weight:900;display:inline-flex;align-items:center;gap:8px}
  .ai-send:disabled{opacity:.65;cursor:not-allowed}
  .ai-status{font-size:12px;color:var(--saas-muted);padding:0 18px 12px}
  @media(max-width:980px){.ai-shell{grid-template-columns:1fr;height:auto}.ai-chat{min-height:620px}}
  @media(max-width:640px){.ai-page-head{padding:16px}.ai-compose{grid-template-columns:1fr}.ai-send{width:100%;justify-content:center}.ai-msg{max-width:100%}}
</style>

<div class="ai-page-head">
  <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center">
    <div>
      <h2 class="ai-title">Assistente IA do ERP</h2>
      <p class="ai-sub">Ajuda contextual para processos, telas, APIs, packages e operacao do MegaG ERP.</p>
    </div>
    <button class="saas-btn" type="button" id="aiReset"><i class="bi bi-arrow-clockwise"></i> Nova conversa</button>
  </div>
</div>

<div class="ai-shell">
  <aside class="ai-panel ai-side">
    <div>
      <div class="ai-kicker mb-2">Foco</div>
      <select class="ai-select" id="aiModule">
        <option value="geral">ERP inteiro</option>
        <option value="inventario_ciclico">Inventario Ciclico</option>
        <option value="despesas">Despesas e aprovacao</option>
        <option value="tasks">Tasks e notificacoes</option>
        <option value="crm">CRM</option>
        <option value="wiki">Wiki</option>
      </select>
    </div>

    <div>
      <div class="ai-kicker mb-2">Perguntas rapidas</div>
      <div class="d-grid gap-2">
        <button class="ai-chip" type="button" data-prompt="Me explique o fluxo completo do inventario ciclico, do ERP ate o app.">Fluxo do inventario ciclico</button>
        <button class="ai-chip" type="button" data-prompt="Quais procedures e APIs existem para despesas e aprovacao?">Procedures de despesas</button>
        <button class="ai-chip" type="button" data-prompt="Como eu descubro por que uma despesa nao aparece para aprovacao?">Diagnosticar aprovacao</button>
        <button class="ai-chip" type="button" data-prompt="Resuma os modulos que existem neste ERP e para que servem.">Mapa dos modulos</button>
      </div>
    </div>

    <div class="mt-auto small text-muted">
      A IA consulta contexto local de leitura e nao executa alteracoes operacionais sozinha.
    </div>
  </aside>

  <section class="ai-panel ai-chat">
    <div class="ai-messages" id="aiMessages">
      <div class="ai-msg meta">Pronto. Pergunte sobre qualquer processo do ERP, procedure, tela ou regra operacional.</div>
    </div>
    <div class="ai-status" id="aiStatus">Modelo: verificando configuracao...</div>
    <form class="ai-compose" id="aiForm">
      <textarea class="ai-input" id="aiMessage" rows="1" placeholder="Ex.: qual PRC libera um plano de inventario para o app?"></textarea>
      <button class="ai-send" type="submit" id="aiSend"><i class="bi bi-send-fill"></i> Enviar</button>
    </form>
  </section>
</div>

<script>
const AI_API = 'api/ai_chat.php';
const aiMessages = document.getElementById('aiMessages');
const aiMessage = document.getElementById('aiMessage');
const aiModule = document.getElementById('aiModule');
const aiStatus = document.getElementById('aiStatus');
const aiSend = document.getElementById('aiSend');

function aiEsc(value) {
  return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function addMsg(role, text) {
  const div = document.createElement('div');
  div.className = 'ai-msg ' + role;
  div.textContent = text;
  aiMessages.appendChild(div);
  aiMessages.scrollTop = aiMessages.scrollHeight;
  return div;
}

async function aiPost(payload) {
  const resp = await fetch(AI_API, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload || {})
  });
  const json = await resp.json().catch(() => null);
  if (!json || !json.success) throw new Error((json && json.error) || 'Falha na API do assistente.');
  return json.data;
}

async function sendMessage(text) {
  const message = String(text || aiMessage.value || '').trim();
  if (!message) return;
  aiMessage.value = '';
  addMsg('user', message);
  const thinking = addMsg('bot', 'Pensando com o contexto do ERP...');
  aiSend.disabled = true;
  try {
    const data = await aiPost({ action: 'chat', module: aiModule.value, message });
    thinking.textContent = data.answer || 'Sem resposta.';
    aiStatus.textContent = data.configured ? `Modelo: ${data.model || 'OpenAI'}` : 'IA sem chave configurada';
  } catch (e) {
    thinking.textContent = e.message || 'Erro ao consultar o assistente.';
    if (window.showToast) window.showToast(thinking.textContent, 'error', 'Assistente IA');
  } finally {
    aiSend.disabled = false;
    aiMessage.focus();
    aiMessages.scrollTop = aiMessages.scrollHeight;
  }
}

document.getElementById('aiForm').addEventListener('submit', (e) => {
  e.preventDefault();
  sendMessage();
});

aiMessage.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

document.querySelectorAll('[data-prompt]').forEach(btn => {
  btn.addEventListener('click', () => sendMessage(btn.dataset.prompt || ''));
});

document.getElementById('aiReset').addEventListener('click', async () => {
  try {
    await aiPost({ action: 'reset' });
    aiMessages.innerHTML = '<div class="ai-msg meta">Conversa reiniciada. Pode mandar a proxima pergunta.</div>';
  } catch (e) {
    if (window.showToast) window.showToast(e.message, 'error', 'Assistente IA');
  }
});

(async () => {
  try {
    const data = await aiPost({ action: 'context' });
    aiStatus.textContent = data.configured ? `Modelo: ${data.model}` : 'IA sem chave configurada';
  } catch (e) {
    aiStatus.textContent = e.message || 'Nao foi possivel validar o assistente.';
  }
})();
</script>

