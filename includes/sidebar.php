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
    usort($itens, function($a, $b){
        $oa = (int)($a['ORDEM_APLICACAO'] ?? 9999);
        $ob = (int)($b['ORDEM_APLICACAO'] ?? 9999);
        if ($oa === $ob) {
            return strcmp((string)($a['APLIACACAO'] ?? ''), (string)($b['APLIACACAO'] ?? ''));
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
// 3) Helper para ícone (por enquanto fixo/fallback)
//    (Como a view não traz ICO, usamos um fallback por módulo)
// ============================
function renderMenuIconFromModulo($codModulo) {
    $codModulo = strtoupper(trim((string)$codModulo));

    if ($codModulo === 'UPLOAD') return '<i class="bi bi-cloud-arrow-up-fill me-2"></i>';
    if ($codModulo === 'DADOS')  return '<i class="bi bi-table me-2"></i>';
    if ($codModulo === 'ADMIN')  return '<i class="bi bi-shield-lock-fill me-2"></i>';

    return '<i class="bi bi-dot me-2"></i>';
}

// ============================
// 3.1) Normaliza LINKMENU vindo da VIEW
// - Remove ".php" se vier
// - Se vier "upload_*", converte para "imp_*"
// ============================
function normalizeLinkMenu($linkMenu) {
    $linkMenu = trim((string)$linkMenu);

    // remove extensão .php se vier
    if ($linkMenu !== '' && str_ends_with(strtolower($linkMenu), '.php')) {
        $linkMenu = substr($linkMenu, 0, -4);
    }

    // normaliza prefixo "upload_" -> "imp_"
    // Ex: upload_setormetacapac -> imp_setormetacapac
    if ($linkMenu !== '' && stripos($linkMenu, 'upload_') === 0) {
        $linkMenu = 'imp_' . substr($linkMenu, strlen('upload_'));
    }

    return $linkMenu;
}
?>

<div class="modern-sidebar flex-shrink-0" id="sidebarMenu">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php?page=home" class="brand mb-0">
            <div class="bg-success bg-gradient rounded-3 d-flex justify-content-center align-items-center me-2" style="width: 36px; height: 36px;">
                 <i class="bi bi-patch-check-fill fs-5 text-white"></i>
            </div>
            Importador Mega G
        </a>

        <button class="btn-close btn-close-white d-md-none" onclick="toggleMenu()"></button>
    </div>

    <div class="position-relative sidebar-search mb-4">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control py-2" placeholder="Buscar...">
    </div>

    <div style="overflow-y: auto; flex-grow: 1;" class="mb-3">

        <!-- ===== Menu Principal (fixo) ===== -->
        <div class="menu-header">Menu Principal</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php?page=home" class="nav-link <?php echo ($paginaAtual == 'home') ? 'active' : ''; ?>">
                    <span><i class="bi bi-grid-fill me-2"></i> Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- ===== Menus dinâmicos (da VIEW via sessão) ===== -->
        <?php foreach ($grupos as $codModulo => $itens): ?>
            <?php $tituloModulo = $labelsModulo[$codModulo] ?? $codModulo; ?>

            <div class="menu-header mt-3"><?php echo htmlspecialchars($tituloModulo, ENT_QUOTES, 'UTF-8'); ?></div>

            <ul class="nav nav-pills flex-column">
                <?php foreach ($itens as $app): ?>
                    <?php
                        $codApp   = (string)($app['CODAPLICACAO'] ?? '');
                        $nomeApp  = (string)($app['APLIACACAO'] ?? $codApp);

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
                    ?>

                    <li>
                        <a
                            href="<?php echo $temAcesso ? $href : '#'; ?>"
                            class="<?php echo $classes; ?>"
                            <?php if (!$temAcesso): ?>
                                aria-disabled="true"
                                data-app="<?php echo htmlspecialchars($nomeApp, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php endif; ?>
                        >
                            <span>
                                <?php echo $icoHtml; ?>
                                <?php echo htmlspecialchars($nomeApp, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </a>
                    </li>

                <?php endforeach; ?>
            </ul>
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
(function(){
    // Intercepta clique nos itens sem permissão e abre modal
    document.addEventListener('click', function(e){
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
})();
</script>
