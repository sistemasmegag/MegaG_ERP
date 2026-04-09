<?php
session_start();

require_once __DIR__ . '/mg_api_bootstrap.php';
require_once __DIR__ . '/../helpers/firebase.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) && !isset($_SESSION['loginid']) && !isset($_SESSION['user'])) {
    mg_json_error('Usuario nao autenticado.');
}

$conn = getConexaoPDO();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string)($_GET['action'] ?? 'config'));
$currentUser = trim((string)($_SESSION['loginid'] ?? $_SESSION['usuario'] ?? $_SESSION['user'] ?? ''));

function firebase_body(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

try {
    if ($method === 'GET' && $action === 'config') {
        mg_json_success([
            'config' => mg_firebase_public_config(),
            'user' => $currentUser,
        ]);
    }

    if ($method === 'POST' && $action === 'register_token') {
        $body = firebase_body();
        $result = mg_firebase_save_token($conn, $currentUser, (string)($body['token'] ?? ''), [
            'platform' => $body['platform'] ?? 'web',
            'user_agent' => $body['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'endpoint' => $body['endpoint'] ?? (mg_firebase_base_url() . ($_SERVER['REQUEST_URI'] ?? '')),
        ]);

        if (empty($result['success'])) {
            mg_json_error($result['error'] ?? 'Falha ao registrar o token do dispositivo.');
        }

        mg_json_success(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'unregister_token') {
        $body = firebase_body();
        $result = mg_firebase_unregister_token($conn, $currentUser, (string)($body['token'] ?? ''));

        if (empty($result['success'])) {
            mg_json_error($result['error'] ?? 'Falha ao remover o token do dispositivo.');
        }

        mg_json_success(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'test') {
        if ($currentUser === '') {
            mg_json_error('Usuario atual nao identificado.');
        }

        $body = firebase_body();
        $title = trim((string)($body['title'] ?? 'Teste de notificacao push'));
        $message = trim((string)($body['message'] ?? 'Firebase configurado com sucesso no ERP da MEGA G.'));

        $result = mg_firebase_notify_user($conn, $currentUser, $title, $message, [
            'url' => 'index.php',
            'data' => [
                'source' => 'erp-megag',
                'kind' => 'test',
            ],
        ]);

        if (empty($result['success'])) {
            mg_json_error(($result['errors'][0] ?? null) ?: ($result['error'] ?? 'Falha ao enviar push de teste.'));
        }

        mg_json_success($result);
    }

    mg_json_error('Metodo ou acao nao permitidos.');
} catch (Throwable $e) {
    mg_json_error($e->getMessage());
}
