<?php
// api/notif.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$usuario = $_SESSION['usuario'];
$action = $_GET['action'] ?? 'list';

// MOCK DATA: Notificações Avançadas
$mockNotifs = [
    ['ID' => 101, 'TITULO' => 'Bem-vindo ao MegaG', 'MENSAGEM' => 'Seu novo módulo de CRM, RH e Wiki estão ativados e prontos para uso.', 'TIPO' => 'SISTEMA', 'LIDA' => 'N', 'CRIADO_EM' => date('Y-m-d H:i', strtotime('-5 min')), 'SENDER' => 'Sistema', 'LINK' => ''],
    ['ID' => 102, 'TITULO' => 'Felipe comentou no seu chamado', 'MENSAGEM' => '"Cara, verifiquei o log da importação e parece que o código de barras veio corrompido."', 'TIPO' => 'CHAMADO', 'LIDA' => 'N', 'CRIADO_EM' => date('Y-m-d H:i', strtotime('-1 hour')), 'SENDER' => 'Felipe', 'LINK' => 'index.php?page=chamados'],
    ['ID' => 103, 'TITULO' => 'Férias Aprovadas!', 'MENSAGEM' => 'Suas férias foram aprovadas pelo RH. Aproveite o descanso.', 'TIPO' => 'RH', 'LIDA' => 'N', 'CRIADO_EM' => date('Y-m-d H:i', strtotime('-1 day')), 'SENDER' => 'RH', 'LINK' => 'index.php?page=rh'],
    ['ID' => 104, 'TITULO' => 'Novo Lead Recebido', 'MENSAGEM' => 'Cliente MegaCorp acabou de entrar em contato e foi atribuído a você.', 'TIPO' => 'CRM', 'LIDA' => 'S', 'CRIADO_EM' => date('Y-m-d H:i', strtotime('-2 days')), 'SENDER' => 'Comercial', 'LINK' => 'index.php?page=crm']
];

try {
    if ($metodo === 'GET') {
        echo json_encode(['success' => true, 'data' => $mockNotifs]);
        exit;
    }

    if ($metodo === 'PATCH') {
        if ($action === 'read_all') {
            echo json_encode(['success' => true]);
            exit;
        }
        if ($action === 'read') {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($metodo === 'POST') {
        // Envio de nova notificação (ex: mentions)
        echo json_encode(['success' => true, 'data' => 'Notificação enviada.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
