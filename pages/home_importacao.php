<?php
require_once(__DIR__ . '/../routes/check_session.php');
$paginaAtual = 'home_importacao';
?>

<style>
:root{
  --saas-bg: #f5f7fb;
  --saas-card: rgba(255,255,255,.94);
  --saas-border: rgba(15,23,42,.10);
  --saas-text: #111827;
  --saas-muted: rgba(17,24,39,.62);
  --saas-shadow: 0 18px 42px rgba(15,23,42,.08);
  --saas-shadow-soft: 0 12px 32px rgba(15,23,42,.06);
}

html[data-theme="dark"]{
  --saas-bg: #0f172a;
  --saas-card: rgba(15,23,42,.84);
  --saas-border: rgba(255,255,255,.12);
  --saas-text: rgba(255,255,255,.94);
  --saas-muted: rgba(255,255,255,.66);
  --saas-shadow: 0 22px 52px rgba(0,0,0,.38);
  --saas-shadow-soft: 0 16px 42px rgba(0,0,0,.26);
}

.page-container{
  padding: 0 !important;
}

.main-content{
  background:
    linear-gradient(180deg, rgba(245,247,251,.76), rgba(245,247,251,.90)),
    radial-gradient(900px 420px at 18% 3%, rgba(37,99,235,.12), transparent 62%),
    url("assets/images/modern_bg.png") center / cover fixed no-repeat,
    var(--saas-bg);
  color: var(--saas-text);
  min-height: calc(100vh - 60px);
  padding: 28px 28px 64px;
}

html[data-theme="dark"] .main-content{
  background:
    linear-gradient(180deg, rgba(15,23,42,.82), rgba(15,23,42,.92)),
    radial-gradient(900px 420px at 18% 3%, rgba(59,130,246,.18), transparent 62%),
    url("assets/images/modern_bg.png") center / cover fixed no-repeat,
    var(--saas-bg);
}

.import-shell{
  max-width: 1540px;
  margin: 0 auto;
}

.saas-page-head{
  border: 1px solid rgba(255,255,255,.72);
  background:
    linear-gradient(135deg, rgba(255,255,255,.88), rgba(239,246,255,.76) 48%, rgba(220,252,231,.62)),
    linear-gradient(135deg, rgba(37,99,235,.12), rgba(16,185,129,.08));
  border-radius: 28px;
  box-shadow: var(--saas-shadow-soft);
  padding: 28px;
  overflow:hidden;
  position:relative;
}

html[data-theme="dark"] .saas-page-head{
  border-color: var(--saas-border);
  background: linear-gradient(135deg, rgba(30,41,59,.88), rgba(15,23,42,.82) 58%, rgba(20,83,45,.40));
}

.saas-page-head:before{
  content:"";
  position:absolute;
  inset:-95px -110px auto auto;
  width: 340px;
  height: 340px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(37,99,235,.24), transparent 68%);
  pointer-events:none;
}

.saas-page-head:after{
  content:"";
  position:absolute;
  right: 30px;
  bottom: -48px;
  width: 220px;
  height: 130px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(245,158,11,.16), transparent 70%);
  pointer-events:none;
}

.saas-kicker{
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: .16em;
  text-transform: uppercase;
  margin: 0 0 9px;
}

html[data-theme="dark"] .saas-kicker{ color:#93c5fd; }

.saas-title{
  color: var(--saas-text);
  font-size: clamp(28px, 3vw, 44px);
  font-weight: 900;
  line-height: 1.05;
  margin:0;
}

.saas-subtitle{
  margin: 12px 0 0;
  color: var(--saas-muted);
  font-size: 15px;
  max-width: 660px;
}

.saas-head-actions{
  display:flex;
  align-items:center;
  gap: 10px;
  flex-wrap: wrap;
}

.saas-stat-pill,
.saas-theme-toggle{
  min-height: 38px;
  border: 1px solid var(--saas-border);
  background: rgba(255,255,255,.58);
  color: var(--saas-text);
  border-radius: 999px;
  padding: 8px 13px;
  font-size: 13px;
  font-weight: 800;
  display:flex;
  align-items:center;
  gap:8px;
}

html[data-theme="dark"] .saas-stat-pill,
html[data-theme="dark"] .saas-theme-toggle{
  background: rgba(255,255,255,.06);
}

.saas-theme-toggle:hover{
  border-color: rgba(37,99,235,.42);
  box-shadow: 0 0 0 4px rgba(37,99,235,.10);
}

.category-rail{
  display:flex;
  gap: 10px;
  flex-wrap: wrap;
  margin: 22px 0 20px;
}

.category-chip{
  border: 1px solid var(--saas-border);
  background: rgba(255,255,255,.72);
  color: var(--saas-muted);
  border-radius: 999px;
  padding: 9px 14px;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: .04em;
  text-transform: uppercase;
  display:inline-flex;
  align-items:center;
  gap: 8px;
}

html[data-theme="dark"] .category-chip{ background: rgba(255,255,255,.06); }

.quick-card{
  text-decoration:none;
  color: inherit;
  display:block;
  height:100%;
}

.saas-card{
  background: rgba(255,255,255,.86) !important;
  border: 1px solid var(--saas-border) !important;
  border-radius: 22px !important;
  box-shadow: var(--saas-shadow) !important;
  overflow:hidden;
  backdrop-filter: blur(14px);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  position:relative;
}

.saas-card:before{
  content:"";
  position:absolute;
  inset:0 auto 0 0;
  width: 5px;
  background: var(--card-accent, #2563eb);
  opacity: .9;
}

.quick-card:hover .saas-card{
  transform: translateY(-4px);
  border-color: var(--card-accent, #2563eb) !important;
  box-shadow: 0 24px 58px rgba(15,23,42,.14) !important;
}

.quick-card:focus-visible{
  outline: 4px solid rgba(37,99,235,.18);
  outline-offset: 4px;
  border-radius: 24px;
}

.quick-icon{
  width: 54px;
  height: 54px;
  border-radius: 17px;
  display:flex;
  align-items:center;
  justify-content:center;
  color: var(--card-accent, #2563eb);
  background: rgba(37,99,235,.10);
  flex: 0 0 auto;
  transition: transform .18s ease;
}

.quick-card:hover .quick-icon{ transform: scale(1.06) rotate(-2deg); }

.quick-kicker{
  color: var(--card-accent, #2563eb);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.quick-title{
  color: var(--saas-text);
  font-size: 17px;
  line-height: 1.2;
  font-weight: 900;
  margin: 0;
}

.quick-description{
  color: var(--saas-muted);
  margin-top: 6px;
  font-size: 14px;
  line-height: 1.45;
}

.quick-arrow{
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display:flex;
  align-items:center;
  justify-content:center;
  color: var(--card-accent, #2563eb);
  background: rgba(15,23,42,.04);
  flex: 0 0 auto;
  transition: transform .18s ease, background .18s ease;
}

.quick-card:hover .quick-arrow{
  transform: translateX(3px);
  background: rgba(37,99,235,.10);
}

html[data-theme="dark"] .quick-icon,
html[data-theme="dark"] .quick-arrow{
  background: rgba(255,255,255,.07);
}

html[data-theme="dark"] .saas-card{
  background: rgba(15,23,42,.78) !important;
}

.tone-blue{ --card-accent:#2563eb; }
.tone-teal{ --card-accent:#0f9f8f; }
.tone-slate{ --card-accent:#64748b; }
.tone-amber{ --card-accent:#d97706; }
.tone-rose{ --card-accent:#e11d48; }

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }

@media (max-width: 767px){
  .main-content{ padding: 18px 14px 42px; }
  .saas-page-head{ padding: 22px; border-radius: 22px; }
  .saas-head-actions{ width:100%; }
  .saas-stat-pill,.saas-theme-toggle{ flex: 1 1 auto; justify-content:center; }
  .card-body{ padding: 18px !important; }
}
</style>

<main class="main-content">
  <div class="container-fluid import-shell">

    <div class="d-flex align-items-center d-md-none mb-4 pb-3 border-bottom">
      <button class="mobile-toggle me-3" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
      </button>
      <h4 class="m-0 fw-bold text-dark">Importador Mega G</h4>
    </div>

    <div class="saas-page-head mb-4">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-4 position-relative">
        <div>
          <p class="saas-kicker">Central de Importações</p>
          <h3 class="saas-title">Escolha o fluxo de importação</h3>
          <p class="saas-subtitle">
            Acesse os módulos por área, com atalhos diretos para logística, compras, vendas e comercialização.
          </p>
        </div>

        <div class="saas-head-actions">
          <span class="saas-stat-pill"><i class="bi bi-grid-3x3-gap"></i> 10 módulos</span>
          <span class="saas-stat-pill"><i class="bi bi-lightning-charge"></i> Atalhos rápidos</span>
          <button type="button" id="btnTemaHome" class="saas-theme-toggle">
            <span id="themeIconHome">🌙</span>
            <span id="themeTextHome">Dark</span>
          </button>
        </div>
      </div>
    </div>

    <div class="category-rail" aria-label="Categorias de importação">
      <span class="category-chip"><i class="bi bi-truck"></i> Logística</span>
      <span class="category-chip"><i class="bi bi-cart-check"></i> Compras</span>
      <span class="category-chip"><i class="bi bi-graph-up-arrow"></i> Vendas</span>
      <span class="category-chip"><i class="bi bi-bullseye"></i> Comercialização</span>
    </div>

    <div class="row g-4">

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-blue" href="index.php?page=imp_setormetacapac">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-box-seam fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Logística</div>
                <h5 class="quick-title">Cargas/Metas</h5>
                <div class="quick-description">Importar capacidade logística e metas operacionais.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-blue" href="index.php?page=imp_lanctocomissao">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-truck fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Logística</div>
                <h5 class="quick-title">Comissão Transportadores</h5>
                <div class="quick-description">Importar comissões de transportadores.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-slate" href="index.php?page=imp_tabvdaprodraio">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-bullseye fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Comercialização</div>
                <h5 class="quick-title">Custo de Comercialização</h5>
                <div class="quick-description">Importar custos por produto e raio de venda.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-amber" href="index.php?page=imp_bi_metas">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-graph-up-arrow fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Compras</div>
                <h5 class="quick-title">Meta de Compras</h5>
                <div class="quick-description">Importar metas do setor de compras.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-amber" href="index.php?page=imp_bi_metas_perspect">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-speedometer fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Compras</div>
                <h5 class="quick-title">Perspectivas - Compras</h5>
                <div class="quick-description">Importar perspectivas do setor de compras.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-rose" href="index.php?page=imp_repcccomissao">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-cash-coin fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Vendas</div>
                <h5 class="quick-title">Comissões</h5>
                <div class="quick-description">Importar comissões de vendedores.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-teal" href="index.php?page=imp_metas">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-graph-up-arrow fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Vendas</div>
                <h5 class="quick-title">Meta de Vendas</h5>
                <div class="quick-description">Importar metas do setor de vendas.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-teal" href="index.php?page=imp_metas_perspec">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-speedometer fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Vendas</div>
                <h5 class="quick-title">Perspectiva - Vendas</h5>
                <div class="quick-description">Importar perspectivas do setor de vendas.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-teal" href="index.php?page=imp_metas_faixa">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-receipt-cutoff fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Vendas</div>
                <h5 class="quick-title">Faixas - Vendas</h5>
                <div class="quick-description">Importar faixas do setor de vendas.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <a class="quick-card tone-teal" href="index.php?page=imp_metas_gap">
          <div class="card saas-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="quick-icon"><i class="bi bi-columns-gap fs-3"></i></div>
              <div class="flex-grow-1">
                <div class="quick-kicker">Vendas</div>
                <h5 class="quick-title">Gap - Vendas</h5>
                <div class="quick-description">Importar gaps do setor de vendas.</div>
              </div>
              <div class="quick-arrow"><i class="bi bi-arrow-right"></i></div>
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
    root.setAttribute('data-theme', theme);
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
