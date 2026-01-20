<?php
// api_usuarios.php - Gestão via Tabelas MEGAG_VW
session_start();
header('Content-Type: application/json');

// 1. Proteção ADMIN
if (!isset($_SESSION['logado']) || $_SESSION['nivel'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
    exit;
}

// 2. Conexão
try {
    $pathConexao = __DIR__ . '/db_config/db_connect.php';
    if (!file_exists($pathConexao)) $pathConexao = dirname(__DIR__) . '/db_config/db_connect.php';
    if (!file_exists($pathConexao)) throw new Exception("Config db_connect.php não encontrada.");
    
    require_once($pathConexao);
    if (!isset($conn) || !$conn) throw new Exception("Falha na conexão.");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => "Banco: " . $e->getMessage()]);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$owner  = "CONSINCO";

// NOMES DOS GRUPOS QUE VAMOS USAR (Certifique-se que eles podem ser inseridos)
$grupoAdmin = 'WEB_ADMIN';
$grupoUser  = 'WEB_USER';

try {
    // ==================================================================
    // GET: LISTAR (Apenas quem tem acesso WEB)
    // ==================================================================
    if ($metodo === 'GET') {
        // Traz apenas usuários que estão nos grupos WEB_ADMIN ou WEB_USER
        // Faz JOIN com CADUSUARIO para pegar o NOME real
        $sql = "SELECT 
                    gu.CODUSUARIO AS USUARIO,
                    u.NOME,
                    CASE WHEN gu.CODGRUPO = :ga THEN 'ADMIN' ELSE 'USER' END AS NIVEL,
                    ' - ' AS CRIADO_EM
                FROM {$owner}.MEGAG_VW_CADGRUPOUSUARIO gu
                INNER JOIN {$owner}.MEGAG_VW_CADUSUARIO u ON gu.CODUSUARIO = u.CODUSUARIO
                WHERE gu.CODGRUPO IN (:ga, :gu)
                ORDER BY u.NOME ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':ga', $grupoAdmin);
        $stmt->bindValue(':gu', $grupoUser);
        $stmt->execute();
        
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ==================================================================
    // POST: ADICIONAR / EDITAR PERMISSÃO
    // ==================================================================
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $user  = strtoupper(trim($input['usuario']));
        $nivel = $input['nivel']; // 'ADMIN' ou 'USER'
        
        if(empty($user)) throw new Exception("Usuário inválido");

        // 1. Verifica se o usuário EXISTE no cadastro geral (MEGAG_VW_CADUSUARIO)
        $check = $conn->prepare("SELECT NOME FROM {$owner}.MEGAG_VW_CADUSUARIO WHERE CODUSUARIO = :u");
        $check->bindValue(':u', $user);
        $check->execute();
        if (!$check->fetch()) {
            throw new Exception("Usuário '$user' não encontrado no cadastro do ERP.");
        }

        // 2. Define qual grupo será vinculado
        $codGrupoDestino = ($nivel === 'ADMIN') ? $grupoAdmin : $grupoUser;

        // 3. Remove permissões antigas WEB desse usuário (limpeza)
        $del = $conn->prepare("DELETE FROM {$owner}.MEGAG_VW_CADGRUPOUSUARIO 
                               WHERE CODUSUARIO = :u AND CODGRUPO IN (:ga, :gu)");
        $del->bindValue(':u', $user);
        $del->bindValue(':ga', $grupoAdmin);
        $del->bindValue(':gu', $grupoUser);
        $del->execute();

        // 4. Insere o novo vínculo
        $ins = $conn->prepare("INSERT INTO {$owner}.MEGAG_VW_CADGRUPOUSUARIO (CODGRUPO, CODUSUARIO) VALUES (:g, :u)");
        $ins->bindValue(':g', $codGrupoDestino);
        $ins->bindValue(':u', $user);
        $ins->execute();

        echo json_encode(['sucesso' => true]);
    }

    // ==================================================================
    // DELETE: REMOVER ACESSO WEB
    // ==================================================================
    if ($metodo === 'DELETE') {
        $user = $_GET['user'] ?? null;
        if (!$user) throw new Exception("Usuário não informado.");

        // Remove dos grupos WEB
        $stmt = $conn->prepare("DELETE FROM {$owner}.MEGAG_VW_CADGRUPOUSUARIO 
                                WHERE CODUSUARIO = :u AND CODGRUPO IN (:ga, :gu)");
        $stmt->bindValue(':u', $user);
        $stmt->bindValue(':ga', $grupoAdmin);
        $stmt->bindValue(':gu', $grupoUser);
        $stmt->execute();

        echo json_encode(['sucesso' => true]);
    }

} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => "Erro Oracle: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>