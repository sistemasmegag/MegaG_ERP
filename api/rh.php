<?php
// api/rh.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/functions.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$usuario = $_SESSION['usuario'];

// MOCK DATA PARA SOLICITACOES DE RH (Férias, Atestados, Holerites)
$solicitacoes = [
    ['ID' => 1, 'TIPO' => 'FERIAS', 'DESCRICAO' => 'Solicitação de 15 dias de férias a partir de 10/03/2026', 'STATUS' => 'APROVADO', 'SOLICITANTE' => 'Felipe', 'DATA_CRIACAO' => '2026-02-15'],
    ['ID' => 2, 'TIPO' => 'ATESTADO', 'DESCRICAO' => 'Atestado médico de 2 dias por gripe.', 'STATUS' => 'PENDENTE', 'SOLICITANTE' => 'Ana', 'DATA_CRIACAO' => '2026-02-24'],
    ['ID' => 3, 'TIPO' => 'HOLERITE', 'DESCRICAO' => 'Solicitação de 2ª via do holerite de Janeiro/2026', 'STATUS' => 'CONCLUIDO', 'SOLICITANTE' => 'Felipe', 'DATA_CRIACAO' => '2026-02-25'],
    ['ID' => 4, 'TIPO' => 'OUTROS', 'DESCRICAO' => 'Dúvida sobre desconto do plano de saúde', 'STATUS' => 'PENDENTE', 'SOLICITANTE' => 'Carlos', 'DATA_CRIACAO' => '2026-02-26']
];

// MOCK DATA PARA MURAL DE AVISOS DO RH
$avisos = [
    ['ID' => 101, 'TITULO' => 'Feriado de Carnaval', 'MENSAGEM' => 'Informamos que entraremos em recesso no dia 15/02 e retornaremos no dia 19/02.', 'DATA' => '2026-02-10', 'IMPORTANTE' => true],
    ['ID' => 102, 'TITULO' => 'Nova Parceria de Convênio', 'MENSAGEM' => 'Fechamos parceria com a academia SmartFit. Solicite seu voucher no RH.', 'DATA' => '2026-02-20', 'IMPORTANTE' => false],
];

try {
    if ($metodo === 'GET') {
        $acao = $_GET['acao'] ?? 'MIMHA_SOLICITACOES'; // Default view is my requests

        if ($acao === 'AVISOS') {
            echo json_encode(['sucesso' => true, 'dados' => $avisos]);
            exit;
        }

        if ($acao === 'MINHAS_SOLICITACOES') {
            // Se for Gestor/RH veria tudo, se não veria só as dele. Vamos fingir que vê tudo agora.
            echo json_encode(['sucesso' => true, 'dados' => $solicitacoes]);
            exit;
        }
    }

    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $tipo = $input['tipo'] ?? 'OUTROS';
        $desc = $input['descricao'] ?? '';

        if (empty($desc)) {
            echo json_encode(['sucesso' => false, 'msg' => 'Descrição é obrigatória']);
            exit;
        }

        // SALVA NO BANCO (Simulado)
        $novoId = rand(100, 900);

        echo json_encode([
            'sucesso' => true,
            'msg' => 'Solicitação enviada ao RH com sucesso!',
            'dado' => [
                'ID' => $novoId,
                'TIPO' => $tipo,
                'DESCRICAO' => $desc,
                'STATUS' => 'PENDENTE',
                'SOLICITANTE' => $usuario,
                'DATA_CRIACAO' => date('Y-m-d')
            ]
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => 'Erro interno na API RH: ' . $e->getMessage()]);
}
