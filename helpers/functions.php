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

    // Se existe na lista da view -> tem acesso
    return isset($_SESSION['permissoes'][$codigoApp]);
}