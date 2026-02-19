<?php
// includes/sidebar.php

require_once __DIR__ . '/../helpers/functions.php';

// Garante que $paginaAtual exista (se não foi setado no index.php)
$paginaAtual = $paginaAtual ?? ($_GET['page'] ?? 'home');

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// ============================
// 1) Menu vem da sessão (carregado no login_action.php pela VIEW)
// ============================
$menuApps = $_SESSION['menu_apps'] ?? [];

// ============================
// 2) Agrupa por módulo (CODMODULO)
// ============================
$grupos = [];
foreach ($menuApps as $app) {
    $mod = $app['CODMODULO'] ?? 'OUTROS';
    if (!isset($grupos[$mod])) $grupos[$mod] = [];
    $grupos[$mod][] = $app;
}

// Ordena os grupos e itens (garante consistência mesmo se a view variar)
foreach ($grupos as $codModulo => &$itens) {
    usort($itens, function ($a, $b) {
        $oa = (int)($a['ORDEM_APLICACAO'] ?? 9999);
        $ob = (int)($b['ORDEM_APLICACAO'] ?? 9999);
        if ($oa === $ob) {
            return strcmp((string)($a['APLICACAO'] ?? ''), (string)($b['APLICACAO'] ?? ''));
        }
        return $oa <=> $ob;
    });
}
unset($itens);

// Labels amigáveis pros módulos
$labelsModulo = [
    'UPLOAD' => 'Importação',
    'DADOS'  => 'Dados',
    'ADMIN'  => 'Administração',
];

// ============================
// 2.1) normalizeLinkMenu / ends_with_ci
// Centralizadas em helpers/functions.php
// Mantém fallback apenas se por algum motivo ainda não existirem
// ============================
if (!function_exists('ends_with_ci')) {
    function ends_with_ci($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle   = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        if ($len > strlen($haystack)) return false;
        return strtolower(substr($haystack, -$len)) === strtolower($needle);
    }
}

if (!function_exists('normalizeLinkMenu')) {
    function normalizeLinkMenu($linkMenu)
    {
        $linkMenu = trim((string)$linkMenu);

        // remove extensão .php se vier (compatível com PHP < 8)
        if ($linkMenu !== '' && ends_with_ci($linkMenu, '.php')) {
            $linkMenu = substr($linkMenu, 0, -4);
        }

        // normaliza prefixo "upload_" -> "imp_"
        // Ex: upload_setormetacapac -> imp_setormetacapac
        if ($linkMenu !== '' && stripos($linkMenu, 'upload_') === 0) {
            $linkMenu = 'imp_' . substr($linkMenu, strlen('upload_'));
        }

        return $linkMenu;
    }
}

// ============================
// 3) Helper para ícone (por enquanto fixo/fallback)
// (Como a view não traz ICO, usamos um fallback por módulo)
// ============================
if (!function_exists('renderMenuIconFromModulo')) {
    function renderMenuIconFromModulo($codModulo) {
        $codModulo = strtoupper(trim((string)$codModulo));

        if ($codModulo === 'UPLOAD') return '<i class="bi bi-cloud-arrow-up-fill me-2"></i>';
        if ($codModulo === 'DADOS')  return '<i class="bi bi-table me-2"></i>';
        if ($codModulo === 'ADMIN')  return '<i class="bi bi-shield-lock-fill me-2"></i>';

        return '<i class="bi bi-dot me-2"></i>';
    }
}

// ============================
// 3.1) Normaliza LINKMENU vindo da VIEW
// - Remove ".php" se vier
// - Se vier "upload_*", converte para "imp_*"
// (MANTIDO, mas protegido para não redeclarar)
// ============================
if (!function_exists('normalizeLinkMenu')) {
    function normalizeLinkMenu($linkMenu)
    {
        $linkMenu = trim((string)$linkMenu);

        // remove extensão .php se vier (compatível com PHP < 8)
        if ($linkMenu !== '' && ends_with_ci($linkMenu, '.php')) {
            $linkMenu = substr($linkMenu, 0, -4);
        }

        // normaliza prefixo "upload_" -> "imp_"
        // Ex: upload_setormetacapac -> imp_setormetacapac
        if ($linkMenu !== '' && stripos($linkMenu, 'upload_') === 0) {
            $linkMenu = 'imp_' . substr($linkMenu, strlen('upload_'));
        }

        return $linkMenu;
    }
}
?>

<style>
    /* =========================
   Sidebar – tamanho + recolher/expandir + busca
   ========================= */

    :root {
        --sidebar-expanded: 300px;
        --sidebar-collapsed: 88px;
    }

    /* LARGURA DO SIDEBAR */
    .modern-sidebar {
        width: var(--sidebar-expanded);
        min-width: var(--sidebar-expanded);
        font-size: 15px;
        transition: width .18s ease, min-width .18s ease, transform .18s ease;
    }

    /* colapsado */
    .modern-sidebar.is-collapsed {
        width: var(--sidebar-collapsed);
        min-width: var(--sidebar-collapsed);
    }

    /* esconder textos no modo recolhido (mantém ícones/estrutura) */
    .modern-sidebar.is-collapsed .brand-text,
    .modern-sidebar.is-collapsed .menu-header,
    .modern-sidebar.is-collapsed .nav-link span .nav-text,
    .modern-sidebar.is-collapsed .sidebar-search,
    .modern-sidebar.is-collapsed .user-info,
    .modern-sidebar.is-collapsed .logout-btn {
        display: none !important;
    }

    /* ainda permite ver o “logo” (ícone verde) e avatar no recolhido */
    .modern-sidebar.is-collapsed .brand {
        justify-content: center;
    }

    .modern-sidebar.is-collapsed .brand .brand-icon {
        margin-right: 0 !important;
    }

    /* itens centralizados no recolhido */
    .modern-sidebar.is-collapsed .nav-link {
        justify-content: center;
        padding: 12px 0;
    }

    /* links e ícones */
    .modern-sidebar .nav-link {
        font-size: 0.98rem;
        padding: 11px 16px;
        border-radius: 12px;
        display: flex;
        align-items: center;
    }

    .modern-sidebar .nav-link i {
        font-size: 1.10rem;
    }

    .modern-sidebar .nav-link.active {
        padding-left: 18px;
    }

    /* headers clicáveis dos módulos */
    .modern-sidebar .menu-header {
        font-size: 0.78rem;
        letter-spacing: .08em;
        padding-top: 12px;
        padding-bottom: 6px;
        opacity: .85;
    }

    /* botão recolher */
    .sidebar-toggle-btn {
        border: 0;
        background: transparent;
        color: inherit;
        padding: 6px 8px;
        border-radius: 10px;
    }

    .sidebar-toggle-btn:hover {
        background: rgba(255, 255, 255, .08);
    }

    /* busca */
    .modern-sidebar .sidebar-search .form-control {
        font-size: 0.95rem;
        padding: 10px 12px;
        border-radius: 14px;
    }

    /* perfil */
    .modern-sidebar .user-profile h6 {
        font-size: .95rem;
    }

    .modern-sidebar .user-profile small {
        font-size: .80rem;
    }

    /* =========================================================
   AJUSTE DO "CONTEÚDO" (pra não ficar aquele espaço estranho)
   - muita gente fixa margin-left no conteúdo
   - a gente tenta acompanhar via CSS (quando possível)
   ========================================================= */

    /* tenta ajustar containers comuns (se existirem) */
    .app-shell .main,
    .app-shell .main-content,
    .app-shell .content,
    .app-shell .content-wrapper,
    .app-shell .page-content,
    .app-shell .main-inner {
        transition: margin-left .18s ease;
    }

    /* quando o sidebar estiver colapsado, o JS também ajusta inline,
   mas esse fallback ajuda quando o layout não usa inline */
    body.sidebar-collapsed .app-shell .main,
    body.sidebar-collapsed .app-shell .main-content,
    body.sidebar-collapsed .app-shell .content,
    body.sidebar-collapsed .app-shell .content-wrapper,
    body.sidebar-collapsed .app-shell .page-content,
    body.sidebar-collapsed .app-shell .main-inner {
        margin-left: var(--sidebar-collapsed) !important;
    }
</style>

<div class="modern-sidebar flex-shrink-0" id="sidebarMenu">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php?page=home" class="brand mb-0 d-flex align-items-center">
            <div class="bg-success bg-gradient rounded-3 d-flex justify-content-center align-items-center me-2 brand-icon" style="width: 36px; height: 36px;">
                <i class="bi bi-patch-check-fill fs-5 text-white"></i>
            </div>
            <span class="brand-text">Importador Mega G</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- botão recolher/expandir (desktop) -->
            <button type="button" class="sidebar-toggle-btn d-none d-md-inline-flex" id="btnSidebarToggle" title="Recolher/Expandir">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <!-- botão fechar (mobile) -->
            <button class="btn-close btn-close-white d-md-none" onclick="toggleMenu()"></button>
        </div>
    </div>

    <div class="position-relative sidebar-search mb-4">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control py-2" placeholder="Buscar..." id="sidebarSearchInput" autocomplete="off">
    </div>

    <div style="overflow-y: auto; flex-grow: 1;" class="mb-3">

        <!-- ===== Menu Principal (fixo) ===== -->
        <div class="menu-header">Menu Principal</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item" data-menu-item="1" data-menu-text="dashboard" data-menu-group="principal">
                <a href="index.php?page=home" class="nav-link <?php echo ($paginaAtual == 'home') ? 'active' : ''; ?>">
                    <span>
                        <i class="bi bi-grid-fill me-2"></i>
                        <span class="nav-text">Dashboard</span>
                    </span>
                </a>
            </li>
        </ul>

        <!-- ===== Menus dinâmicos (da VIEW via sessão) ===== -->
        <?php foreach ($grupos as $codModulo => $itens): ?>
            <?php
            $tituloModulo = $labelsModulo[$codModulo] ?? $codModulo;

            // ID seguro (evita quebrar HTML/JS com espaços e caracteres especiais)
            $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$codModulo);
            $collapseId = 'menu_modulo_' . $safeId;

            // Detecta se algum item está ativo para abrir a "pastinha" automaticamente
            $hasActive = false;
            foreach ($itens as $appTmp) {
                $tmpLink = normalizeLinkMenu((string)($appTmp['LINKMENU'] ?? ''));
                if ($paginaAtual === $tmpLink) {
                    $hasActive = true;
                    break;
                }
            }

            $moduleText = strtolower((string)$tituloModulo);
            ?>

            <!-- Cabeçalho do módulo (clicável / pastinha) -->
            <div class="menu-header mt-3"
                role="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?php echo $collapseId; ?>"
                aria-expanded="<?php echo $hasActive ? 'true' : 'false'; ?>"
                style="cursor:pointer; user-select:none;">
                <?php echo renderMenuIconFromModulo($codModulo); ?>
                <?php echo htmlspecialchars($tituloModulo, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <!-- Conteúdo colapsável do módulo -->
            <div class="collapse <?php echo $hasActive ? 'show' : ''; ?>"
                id="<?php echo $collapseId; ?>"
                data-menu-collapse="1"
                data-menu-module="<?php echo htmlspecialchars($moduleText, ENT_QUOTES, 'UTF-8'); ?>">

                <ul class="nav nav-pills flex-column mt-1">
                    <?php foreach ($itens as $app): ?>
                        <?php
                        $codApp   = (string)($app['CODAPLICACAO'] ?? '');
                        $nomeApp  = (string)($app['APLICACAO'] ?? $codApp);

                        // pega o link cru da view
                        $linkMenuRaw = (string)($app['LINKMENU'] ?? '');

                        // normaliza para bater com suas pages (imp_*)
                        $linkMenu = normalizeLinkMenu($linkMenuRaw);

                        // LINKMENU deve ser o slug da page (sem .php)
                        $href = 'index.php?page=' . urlencode($linkMenu);

                        // Permissão simples: existe na sessão?
                        $temAcesso = temPermissao($codApp);

                        // Active
                        $isActive = ($paginaAtual === $linkMenu);

                        // Ícone (fallback por módulo)
                        $icoHtml = renderMenuIconFromModulo($codModulo);

                        $classes = 'nav-link';
                        if ($isActive) $classes .= ' active';
                        if (!$temAcesso) $classes .= ' js-no-permission';

                        // texto para busca (mantém simples; o JS remove acento)
                        $searchText = strtolower($nomeApp . ' ' . $tituloModulo . ' ' . $codModulo);
                        ?>

                        <li class="nav-item"
                            data-menu-item="1"
                            data-menu-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                            data-menu-group="<?php echo htmlspecialchars(strtolower((string)$codModulo), ENT_QUOTES, 'UTF-8'); ?>">
                            <a
                                href="<?php echo $temAcesso ? $href : '#'; ?>"
                                class="<?php echo $classes; ?>"
                                <?php if (!$temAcesso): ?>
                                aria-disabled="true"
                                data-app="<?php echo htmlspecialchars($nomeApp, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php endif; ?>>
                                <span>
                                    <?php echo $icoHtml; ?>
                                    <span class="nav-text"><?php echo htmlspecialchars($nomeApp, ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </a>
                        </li>

                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endforeach; ?>

    </div>

    <div class="user-profile">
        <?php
        $user = $_SESSION['usuario'] ?? 'Admin';
        $iniciais = strtoupper(substr($user, 0, 2));
        ?>
        <div class="user-avatar">
            <?php echo htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="user-info">
            <h6><?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars(strtolower($user), ENT_QUOTES, 'UTF-8'); ?>@megag.com</small>
        </div>
        <a href="logout.php" class="logout-btn ms-3" title="Sair">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>

</div>

<!-- ============================
     Modal: Sem Permissão
============================= -->
<div class="modal fade" id="modalSemPermissao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock me-2"></i>Acesso negado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Você não tem permissão para acessar:</p>
                <div class="p-3 rounded-3" style="background: rgba(13,110,253,.06); border:1px solid rgba(17,24,39,.10);">
                    <strong id="modalAppName">Módulo</strong>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Se você acredita que isso é um erro, solicite acesso ao administrador.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // ============================
        // 1) Modal Sem Permissão
        // ============================
        document.addEventListener('click', function(e) {
            const a = e.target.closest('.js-no-permission');
            if (!a) return;

            e.preventDefault();

            const appName = a.getAttribute('data-app') || 'Este módulo';
            const elName = document.getElementById('modalAppName');
            if (elName) elName.textContent = appName;

            const modalEl = document.getElementById('modalSemPermissao');
            if (!modalEl) return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });

        // ============================
        // 2) Recolher/Expandir (com persistência + ajusta conteúdo)
        // ============================
        const sidebar = document.getElementById('sidebarMenu');
        const btnToggle = document.getElementById('btnSidebarToggle');

        function currentSidebarWidthPx() {
            if (!sidebar) return 0;
            return sidebar.classList.contains('is-collapsed') ?
                parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-collapsed')) || 88 :
                parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-expanded')) || 300;
        }

        function adjustAppLayout() {
            const w = currentSidebarWidthPx() + 'px';
            const shell = document.querySelector('.app-shell');

            document.body.classList.toggle('sidebar-collapsed', !!(sidebar && sidebar.classList.contains('is-collapsed')));

            if (!shell) return;

            Array.prototype.forEach.call(shell.children, function(child) {
                if (!child || child.id === 'sidebarMenu') return;

                const ml = parseFloat(getComputedStyle(child).marginLeft) || 0;
                if (ml >= 60) {
                    child.style.marginLeft = w;
                }
            });

            const common = shell.querySelectorAll('.main, .main-content, .content, .content-wrapper, .page-content, .main-inner');
            common.forEach(function(el) {
                const ml = parseFloat(getComputedStyle(el).marginLeft) || 0;
                if (ml >= 60) el.style.marginLeft = w;
            });

            const inlineCandidates = document.querySelectorAll('[style*="margin-left"]');
            inlineCandidates.forEach(function(el) {
                if (el.id === 'sidebarMenu') return;
                if (shell && !shell.contains(el)) return;

                const ml = parseFloat(getComputedStyle(el).marginLeft) || 0;
                if (ml >= 60) el.style.marginLeft = w;
            });
        }

        function setCollapsed(state) {
            if (!sidebar) return;
            sidebar.classList.toggle('is-collapsed', !!state);
            try {
                localStorage.setItem('sidebar_collapsed', state ? '1' : '0');
            } catch (e) {}
            adjustAppLayout();
        }

        try {
            const saved = localStorage.getItem('sidebar_collapsed');
            if (saved === '1') {
                setCollapsed(true);
            } else {
                adjustAppLayout();
            }
        } catch (e) {
            adjustAppLayout();
        }

        if (btnToggle) {
            btnToggle.addEventListener('click', function() {
                const isCollapsed = sidebar && sidebar.classList.contains('is-collapsed');
                setCollapsed(!isCollapsed);
            });
        }

        // ============================
        // 3) Busca: filtra itens e abre pastinhas com match
        // ============================
        const input = document.getElementById('sidebarSearchInput');

        function normalizeText(s) {
            return (s || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function applySearch(term) {
            const q = normalizeText(term);

            const items = sidebar ? sidebar.querySelectorAll('[data-menu-item="1"]') : [];
            const collapses = sidebar ? sidebar.querySelectorAll('[data-menu-collapse="1"]') : [];

            if (!q) {
                items.forEach(li => { li.style.display = ''; });
                return;
            }

            const matchedCollapseIds = new Set();

            items.forEach(li => {
                const text = normalizeText(li.getAttribute('data-menu-text') || li.textContent);
                const ok = text.includes(q);
                li.style.display = ok ? '' : 'none';

                if (ok) {
                    const collapse = li.closest('.collapse');
                    if (collapse && collapse.id) matchedCollapseIds.add(collapse.id);
                }
            });

            collapses.forEach(col => {
                if (!col.id) return;
                const hasMatch = matchedCollapseIds.has(col.id);

                if (typeof bootstrap === 'undefined' || !bootstrap.Collapse) return;

                const inst = bootstrap.Collapse.getOrCreateInstance(col, { toggle: false });
                if (hasMatch) inst.show();
                else inst.hide();
            });
        }

        if (input) {
            input.addEventListener('input', function() {
                applySearch(input.value || '');
            });
        }

        window.addEventListener('resize', function() {
            adjustAppLayout();
        });
    })();
</script>
