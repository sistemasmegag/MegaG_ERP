<?php
require_once __DIR__ . '/../routes/check_session.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bootstrap/db.php';
// Procura o db_connect em pastas acima
$pathConexao = mg_db_config_path();
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

function cfg_bind_pkg_status(PDOStatement $stmt, &$sfx, &$ico, &$tiporet, &$msg): void
{
    $sfx = str_repeat(' ', 20);
    $ico = str_repeat(' ', 20);
    $tiporet = str_repeat(' ', 2);
    $msg = str_repeat(' ', 4000);

    $stmt->bindParam(':S_SFX', $sfx, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
    $stmt->bindParam(':S_ICO', $ico, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
    $stmt->bindParam(':S_TIPORET', $tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2);
    $stmt->bindParam(':S_MSG', $msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
}

function cfg_pkg_response(string $sfx, string $ico, string $tiporet, string $msg): array
{
    return [
        's_sfx' => trim($sfx),
        's_ico' => trim($ico),
        's_tiporet' => trim($tiporet),
        's_msg' => trim($msg),
    ];
}

function cfg_pkg_failed(array $pkgResult): bool
{
    return ($pkgResult['s_tiporet'] ?? '') !== 'S';
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
    int $centroCusto,
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
            $centroCusto,
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

    $sql = "SELECT CENTRORESULTADO, DESCRICAO
              FROM CONSINCO.ABA_CENTRORESULTADO
             WHERE TO_CHAR(CENTRORESULTADO) = :COD";
    $st = $conn->prepare(cfg_sql($sql));
    $st->bindValue(':COD', $codigo);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function existe_centro_resultado(PDO $conn, string $centroCusto): bool
{
    return buscar_centro_resultado($conn, $centroCusto) !== null;
}

function existe_vinculo_aprovador(PDO $conn, string $codgrupo, int $sequsuario, string $centroCustoCode): bool
{
    $centroInfo = buscar_centro_resultado($conn, $centroCustoCode);
    $centroCustoNumero = (int)($centroInfo['CENTRORESULTADO'] ?? 0);
    if ($centroCustoNumero <= 0) {
        return false;
    }

    $sql = "SELECT COUNT(1)
              FROM CONSINCO.MEGAG_DESP_APROVADORES
             WHERE CODGRUPO = :GRUPO
               AND SEQUSUARIO = :SEQ
               AND CENTROCUSTO = :CODCC";
    $st = $conn->prepare(cfg_sql($sql));
    $st->bindValue(':GRUPO', $codgrupo);
    $st->bindValue(':SEQ', $sequsuario, PDO::PARAM_INT);
    $st->bindValue(':CODCC', $centroCustoNumero, PDO::PARAM_INT);
    $st->execute();
    return ((int)($st->fetchColumn() ?: 0)) > 0;
}

function normalizar_vinculo_aprovador(PDO $conn, ?int $codgrupo, int $sequsuario, int $centroCusto): void
{
    if ($sequsuario <= 0 || $centroCusto <= 0) {
        return;
    }

    $sql = "UPDATE CONSINCO.MEGAG_DESP_APROVADORES
               SET CENTROCUSTO = :CODCC
             WHERE SEQUSUARIO = :SEQ
               AND CENTROCUSTO = :CODCC";

    if ($codgrupo === null) {
        $sql .= " AND CODGRUPO IS NULL";
    } else {
        $sql .= " AND CODGRUPO = :GRUPO";
    }

    $st = $conn->prepare(cfg_sql($sql));
    $st->bindValue(':CODCC', $centroCusto, PDO::PARAM_INT);
    $st->bindValue(':SEQ', $sequsuario, PDO::PARAM_INT);
    if ($codgrupo !== null) {
        $st->bindValue(':GRUPO', $codgrupo, PDO::PARAM_INT);
    }
    $st->execute();
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

        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_GRUPO(
                    p_nomegrupo => :NOME,
                    p_dtainclusao => SYSDATE,
                    p_dtaalteracao => NULL,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':NOME', $nome);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) jexit(false, [], $pkgResult['s_msg']);


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

        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg'], 'grupo' => $grupo]]);
    }

    if ($action === 'del_grupo') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_DEL_MEGAG_DESP_GRUPO(
                    p_codgrupo => :ID,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':ID', $id);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) jexit(false, [], $pkgResult['s_msg']);
        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg']]]);

    }

    // ============================================
    // POLÍTICAS (MEGAG_DESP_POLIT_CENTRO_CUSTO)
    // ============================================
    if ($action === 'list_politicas') {
        $sql = "SELECT P.*, G.NOMEGRUPO, C.CENTRORESULTADO AS CODIGO_CC, C.DESCRICAO AS NOME_CC, U.NOME AS NOME_USUARIO
                  FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO P
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON P.CODGRUPO = G.CODGRUPO
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON P.CENTROCUSTO = C.CENTRORESULTADO
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

        $centroInfo = buscar_centro_resultado($conn, $cc_str);
        $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);

        if (!$grupo || !$centro_custo || !$seq_usuario) {
            jexit(false, [], 'Informe grupo, centro de custo e usuário da política.');
        }

        normalizar_vinculo_aprovador($conn, $grupo, $seq_usuario, $centro_custo);

        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_POLITICA(
                    p_descricao         => :DESC,
                    p_codgrupo          => :GRUPO,
                    p_sequsuario        => :SEQ_USUARIO,
                    p_centrocusto       => :CC,
                    p_nivel_aprovacao   => :NIVEL,
                    p_descricao_vinculo => :DESC_VINCULO,
                    p_codpolitica       => :OUT_CODPOL,
                    p_codpolit_cc       => :OUT_CODPOLCC,
                    s_sfx               => :S_SFX,
                    s_ico               => :S_ICO,
                    s_tiporet           => :S_TIPORET,
                    s_msg               => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':GRUPO', $grupo);
        $st->bindValue(':SEQ_USUARIO', $seq_usuario);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':DESC', $desc);
        $st->bindValue(':DESC_VINCULO', $desc !== '' ? $desc : null, $desc !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':NIVEL', $nivel);
        $outCodPolitica = 0;
        $outCodPolitCc = 0;
        $st->bindParam(':OUT_CODPOL', $outCodPolitica, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
        $st->bindParam(':OUT_CODPOLCC', $outCodPolitCc, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) {
            jexit(false, [], $pkgResult['s_msg']);
        }

        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg'], 'codpolitica' => (int)$outCodPolitica, 'codpolit_cc' => (int)$outCodPolitCc]]);
    }

    if ($action === 'add_politica_lote') {
        $cc_global_str = trim($req['centro_custo'] ?? '');
        $desc = trim($req['descricao'] ?? '');
        $niveis = is_array($req['niveis'] ?? null) ? $req['niveis'] : [];
        
        // Fallback p/ estrutura antiga (Wizard ou UI legada)
        if (empty($niveis)) {
            $grupoItem = (int)($req['codgrupo'] ?? 0);
            $aprovadoresLegado = is_array($req['aprovadores'] ?? null) ? $req['aprovadores'] : [];
            if ($grupoItem > 0 && !empty($aprovadoresLegado)) {
                $niveis = [[
                    'grupo' => $grupoItem,
                    'aprovadores' => $aprovadoresLegado
                ]];
            }
        }

        if ($desc === '' || empty($niveis)) {
            jexit(false, [], 'Informe a descrição e os níveis de aprovação.');
        }

        // Se informou CC global, pegamos as informações dele
        $centro_custo_global = 0;
        if ($cc_global_str !== '') {
            $centroGlobalInfo = buscar_centro_resultado($conn, $cc_global_str);
            $centro_custo_global = (int)($centroGlobalInfo['CENTRORESULTADO'] ?? 0);
        }

        try {
            $conn->beginTransaction();
            $codpolitica = 0;

            foreach ($niveis as $idxNivel => $nivelObj) {
                $grupoId = trim((string)($nivelObj['grupo'] ?? ''));
                $aprovs = is_array($nivelObj['aprovadores'] ?? null) ? $nivelObj['aprovadores'] : [];
                $nivelNum = (int)($nivelObj['nivel'] ?? ($idxNivel + 1));

                if (!$grupoId || empty($aprovs)) {
                    continue;
                }

                foreach ($aprovs as $aprovItem) {
                    $seq_usuario = 0;
                    $centro_custo_val = $cc_global_str;
                    $nivel_aprov = $nivelNum;

                    if (is_array($aprovItem)) {
                        $seq_usuario = (int)($aprovItem['sequsuario'] ?? 0);
                        if (isset($aprovItem['centro_custo']) && trim((string)$aprovItem['centro_custo']) !== '') {
                            $centro_custo_val = trim((string)$aprovItem['centro_custo']);
                        }
                        if (isset($aprovItem['nivel'])) {
                            $nivel_aprov = (int)$aprovItem['nivel'];
                        }
                    } else {
                        $parts = explode('|', (string)$aprovItem);
                        $seq_usuario = (int)($parts[0] ?? 0);
                        if (isset($parts[1]) && trim((string)$parts[1]) !== '') {
                            $centro_custo_val = trim((string)$parts[1]);
                        }
                    }

                    if (!$seq_usuario || $centro_custo_val === '') {
                        continue;
                    }

                    if (!existe_vinculo_aprovador($conn, $grupoId, $seq_usuario, $centro_custo_val)) {
                        $nomeUsuario = nome_usuario_por_seq($conn, $seq_usuario);
                        throw new Exception("O aprovador " . ($nomeUsuario ?: "SEQ $seq_usuario") . " nao esta vinculado ao grupo selecionado neste centro de custo.");
                    }

                    $centroInfo = buscar_centro_resultado($conn, $centro_custo_val);
                    $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);
                    if ($centro_custo <= 0) {
                        throw new Exception('Centro de custo invalido para a politica.');
                    }

                    normalizar_vinculo_aprovador($conn, (int)$grupoId, $seq_usuario, $centro_custo);

                    if ($codpolitica === 0) {
                        $codpolitica = ensure_politica_cadastro($conn, 0, $desc);
                    }

                    inserir_vinculo_politica(
                        $conn,
                        $codpolitica,
                        (int)$grupoId,
                        $seq_usuario,
                        $centro_custo,
                        $nivel_aprov,
                        $desc
                    );
                }
            }

            $conn->commit();
            jexit(true, ['dados' => ['mensagem' => 'PolÃ­tica e fluxos cadastrados com sucesso!', 'codpolitica' => $codpolitica]]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            jexit(false, [], $e->getMessage());
        }

        try {
            $conn->beginTransaction();
            $codpolitica = 0;

            foreach ($niveis as $idxNivel => $nivelObj) {
                $grupoId = trim((string)($nivelObj['grupo'] ?? ''));
                $aprovs = is_array($nivelObj['aprovadores'] ?? null) ? $nivelObj['aprovadores'] : [];
                $nivelNum = (int)($nivelObj['nivel'] ?? ($idxNivel + 1));

                if (!$grupoId || empty($aprovs)) continue;

                foreach ($aprovs as $idxAprov => $aprovItem) {
                    $seq_usuario = 0;
                    $centro_custo_val = $cc_global_str;
                    $nivel_aprov = $nivelNum;

                    if (is_array($aprovItem)) {
                        $seq_usuario = (int)($aprovItem['sequsuario'] ?? 0);
                        // Se o item tem CC próprio (vindo do seletor múltiplo sem CC global)
                        if (isset($aprovItem['centro_custo']) && trim($aprovItem['centro_custo']) !== '') {
                            $centro_custo_val = trim($aprovItem['centro_custo']);
                        }
                        if (isset($aprovItem['nivel'])) {
                            $nivel_aprov = (int)$aprovItem['nivel'];
                        }
                    } else {
                        // Formato legado ou string "id|cc"
                        $parts = explode('|', (string)$aprovItem);
                        $seq_usuario = (int)$parts[0];
                        if (isset($parts[1]) && trim($parts[1]) !== '') {
                            $centro_custo_val = trim($parts[1]);
                        }
                    }

                    if (!$seq_usuario || $centro_custo_val === '') continue;

                    if (!existe_vinculo_aprovador($conn, $grupoId, $seq_usuario, $centro_custo_val)) {
                        $nomeUsuario = nome_usuario_por_seq($conn, $seq_usuario);
                        throw new Exception("O aprovador " . ($nomeUsuario ?: "SEQ $seq_usuario") . " não está vinculado ao grupo selecionado neste centro de custo.");
                    }

                    $centroInfo = buscar_centro_resultado($conn, $centro_custo_val);
                    $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);
                    if ($centro_custo <= 0) {
                        throw new Exception('Centro de custo invalido para a politica.');
                    }

                    if ($codpolitica === 0) {
                        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_POLITICA(
                                    p_descricao         => :DESC,
                                    p_codgrupo          => :GRUPO,
                                    p_sequsuario        => :SEQ_USUARIO,
                                    p_centrocusto       => :CC,
                                    p_nivel_aprovacao   => :NIVEL,
                                    p_descricao_vinculo => :DESC_VINCULO,
                                    p_codpolitica       => :OUT_CODPOL,
                                    p_codpolit_cc       => :OUT_CODPOLCC,
                                    s_sfx               => :S_SFX,
                                    s_ico               => :S_ICO,
                                    s_tiporet           => :S_TIPORET,
                                    s_msg               => :S_MSG
                                ); END;";
                        $st = $conn->prepare(cfg_sql($sql));
                        $st->bindValue(':DESC', $desc);
                        $st->bindValue(':GRUPO', $grupoId, PDO::PARAM_INT);
                        $st->bindValue(':SEQ_USUARIO', $seq_usuario, PDO::PARAM_INT);
                        $st->bindValue(':CC', $centro_custo, PDO::PARAM_INT);
                        $st->bindValue(':NIVEL', $nivel_aprov, PDO::PARAM_INT);
                        $st->bindValue(':DESC_VINCULO', $desc);
                        $outCodPol = 0; $outCodPolCc = 0;
                        $st->bindParam(':OUT_CODPOL', $outCodPol, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                        $st->bindParam(':OUT_CODPOLCC', $outCodPolCc, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                        cfg_bind_pkg_status($st, $pSfx, $pIco, $pTipo, $pMsg);
                        $st->execute();

                        $res = cfg_pkg_response($pSfx, $pIco, $pTipo, $pMsg);
                        if (cfg_pkg_failed($res)) throw new Exception($res['s_msg'] ?: 'Erro ao iniciar política.');
                        $codpolitica = (int)$outCodPol;
                    } else {
                        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
                                    p_codpolitica     => :CODPOL,
                                    p_codgrupo        => :GRUPO,
                                    p_sequsuario      => :SEQ_USUARIO,
                                    p_centrocusto     => :CC,
                                    p_nivel_aprovacao => :NIVEL,
                                    p_descricao       => :DESC,
                                    p_codpolit_cc     => :OUT_CODPOLCC,
                                    s_sfx             => :S_SFX,
                                    s_ico             => :S_ICO,
                                    s_tiporet         => :S_TIPORET,
                                    s_msg             => :S_MSG
                                ); END;";
                        $st = $conn->prepare(cfg_sql($sql));
                        $st->bindValue(':CODPOL', $codpolitica, PDO::PARAM_INT);
                        $st->bindValue(':GRUPO', $grupoId, PDO::PARAM_INT);
                        $st->bindValue(':SEQ_USUARIO', $seq_usuario, PDO::PARAM_INT);
                        $st->bindValue(':CC', $centro_custo, PDO::PARAM_INT);
                        $st->bindValue(':NIVEL', $nivel_aprov, PDO::PARAM_INT);
                        $st->bindValue(':DESC', $desc);
                        $outCodPolCc = 0;
                        $st->bindParam(':OUT_CODPOLCC', $outCodPolCc, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                        cfg_bind_pkg_status($st, $pSfx, $pIco, $pTipo, $pMsg);
                        $st->execute();

                        $res = cfg_pkg_response($pSfx, $pIco, $pTipo, $pMsg);
                        if (cfg_pkg_failed($res)) throw new Exception($res['s_msg'] ?: "Erro ao vincular aprovador no nível $nivelNum.");
                    }
                }
            }

            $conn->commit();
            jexit(true, ['dados' => ['mensagem' => 'Política e fluxos cadastrados com sucesso!', 'codpolitica' => $codpolitica]]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $msg = $e->getMessage();
            if (stripos($msg, 'FK_POLITCC_CENTRO') !== false || stripos($msg, 'ORA-02291') !== false) {
                $msg = 'Um ou mais aprovadores não possuem vínculo pai com o grupo/centro de custo selecionado.';
            }
            jexit(false, [], $msg);
        }
    }

    if ($action === 'del_politica') {
        $id = (int)($req['id'] ?? 0);
        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
                    p_codpolit_cc => :ID,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':ID', $id);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) jexit(false, [], $pkgResult['s_msg']);
        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg']]]);
    }

    if ($action === 'update_politica') {
        $codpolitCc = (int)($req['codpolit_cc'] ?? 0);
        $grupo = (int)($req['codgrupo'] ?? 0);
        $cc_str = trim($req['centro_custo'] ?? '');
        $seq_usuario = (int)($req['sequsuario'] ?? 0);
        $desc = trim($req['descricao'] ?? '');
        $nivel = max(1, (int)($req['nivel'] ?? 1));

        $centroInfo = buscar_centro_resultado($conn, $cc_str);
        $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);

        if ($codpolitCc <= 0 || !$grupo || !$centro_custo || !$seq_usuario || $desc === '') {
            jexit(false, [], 'Informe grupo, centro de custo, aprovador, descricao e nivel da politica.');
        }

        normalizar_vinculo_aprovador($conn, $grupo, $seq_usuario, $centro_custo);

        if (!existe_vinculo_aprovador($conn, $grupo, $seq_usuario, (string)$centro_custo)) {
            $nomeUsuario = nome_usuario_por_seq($conn, $seq_usuario);
            $nomeLabel = $nomeUsuario !== '' ? $nomeUsuario : ('SEQ ' . $seq_usuario);
            jexit(false, [], "O aprovador {$nomeLabel} nao esta vinculado ao grupo e centro de custo selecionados.");
        }

        $sql = "UPDATE CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO
                   SET CODGRUPO = :GRUPO,
                       SEQUSUARIO = :SEQ_USUARIO,
                       CENTROCUSTO = :CC,
                       DESCRICAO = :DESC,
                       NIVEL_APROVACAO = :NIVEL
                 WHERE CODPOLIT_CC = :ID";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':GRUPO', $grupo, PDO::PARAM_INT);
        $st->bindValue(':SEQ_USUARIO', $seq_usuario, PDO::PARAM_INT);
        $st->bindValue(':CC', $centro_custo, PDO::PARAM_INT);
        $st->bindValue(':DESC', $desc, PDO::PARAM_STR);
        $st->bindValue(':NIVEL', $nivel, PDO::PARAM_INT);
        $st->bindValue(':ID', $codpolitCc, PDO::PARAM_INT);
        $st->execute();

        if ($st->rowCount() === 0) {
            jexit(false, [], 'Nenhuma politica encontrada para atualizacao.');
        }

        jexit(true, ['dados' => ['mensagem' => 'Politica atualizada com sucesso.']]);
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

        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_TIPO(
                    p_DESCRICAO => :DESC,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':DESC', $desc);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) jexit(false, [], $pkgResult['s_msg']);
        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg']]]);
    }

    if ($action === 'del_tipo') {
        $id = (int) ($req['id'] ?? 0);
        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_DEL_MEGAG_DESP_TIPO(
                    p_CODTIPODESPESA => :ID,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':ID', $id);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Categoria excluída com sucesso!']]);
    }

    // ============================================
    // DADOS PARA SELECTS (API CARREGAMENTO)
    // ============================================
    if ($action === 'get_doms_aprovador') {
        $ccSql = "SELECT CENTRORESULTADO AS CENTROCUSTO, DESCRICAO AS NOME FROM CONSINCO.ABA_CENTRORESULTADO ORDER BY DESCRICAO";
        $stCC = $conn->prepare(cfg_sql($ccSql)); $stCC->execute();
        $ccs = $stCC->fetchAll(PDO::FETCH_ASSOC);

        $usuSql = "SELECT SEQUSUARIO, NOME FROM CONSINCO.GE_USUARIO ORDER BY NOME";
        $stU = $conn->prepare(cfg_sql($usuSql)); $stU->execute();
        $usus = $stU->fetchAll(PDO::FETCH_ASSOC);

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
        $centro_custo = 0;
        
        if ($cc_str !== '') {
            $centroInfo = buscar_centro_resultado($conn, $cc_str);
            $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);
        }

        $sql = "SELECT DISTINCT A.SEQUSUARIO, A.NOME, A.CODGRUPO,
                               A.CENTROCUSTO AS CENTROCUSTO,
                               C.CENTRORESULTADO AS CODIGO_CC,
                               C.DESCRICAO AS NOME_CC
                  FROM CONSINCO.MEGAG_DESP_APROVADORES A
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON C.CENTRORESULTADO = A.CENTROCUSTO
                 WHERE 1=1";
        
        if ($centro_custo > 0) {
            $sql .= " AND A.CENTROCUSTO = :CC";
        }
        
        if ($grupo !== null) {
            $sql .= " AND A.CODGRUPO = :GRUPO";
        }

        $st = $conn->prepare(cfg_sql($sql));
        if ($centro_custo > 0) $st->bindValue(':CC', $centro_custo);
        if ($grupo !== null) $st->bindValue(':GRUPO', $grupo);
        $st->execute();

        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ============================================
    // APROVADORES X C.C. (MEGAG_DESP_APROVADORES)
    // ============================================
    if ($action === 'list_aprovadores') {
        $sql = "SELECT A.SEQUSUARIO, A.CODGRUPO, A.CENTROCUSTO, C.CENTRORESULTADO AS CODIGO_CC, A.NOME AS GESTOR,
                       G.NOMEGRUPO, TO_CHAR(A.DTAINCLUSAO, 'DD/MM/YYYY') AS DATA_VINCULO
                  FROM CONSINCO.MEGAG_DESP_APROVADORES A
                  LEFT JOIN CONSINCO.MEGAG_DESP_GRUPO G ON A.CODGRUPO = G.CODGRUPO
                  LEFT JOIN CONSINCO.ABA_CENTRORESULTADO C ON C.CENTRORESULTADO = A.CENTROCUSTO
                 ORDER BY A.NOME";
        $st = $conn->prepare(cfg_sql($sql));
        $st->execute();
        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_aprovador') {
        $cc_str = trim($req['centro_custo'] ?? '');
        $gestor_str = trim($req['gestor'] ?? '');
        $seq_usuario_req = (int)($req['sequsuario'] ?? 0);
        $nome_req = trim((string)($req['nome'] ?? ''));
        $codgrupoRaw = $req['codgrupo'] ?? null;
        $codgrupo = ($codgrupoRaw === '' || $codgrupoRaw === null) ? null : (int)$codgrupoRaw;

        if ($cc_str === '' || ($gestor_str === '' && ($seq_usuario_req <= 0 || $nome_req === ''))) {
            jexit(false, [], 'Preencha Centro de Custo e Gestor.');
        }

        $centroInfo = buscar_centro_resultado($conn, $cc_str);
        $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);

        if ($gestor_str !== '') {
            $gestor_parts = explode('|', $gestor_str);
            $seq_usuario = (int) ($gestor_parts[0] ?? 0);
            $nome = trim($gestor_parts[1] ?? '');
        } else {
            $seq_usuario = $seq_usuario_req;
            $nome = $nome_req;
        }

        if ($centro_custo <= 0 || $seq_usuario <= 0 || $nome === '') {
            jexit(false, [], 'Centro de custo ou aprovador invalido.');
        }

        normalizar_vinculo_aprovador($conn, $codgrupo, $seq_usuario, $centro_custo);

        if (existe_vinculo_aprovador($conn, (string)$codgrupo, $seq_usuario, (string)$centro_custo)) {
            jexit(true, ['dados' => ['mensagem' => 'Aprovador ja vinculado a este grupo e centro de custo.']]);
        }

        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_APROVADORES(
                      p_sequsuario         => :SEQ_USU,
                      p_centrocusto        => :CC,
                      p_nome               => :NOME,
                      p_sequusuarioalt     => :USU_ALT,
                      p_dtaalteracao       => NULL,
                      p_codgrupo           => :GRP,
                      s_sfx                => :S_SFX,
                      s_ico                => :S_ICO,
                      s_tiporet            => :S_TIPORET,
                      s_msg                => :S_MSG
                  ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':SEQ_USU', $seq_usuario);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':NOME', $nome);
        $st->bindValue(':USU_ALT', $userIdInt);
        if ($codgrupo === null) {
            $st->bindValue(':GRP', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':GRP', $codgrupo);
        }
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = cfg_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (cfg_pkg_failed($pkgResult)) jexit(false, [], $pkgResult['s_msg']);

        jexit(true, ['dados' => ['mensagem' => $pkgResult['s_msg']]]);
    }

    if ($action === 'del_aprovador_vinculo') {
        $codgrupoRaw = $req['codgrupo'] ?? null;
        $codgrupo = ($codgrupoRaw === '' || $codgrupoRaw === null) ? null : (int)$codgrupoRaw;
        $centroInfo = buscar_centro_resultado($conn, trim($req['centro_custo'] ?? ''));
        $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);
        $sequsuario = (int)($req['sequsuario'] ?? 0);

        if ($centro_custo <= 0 || !$sequsuario) {
            jexit(false, [], 'Parametros invalidos para remover o vinculo.');
        }

        $sql = "DELETE FROM CONSINCO.MEGAG_DESP_APROVADORES
                 WHERE CENTROCUSTO = :SEQCC
                   AND SEQUSUARIO = :SEQ";
        if ($codgrupo === null) {
            $sql .= " AND CODGRUPO IS NULL";
        } else {
            $sql .= " AND CODGRUPO = :GRUPO";
        }
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':SEQCC', $centro_custo);
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
        $centro_custo = (int)($centroInfo['CENTRORESULTADO'] ?? 0);

        if ($centro_custo <= 0) {
            jexit(false, [], 'Parametros invalidos para excluir o centro de custo.');
        }

        $sql = "DELETE FROM CONSINCO.MEGAG_DESP_APROVADORES
                 WHERE CENTROCUSTO = :SEQCC";
        if ($codgrupo === null) {
            $sql .= " AND CODGRUPO IS NULL";
        } else {
            $sql .= " AND CODGRUPO = :GRUPO";
        }

        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':SEQCC', $centro_custo);
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

        $sql = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_DEL_MEGAG_DESP_APROVADORES(
                    p_nome => :NOME,
                    s_sfx => :S_SFX,
                    s_ico => :S_ICO,
                    s_tiporet => :S_TIPORET,
                    s_msg => :S_MSG
                ); END;";
        $st = $conn->prepare(cfg_sql($sql));
        $st->bindValue(':NOME', $nome);
        cfg_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        jexit(true, ['dados' => ['mensagem' => 'Vinculação removida com sucesso.']]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
