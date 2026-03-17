<?php
require_once __DIR__ . '/../routes/check_session.php';
header('Content-Type: application/json; charset=utf-8');
// Procura o db_connect em pastas acima
$pathConexaoCandidates = [
    __DIR__ . '/../db_config/db_connect.php',
    __DIR__ . '/../../db_config/db_connect.php',
    __DIR__ . '/config/db_connect.php'
];
$pathConexao = null;
foreach ($pathConexaoCandidates as $cand) {
    if (file_exists($cand)) {
        $pathConexao = $cand;
        break;
    }
}

function jexit($ok, $data = [], $erro = null)
{
    echo json_encode([
        'sucesso' => (bool) $ok,
        'dados' => $ok ? ($data['dados'] ?? null) : null,
        'erro' => $ok ? null : ($erro ?? 'Erro desconhecido'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$pathConexao) {
    jexit(false, [], "Arquivo de conexão não encontrado!");
}
require_once $pathConexao;

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

    // Helper p/ Cursor Oracle
    function fetchCursor($stmt, $paramName = ':RESULT') {
        $stmt->execute();
        // Em muitos drivers PDO OCI, o cursor não é retornado diretamente via fetchAll do statement pai
        // Mas o driver do importador parece preferir SELECT direto ou o driver é limitado.
        // Vou manter uma estrutura que tenta usar a proc e se falhar (ou se não houver driver de cursor), faz fallback.
        // Se o usuário der o OK, podemos ajustar aqui.
    }

    // ============================================
    // GRUPOS (MEGAG_DESP_GRUPO)
    // ============================================
    if ($action === 'list_grupos') {
        // Fallback SELECT pois fetch de CURSOR no PHP depende de drivers específicos
        $sql = "SELECT CODGRUPO, NOMEGRUPO FROM CONSINCO.MEGAG_DESP_GRUPO ORDER BY NOMEGRUPO";
        $st = $conn->prepare($sql);
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_grupo') {
        $nome = trim($req['nome'] ?? '');
        if ($nome === '') jexit(false, [], 'Informe o nome do grupo.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_GRUPO(p_nomegrupo => :NOME, p_msg_retorno => :MSG); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':NOME', $nome);
        $msg = '';
        $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();

        if (strpos(strtoupper($msg), 'ERRO') !== false) jexit(false, [], $msg);
        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    if ($action === 'del_grupo') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_GRUPO(p_codgrupo => :ID, p_msg_retorno => :MSG); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':ID', $id);
        $msg = '';
        $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    // ============================================
    // POLÍTICAS (MEGAG_DESP_POLIT_CENTRO_CUSTO)
    // ============================================
    if ($action === 'list_politicas') {
        $sql = "SELECT P.*, G.NOMEGRUPO, C.DESCRICAO AS NOME_CC
                  FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO P
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON P.CODGRUPO = G.CODGRUPO
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON P.SEQCENTRORESULTADO = C.SEQCENTRORESULTADO
                 ORDER BY P.CODPOLITICA DESC";
        $st = $conn->prepare($sql);
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_politica') {
        $grupo = (int)($req['codgrupo'] ?? 0);
        $cc_str = trim($req['centro_custo'] ?? '');
        $desc = trim($req['descricao'] ?? '');
        $nivel = (int)($req['nivel'] ?? 1);

        $cc_parts = explode('|', $cc_str);
        $centro_custo = $cc_parts[0] ?? '';
        $seq_cc = (int)($cc_parts[1] ?? 0);

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
                    p_codgrupo => :GRUPO, p_centrocusto => :CC, p_seqcentroresultado => :SEQCC,
                    p_descricao => :DESC, p_nivel_aprovacao => :NIVEL, p_msg_retorno => :MSG
                ); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':GRUPO', $grupo);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':SEQCC', $seq_cc);
        $st->bindValue(':DESC', $desc);
        $st->bindValue(':NIVEL', $nivel);
        $msg = ''; $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    if ($action === 'del_politica') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(p_codpolitica => :ID, p_msg_retorno => :MSG); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':ID', $id);
        $msg = ''; $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    // ============================================
    // CATEGORIAS (MEGAG_DESP_TIPO)
    // ============================================
    if ($action === 'list_tipos') {
        $sql = "SELECT CODTIPODESPESA, DESCRICAO FROM CONSINCO.MEGAG_DESP_TIPO ORDER BY CODTIPODESPESA ASC";
        $st = $conn->prepare($sql);
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_tipo') {
        $desc = trim($req['descricao'] ?? '');
        if ($desc === '') jexit(false, [], 'Informe a descrição da categoria.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_TIPO(p_DESCRICAO => :DESC); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':DESC', $desc);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Categoria cadastrada com sucesso!']]);
    }

    if ($action === 'del_tipo') {
        $id = (int) ($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_TIPO(p_CODTIPODESPESA => :ID); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':ID', $id);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Categoria excluída com sucesso!']]);
    }

    // ============================================
    // DADOS PARA SELECTS (API CARREGAMENTO)
    // ============================================
    if ($action === 'get_doms_aprovador') {
        $ccSql = "SELECT CENTRORESULTADO AS CENTROCUSTO, SEQCENTRORESULTADO, DESCRICAO AS NOME FROM CONSINCO.ABA_CENTRORESULTADO ORDER BY DESCRICAO";
        $stCC = $conn->prepare($ccSql); $stCC->execute();
        $ccs = $stCC->fetchAll(PDO::FETCH_ASSOC);

        $usuSql = "SELECT SEQUSUARIO, NOME FROM CONSINCO.GE_USUARIO ORDER BY NOME";
        $stU = $conn->prepare($usuSql); $stU->execute();
        $usus = $stU->fetchAll(PDO::FETCH_ASSOC);

        $grpSql = "SELECT CODGRUPO, NOMEGRUPO FROM CONSINCO.MEGAG_DESP_GRUPO ORDER BY NOMEGRUPO";
        $stG = $conn->prepare($grpSql); $stG->execute();
        $grps = $stG->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => ['ccs' => $ccs, 'usuarios' => $usus, 'grupos' => $grps]]);
    }

    // ============================================
    // APROVADORES X C.C. (MEGAG_DESP_APROVADORES)
    // ============================================
    if ($action === 'list_aprovadores') {
        $sql = "SELECT A.SEQUSUARIO, A.CENTROCUSTO, A.SEQCENTRORESULTADO, A.NOME AS GESTOR,
                       G.NOMEGRUPO, TO_CHAR(A.DTAINCLUSAO, 'DD/MM/YYYY') AS DATA_VINCULO
                  FROM CONSINCO.MEGAG_DESP_APROVADORES A
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON A.CODGRUPO = G.CODGRUPO
                 ORDER BY A.NOME";
        $st = $conn->prepare($sql);
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_aprovador') {
        $cc_str = trim($req['centro_custo'] ?? '');
        $gestor_str = trim($req['gestor'] ?? '');
        $codgrupo = (int)($req['codgrupo'] ?? 0);

        if ($cc_str === '' || $gestor_str === '') jexit(false, [], 'Preencha Centro de Custo e Gestor.');

        $cc_parts = explode('|', $cc_str);
        $centro_custo = $cc_parts[0] ?? '';
        $seq_cc = (int) ($cc_parts[1] ?? 0);

        $gestor_parts = explode('|', $gestor_str);
        $seq_usuario = (int) ($gestor_parts[0] ?? 0);
        $nome = trim($gestor_parts[1] ?? '');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_APROVADORES(
                      p_sequsuario         => :SEQ_USU,
                      p_centrocusto        => :CC,
                      p_seqcentroresultado => :SEQ_CC,
                      p_nome               => :NOME,
                      p_sequusuarioalt     => :USU_ALT,
                      p_dtaalteracao       => NULL,
                      p_codgrupo           => :GRP
                  ); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':SEQ_USU', $seq_usuario);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':SEQ_CC', $seq_cc);
        $st->bindValue(':NOME', $nome);
        $st->bindValue(':USU_ALT', $userIdInt);
        $st->bindValue(':GRP', $codgrupo);
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Aprovador vinculado com sucesso.']]);
    }

    if ($action === 'del_aprovador') {
        $nome = trim($req['nome'] ?? '');
        if ($nome === '') jexit(false, [], 'Nome inválido para exclusão.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_APROVADORES(p_nome => :NOME); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':NOME', $nome);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Vinculação removida com sucesso.']]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
