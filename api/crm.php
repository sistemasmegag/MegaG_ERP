<?php
// api/crm.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/functions.php';

// Ajuste aqui com o seu helper de banco de dados
// require_once __DIR__ . '/../helpers/db.php'; // exemplo

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

// Para fins práticos de demonstração ou se não houver a tabela TBL_CRM_LEADS
// O ideal é você criar a tabela no seu banco Oracle/MySQL com:
// ID, NOME, EMPRESA, VALOR, STATUS, PRIORIDADE, RESPONSAVEL, CRIADO_EM
// Aqui usaremos dados em memória caso o PDO não esteja configurado, 
// senão adaptaremos para o PDO real.

try {
    // $pdo = getConexaoPDO(); // Descomente e use sua conexão real

    if ($metodo === 'GET') {
        // Exemplo SQL:
        // $stmt = $pdo->query("SELECT * FROM TBL_CRM_LEADS ORDER BY ID DESC");
        // $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // MOCK DATA PARA DEMONSTRAÇÃO IMEDIATA (Substitua por SQL assim que a tabela existir)
        $dados = [
            ['ID' => 1, 'NOME' => 'João Silva', 'EMPRESA' => 'Tech Corp', 'VALOR' => '15000.00', 'STATUS' => 'LEAD', 'PRIORIDADE' => 'ALTA', 'RESPONSAVEL' => 'Felipe', 'CRIADO_EM' => '2026-02-26'],
            ['ID' => 2, 'NOME' => 'Maria Oliveira', 'EMPRESA' => 'Mega Logística', 'VALOR' => '8500.00', 'STATUS' => 'CONTATO', 'PRIORIDADE' => 'MEDIA', 'RESPONSAVEL' => 'Felipe', 'CRIADO_EM' => '2026-02-25'],
            ['ID' => 3, 'NOME' => 'Carlos Santos', 'EMPRESA' => 'Varejo SA', 'VALOR' => '32000.00', 'STATUS' => 'PROPOSTA', 'PRIORIDADE' => 'URGENTE', 'RESPONSAVEL' => 'Ana', 'CRIADO_EM' => '2026-02-24'],
            ['ID' => 4, 'NOME' => 'Lucia Fernandes', 'EMPRESA' => 'Consultoria LF', 'VALOR' => '4500.00', 'STATUS' => 'GANHO', 'PRIORIDADE' => 'BAIXA', 'RESPONSAVEL' => 'Felipe', 'CRIADO_EM' => '2026-02-20']
        ];

        echo json_encode(['sucesso' => true, 'dados' => $dados]);
        exit;
    }

    if ($metodo === 'POST' || $metodo === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $acao = $input['acao'] ?? '';

        if ($acao === 'STATUS') {
            // Atualizar status no Kanban (arrastar)
            $id = $input['id_lead'];
            $novoStatus = $input['status'];
            // SQL: "UPDATE TBL_CRM_LEADS SET STATUS = :status WHERE ID = :id"
            echo json_encode(['sucesso' => true, 'msg' => 'Status atualizado com sucesso.']);
            exit;
        }

        // Criar ou Editar Lead
        // SQL: INSERT INTO TBL_CRM_LEADS ...
        echo json_encode(['sucesso' => true, 'msg' => 'Lead salvo com sucesso.', 'id' => rand(10, 999)]);
        exit;
    }

    if ($metodo === 'DELETE') {
        $id = $_GET['id_lead'] ?? 0;
        // SQL: DELETE FROM TBL_CRM_LEADS WHERE ID = :id
        echo json_encode(['sucesso' => true, 'msg' => 'Lead removido com sucesso.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => 'Erro interno na API CRM: ' . $e->getMessage()]);
}
