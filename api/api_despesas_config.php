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

require_once __DIR__ . '/mg_api_bootstrap.php';

function cfg_schema(): string
{
    return mg_schema();
}

function cfg_sql(string $sql): string
{
    return mg_with_schema($sql);
}

function body_json()
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function ensure_politica_cadastro(PDO $conn, int $codpolitica, string $descricao): int
{
    if ($codpolitica > 0) {
        return $codpolitica;
    }

    $sqlNext = "SELECT NVL(MAX(CODPOLITICA), 0) + 1 AS PROXIMO
                  FROM CONSINCO.MEGAG_DESP_POLITICA";
    try {
        $stNext = $conn->prepare(cfg_sql($sqlNext));
        $stNext->execute();
        $novoCodigo = (int)($stNext->fetchColumn() ?: 0);
    } catch (Exception $e) {
        throw new Exception('Falha ao buscar proximo CODPOLITICA: ' . $e->getMessage());
    }

    if ($novoCodigo <= 0) {
        throw new Exception('Nao foi possivel gerar o codigo da politica.');
    }

    $sqlIns = "INSERT INTO CONSINCO.MEGAG_DESP_POLITICA (CODPOLITICA, DESCRICAO)
               VALUES (?, ?)";
    try {
        $stIns = $conn->prepare(cfg_sql($sqlIns));
        $stIns->execute([$novoCodigo, $descricao]);
    } catch (Exception $e) {
        throw new Exception('Falha ao inserir MEGAG_DESP_POLITICA: ' . $e->getMessage());
    }

    return $novoCodigo;
}

function inserir_vinculo_politica(
    PDO $conn,
    int $codpolitica,
    int $codgrupo,
    int $sequsuario,
    int $seqCentroResultado,
    int $nivel,
    string $descricao
): int {
    $sqlNext = "SELECT NVL(MAX(CODPOLIT_CC), 0) + 1 AS PROXIMO
                  FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO";
    try {
        $stNext = $conn->prepare(cfg_sql($sqlNext));
        $stNext->execute();
        $novoCodigo = (int)($stNext->fetchColumn() ?: 0);
    } catch (Exception $e) {
        throw new Exception('Falha ao buscar proximo CODPOLIT_CC: ' . $e->getMessage());
    }

    if ($novoCodigo <= 0) {
        throw new Exception('Nao foi possivel gerar o codigo do vinculo da politica.');
    }

    $sqlIns = "INSERT INTO CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO (
                    CODPOLIT_CC,
                    CODPOLITICA,
                    CODGRUPO,
                    SEQUSUARIO,
                    CENTROCUSTO,
                    NIVEL_APROVACAO,
                    DESCRICAO,
                    DTAINCLUSAO
               ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, SYSDATE
               )";
    try {
        $stIns = $conn->prepare(cfg_sql($sqlIns));
        $stIns->execute([
            $novoCodigo,
            $codpolitica,
            $codgrupo,
            $sequsuario,
            $seqCentroResultado,
            $nivel,
            $descricao,
        ]);
    } catch (Exception $e) {
        throw new Exception('Falha ao inserir MEGAG_DESP_POLIT_CENTRO_CUSTO: ' . $e->getMessage());
    }

    return $novoCodigo;
}

function buscar_centro_resultado(PDO $conn, string $valor): ?array
{
    $valor = trim($valor);
    if ($valor === '') {
        return null;
    }

    $partes = explode('|', $valor);
    $codigo = trim($partes[0] ?? '');
    $seqInformado = trim($partes[1] ?? '');

    $sql = "SELECT CENTRORESULTADO, SEQCENTRORESULTADO, DESCRICAO
              FROM CONSINCO.ABA_CENTRORESULTADO
             WHERE (:SEQ IS NOT NULL AND TO_CHAR(SEQCENTRORESULTADO) = :SEQ)
                OR TO_CHAR(CENTRORESULTADO) = :COD
                OR TO_CHAR(SEQCENTRORESULTADO) = :COD";
    $st = $conn->prepare(cfg_sql($sql));
    if ($seqInformado !== '') {
        $st->bindValue(':SEQ', $seqInformado);
    } else {
        $st->bindValue(':SEQ', null, PDO::PARAM_NULL);
    }
    $st->bindValue(':COD', $codigo);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function existe_centro_resultado(PDO $conn, string $centroCusto): bool
{
    return buscar_centro_resultado($conn, $centroCusto) !== null;
}

function existe_vinculo_aprovador(PDO $conn, int $codgrupo, int $sequsuario, int $seqCentroResultado): bool
{
    $sql = "SELECT COUNT(1)
              FROM CONSINCO.MEGAG_DESP_APROVADORES
             WHERE CODGRUPO = :GRUPO
               AND SEQUSUARIO = :SEQ
               AND CENTROCUSTO = :CC";
    $st = $conn->prepare(cfg_sql($sql));
    $st->bindValue(':GRUPO', $codgrupo);
    $st->bindValue(':SEQ', $sequsuario);
    $st->bindValue(':CC', $seqCentroResultado);
    $st->execute();
    return ((int)($st->fetchColumn() ?: 0)) > 0;
}

function nome_usuario_por_seq(PDO $conn, int $sequsuario): string
{
    $sql = "SELECT NOME
              FROM CONSINCO.GE_USUARIO
             WHERE SEQUSUARIO = :SEQ";
    $st = $conn->prepare(cfg_sql($sql));
    $st->bindValue(':SEQ', $sequsuario);
    $st->execute();
    return trim((string)($st->fetchColumn() ?: ''));
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
        $st = $conn->prepare(cfg_sql($sql));
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_grupo') {
        $nome = trim($req['nome'] ?? '');
        if ($nome === '') jexit(false, [], 'Informe o nome do grupo.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_GRUPO(
                    p_nomegrupo => :NOME,
                    p_dtainclusao => SYSDATE,
                    p_dtaalteracao => NULL,
                    p_msg_retorno => :MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':NOME', $nome);
        $msg = '';
        $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();

        if (strpos(strtoupper($msg), 'ERRO') !== false) jexit(false, [], $msg);

        $stLast = $conn->prepare("
            SELECT CODGRUPO, NOMEGRUPO
              FROM (
                    SELECT CODGRUPO, NOMEGRUPO
                      FROM CONSINCO.MEGAG_DESP_GRUPO
                     WHERE UPPER(TRIM(NOMEGRUPO)) = UPPER(TRIM(:NOME))
                     ORDER BY CODGRUPO DESC
                   )
             WHERE ROWNUM = 1
        ");
        $stLast->bindValue(':NOME', $nome);
        $stLast->execute();
        $grupo = $stLast->fetch(PDO::FETCH_ASSOC) ?: null;

        jexit(true, ['dados' => ['mensagem' => $msg, 'grupo' => $grupo]]);
    }

    if ($action === 'del_grupo') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_GRUPO(p_codgrupo => :ID, p_msg_retorno => :MSG); END;";
        $st = $conn->prepare(cfg_sql($sql));
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
        $sql = "SELECT P.*, G.NOMEGRUPO, C.CENTRORESULTADO AS CODIGO_CC, C.DESCRICAO AS NOME_CC, U.NOME AS NOME_USUARIO
                  FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO P
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON P.CODGRUPO = G.CODGRUPO
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON P.CENTROCUSTO = C.SEQCENTRORESULTADO
                  LEFT JOIN CONSINCO.GE_USUARIO U ON P.SEQUSUARIO = U.SEQUSUARIO
                 ORDER BY P.CODPOLITICA DESC";
        $st = $conn->prepare(cfg_sql($sql));
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_politica') {
        $grupo = (int)($req['codgrupo'] ?? 0);
        $cc_str = trim($req['centro_custo'] ?? '');
        $seq_usuario = (int)($req['sequsuario'] ?? 0);
        $desc = trim($req['descricao'] ?? '');
        $nivel = (int)($req['nivel'] ?? 1);

        $cc_parts = explode('|', $cc_str);
        $seq_cc = (int)($cc_parts[1] ?? 0);

        if (!$grupo || !$seq_cc || !$seq_usuario) {
            jexit(false, [], 'Informe grupo, centro de custo e usuário da política.');
        }

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
                    p_codgrupo => :GRUPO,
                    p_sequsuario => :SEQ_USUARIO,
                    p_centrocusto => :CC,
                    p_descricao => :DESC,
                    p_nivel_aprovacao => :NIVEL,
                    p_msg_retorno => :MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':GRUPO', $grupo);
        $st->bindValue(':SEQ_USUARIO', $seq_usuario);
        $st->bindValue(':CC', $seq_cc);
        $st->bindValue(':DESC', $desc);
        $st->bindValue(':NIVEL', $nivel);
        $msg = '';
        $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();

        if (strpos(strtoupper($msg), 'ERRO') !== false) {
            jexit(false, [], $msg);
        }

        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    if ($action === 'add_politica_lote') {
        $codpolitica = (int)($req['codpolitica'] ?? 0);
        $grupo = (int)($req['codgrupo'] ?? 0);
        $cc_str = trim($req['centro_custo'] ?? '');
        $desc = trim($req['descricao'] ?? '');
        $aprovadores = is_array($req['aprovadores'] ?? null) ? $req['aprovadores'] : [];

        $centroInfo = buscar_centro_resultado($conn, $cc_str);
        $centro_custo = trim((string)($centroInfo['CENTRORESULTADO'] ?? ''));
        $seq_centro_resultado = (int)($centroInfo['SEQCENTRORESULTADO'] ?? 0);

        if (!$grupo || !$centro_custo || !$seq_centro_resultado || $desc === '' || empty($aprovadores)) {
            jexit(false, [], 'Informe grupo, centro de custo, descrição e ao menos um aprovador.');
        }

        if (!existe_centro_resultado($conn, $cc_str)) {
            jexit(false, [], 'O centro de custo selecionado nao existe mais no cadastro do ERP.');
        }

        $conn->beginTransaction();

        try {
            $codpolitica = ensure_politica_cadastro($conn, $codpolitica, $desc);

            foreach ($aprovadores as $item) {
                $seq_usuario = (int)($item['sequsuario'] ?? 0);
                $nivel = max(1, (int)($item['nivel'] ?? 1));

                if (!$seq_usuario) {
                    throw new Exception('Um dos aprovadores informados é inválido.');
                }

                if (!existe_vinculo_aprovador($conn, $grupo, $seq_usuario, $seq_centro_resultado)) {
                    $nomeUsuario = nome_usuario_por_seq($conn, $seq_usuario);
                    $nomeLabel = $nomeUsuario !== '' ? $nomeUsuario : ('SEQ ' . $seq_usuario);
                    throw new Exception("O aprovador {$nomeLabel} nao esta vinculado ao grupo e centro de custo selecionados.");
                }

                inserir_vinculo_politica($conn, $codpolitica, $grupo, $seq_usuario, $seq_centro_resultado, $nivel, $desc);
            }

            $conn->commit();
            jexit(true, ['dados' => ['mensagem' => 'Política cadastrada com múltiplos aprovadores.']]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $msg = $e->getMessage();
            if (stripos($msg, 'FK_POLITCC_CENTRO') !== false || stripos($msg, 'ORA-02291') !== false) {
                jexit(false, [], 'Nao foi possivel salvar a politica porque um dos aprovadores nao possui vinculo pai com este grupo e centro de custo.');
            }
            jexit(false, [], $msg);
        }
    }

    if ($action === 'del_politica') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(p_codpolit_cc => :ID, p_msg_retorno => :MSG); END;";
        $st = $conn->prepare(cfg_sql($sql));
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
        $st = $conn->prepare(cfg_sql($sql));
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_tipo') {
        $desc = trim($req['descricao'] ?? '');
        if ($desc === '') jexit(false, [], 'Informe a descrição da categoria.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_TIPO(p_DESCRICAO => :DESC); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':DESC', $desc);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Categoria cadastrada com sucesso!']]);
    }

    if ($action === 'del_tipo') {
        $id = (int) ($req['id'] ?? 0);
        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_TIPO(p_CODTIPODESPESA => :ID); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':ID', $id);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Categoria excluída com sucesso!']]);
    }

    // ============================================
    // DADOS PARA SELECTS (API CARREGAMENTO)
    // ============================================
    if ($action === 'get_doms_aprovador') {
        $ccSql = "SELECT CENTRORESULTADO AS CENTROCUSTO, SEQCENTRORESULTADO, DESCRICAO AS NOME FROM CONSINCO.ABA_CENTRORESULTADO ORDER BY DESCRICAO";
        $stCC = $conn->prepare(cfg_sql($ccSql)); $stCC->execute();
        $ccs = $stCC->fetchAll(PDO::FETCH_ASSOC);

        $usuSql = "SELECT SEQUSUARIO, NOME FROM CONSINCO.GE_USUARIO ORDER BY NOME";
        $stU = $conn->prepare(cfg_sql($usuSql)); $stU->execute();
        $usus = $stU->fetchAll(PDO::FETCH_ASSOC);

        $grpSql = "SELECT CODGRUPO, NOMEGRUPO FROM CONSINCO.MEGAG_DESP_GRUPO ORDER BY NOMEGRUPO";
        $stG = $conn->prepare(cfg_sql($grpSql)); $stG->execute();
        $grps = $stG->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => ['ccs' => $ccs, 'usuarios' => $usus, 'grupos' => $grps]]);
    }

    if ($action === 'list_aprovadores_vinculados') {
        $grupoRaw = $req['codgrupo'] ?? null;
        $grupo = ($grupoRaw === '' || $grupoRaw === null) ? null : (int)$grupoRaw;
        $cc_str = trim($req['centro_custo'] ?? '');
        $centroInfo = buscar_centro_resultado($conn, $cc_str);
        $seq_centro_resultado = (int)($centroInfo['SEQCENTRORESULTADO'] ?? 0);

        if ($seq_centro_resultado <= 0) {
            jexit(true, ['dados' => []]);
        }

        $sql = "SELECT DISTINCT A.SEQUSUARIO, A.NOME, A.CODGRUPO,
                               A.CENTROCUSTO AS SEQCENTRORESULTADO,
                               C.CENTRORESULTADO AS CENTROCUSTO,
                               C.DESCRICAO AS NOME_CC
                  FROM CONSINCO.MEGAG_DESP_APROVADORES A
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON C.SEQCENTRORESULTADO = A.CENTROCUSTO
                 WHERE A.CENTROCUSTO = :CC";
        if ($grupo === null) {
            $sql .= " AND A.CODGRUPO IS NULL";
        } else {
            $sql .= " AND A.CODGRUPO = :GRUPO";
        }
        $sql .= "
                 ORDER BY A.NOME";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':CC', $seq_centro_resultado);
        if ($grupo !== null) {
            $st->bindValue(':GRUPO', $grupo);
        }
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ============================================
    // APROVADORES X C.C. (MEGAG_DESP_APROVADORES)
    // ============================================
    if ($action === 'list_aprovadores') {
        $sql = "SELECT A.SEQUSUARIO, A.CODGRUPO, C.CENTRORESULTADO AS CENTROCUSTO, A.CENTROCUSTO AS SEQCENTRORESULTADO, A.NOME AS GESTOR,
                       G.NOMEGRUPO, TO_CHAR(A.DTAINCLUSAO, 'DD/MM/YYYY') AS DATA_VINCULO
                  FROM CONSINCO.MEGAG_DESP_APROVADORES A
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON A.CODGRUPO = G.CODGRUPO
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON C.SEQCENTRORESULTADO = A.CENTROCUSTO
                 ORDER BY A.NOME";
        $st = $conn->prepare(cfg_sql($sql));
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_aprovador') {
        $cc_str = trim($req['centro_custo'] ?? '');
        $gestor_str = trim($req['gestor'] ?? '');
        $codgrupoRaw = $req['codgrupo'] ?? null;
        $codgrupo = ($codgrupoRaw === '' || $codgrupoRaw === null) ? null : (int)$codgrupoRaw;

        if ($cc_str === '' || $gestor_str === '') jexit(false, [], 'Preencha Centro de Custo e Gestor.');

        $cc_parts = explode('|', $cc_str);
        $seq_cc = (int) ($cc_parts[1] ?? 0);

        $gestor_parts = explode('|', $gestor_str);
        $seq_usuario = (int) ($gestor_parts[0] ?? 0);
        $nome = trim($gestor_parts[1] ?? '');

        if ($seq_cc <= 0 || $seq_usuario <= 0 || $nome === '') {
            jexit(false, [], 'Centro de custo ou aprovador invalido.');
        }

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_APROVADORES(
                      p_sequsuario         => :SEQ_USU,
                      p_centrocusto        => :CC,
                      p_seqcentroresultado => :SEQ_CC,
                      p_nome               => :NOME,
                      p_sequusuarioalt     => :USU_ALT,
                      p_dtaalteracao       => NULL,
                      p_codgrupo           => :GRP
                  ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':SEQ_USU', $seq_usuario);
        $st->bindValue(':CC', $seq_cc);
        $st->bindValue(':SEQ_CC', $seq_cc);
        $st->bindValue(':NOME', $nome);
        $st->bindValue(':USU_ALT', $userIdInt);
        if ($codgrupo === null) {
            $st->bindValue(':GRP', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':GRP', $codgrupo);
        }
        $st->execute();

        jexit(true, ['dados' => ['mensagem' => 'Aprovador vinculado com sucesso.']]);
    }

    if ($action === 'del_aprovador_vinculo') {
        $codgrupoRaw = $req['codgrupo'] ?? null;
        $codgrupo = ($codgrupoRaw === '' || $codgrupoRaw === null) ? null : (int)$codgrupoRaw;
        $centroInfo = buscar_centro_resultado($conn, trim($req['centro_custo'] ?? ''));
        $seq_centro_resultado = (int)($centroInfo['SEQCENTRORESULTADO'] ?? 0);
        $sequsuario = (int)($req['sequsuario'] ?? 0);

        if ($seq_centro_resultado <= 0 || !$sequsuario) {
            jexit(false, [], 'Parametros invalidos para remover o vinculo.');
        }

        $sql = "DELETE FROM CONSINCO.MEGAG_DESP_APROVADORES
                 WHERE CENTROCUSTO = :CC
                   AND SEQUSUARIO = :SEQ";
        if ($codgrupo === null) {
            $sql .= " AND CODGRUPO IS NULL";
        } else {
            $sql .= " AND CODGRUPO = :GRUPO";
        }
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':CC', $seq_centro_resultado);
        $st->bindValue(':SEQ', $sequsuario);
        if ($codgrupo !== null) {
            $st->bindValue(':GRUPO', $codgrupo);
        }
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Vinculo removido com sucesso.']]);
    }

    if ($action === 'del_centro_custo_vinculo') {
        $codgrupoRaw = $req['codgrupo'] ?? null;
        $codgrupo = ($codgrupoRaw === '' || $codgrupoRaw === null) ? null : (int)$codgrupoRaw;
        $centroInfo = buscar_centro_resultado($conn, trim($req['centro_custo'] ?? ''));
        $seq_centro_resultado = (int)($centroInfo['SEQCENTRORESULTADO'] ?? 0);

        if ($seq_centro_resultado <= 0) {
            jexit(false, [], 'Parametros invalidos para excluir o centro de custo.');
        }

        $sql = "DELETE FROM CONSINCO.MEGAG_DESP_APROVADORES
                 WHERE CENTROCUSTO = :CC";
        if ($codgrupo === null) {
            $sql .= " AND CODGRUPO IS NULL";
        } else {
            $sql .= " AND CODGRUPO = :GRUPO";
        }

        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':CC', $seq_centro_resultado);
        if ($codgrupo !== null) {
            $st->bindValue(':GRUPO', $codgrupo);
        }

        $st->execute();

        if ($st->rowCount() === 0) {
            jexit(false, [], 'Nenhum vinculo encontrado para este centro de custo.');
        }

        jexit(true, ['dados' => ['mensagem' => 'Centro de custo excluido com sucesso.']]);
    }

    if ($action === 'del_aprovador') {
        $nome = trim($req['nome'] ?? '');
        if ($nome === '') jexit(false, [], 'Nome inválido para exclusão.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_DEL_MEGAG_DESP_APROVADORES(p_nome => :NOME); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':NOME', $nome);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Vinculação removida com sucesso.']]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}

