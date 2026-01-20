<?php
$paginaAtual = 'imp_metas_faixa';
?>

<style>
/* ===== Clean SaaS (escopado pra página IMP_METAS_FAIXA) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(220,53,69,.14), rgba(220,53,69,.05)); /* danger */
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(220,53,69,.16), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(220,53,69,.32), transparent 60%);
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

/* Cards SaaS */
.saas-card{
    background: var(--saas-surface) !important;
    border: 1px solid var(--saas-border) !important;
    border-radius: 18px !important;
    box-shadow: var(--saas-shadow) !important;
    overflow:hidden;
    backdrop-filter: blur(10px);
}
.saas-card .card-header{
    background: transparent !important;
    border-bottom: 1px solid var(--saas-border) !important;
}
.saas-kicker{
    color: var(--saas-muted);
    font-size: 12px;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 900;
}

/* Upload input + botão */
.saas-upload .form-control{
    border-radius: 14px 0 0 14px;
    border: 1px solid var(--saas-border);
    background: rgba(220,53,69,.14); /* danger */
    color: var(--saas-text);
    height: 52px;
}
html[data-theme="dark"] .saas-upload .form-control{ background: rgba(255,255,255,.06); }
.saas-upload .form-control:focus{
    box-shadow: 0 0 0 .22rem var(--ring);
    border-color: rgba(220,53,69,.45);
    background: var(--saas-surface);
}
.saas-upload .btn{
    border-radius: 0 14px 14px 0;
    height: 52px;
    font-weight: 900;
    letter-spacing: .02em;
    box-shadow: 0 10px 18px rgba(220,53,69,.22); /* danger */
}

/* Console */
.saas-console{
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.10);
    overflow:hidden;
    box-shadow: var(--saas-shadow);
}
.saas-console .card-header{
    background: rgba(255,255,255,.04) !important;
    border-bottom: 1px solid rgba(255,255,255,.10) !important;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    font-size: 12px;
}
.saas-console .card-body{ background: #0b1220; }
html[data-theme="dark"] .saas-console .card-body{ background: #070c16; }

#consoleLog{ scrollbar-width: thin; }
#consoleLog::-webkit-scrollbar{ width: 10px; }
#consoleLog::-webkit-scrollbar-track{ background: rgba(255,255,255,.04); }
#consoleLog::-webkit-scrollbar-thumb{
    background: rgba(255,255,255,.18);
    border-radius: 999px;
    border: 2px solid rgba(0,0,0,0.2);
}
#consoleLog::-webkit-scrollbar-thumb:hover{ background: rgba(255,255,255,.26); }

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }
</style>

<main class="main-content">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">

        <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
          <button class="mobile-toggle me-3" onclick="toggleMenu()">
            <i class="bi bi-list"></i>
          </button>
          <h4 class="m-0 fw-bold text-dark">Importador Mega G</h4>
        </div>

        <div class="saas-head mb-4">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
            <div>
              <h3 class="saas-title">Importação Metas Faixas - Setor de Vendas</h3>
              <p class="saas-subtitle">
                Faça o upload da planilha com
                <strong>CODPERIODO, CODVENDEDOR, CODMETA, CODFAIXA, DESCFAIXA, DESCFAIXARCA, DESCFATURAMENTO, FAIXAINI, FAIXAFIM, GANHO, DATAATAULIZACAO</strong>.
              </p>
            </div>

            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-pill bg-danger bg-opacity-10 text-muted fw-bold px-3 py-2">
                Destino: Oracle
              </span>
            </div>
          </div>
        </div>

        <div class="card saas-card mb-4">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="bg-danger bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                <i class="bi bi-layers fs-4 text-danger"></i>
              </div>
              <div>
                <div class="saas-kicker">Upload</div>
                <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Metas Faixas - Setor de Vendas</div>
              </div>
            </div>
            <span class="text-muted small">Arquivo .xls / .xlsx</span>
          </div>

          <div class="card-body">
            <p class="text-muted mb-4">
              Selecione o arquivo e clique em importar. O log abaixo exibirá cada etapa.
            </p>

            <div class="input-group input-group-lg mb-2 saas-upload">
              <input type="file" class="form-control" id="arquivoInput" accept=".xls,.xlsx">
              <button class="btn btn-danger px-4" type="button" onclick="iniciar()" id="btnGo">
                <i class="bi bi-play-fill me-1"></i> IMPORTAR TABELA
              </button>
            </div>

            <small class="text-muted">
              Colunas esperadas: CODPERIODO | CODVENDEDOR | CODMETA | CODFAIXA | DESCFAIXA | DESCFAIXARCA | DESCFATURAMENTO | FAIXAINI | FAIXAFIM | GANHO | DATAATAULIZACAO.
            </small>
          </div>
        </div>

        <div class="card saas-console text-white" style="min-height: 300px;">
          <div class="card-header bg-transparent d-flex align-items-center">
            <i class="bi bi-terminal-fill me-2"></i> Log de Processamento
          </div>
          <div class="card-body overflow-auto" id="consoleLog" style="max-height: 420px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
            <div class="text-secondary opacity-50">Aguardando arquivo...</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<script>
const term = document.getElementById('consoleLog');
const btn = document.getElementById('btnGo');
const fileInput = document.getElementById('arquivoInput');

function log(msg, tipo) {
  if (term.innerText.includes('Aguardando arquivo...')) term.innerHTML = '';
  const d = document.createElement('div');
  d.className = 'mb-1';

  let color = 'text-light';
  let icon = '<i class="bi bi-caret-right me-2 text-secondary"></i>';

  if (tipo === 'erro') { color = 'text-danger fw-bold'; icon = '<i class="bi bi-x-circle-fill me-2 text-danger"></i>'; }
  else if (tipo === 'sucesso') { color = 'text-success fw-bold'; icon = '<i class="bi bi-check-circle-fill me-2 text-success"></i>'; }
  else if (tipo === 'aviso') { color = 'text-warning'; icon = '<i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>'; }
  else if (tipo === 'sistema') { color = 'text-info'; icon = '<i class="bi bi-cpu-fill me-2 text-info"></i>'; }

  d.innerHTML = `${icon} <span class="${color}">${msg}</span>`;
  term.appendChild(d);
  term.scrollTop = term.scrollHeight;
}

async function iniciar() {
  if (!fileInput.files.length) return alert('Selecione um arquivo!');

  btn.disabled = true;
  const originalText = btn.innerHTML;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Processando...`;
  term.innerHTML = '';
  log('Iniciando upload do arquivo...', 'sistema');

  const fd = new FormData();
  fd.append('arquivo', fileInput.files[0]);

  try {
    const resp = await fetch('upload.php', { method: 'POST', body: fd });
    const json = await resp.json();

    if (!json.sucesso) throw new Error(json.erro || 'Erro no upload');

    log(`Arquivo salvo: ${json.arquivo}`, 'sistema');
    log(`Conectando ao Oracle e iniciando leitura...`, 'sistema');

    const evt = new EventSource(`processors/processa_metas_faixas.php?arquivo=${encodeURIComponent(json.arquivo)}`);

    evt.onmessage = (e) => {
      const data = JSON.parse(e.data);
      log(data.msg, data.tipo);
    };

    evt.addEventListener('close', () => {
      log('Processo finalizado pelo servidor.', 'sucesso');
      evt.close();
      resetBtn();
    });

    evt.onerror = () => {
      if (evt.readyState !== EventSource.CLOSED) {
        log('Conexão encerrada ou erro de rede.', 'aviso');
      }
      evt.close();
      resetBtn();
    };

    function resetBtn() {
      btn.disabled = false;
      btn.innerHTML = originalText;
      fileInput.value = '';
    }

  } catch (e) {
    log('ERRO CRÍTICO: ' + e.message, 'erro');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}
</script>
