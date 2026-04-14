<?php
session_start();

require_once __DIR__ . '/mg_api_bootstrap.php';
require_once __DIR__ . '/../helpers/firebase.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    mg_json_error('Usuário não autenticado.');
}

$conn = getConexaoPDO();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$sessionUser = trim((string)$_SESSION['usuario']);
$usuario = trim((string)($_GET['usuario'] ?? $sessionUser));
$action = trim((string)($_GET['action'] ?? 'list'));

function notif_pick_link(array $row): string
{
    $tipo = strtoupper(trim((string)($row['TIPO'] ?? '')));

    if ($tipo === 'CHAMADO') {
        return 'index.php?page=chamados';
    }

    if ($tipo === 'RH') {
        return 'index.php?page=rh';
    }

    if ($tipo === 'CRM') {
        return 'index.php?page=crm';
    }

    if ($tipo === 'DESPESA' || $tipo === 'APROVACAO') {
        return 'index.php?page=despesas_aprovacao';
    }

    return '';
}

function notif_pick_sender(array $row): string
{
    $tipo = strtoupper(trim((string)($row['TIPO'] ?? '')));

    if ($tipo === 'RH') {
        return 'RH';
    }

    if ($tipo === 'CRM') {
        return 'CRM';
    }

    if ($tipo === 'CHAMADO') {
        return 'Sistema';
    }

    if ($tipo === 'DESPESA' || $tipo === 'APROVACAO') {
        return 'Despesas';
    }

    return 'Sistema';
}

function notif_list(PDO $conn, string $usuario): void
{
    if ($usuario === '') {
        mg_json_error('Parâmetro "usuario" obrigatório.');
    }

    $sql = "SELECT
                ID,
                USUARIO,
                TIPO,
                TITULO,
                MENSAGEM,
                TASK_ID,
                LIDA,
                TO_CHAR(CRIADO_EM, 'YYYY-MM-DD HH24:MI') AS CRIADO_EM,
                TO_CHAR(LIDA_EM, 'YYYY-MM-DD HH24:MI') AS LIDA_EM
            FROM MEGAG_TASK_NOTIFICACOES
            WHERE UPPER(USUARIO) = UPPER(:USUARIO)
            ORDER BY CASE WHEN NVL(LIDA, 'N') = 'N' THEN 0 ELSE 1 END,
                     CRIADO_EM DESC,
                     ID DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $data = array_map(static function (array $row) {
        $row['SENDER'] = notif_pick_sender($row);
        $row['LINK'] = notif_pick_link($row);
        return $row;
    }, $rows);

    mg_json_success($data);
}

function notif_mark_one(PDO $conn, int $id): void
{
    if ($id <= 0) {
        mg_json_error('Parâmetro "id" obrigatório.');
    }

    $sql = "UPDATE MEGAG_TASK_NOTIFICACOES
               SET LIDA = 'S',
                   LIDA_EM = SYSDATE
             WHERE ID = :ID";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':ID', $id, PDO::PARAM_INT);
    $stmt->execute();

    mg_json_success(['ok' => true]);
}

function notif_mark_all(PDO $conn, string $usuario): void
{
    if ($usuario === '') {
        mg_json_error('Parâmetro "usuario" obrigatório.');
    }

    $sql = "UPDATE MEGAG_TASK_NOTIFICACOES
               SET LIDA = 'S',
                   LIDA_EM = SYSDATE
             WHERE UPPER(USUARIO) = UPPER(:USUARIO)
               AND NVL(LIDA, 'N') <> 'S'";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $stmt->execute();

    mg_json_success(['ok' => true]);
}

function notif_create(PDO $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    $usuario = trim((string)($body['usuario'] ?? ''));
    $tipo = trim((string)($body['tipo'] ?? 'SISTEMA'));
    $titulo = trim((string)($body['titulo'] ?? 'Notificação'));
    $mensagem = trim((string)($body['mensagem'] ?? ''));
    $taskId = isset($body['task_id']) && $body['task_id'] !== '' ? (int)$body['task_id'] : null;

    if ($usuario === '' || $mensagem === '') {
        mg_json_error('Campos "usuario" e "mensagem" são obrigatórios.');
    }

    $sql = "INSERT INTO MEGAG_TASK_NOTIFICACOES
                (ID, USUARIO, TIPO, TITULO, MENSAGEM, TASK_ID, LIDA, CRIADO_EM)
            VALUES
                (SEQ_MEGAG_TASK_NOTIFICACOES.NEXTVAL, :USUARIO, :TIPO, :TITULO, :MENSAGEM, :TASK_ID, 'N', SYSDATE)";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':TIPO', $tipo, PDO::PARAM_STR);
    $stmt->bindValue(':TITULO', $titulo, PDO::PARAM_STR);
    $stmt->bindValue(':MENSAGEM', $mensagem, PDO::PARAM_STR);
    $stmt->bindValue(':TASK_ID', $taskId, $taskId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();

    mg_firebase_notify_user($conn, $usuario, $titulo, $mensagem, [
        'url' => notif_pick_link(['TIPO' => $tipo]),
        'data' => [
            'tipo' => $tipo,
            'task_id' => $taskId,
        ],
    ]);

    mg_json_success(['ok' => true]);
}

try {
    if ($method === 'GET') {
        notif_list($conn, $usuario);
    }

    if ($method === 'PATCH' && $action === 'read') {
        notif_mark_one($conn, (int)($_GET['id'] ?? 0));
    }

    if ($method === 'PATCH' && $action === 'read_all') {
        notif_mark_all($conn, $usuario);
    }

    if ($method === 'POST') {
        notif_create($conn);
    }

    mg_json_error('Método não permitido.');
} catch (Throwable $e) {
    mg_json_error($e->getMessage());
}
