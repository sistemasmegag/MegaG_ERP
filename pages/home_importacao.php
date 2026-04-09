<?php
require_once(__DIR__ . '/../routes/check_session.php');
$paginaAtual = 'home';
?>

<style>
/* ========= Clean SaaS ========= */
:root{
  --saas-bg: #f6f8fb;
  --saas-card: #ffffff;
  --saas-border: rgba(17,24,39,.10);
  --saas-text: #111827;
  --saas-muted: rgba(17,24,39,.60);
  --saas-shadow: 0 12px 30px rgba(17,24,39,.08);
  --saas-shadow-soft: 0 10px 30px rgba(17,24,39,.06);
  --saas-ring: rgba(13,110,253,.12);
}

html[data-theme="dark"]{
  --saas-bg: #1f1f1f;
  --saas-card: rgba(255,255,255,.05);
  --saas-border: rgba(255,255,255,.10);
  --saas-text: rgba(255,255,255,.92);
  --saas-muted: rgba(255,255,255,.65);
  --saas-shadow: 0 16px 40px rgba(0,0,0,.35);
  --saas-shadow-soft: 0 14px 40px rgba(0,0,0,.25);
  --saas-ring: rgba(13,110,253,.20);
}

/* Fundo e tipografia só da área principal */
.main-content{
  background:
    radial-gradient(1200px 600px at 15% 10%, rgba(13,110,253,.14), transparent 60%),
    radial-gradient(1000px 500px at 85% 25%, rgba(25,135,84,.10), transparent 55%),
    var(--saas-bg);
  color: var(--saas-text);
  min-height: 100vh;
}

/* Cabeçalho */
.saas-page-head{
  border: 1px solid var(--saas-border);
  background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(13,110,253,.04));
  border-radius: 18px;
  box-shadow: var(--saas-shadow-soft);
  padding: 18px 18px;
  overflow:hidden;
  position:relative;
}
html[data-theme="dark"] .saas-page-head{
  background: linear-gradient(135deg, rgba(13,110,253,.14), rgba(255,255,255,.02));
}
.saas-page-head:before{
  content:"";
  position:absolute;
  inset:-130px -190px auto auto;
  width: 360px;
  height: 360px;
  background: radial-gradient(circle at 30% 30%, rgba(13,110,253,.30), transparent 60%);
  filter: blur(6px);
  transform: rotate(10deg);
  pointer-events:none;
}
.saas-title{ font-weight: 900; letter-spacing: -.02em; margin:0; }
.saas-subtitle{ margin: 6px 0 0; color: var(--saas-muted); font-size: 14px; }

/* Botão tema */
.saas-theme-toggle{
  border: 1px solid var(--saas-border);
  background: transparent;
  color: var(--saas-muted);
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 800;
  display:flex;
  align-items:center;
  gap:8px;
}
.saas-theme-toggle:hover{ color: var(--saas-text); border-color: rgba(13,110,253,.35); }

/* Cards */
.saas-card{
  background: var(--saas-card) !important;
  border: 1px solid var(--saas-border) !important;
  border-radius: 18px !important;
  box-shadow: var(--saas-shadow) !important;
  overflow:hidden;
  backdrop-filter: blur(10px);
}

.quick-card{
  text-decoration:none;
  color: inherit;
  display:block;
}
.quick-card:hover .saas-card{
  transform: translateY(-2px);
  transition: transform .18s ease;
  box-shadow: 0 18px 44px rgba(17,24,39,.10) !important;
}

.quick-icon{
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display:flex;
  align-items:center;
  justify-content:center;
}

.quick-card:hover .quick-icon {
  transform: scale(1.1);
  transition: 0.2s ease;
}

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }

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

<main class="main-content">
  <div class="container-fluid">

    <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
      <button class="mobile-toggle me-3" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
      </button>
      <h4 class="m-0 fw-bold text-dark">Importador Mega G</h4>
    </div>

    <!-- Header -->
    <div class="saas-page-head mb-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
        <div>
          <h3 class="saas-title text-dark">Visão Geral</h3>
          <p class="saas-subtitle">
            Acesse rapidamente os módulos de importação.
          </p>
        </div>

        <button type="button" id="btnTemaHome" class="saas-theme-toggle">
          <span id="themeIconHome">🌙</span>
          <span id="themeTextHome">Dark</span>
        </button>
      </div>
    </div>

    <!-- Cards de navegação -->
     <!-- Cards - Logistica -->
    <div class="row g-4">

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_setormetacapac">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-primary bg-opacity-10">
                <i class="bi bi-box-seam fs-2 text-primary"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Cargas/Metas</h5>
                <div class="text-muted mt-1">Importar capacidade logística / metas.</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_lanctocomissao">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-primary bg-opacity-10">
                <i class="bi bi-truck fs-2 text-primary"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Comissão Transportadores</h5>
                <div class="text-muted mt-1">Importar comissão de transportadores.</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <!-- Card - Comercialização -->

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_tabvdaprodraio">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-secondary bg-opacity-10">
                <i class="bi bi-bullseye fs-2 text-secondary"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Custo de Comercialização</h5>
                <div class="text-muted mt-1">Importar custo de comercialização.</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <!-- Cards - Compras -->

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_bi_metas">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-warning bg-opacity-10">
                <i class="bi bi-graph-up-arrow fs-2 text-warning"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Meta de Compras</h5>
                <div class="text-muted mt-1">Importar tabela de meta de compras - Setor de Compras</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_bi_metas_perspect">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-warning bg-opacity-10">
                <i class="bi bi-speedometer fs-2 text-warning"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Perspectivas - Compras</h5>
                <div class="text-muted mt-1">Importar tabelas de perspectivas - Setor de Compras</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <!-- Cards - Vendas -->

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_repcccomissao">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-danger bg-opacity-10">
                <i class="bi bi-cash-coin fs-2 text-danger"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Comissões</h5>
                <div class="text-muted mt-1">Importar comissões de vendedor.</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_metas">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-danger bg-opacity-10">
                <i class="bi bi-graph-up-arrow fs-2 text-danger"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Meta de Vendas</h5>
                <div class="text-muted mt-1">Importar tabela de meta - Setor de Vendas.</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_metas_perspec">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-danger bg-opacity-10">
                <i class="bi bi-speedometer fs-2 text-danger"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Perspectiva - Vendas </h5>
                <div class="text-muted mt-1">Importar tabela de perspectiva - Setor de Vendas</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_metas_faixa">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-danger bg-opacity-10">
                <i class="bi bi-receipt-cutoff fs-2 text-danger"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Faixas - Vendas</h5>
                <div class="text-muted mt-1">Importar tabela de faixas - Setor de Vendas</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card" href="index.php?page=imp_metas_gap">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon bg-danger bg-opacity-10">
                <i class="bi bi-receipt-cutoff fs-2 text-danger"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="m-0 fw-bold">Gap - Vendas </h5>
                <div class="text-muted mt-1">Importar tabela de Gap - Setor de Vendas</div>
              </div>
              <i class="bi bi-arrow-right fs-4 text-muted"></i>
            </div>
          </div>
        </a>
      </div>

    </div>

  </div>
</main>

<script>
(function () {
  const root = document.documentElement;
  const btnTema = document.getElementById('btnTemaHome');
  const icon = document.getElementById('themeIconHome');
  const text = document.getElementById('themeTextHome');

  function applyTheme(theme){
    const isDark = theme === 'dark';

    // Seu tema
    root.setAttribute('data-theme', theme);

    // Tema do Bootstrap (ESSENCIAL pro seu CSS [data-bs-theme="dark"])
    root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');

    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    if (text) text.textContent = isDark ? 'Light' : 'Dark';
  }

  const saved = localStorage.getItem('theme');
  if (saved === 'dark' || saved === 'light') {
    applyTheme(saved);
  } else {
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(prefersDark ? 'dark' : 'light');
  }

  if (btnTema) {
    btnTema.addEventListener('click', function(){
      const current = root.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      localStorage.setItem('theme', next);
      applyTheme(next);
    });
  }
})();
</script>
