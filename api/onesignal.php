<?php
session_start();

require_once __DIR__ . '/mg_api_bootstrap.php';
require_once __DIR__ . '/../helpers/onesignal.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) && !isset($_SESSION['loginid']) && !isset($_SESSION['user'])) {
    mg_json_error('Usuario nao autenticado.');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string)($_GET['action'] ?? 'config'));
$currentUser = trim((string)($_SESSION['loginid'] ?? $_SESSION['usuario'] ?? $_SESSION['user'] ?? ''));

function onesignal_body(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

try {
    if ($method === 'GET' && $action === 'config') {
        mg_json_success([
            'config' => mg_onesignal_public_config(),
            'user' => $currentUser,
        ]);
    }

    if ($method === 'POST' && $action === 'test') {
        if ($currentUser === '') {
            mg_json_error('Usuario atual nao identificado.');
        }

        $body = onesignal_body();
        $title = trim((string)($body['title'] ?? 'Teste de notificacao push'));
        $message = trim((string)($body['message'] ?? 'OneSignal integrado com sucesso ao ERP da MEGA G.'));

        $result = mg_onesignal_notify_user($currentUser, $title, $message, [
            'url' => 'index.php',
            'data' => [
                'source' => 'erp-megag',
                'kind' => 'test',
            ],
        ]);

        if (empty($result['success'])) {
            mg_json_error($result['error'] ?? 'Falha ao enviar push de teste.');
        }

        mg_json_success($result['data'] ?? ['ok' => true]);
    }

    mg_json_error('Metodo ou acao nao permitidos.');
} catch (Throwable $e) {
    mg_json_error($e->getMessage());
}
