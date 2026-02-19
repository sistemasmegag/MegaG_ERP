<?php
function temPermissao(string $codigoApp): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (!empty($_SESSION['nivel']) && $_SESSION['nivel'] === 'ADMIN') {
        return true;
    }

    if (empty($_SESSION['permissoes']) || !is_array($_SESSION['permissoes'])) {
        return false;
    }

    return isset($_SESSION['permissoes'][$codigoApp]);
}

/**
 * Bloqueia acesso se não tiver permissão.
 * $modo: 'html' (página) ou 'json' (API/upload) ou 'sse' (EventSource)
 */
function exigirPermissao(string $codigoApp, string $modo = 'html'): void
{
    if (!temPermissao($codigoApp)) {
        if ($modo === 'json') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($modo === 'sse') {
            http_response_code(403);
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            echo "data: " . json_encode(['msg' => 'Sem permissão', 'tipo' => 'erro'], JSON_UNESCAPED_UNICODE) . "\n\n";
            echo "event: close\n";
            echo "data: {}\n\n";
            @ob_flush(); @flush();
            exit;
        }

        // html
        http_response_code(403);
        echo 'Acesso negado';
        exit;
    }
}

if (!function_exists('ends_with_ci')) {
    function ends_with_ci($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle   = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        if ($len > strlen($haystack)) return false;
        return strtolower(substr($haystack, -$len)) === strtolower($needle);
    }
}

if (!function_exists('normalizeLinkMenu')) {
    function normalizeLinkMenu($linkMenu) {
        $linkMenu = trim((string)$linkMenu);

        // remove extensão .php se vier
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

/**
 * Equivalente ao fnValidarPermAplicacao($page, $_SESSION['...']['menu'])
 * Só que usando $_SESSION['menu_apps'] (sua view do login).
 *
 * Retorna true se:
 * - page for "home" (liberada)
 * - OU existir em menu_apps (comparando com LINKMENU normalizado)
 */
if (!function_exists('fnValidarPermAplicacao')) {
    function fnValidarPermAplicacao(string $page, array $menuApps): bool
    {
        $page = trim($page);

        // páginas liberadas (ajuste se quiser)
        if ($page === '' || $page === 'home') {
            return true;
        }

        // se menu vazio, não libera nada além das liberadas
        if (empty($menuApps)) {
            return false;
        }

        // compara com LINKMENU normalizado (mesmo critério do sidebar)
        foreach ($menuApps as $app) {
            $linkRaw = (string)($app['LINKMENU'] ?? '');
            $link    = normalizeLinkMenu($linkRaw);

            if ($link !== '' && $link === $page) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Descobre o "módulo" da aplicação a partir do menu_apps.
 */
if (!function_exists('fnVerificaModPorAplicacao')) {
    function fnVerificaModPorAplicacao(string $page, array $menuApps): string
    {
        $page = trim($page);
        if ($page === '' || $page === 'home') return 'PRINCIPAL';

        foreach ($menuApps as $app) {
            $linkRaw = (string)($app['LINKMENU'] ?? '');
            $link    = normalizeLinkMenu($linkRaw);

            if ($link !== '' && $link === $page) {
                return (string)($app['CODMODULO'] ?? 'OUTROS');
            }
        }

        return 'OUTROS';
    }
}