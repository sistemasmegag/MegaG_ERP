<?php
// index.php - controlador central

require __DIR__ . '/routes/check_session.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Página padrão
$page = $_GET['page'] ?? 'home';

// Segurança: remove qualquer caractere suspeito
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// =====================================================
// WHITELIST DINÂMICA: tudo que existir em /pages/*.php
// =====================================================
$allowedPages = [];
$pagesDir = __DIR__ . '/pages';

foreach (glob($pagesDir . '/*.php') as $file) {
    $allowedPages[] = basename($file, '.php'); // ex: imp_setormetacapac
}

// Se a página não existir em /pages, volta pra home
if (!in_array($page, $allowedPages, true)) {
    $page = 'home'; // ou um 404, se preferir
}

// ============================
// MENU vem da sessão (carregado no login_action.php pela VIEW)
// ============================
$menuApps = $_SESSION['menu_apps'] ?? [];

// =====================================================
// VERIFICAÇÃO CENTRAL (igual ao exemplo do gestor)
// - valida permissão pela sessão (menu_apps)
// - valida se a page existe fisicamente
// - se ok: inclui a página
// - se não: página de sem permissão (ou erro)
// =====================================================
$pageFile = $pagesDir . "/{$page}.php";

$temPermissao = fnValidarPermAplicacao($page, $menuApps);
$existePagina = file_exists($pageFile);

// Define página atual para o sidebar marcar "active"
$paginaAtual = $page;

// Opcional: registra módulo/aplicação na sessão (igual ao exemplo)
$_SESSION['sessao']['modulo']    = fnVerificaModPorAplicacao($page, $menuApps);
$_SESSION['sessao']['aplicacao'] = $page;

// Se não tem permissão OU não existe, redireciona para páginas específicas
if (!$temPermissao) {
    http_response_code(403);

    // se existir uma page de sem permissão, usa ela; senão usa home
    if (in_array('sem_permissao', $allowedPages, true)) {
        $page = 'sem_permissao';
        $paginaAtual = $page;
        $pageFile = $pagesDir . "/{$page}.php";
    } else {
        $page = 'home';
        $paginaAtual = $page;
        $pageFile = $pagesDir . "/{$page}.php";
    }
} else if (!$existePagina) {
    http_response_code(404);

    // se existir uma page de erro404, usa ela; senão usa home
    if (in_array('erro404', $allowedPages, true)) {
        $page = 'erro404';
        $paginaAtual = $page;
        $pageFile = $pagesDir . "/{$page}.php";
    } else {
        $page = 'home';
        $paginaAtual = $page;
        $pageFile = $pagesDir . "/{$page}.php";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include __DIR__ . '/includes/header.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">
    <?php
    if (file_exists($pageFile)) {
        include $pageFile;
    } else {
        echo '<p>Página não encontrada.</p>';
    }
    ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
