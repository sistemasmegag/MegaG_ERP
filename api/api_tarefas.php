<?php
// ARQUIVO: api_tarefas.php (NA RAIZ)
session_start();
header('Content-Type: application/json');

// Inclui conexão (está na mesma pasta)
require 'db.php';

// Inclui funções de ajuda (para checar permissões)
// Certifique-se de que o arquivo 'helpers/functions.php' existe conforme criado no passo anterior
require 'helpers/functions.php';

// Verifica login
if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Login necessário']);
    exit;
}

$pdo = getConexaoPDO();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // --- LISTAR TAREFAS (GET) ---
    if ($metodo === 'GET') {
        
        // Verifica Permissão de LEITURA
        if (!temPermissao('APP_TAREFAS', 'LER')) {
            echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado: Sem permissão de leitura.']);
            exit;
        }

        $sql = "SELECT ID, TITULO, DESCRICAO, STATUS, RESPONSAVEL, 
                TO_CHAR(DATA_ENTREGA, 'YYYY-MM-DD') as DATA_ENTREGA 
                FROM TBL_TAREFAS ORDER BY DATA_ENTREGA ASC";
        $stmt = $pdo->query($sql);
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
    }

    // --- CRIAR OU ATUALIZAR (POST) ---
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $pdo->beginTransaction();

        // CENÁRIO A: ATUALIZAÇÃO DE STATUS (Drag & Drop)
        if (isset($input['id']) && isset($input['novoStatus'])) {
            
            // Verifica Permissão de EDIÇÃO
            if (!temPermissao('APP_TAREFAS', 'EDITAR')) {
                echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado: Sem permissão para editar.']);
                exit;
            }

            $sql = "UPDATE TBL_TAREFAS SET STATUS = :p_status WHERE ID = :p_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':p_status', $input['novoStatus']);
            $stmt->bindValue(':p_id', $input['id']);
            $stmt->execute();
        } 
        // CENÁRIO B: CRIAR NOVA TAREFA
        else {
            
            // Verifica Permissão de CRIAÇÃO
            if (!temPermissao('APP_TAREFAS', 'CRIAR')) {
                echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado: Sem permissão para criar tarefas.']);
                exit;
            }

            $responsavel = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'ADMIN';

            // Correção ORA-01745: Nomes das variáveis seguros (:p_descricao, etc)
            $sql = "INSERT INTO TBL_TAREFAS (TITULO, DESCRICAO, DATA_ENTREGA, RESPONSAVEL, STATUS) 
                    VALUES (:p_titulo, :p_descricao, TO_DATE(:p_data, 'YYYY-MM-DD'), :p_resp, 'TODO')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':p_titulo',    $input['titulo']);
            $stmt->bindValue(':p_descricao', $input['descricao']); 
            $stmt->bindValue(':p_data',      $input['data']);
            $stmt->bindValue(':p_resp',      $responsavel); 
            $stmt->execute();
        }

        $pdo->commit(); 
        echo json_encode(['sucesso' => true]);
    }

    // --- DELETAR TAREFA (DELETE) ---
    if ($metodo === 'DELETE') {
        $id = $_GET['id'];

        // Verifica Permissão de EXCLUSÃO
        if (!temPermissao('APP_TAREFAS', 'EXCLUIR')) {
            echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado: Sem permissão para excluir.']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // Correção ORA-01745: Nome da variável seguro (:p_id)
        $stmt = $pdo->prepare("DELETE FROM TBL_TAREFAS WHERE ID = :p_id");
        $stmt->bindValue(':p_id', $id);
        $stmt->execute();
        
        $pdo->commit(); 
        
        echo json_encode(['sucesso' => true]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Loga o erro exato para debug
    echo json_encode(['sucesso' => false, 'erro' => 'Erro DB: ' . $e->getMessage()]);
}
?>