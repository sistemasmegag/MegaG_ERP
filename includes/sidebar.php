<?php
// includes/sidebar.php

require_once __DIR__ . '/../helpers/functions.php';

// Garante que $paginaAtual exista (se não foi setado no index.php)
$paginaAtual = $paginaAtual ?? ($_GET['page'] ?? 'home');

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

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
    'SAAS_APPS' => 'Aplicativos SaaS',
    'UPLOAD'    => 'Importação',
    'DADOS'     => 'Dados',
    'ADMIN'     => 'Administração',
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
if (!function_exists('renderSvgIcon')) {
    function renderSvgIcon($iconName, $extraClasses = '')
    {
        $iconName = strtolower(trim((string)$iconName));
        $extraClasses = trim((string)$extraClasses);

        $svg = '';
        switch ($iconName) {
            case 'search':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>';
                break;
            case 'layout':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M9 4v16"></path></svg>';
                break;
            case 'dashboard':
                $svg = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1.5"></rect><rect x="13" y="3" width="8" height="5" rx="1.5"></rect><rect x="13" y="10" width="8" height="11" rx="1.5"></rect><rect x="3" y="13" width="8" height="8" rx="1.5"></rect></svg>';
                break;
            case 'rocket':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 19c1.5-3 4-4 4-4"></path><path d="M15 9l-6 6"></path><path d="M14 4c3.5 0 6 2.5 6 6-2 2-4.5 3.5-7.5 4.5L9.5 11.5C10.5 8.5 12 6 14 4z"></path><path d="M7 13l-3 1 1-3 2-2 2 2-2 2z"></path></svg>';
                break;
            case 'upload':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V5"></path><path d="M8 9l4-4 4 4"></path><path d="M20 16.5V18a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1.5"></path></svg>';
                break;
            case 'table':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M3 10h18M9 4v16M15 4v16"></path></svg>';
                break;
            case 'shield':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"></path><path d="M9.5 12l1.8 1.8L15 10"></path></svg>';
                break;
            case 'receipt':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z"></path><path d="M9 8h6M9 12h6"></path></svg>';
                break;
            case 'kanban':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M8 9v6M12 9v3M16 9v8"></path></svg>';
                break;
            case 'headset':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 13a8 8 0 0 1 16 0"></path><rect x="3" y="12" width="4" height="7" rx="2"></rect><rect x="17" y="12" width="4" height="7" rx="2"></rect><path d="M21 18a3 3 0 0 1-3 3h-2"></path></svg>';
                break;
            case 'plus-square':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M12 8v8M8 12h8"></path></svg>';
                break;
            case 'card-text':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M7 10h10M7 14h7"></path></svg>';
                break;
            case 'box-in':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 4-8 4-8-4 8-4z"></path><path d="M4 7v10l8 4 8-4V7"></path><path d="M12 11v6"></path><path d="M9 14l3 3 3-3"></path></svg>';
                break;
            case 'coin':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="6" rx="7" ry="3"></ellipse><path d="M5 6v8c0 1.7 3.1 3 7 3s7-1.3 7-3V6"></path><path d="M12 9v6"></path></svg>';
                break;
            case 'bullseye':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="4"></circle><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"></circle></svg>';
                break;
            case 'chart-down':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6v14h16"></path><path d="M7 9l4 4 4-4 2 2"></path></svg>';
                break;
            case 'chart-bars':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 19V9"></path><path d="M12 19V5"></path><path d="M19 19v-7"></path></svg>';
                break;
            case 'eye':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>';
                break;
            case 'ruler':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 15l7-7 9 9-7 3-9-5z"></path><path d="M10 9l2 2M13 12l2 2M16 15l2 2"></path></svg>';
                break;
            case 'cash-stack':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="2"></rect><path d="M7 10h.01M17 14h.01"></path><circle cx="12" cy="12" r="2.5"></circle><path d="M6 5h12M7 19h10"></path></svg>';
                break;
            case 'people':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><circle cx="17" cy="10" r="2.5"></circle><path d="M4 19c0-3 3-5 5-5s5 2 5 5"></path><path d="M14 19c.4-1.9 2.1-3.2 4-3.5"></path></svg>';
                break;
            case 'diagram':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="6" height="4" rx="1"></rect><rect x="15" y="4" width="6" height="4" rx="1"></rect><rect x="9" y="16" width="6" height="4" rx="1"></rect><path d="M6 8v4h12V8M12 12v4"></path></svg>';
                break;
            case 'book':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5z"></path><path d="M8 7h8M8 11h8"></path></svg>';
                break;
            case 'badge':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><path d="M6 19c0-3.2 2.7-5 6-5s6 1.8 6 5"></path><path d="M8 21l4-2 4 2"></path></svg>';
                break;
            case 'logout':
                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17l5-5-5-5"></path><path d="M15 12H4"></path><path d="M20 19v-2a2 2 0 0 0-2-2h-2"></path><path d="M20 5v2a2 2 0 0 1-2 2h-2"></path></svg>';
                break;
            case 'circle':
            default:
                $svg = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle></svg>';
                break;
        }

        return '<span class="menu-icon-svg ' . htmlspecialchars($extraClasses, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true">' . $svg . '</span>';
    }
}

if (!function_exists('mapDbIconToSvgName')) {
    function mapDbIconToSvgName($icoHtml)
    {
        $icoHtml = strtolower((string)$icoHtml);
        if ($icoHtml === '') return '';

        $map = [
            'fa-bullseye' => 'bullseye',
            'fa-table' => 'table',
            'fa-list' => 'dashboard',
            'fa-tasks' => 'kanban',
            'fa-abacus' => 'chart-bars',
            'fa-file' => 'card-text',
            'fa-book' => 'book',
            'fa-headset' => 'headset',
            'fa-user' => 'badge',
            'fa-users' => 'people',
            'fa-cog' => 'shield',
            'fa-gear' => 'shield',
            'fa-money' => 'coin',
            'fa-dollar' => 'coin',
        ];

        foreach ($map as $needle => $iconName) {
            if (strpos($icoHtml, $needle) !== false) {
                return $iconName;
            }
        }

        return '';
    }
}

if (!function_exists('renderDbHtmlIcon')) {
    function renderDbHtmlIcon($icoHtml, $extraClasses = 'me-2')
    {
        $icoHtml = (string)$icoHtml;
        if ($icoHtml === '') return '';

        if (!preg_match('/class\s*=\s*"([^"]+)"/i', $icoHtml, $m)) {
            return '';
        }

        $classAttr = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string)$m[1]);
        $classAttr = trim(preg_replace('/\s+/', ' ', $classAttr));
        $extraClasses = trim((string)$extraClasses);

        if ($classAttr === '' || stripos($classAttr, 'fa') === false) {
            return '';
        }

        if ($extraClasses !== '') {
            $classAttr .= ' ' . $extraClasses;
        }

        return '<i class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
    }
}

if (!function_exists('renderMenuIconFromModulo')) {
    function renderMenuIconFromModulo($codModulo)
    {
        $codModulo = strtoupper(trim((string)$codModulo));
        if ($codModulo === 'SAAS_APPS') return renderSvgIcon('rocket', 'me-2');
        if ($codModulo === 'UPLOAD')    return renderSvgIcon('upload', 'me-2');
        if ($codModulo === 'DADOS')     return renderSvgIcon('table', 'me-2');
        if ($codModulo === 'ADMIN')     return renderSvgIcon('shield', 'me-2');
        return renderSvgIcon('circle', 'me-2');
    }
}

// ============================
// 3.1) Icone por item (CODAPLICACAO ou LINKMENU)
// ============================
if (!function_exists('renderMenuIcon')) {
    function renderMenuIcon($codModulo, $codApp = '', $linkMenu = '', $icoHtml = '')
    {
        $codApp   = strtoupper(trim((string)$codApp));
        $linkMenu = strtolower(trim((string)$linkMenu));
        $mod      = strtoupper(trim((string)$codModulo));

        $dbHtmlIcon = renderDbHtmlIcon($icoHtml, 'me-2');
        if ($dbHtmlIcon !== '') {
            return $dbHtmlIcon;
        }

        $dbIcon = mapDbIconToSvgName($icoHtml);
        if ($dbIcon !== '') {
            return renderSvgIcon($dbIcon, 'me-2');
        }

        // Mapa por CODAPLICACAO
        $mapaCodApp = [
            'APP_DESP_LANC'      => 'coin',
            'APP_DESP_GERENCIAR' => 'receipt',
            'APP_DESP_CONFIG'    => 'shield',
            'APP_DESP'           => 'receipt',
            'APP_INVENTARIO_TI'  => 'box-in',
            'APP_TI_INVENTARIO'  => 'box-in',
            'APP_TAREFAS'        => 'kanban',
            'APP_TASK'           => 'kanban',
            'APP_CRM'            => 'diagram',
            'APP_WIKI'           => 'book',
            'APP_RH'             => 'badge',
            'APP_USUARIOS'       => 'people',
            'APP_CONFIG'         => 'shield',
            'APP_CHAMADOS'       => 'headset',
        ];
        if ($codApp !== '' && isset($mapaCodApp[$codApp])) {
            return renderSvgIcon($mapaCodApp[$codApp], 'me-2');
        }

        // Mapa por LINKMENU (slug normalizado)
        $mapaLink = [
            'despesas'            => 'coin',
            'gerenciar_despesas'  => 'receipt',
            'config_despesas'     => 'shield',
            'tarefas'             => 'kanban',
            'tarefas_criar_tasks' => 'plus-square',
            'tarefas_criar_task'  => 'plus-square',
            'tarefas_detalhes'    => 'card-text',
            'cargas'              => 'box-in',
            'comissoes'           => 'coin',
            'imp_metas'           => 'bullseye',
            'imp_metas_gap'       => 'chart-down',
            'imp_metas_faixa'     => 'chart-bars',
            'imp_metas_perspec'   => 'eye',
            'imp_bi_metas'        => 'chart-bars',
            'bi_metas_perspect'   => 'eye',
            'imp_tabvdaprodraio'  => 'ruler',
            'imp_lanctocomissao'  => 'cash-stack',
            'dados_visualizar'    => 'table',
            'inventario_ti'       => 'box-in',
            'ti_inventario'       => 'box-in',
            'chamados'            => 'headset',
            'usuarios'            => 'people',
            'crm'                 => 'diagram',
            'wiki'                => 'book',
            'rh'                  => 'badge',
            'lancamento_campanhas' => 'rocket',
        ];
        if ($linkMenu !== '' && isset($mapaLink[$linkMenu])) {
            return renderSvgIcon($mapaLink[$linkMenu], 'me-2');
        }

        // Fallback por modulo
        if ($mod === 'SAAS_APPS') return renderSvgIcon('rocket', 'me-2');
        if ($mod === 'UPLOAD')    return renderSvgIcon('upload', 'me-2');
        if ($mod === 'DADOS')     return renderSvgIcon('table', 'me-2');
        if ($mod === 'ADMIN')     return renderSvgIcon('shield', 'me-2');
        if ($mod === 'DESPESAS')  return renderSvgIcon('receipt', 'me-2');
        if ($mod === 'TAREFAS')   return renderSvgIcon('kanban', 'me-2');
        if ($mod === 'CHAMADOS')  return renderSvgIcon('headset', 'me-2');
        if ($mod === 'CRM')       return renderSvgIcon('diagram', 'me-2');
        if ($mod === 'WIKI')      return renderSvgIcon('book', 'me-2');
        if ($mod === 'RH')        return renderSvgIcon('badge', 'me-2');

        return renderSvgIcon('circle', 'me-2');
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

if (!function_exists('findMobileShortcut')) {
    function findMobileShortcut(array $menuApps, string $modulo, array $preferredLinks = []): ?array
    {
        foreach ($preferredLinks as $preferred) {
            foreach ($menuApps as $app) {
                $appModulo = strtoupper(trim((string)($app['CODMODULO'] ?? '')));
                $linkMenu = normalizeLinkMenu((string)($app['LINKMENU'] ?? ''));
                if ($appModulo === strtoupper($modulo) && $linkMenu === $preferred) {
                    return $app;
                }
            }
        }

        foreach ($menuApps as $app) {
            $appModulo = strtoupper(trim((string)($app['CODMODULO'] ?? '')));
            if ($appModulo === strtoupper($modulo)) {
                return $app;
            }
        }

        return null;
    }
}

$mobileShortcuts = [];

$mobileShortcuts[] = [
    'label' => 'Home',
    'href' => 'index.php?page=home',
    'active' => $paginaAtual === 'home',
    'icon' => renderSvgIcon('dashboard'),
    'kind' => 'link',
];

$shortcutUpload = findMobileShortcut($menuApps, 'UPLOAD', ['home_importacao']);
if ($shortcutUpload) {
    $uploadLink = normalizeLinkMenu((string)($shortcutUpload['LINKMENU'] ?? ''));
    $mobileShortcuts[] = [
        'label' => 'Importar',
        'href' => 'index.php?page=' . urlencode($uploadLink),
        'active' => $paginaAtual === $uploadLink,
        'icon' => renderMenuIcon(
            (string)($shortcutUpload['CODMODULO'] ?? 'UPLOAD'),
            (string)($shortcutUpload['CODAPLICACAO'] ?? ''),
            $uploadLink,
            (string)($shortcutUpload['ICO'] ?? '')
        ),
        'kind' => 'link',
    ];
}

$shortcutDados = findMobileShortcut($menuApps, 'DADOS', ['dados_visualizar']);
if ($shortcutDados) {
    $dadosLink = normalizeLinkMenu((string)($shortcutDados['LINKMENU'] ?? ''));
    $mobileShortcuts[] = [
        'label' => 'Dados',
        'href' => 'index.php?page=' . urlencode($dadosLink),
        'active' => $paginaAtual === $dadosLink,
        'icon' => renderMenuIcon(
            (string)($shortcutDados['CODMODULO'] ?? 'DADOS'),
            (string)($shortcutDados['CODAPLICACAO'] ?? ''),
            $dadosLink,
            (string)($shortcutDados['ICO'] ?? '')
        ),
        'kind' => 'link',
    ];
}

$shortcutApps = findMobileShortcut($menuApps, 'SAAS_APPS', ['despesas', 'tarefas', 'despesas_dashboard']);
if ($shortcutApps) {
    $appsLink = normalizeLinkMenu((string)($shortcutApps['LINKMENU'] ?? ''));
    $appsLabel = (stripos($appsLink, 'desp') !== false) ? 'Despesas' : 'Módulo';
    $mobileShortcuts[] = [
        'label' => $appsLabel,
        'href' => 'index.php?page=' . urlencode($appsLink),
        'active' => $paginaAtual === $appsLink,
        'icon' => renderMenuIcon(
            (string)($shortcutApps['CODMODULO'] ?? 'SAAS_APPS'),
            (string)($shortcutApps['CODAPLICACAO'] ?? ''),
            $appsLink,
            (string)($shortcutApps['ICO'] ?? '')
        ),
        'kind' => 'link',
        'featured' => true,
    ];
}

if (count($mobileShortcuts) > 4) {
    $mobileShortcuts = array_slice($mobileShortcuts, 0, 4);
}
?>

<aside class="modern-sidebar" id="sidebarMenu">
    <div class="sidebar-header">
        <a href="index.php?page=home" class="brand-logo">
            <img src="assets/images/logo.png" alt="MegaG">
            <span>MEGAG <span class="blue-text">ERP</span></span>
        </a>
    </div>

    <div class="sidebar-menu">
        <nav class="nav-group">
            <a href="index.php?page=home" class="nav-item <?php echo ($paginaAtual == 'home') ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>

            <!-- Menus dinâmicos em Abinhas (Accordion) -->
            <?php foreach ($grupos as $codModulo => $itens): ?>
                <?php
                $tituloModulo = $labelsModulo[$codModulo] ?? $codModulo;
                $safeId = 'collapse_' . preg_replace('/[^a-zA-Z0-9]/', '', $codModulo);
                
                // Verifica se algum item deste grupo está ativo para já começar aberto
                $hasActive = false;
                foreach ($itens as $appTmp) {
                    if ($paginaAtual === normalizeLinkMenu($appTmp['LINKMENU'] ?? '')) {
                        $hasActive = true;
                        break;
                    }
                }
                ?>
                <div class="menu-section">
                    <div class="section-header <?php echo $hasActive ? '' : 'collapsed'; ?>" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#<?php echo $safeId; ?>" 
                         aria-expanded="<?php echo $hasActive ? 'true' : 'false'; ?>">
                        <span><?php echo htmlspecialchars($tituloModulo); ?></span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </div>
                    
                    <div class="collapse <?php echo $hasActive ? 'show' : ''; ?>" id="<?php echo $safeId; ?>">
                        <div class="section-content">
                            <?php foreach ($itens as $app): ?>
                                <?php
                                $codApp   = (string)($app['CODAPLICACAO'] ?? '');
                                $nomeApp  = (string)($app['APLICACAO'] ?? $codApp);
                                $linkMenu = normalizeLinkMenu((string)($app['LINKMENU'] ?? ''));
                                $href     = 'index.php?page=' . urlencode($linkMenu);
                                $isActive = ($paginaAtual === $linkMenu);
                                ?>
                                <a href="<?php echo $href; ?>" class="nav-subitem <?php echo $isActive ? 'active' : ''; ?>">
                                    <span class="dot"></span>
                                    <span><?php echo htmlspecialchars($nomeApp); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="sidebar-footer">
        <?php
        $user = $_SESSION['usuario'] ?? 'Usuário';
        $iniciais = strtoupper(substr($user, 0, 2));
        ?>
        <div class="dark-mode-toggle" onclick="window.__applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark')">
            <div class="toggle-content">
                <i class="bi bi-moon-stars"></i>
                <span>Modo Escuro</span>
            </div>
        </div>

        <div class="user-footer-wrapper">
            <div class="user-block">
                <div class="user-avatar-modern">
                    <?php echo htmlspecialchars($iniciais); ?>
                </div>
                <div class="user-meta">
                    <span class="user-name"><?php echo htmlspecialchars($user); ?></span>
                    <span class="user-role">Administrador</span>
                </div>
            </div>
            
            <a href="logout.php" class="logout-btn" title="Sair do Sistema">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<style>
    .modern-sidebar { width: 280px; background: var(--sidebar-bg); border-right: 1px solid rgba(226, 232, 240, 0.8); border-radius: 0 28px 28px 0; display: flex; flex-direction: column; height: 100vh; position: sticky; top: 0; z-index: 1000; overflow: hidden; }
    .sidebar-header { padding: 2rem 1.5rem; }
    .brand-logo { display: flex; align-items: center; gap: 14px; text-decoration: none; font-weight: 900; font-size: 1.35rem; color: #1e293b; }
    .brand-logo > span { display: flex; flex-direction: column; align-items: center; line-height: 1.12; }
    .brand-logo img { width: 92px; height: 92px; object-fit: contain; }
    .blue-text { color: var(--brand-primary); }
    
    .sidebar-menu { flex: 1; padding: 0 1rem; overflow-y: auto; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--sidebar-text); text-decoration: none; font-weight: 600; transition: 0.2s; margin-bottom: 4px; }
    .nav-item:hover, .nav-item.active { background: var(--brand-primary-soft); color: var(--brand-primary); }
    .nav-item.active { background: var(--brand-primary-glow); border-left: 3px solid var(--brand-primary); }

    .menu-section { margin-top: 0.5rem; }
    .section-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; cursor: pointer; color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; transition: 0.2s; border-radius: 12px; }
    .section-header:hover { background: #f8fafc; color: #64748b; }
    .section-header .chevron-icon { transition: transform 0.3s ease; font-size: 0.8rem; }
    .section-header.collapsed .chevron-icon { transform: rotate(-90deg); }

    .section-content { display: flex; flex-direction: column; gap: 2px; padding-left: 12px; margin-top: 4px; }
    .nav-subitem { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 10px; color: var(--sidebar-text); text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
    .nav-subitem .dot { width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1; transition: 0.2s; }
    .nav-subitem:hover { color: var(--brand-primary); background: #f8fafc; }
    .nav-subitem:hover .dot { background: var(--brand-primary); transform: scale(1.2); }
    .nav-subitem.active { color: var(--brand-primary); font-weight: 700; background: var(--brand-primary-soft); }
    .nav-subitem.active .dot { background: var(--brand-primary); box-shadow: 0 0 8px var(--brand-primary); }

    .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(226, 232, 240, 0.8); }
    .dark-mode-toggle { margin-bottom: 1rem; cursor: pointer; }
    .toggle-content { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 12px; color: var(--sidebar-text); font-weight: 600; }
    .toggle-content:hover { background: #f1f5f9; }
    
    .user-footer-wrapper { display: flex; align-items: center; justify-content: space-between; }
    .user-block { display: flex; align-items: center; gap: 12px; }
    .user-avatar-modern { width: 40px; height: 40px; background: #4f46e5; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .user-meta { display: flex; flex-direction: column; line-height: 1.2; }
    .user-name { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
    .user-role { font-size: 0.75rem; color: #94a3b8; }
    
    .logout-btn { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8; transition: 0.2s; text-decoration: none; }
    .logout-btn:hover { background: #fef2f2; color: #ef4444; transform: scale(1.05); }

    @media (max-width: 768px) {
        .modern-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(290px, calc(100vw - 36px));
            min-width: 0;
            max-width: calc(100vw - 36px);
            height: 100dvh;
            border-radius: 0 24px 24px 0;
            z-index: 1050;
            transform: translateX(-105%);
            transition: transform .2s ease;
            box-shadow: 16px 0 42px rgba(15, 23, 42, .22);
        }

        .modern-sidebar.show {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 1.25rem 1.25rem 1rem;
        }

        .sidebar-menu {
            padding: 0 .85rem;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
        }
    }
</style>

<nav class="mobile-bottom-nav d-md-none" aria-label="Navegação rápida">
    <?php foreach ($mobileShortcuts as $shortcut): ?>
        <a href="<?php echo htmlspecialchars($shortcut['href'], ENT_QUOTES, 'UTF-8'); ?>"
           class="mobile-bottom-link <?php echo !empty($shortcut['active']) ? 'active' : ''; ?> <?php echo !empty($shortcut['featured']) ? 'featured' : ''; ?>">
            <span class="mobile-bottom-icon"><?php echo $shortcut['icon']; ?></span>
            <span class="mobile-bottom-label"><?php echo htmlspecialchars($shortcut['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>

    <button type="button" class="mobile-bottom-link mobile-bottom-menu" onclick="toggleMenu()" aria-label="Abrir menu">
        <span class="mobile-bottom-icon"><?php echo renderSvgIcon('layout'); ?></span>
        <span class="mobile-bottom-label">Menu</span>
    </button>
</nav>

<!-- ============================
     Modal: Sem Permissão
============================= -->
<div class="modal fade" id="modalSemPermissao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?php echo renderSvgIcon('shield', 'me-2'); ?>Acesso negado</h5>
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
                items.forEach(li => {
                    li.style.display = '';
                });
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

                const inst = bootstrap.Collapse.getOrCreateInstance(col, {
                    toggle: false
                });
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
