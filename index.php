<?php
// index.php - controlador central

require __DIR__ . '/routes/check_session.php';

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

// Define página atual para o sidebar marcar "active"
$paginaAtual = $page;

$pageFile = $pagesDir . "/{$page}.php";
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
