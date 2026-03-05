<?php
require_once __DIR__ . '/../routes/check_session.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config/db_connect.php';

function jexit($ok, $data = [], $erro = null)
{
    echo json_encode([
        'sucesso' => (bool) $ok,
        'dados' => $ok ? ($data['dados'] ?? null) : null,
        'erro' => $ok ? null : ($erro ?? 'Erro desconhecido'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function body_json()
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

try {
    if (empty($_SESSION['logado']) || empty($_SESSION['usuario'])) {
        jexit(false, [], 'Sessão expirada. Faça login novamente.');
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user = $_SESSION['usuario'];

    // Obter ID numérico do usuário logado e nome (MOCK/ajuste conforme banco real)
    $userIdInt = is_numeric($user) ? (int) $user : 1;
    $userName = isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'SISTEMA';

    $req = body_json();
    $action = $req['action'] ?? '';

    // ============================================
    // CATEGORIAS (MEGAG_DESP_TIPO)
    // ============================================
    if ($action === 'list_tipos') {
        // PRC_LIST_MEGAG_DESP_TIPO pede código e desc, e devolve cursor
        $sql = "BEGIN PKG_MEGAG_DESP_CADASTRO.PRC_LIST_MEGAG_DESP_TIPO(NULL, NULL, :RESULT); END;";
        $st = $conn->prepare($sql);
        // Em um cenário real com Oracle PDO, executar e dar fetch no Cursor é complexo se driver for básico. 
        // Usamos SELECT direto como Fallback se a proc der problema com Cursor no PHP
        $fallback = "SELECT CODTIPODESPESA, DESCRICAO FROM MEGAG_DESP_TIPO ORDER BY CODTIPODESPESA ASC";
        $stFB = $conn->prepare($fallback);
        $stFB->execute();
        $res = $stFB->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => $res]);
    }

    if ($action === 'add_tipo') {
        $desc = trim($req['descricao'] ?? '');
        if ($desc === '')
            jexit(false, [], 'Informe a descrição da categoria.');

        $sql = "BEGIN PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_TIPO(p_DESCRICAO => :DESC); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':DESC', $desc);
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Categoria cadastrada com sucesso!']]);
    }

    if ($action === 'del_tipo') {
        $id = (int) ($req['id'] ?? 0);
        if ($id <= 0)
            jexit(false, [], 'ID Inválido.');

        $sql = "BEGIN PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_TIPO(p_CODTIPODESPESA => :ID); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':ID', $id);
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Categoria excluída com sucesso!']]);
    }


    // ============================================
    // DADOS PARA SELECTS (API CARREGAMENTO)
    // ============================================
    if ($action === 'get_doms_aprovador') {
        // Centros de custo via PRC do banco
        // Fallback p/ consulta direta simulando a package
        $ccSql = "SELECT CENTRORESULTADO, SEQCENTRORESULTADO, DESCRICAO FROM ABA_CENTRORESULTADO ORDER BY DESCRICAO";
        $stCC = $conn->prepare($ccSql);
        $stCC->execute();
        $ccs = $stCC->fetchAll(PDO::FETCH_ASSOC);

        // Usuários p/ Gestor
        $usuSql = "SELECT SEQUSUARIO, NOME FROM GE_USUARIO WHERE ATIVO = 'S' ORDER BY NOME";
        $stU = $conn->prepare($usuSql);
        $stU->execute();
        $usus = $stU->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => ['ccs' => $ccs, 'usuarios' => $usus]]);
    }


    // ============================================
    // APROVADORES X C.C. (MEGAG_DESP_APROVADORES)
    // ============================================
    if ($action === 'list_aprovadores') {
        // Exibição da tabela principal
        $sql = "SELECT A.SEQUSUARIO, A.CENTROCUSTO, A.SEQCENTRORESULTADO, A.NOME AS GESTOR,
                       TO_CHAR(A.DTAINCLUSAO, 'DD/MM/YYYY') AS DATA_VINCULO
                  FROM MEGAG_DESP_APROVADORES A
                 ORDER BY A.NOME";
        $st = $conn->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => $res]);
    }

    if ($action === 'add_aprovador') {
        $cc_str = trim($req['centro_custo'] ?? '');
        $gestor_str = trim($req['gestor'] ?? '');

        if ($cc_str === '' || $gestor_str === '')
            jexit(false, [], 'Preencha Centro de Custo e Gestor.');

        $cc_parts = explode('|', $cc_str);
        $centro_custo = (int) ($cc_parts[0] ?? 0);
        $seq_cc = (int) ($cc_parts[1] ?? 0);

        $gestor_parts = explode('|', $gestor_str);
        $seq_usuario = (int) ($gestor_parts[0] ?? 0);
        $nome = trim($gestor_parts[1] ?? '');

        // Usando a PROC conforme SPEC
        $sql = "BEGIN 
                  PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_APROVADORES(
                      p_sequsuario         => :SEQ_USU,
                      p_centrocusto        => :CC,
                      p_seqcentroresultado => :SEQ_CC,
                      p_nome               => :NOME,
                      p_sequusuarioalt     => :USU_ALT,
                      p_dtaalteracao       => NULL
                  ); 
                END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':SEQ_USU', $seq_usuario);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':SEQ_CC', $seq_cc);
        $st->bindValue(':NOME', $nome);
        $st->bindValue(':USU_ALT', $userIdInt);

        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Aprovador vinculado com sucesso.']]);
    }

    if ($action === 'del_aprovador') {
        $nome = trim($req['nome'] ?? '');
        if ($nome === '')
            jexit(false, [], 'Nome inválido para exclusão.');

        $sql = "BEGIN PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_APROVADORES(p_nome => :NOME); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':NOME', $nome);
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Vinculação removida com sucesso.']]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
