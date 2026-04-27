<?php
require_once __DIR__ . '/../routes/check_session.php';
header('Content-Type: application/json; charset=utf-8');
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../bootstrap/db.php';
require_once __DIR__ . '/mg_api_bootstrap.php';
require_once __DIR__ . '/../helpers/firebase.php';
$pathConexao = mg_db_config_path();
if (!$pathConexao) {
    jexit(false, [], "Arquivo de conexão não encontrado!");
}
require_once $pathConexao;

function jexit($ok, $data = [], $erro = null)
{
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'sucesso' => (bool) $ok,
        'dados' => $ok ? ($data['dados'] ?? null) : null,
        'erro' => $ok ? null : ($erro ?? 'Erro desconhecido'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'sucesso' => false,
        'dados' => null,
        'erro' => (string)($error['message'] ?? 'Erro fatal inesperado.'),
    ], JSON_UNESCAPED_UNICODE);
});

function body_json()
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function normalize_uploaded_files(string $field): array
{
    if (empty($_FILES[$field])) {
        return [];
    }

    $file = $_FILES[$field];
    if (!is_array($file['name'])) {
        return [$file];
    }

    $normalized = [];
    $total = count($file['name']);
    for ($i = 0; $i < $total; $i++) {
        $normalized[] = [
            'name' => $file['name'][$i] ?? '',
            'type' => $file['type'][$i] ?? '',
            'tmp_name' => $file['tmp_name'][$i] ?? '',
            'error' => $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'][$i] ?? 0,
        ];
    }

    return $normalized;
}

function desp_extract_centro_custo($value): int
{
    $raw = trim((string)$value);
    if ($raw === '') {
        return 0;
    }

    $parts = explode('|', $raw);
    return (int)trim((string)($parts[0] ?? '0'));
}

function resolve_politica_despesa(PDO $conn, int $centroCusto): int
{
    $sql = mg_with_schema("SELECT DISTINCT CODPOLITICA
              FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO
             WHERE CENTROCUSTO = :CC
             ORDER BY CODPOLITICA");
    $st = $conn->prepare(mg_with_schema($sql));
    $st->bindValue(':CC', $centroCusto, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_COLUMN);

    if (!$rows) {
        throw new Exception('Nenhuma política de aprovação encontrada para o centro de custo informado.');
    }

    $rows = array_values(array_unique(array_map('intval', $rows)));
    if (count($rows) > 1) {
        throw new Exception('Existe mais de uma política vinculada a este centro de custo. Ajuste a configuração antes de lançar a despesa.');
    }

    return (int) $rows[0];
}

function resolve_session_sequsuario(PDO $conn, $sessionUser): int
{
    if (is_numeric($sessionUser)) {
        return (int)$sessionUser;
    }

    $user = strtoupper(trim((string)$sessionUser));
    if ($user === '') {
        throw new Exception('Usuário da sessão inválido.');
    }

    $tentativas = [
        mg_with_schema("SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(LOGINID) = :U AND ROWNUM = 1"),
        mg_with_schema("SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(CODUSUARIO) = :U AND ROWNUM = 1"),
        mg_with_schema("SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(LOGIN) = :U AND ROWNUM = 1"),
        mg_with_schema("SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(NOME) = :U AND ROWNUM = 1"),
    ];

    foreach ($tentativas as $sql) {
        try {
            $st = $conn->prepare(mg_with_schema($sql));
            $st->bindValue(':U', $user);
            $st->execute();
            $seq = (int)($st->fetchColumn() ?: 0);
            if ($seq > 0) {
                return $seq;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    throw new Exception('Não foi possível identificar o SEQUSUARIO do usuário logado.');
}

function resolve_list_mine_user_candidates($sessionUser, int $sessionSeqUsuario): array
{
    $candidates = [$sessionSeqUsuario];
    $sessionUser = strtoupper(trim((string)$sessionUser));

    if ($sessionUser === 'CONSINCO') {
        $candidates[] = 1;
    }

    return array_values(array_unique(array_filter(array_map('intval', $candidates))));
}

function resolve_loginid_by_sequsuario(PDO $conn, int $seqUsuario): string
{
    if ($seqUsuario <= 0) {
        return '';
    }

    $sql = mg_with_schema("SELECT LOGINID
              FROM CONSINCO.GE_USUARIO
             WHERE SEQUSUARIO = :SEQ
               AND ROWNUM = 1");
    $st = $conn->prepare(mg_with_schema($sql));
    $st->bindValue(':SEQ', $seqUsuario, PDO::PARAM_INT);
    $st->execute();

    return trim((string)($st->fetchColumn() ?: ''));
}

function create_notification(PDO $conn, string $usuario, string $tipo, string $titulo, string $mensagem, ?int $taskId = null): void
{
    $usuario = trim($usuario);
    if ($usuario === '') {
        return;
    }

    $sql = "INSERT INTO MEGAG_TASK_NOTIFICACOES
                (ID, USUARIO, TIPO, TITULO, MENSAGEM, TASK_ID, LIDA, CRIADO_EM)
            VALUES
                (SEQ_MEGAG_TASK_NOTIFICACOES.NEXTVAL, :USUARIO, :TIPO, :TITULO, :MENSAGEM, :TASK_ID, 'N', SYSDATE)";
    $st = $conn->prepare($sql);
    $st->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $st->bindValue(':TIPO', $tipo, PDO::PARAM_STR);
    $st->bindValue(':TITULO', $titulo, PDO::PARAM_STR);
    $st->bindValue(':MENSAGEM', $mensagem, PDO::PARAM_STR);
    $st->bindValue(':TASK_ID', $taskId, $taskId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $st->execute();

    // O push via Firebase fica desabilitado aqui para nao comprometer o retorno da API
    // de despesas. O registro interno da notificacao continua gravado normalmente.
}

function desp_bind_pkg_status(PDOStatement $stmt, ?string &$sfx, ?string &$ico, ?string &$tiporet, ?string &$msg): void
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

function desp_pkg_response(string $sfx, string $ico, string $tiporet, string $msg): array
{
    return [
        's_sfx' => trim($sfx),
        's_ico' => trim($ico),
        's_tiporet' => trim($tiporet),
        's_msg' => trim($msg),
    ];
}

function desp_pkg_failed(array $pkgResult): bool
{
    return ($pkgResult['s_tiporet'] ?? '') !== 'S';
}

function desp_request_fingerprint(array $payload, int $seqUsuario): string
{
    $normalized = [
        'seq_usuario' => $seqUsuario,
        'valor' => (string)($payload['valor'] ?? ''),
        'estabelecimento' => mb_strtoupper(trim((string)($payload['estabelecimento'] ?? '')), 'UTF-8'),
        'data_despesa' => (string)($payload['data_despesa'] ?? ''),
        'categoria' => (string)($payload['categoria'] ?? ''),
        'centros_custo' => (string)($payload['centros_custo'] ?? ''),
        'valores_rateio' => (string)($payload['valores_rateio'] ?? ''),
        'vencimento' => (string)($payload['vencimento'] ?? ''),
        'comentario' => trim((string)($payload['comentario'] ?? '')),
        'arquivo_nomes' => array_values(array_map(
            static fn(array $file): string => (string)($file['name'] ?? ''),
            array_merge(
                normalize_uploaded_files('arquivo'),
                normalize_uploaded_files('arquivo[]')
            )
        )),
    ];

    return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function desp_duplicate_submission_guard(string $fingerprint): void
{
    if ($fingerprint === '') {
        return;
    }

    $now = time();
    $windowSeconds = 45;
    $last = $_SESSION['despesa_last_submission'] ?? null;

    if (is_array($last)
        && ($last['fingerprint'] ?? '') === $fingerprint
        && ($now - (int)($last['timestamp'] ?? 0)) <= $windowSeconds) {
        jexit(false, [], 'Ja recebemos uma solicitacao identica ha instantes. Confira a lista antes de enviar novamente.');
    }

    $_SESSION['despesa_last_submission'] = [
        'fingerprint' => $fingerprint,
        'timestamp' => $now,
    ];
}

function desp_find_recent_duplicate(
    PDO $conn,
    int $seqUsuario,
    float $valor,
    string $fornecedor,
    string $dataDespesa,
    int $categoria,
    string $comentario,
    int $centroCusto
): ?array {
    $sql = mg_with_schema("
        SELECT CODDESPESA,
               FORNECEDOR,
               VLRRATDESPESA,
               TO_CHAR(DTADESPESA, 'YYYY-MM-DD') AS DTADESPESA_FORMAT,
               TO_CHAR(DTAINCLUSAO, 'YYYY-MM-DD HH24:MI:SS') AS DTAINCLUSAO_FORMAT
          FROM CONSINCO.MEGAG_DESP
         WHERE USUARIOSOLICITANTE = :USUARIO
           AND CODTIPODESPESA = :TIPO
           AND CENTROCUSTO = :SEQCC
           AND ABS(NVL(VLRRATDESPESA, 0) - :VALOR) < 0.01
           AND UPPER(TRIM(NVL(FORNECEDOR, ' '))) = UPPER(TRIM(:FORNECEDOR))
           AND TRIM(NVL(OBSERVACAO, ' ')) = TRIM(:OBSERVACAO)
           AND TO_CHAR(DTADESPESA, 'YYYY-MM-DD') = :DTADESPESA
           AND DTAINCLUSAO >= (SYSDATE - (2 / 1440))
         ORDER BY CODDESPESA DESC
    ");

    $st = $conn->prepare(mg_with_schema($sql));
    $st->bindValue(':USUARIO', $seqUsuario, PDO::PARAM_INT);
    $st->bindValue(':TIPO', $categoria, PDO::PARAM_INT);
    $st->bindValue(':SEQCC', $centroCusto, PDO::PARAM_INT);
    $st->bindValue(':VALOR', $valor);
    $st->bindValue(':FORNECEDOR', $fornecedor);
    $st->bindValue(':OBSERVACAO', $comentario);
    $st->bindValue(':DTADESPESA', $dataDespesa);
    $st->execute();

    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function notify_pending_approvers_for_despesa(PDO $conn, int $codDespesa, string $solicitanteLogin, string $fornecedor, float $valor): void
{
    $sql = mg_with_schema("SELECT DISTINCT USUARIOAPROVADOR, NIVEL_APROVACAO
              FROM CONSINCO.MEGAG_DESP_APROVACAO
             WHERE CODDESPESA = :ID
               AND STATUS = 'LANCADO'
               AND NIVEL_APROVACAO = (
                    SELECT MIN(NIVEL_APROVACAO)
                      FROM CONSINCO.MEGAG_DESP_APROVACAO
                     WHERE CODDESPESA = :ID2
                       AND STATUS = 'LANCADO'
               )");
    $st = $conn->prepare(mg_with_schema($sql));
    $st->execute([
        ':ID' => $codDespesa,
        ':ID2' => $codDespesa,
    ]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        $loginDestino = resolve_loginid_by_sequsuario($conn, (int)($row['USUARIOAPROVADOR'] ?? 0));
        if ($loginDestino === '') {
            continue;
        }

        if (strcasecmp($loginDestino, $solicitanteLogin) === 0) {
            continue;
        }

        $nivel = (int)($row['NIVEL_APROVACAO'] ?? 0);
        create_notification(
            $conn,
            $loginDestino,
            'DESPESA',
            'Nova despesa aguardando sua aprovação',
            "A despesa EXP-{$codDespesa} de {$solicitanteLogin} foi enviada para sua alçada no nível {$nivel}. Fornecedor: {$fornecedor}. Valor: " . number_format($valor, 2, ',', '.')
        );
    }
}

function is_update_approval_error_message(string $msg): bool
{
    $msgUpper = strtoupper(trim($msg));
    if ($msgUpper === '') {
        return true;
    }

    $bloqueios = [
        'ERRO',
        'SEM PERMISS',
        'FORA DA ORDEM',
        'SOLICITANTE NÃO PODE APROVAR',
        'SOLICITANTE NAO PODE APROVAR',
        'DESPESA JÁ FINALIZADA',
        'DESPESA JA FINALIZADA',
        'NÃO FOI POSSÍVEL',
        'NAO FOI POSSIVEL',
    ];

    foreach ($bloqueios as $trecho) {
        if (strpos($msgUpper, $trecho) !== false) {
            return true;
        }
    }

    return false;
}

function process_approval_action(PDO $conn, int $codDespesa, int $seqUsuario, string $status, string $pago, string $observacao): string
{
    $before = desp_get_approval_state($conn, $codDespesa, $seqUsuario);

    if (!$before) {
        throw new Exception('Despesa nao encontrada.');
    }

    $statusAtual = strtoupper(trim((string)($before['STATUS'] ?? '')));
    if (in_array($statusAtual, ['APROVADO', 'REJEITADO'], true)) {
        throw new Exception('Despesa ja finalizada com status: ' . $statusAtual);
    }

    if ((int)($before['USUARIOSOLICITANTE'] ?? 0) === $seqUsuario) {
        throw new Exception('Solicitante nao pode aprovar a propria despesa.');
    }

    try {
        $status = strtoupper($status) === 'REJEITADO' ? 'REJEITADO' : 'APROVADO';

        $sqlUpdate = mg_with_schema("
            UPDATE CONSINCO.MEGAG_DESP_APROVACAO A
               SET A.STATUS = :STATUS,
                   A.DTAACAO = SYSDATE,
                   A.OBSERVACAO = :OBS
             WHERE A.CODDESPESA = :ID
               AND A.USUARIOAPROVADOR = :USU
               AND A.STATUS = 'LANCADO'
               AND A.NIVEL_APROVACAO = (
                    SELECT MIN(P.NIVEL_APROVACAO)
                      FROM CONSINCO.MEGAG_DESP_APROVACAO P
                     WHERE P.CODDESPESA = A.CODDESPESA
                       AND P.CENTROCUSTO = A.CENTROCUSTO
                       AND P.STATUS = 'LANCADO'
               )
        ");
        $st = $conn->prepare($sqlUpdate);
        $st->bindValue(':STATUS', $status);
        $st->bindValue(':OBS', $observacao !== '' ? $observacao : null);
        $st->bindValue(':ID', $codDespesa, PDO::PARAM_INT);
        $st->bindValue(':USU', $seqUsuario, PDO::PARAM_INT);
        $st->execute();

        if ($st->rowCount() <= 0) {
            throw new Exception('Sem permissao ou fora da ordem de aprovacao.');
        }

        if ($status === 'REJEITADO') {
            $stDesp = $conn->prepare(mg_with_schema("
                UPDATE CONSINCO.MEGAG_DESP
                   SET STATUS = 'REJEITADO',
                       DTAALTERACAO = SYSDATE
                 WHERE CODDESPESA = :ID
            "));
            $stDesp->execute([':ID' => $codDespesa]);
            $conn->exec('COMMIT');
            return 'Despesa rejeitada com sucesso.';
        }

        $stRestante = $conn->prepare(mg_with_schema("
            SELECT COUNT(1)
              FROM CONSINCO.MEGAG_DESP_APROVACAO
             WHERE CODDESPESA = :ID
               AND STATUS = 'LANCADO'
        "));
        $stRestante->execute([':ID' => $codDespesa]);
        $restante = (int)($stRestante->fetchColumn() ?: 0);

        if ($restante === 0) {
            $stDesp = $conn->prepare(mg_with_schema("
                UPDATE CONSINCO.MEGAG_DESP
                   SET STATUS = 'APROVADO',
                       PAGO = :PAGO,
                       DTAALTERACAO = SYSDATE
                 WHERE CODDESPESA = :ID
            "));
            $stDesp->execute([
                ':PAGO' => strtoupper($pago) === 'S' ? 'S' : 'N',
                ':ID' => $codDespesa,
            ]);
            $conn->exec('COMMIT');
            return 'Despesa aprovada com sucesso.';
        }

        $stDesp = $conn->prepare(mg_with_schema("
            UPDATE CONSINCO.MEGAG_DESP
               SET STATUS = 'APROVACAO',
                   DTAALTERACAO = SYSDATE
             WHERE CODDESPESA = :ID
        "));
        $stDesp->execute([':ID' => $codDespesa]);
        $conn->exec('COMMIT');
        return 'Aprovacao registrada. Aguardando proximos niveis.';
    } catch (Throwable $e) {
        try {
            $conn->exec('ROLLBACK');
        } catch (Throwable $rollbackError) {
        }
        throw new Exception("Erro ao processar aprovacao no banco: " . $e->getMessage());
    }
}

function desp_get_approval_state(PDO $conn, int $codDespesa, int $seqUsuario): ?array
{
    $sql = mg_with_schema("
        SELECT D.CODDESPESA,
               D.USUARIOSOLICITANTE,
               D.STATUS,
               (SELECT COUNT(*)
                  FROM CONSINCO.MEGAG_DESP_APROVACAO A
                 WHERE A.CODDESPESA = D.CODDESPESA
                   AND A.USUARIOAPROVADOR = :USU) AS QTD_APROVACOES_USUARIO
          FROM CONSINCO.MEGAG_DESP D
         WHERE D.CODDESPESA = :ID
    ");
    $st = $conn->prepare($sql);
    $st->bindValue(':ID', $codDespesa, PDO::PARAM_INT);
    $st->bindValue(':USU', $seqUsuario, PDO::PARAM_INT);
    $st->execute();

    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

try {
    if (empty($_SESSION['logado']) || empty($_SESSION['usuario'])) {
        jexit(false, [], 'Sessão expirada. Faça login novamente.');
    }

    // $conn é a PDO de Oracle presente no db_connect.php (esperado)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user = $_SESSION['usuario']; // login textual na sessão
    $sessionSeqUsuario = resolve_session_sequsuario($conn, $user);

    // Decide if handling JSON payload or FormData (multipart/form-data)
    $req = body_json();
    if (empty($req) && !empty($_POST)) {
        $req = $_POST;
    }

    $action = $req['action'] ?? '';

    // ============================================
    // AUXILIARES: SELECTS DE DOMÍNIO
    // ============================================
    if ($action === 'get_doms') {
        // Obter tipos de despesas
        $st = $conn->prepare(mg_with_schema("SELECT CODTIPODESPESA, DESCRICAO FROM CONSINCO.MEGAG_DESP_TIPO ORDER BY DESCRICAO"));
        $st->execute();
        $tipos = $st->fetchAll(PDO::FETCH_ASSOC);

        // Obter centros de custo da tabela oficial ABA_CENTRORESULTADO 
        // (conforme procedure PRC_LIST_MEGAG_DESP_CENTRORESULTADO)
        $stCc = $conn->prepare(mg_with_schema("SELECT CENTRORESULTADO AS CENTROCUSTO, DESCRICAO AS NOME FROM CONSINCO.ABA_CENTRORESULTADO ORDER BY DESCRICAO"));
        $stCc->execute();
        $ccs = $stCc->fetchAll(PDO::FETCH_ASSOC);

        // Lista equivalente ao que a PRC_LIST_MEGAG_DESP_FORNECEDOR expõe.
        // Mantido em SELECT direto para evitar limitações de REF CURSOR no driver PDO OCI.

        jexit(true, ['dados' => ['tipos' => $tipos, 'ccs' => $ccs]]);
    }

    if ($action === 'search_fornecedores') {
        $q = trim((string)($req['q'] ?? ''));
        if (strlen($q) < 2) {
            jexit(true, ['dados' => []]);
        }

        $sql = "
            SELECT *
              FROM (
                    SELECT SEQPESSOA,
                           NOMERAZAO,
                           FANTASIA
                      FROM CONSINCO.GE_PESSOA
                     WHERE TRIM(NOMERAZAO) IS NOT NULL
                       AND (
                            UPPER(NOMERAZAO) LIKE UPPER(:Q)
                            OR UPPER(NVL(FANTASIA, '')) LIKE UPPER(:Q)
                       )
                     ORDER BY NOMERAZAO
              )
             WHERE ROWNUM <= 30
        ";
        $stForn = $conn->prepare(mg_with_schema($sql));
        $like = '%' . $q . '%';
        $stForn->bindValue(':Q', $like);
        $stForn->execute();

        jexit(true, ['dados' => $stForn->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'create_fornecedor') {
        $nomeRazao = mb_substr(trim((string)($req['nomerazao'] ?? '')), 0, 100);
        $fantasia = mb_substr(trim((string)($req['fantasia'] ?? '')), 0, 35);
        $palavraChave = mb_substr(trim((string)($req['palavrachave'] ?? '')), 0, 35);
        $cep = trim((string)($req['cep'] ?? ''));
        $fisicaJuridica = strtoupper(substr(trim((string)($req['fisicajuridica'] ?? 'J')), 0, 1));
        $sexo = strtoupper(substr(trim((string)($req['sexo'] ?? '')), 0, 1));
        $cidade = mb_substr(trim((string)($req['cidade'] ?? '')), 0, 60);
        $uf = strtoupper(substr(trim((string)($req['uf'] ?? '')), 0, 2));
        $bairro = mb_substr(trim((string)($req['bairro'] ?? '')), 0, 30);
        $logradouro = mb_substr(trim((string)($req['logradouro'] ?? '')), 0, 35);
        $nroLogradouro = trim((string)($req['nrologradouro'] ?? ''));
        $foneDdd1 = trim((string)($req['foneddd1'] ?? ''));
        $foneNro1 = trim((string)($req['fonenro1'] ?? ''));
        $email = mb_substr(trim((string)($req['email'] ?? '')), 0, 50);
        $inscricaoRg = mb_substr(trim((string)($req['inscricaorg'] ?? '')), 0, 20);
        $dtaAtivacao = trim((string)($req['dtaativacao'] ?? ''));
        $documento = preg_replace('/\D+/', '', (string)($req['documento'] ?? ''));

        if ($nomeRazao === '') {
            jexit(false, [], 'Razao social/nome e obrigatorio.');
        }
        if ($cidade === '') {
            jexit(false, [], 'Cidade e obrigatoria.');
        }
        if ($uf === '' || strlen($uf) !== 2) {
            jexit(false, [], 'UF invalida.');
        }
        if (!in_array($fisicaJuridica, ['F', 'J'], true)) {
            jexit(false, [], 'Tipo de pessoa invalido.');
        }
        if (strlen($documento) < 11) {
            jexit(false, [], 'Documento invalido. Informe CPF ou CNPJ completo.');
        }

        $nroCgcCpf = substr($documento, 0, -2);
        $digCgcCpf = substr($documento, -2);
        if ($nroCgcCpf === '' || $digCgcCpf === '') {
            jexit(false, [], 'Documento invalido. Nao foi possivel separar numero e digito.');
        }

        if ($fisicaJuridica === 'J') {
            $sexo = null;
        } elseif (!in_array($sexo, ['M', 'F'], true)) {
            $sexo = null;
        }

        if ($palavraChave === '') {
            $palavraChave = $fantasia !== '' ? $fantasia : $nomeRazao;
        }

        $dtaAtivacaoFormat = 'YYYY-MM-DD';
        if ($dtaAtivacao !== '' && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dtaAtivacao)) {
            $dtaAtivacaoFormat = 'YYYY-MM-DD"T"HH24:MI';
        } elseif ($dtaAtivacao !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dtaAtivacao)) {
            $dtaAtivacaoFormat = 'YYYY-MM-DD HH24:MI';
        } elseif ($dtaAtivacao === '') {
            $dtaAtivacao = date('Y-m-d H:i');
            $dtaAtivacaoFormat = 'YYYY-MM-DD HH24:MI';
        }

        $sql = "BEGIN
                  " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_FORNECEDOR(
                      p_NOMERAZAO      => :NOMERAZAO,
                      p_FANTASIA       => :FANTASIA,
                      p_PALAVRACHAVE   => :PALAVRACHAVE,
                      p_CEP            => :CEP,
                      p_FISICAJURIDICA => :FISICAJURIDICA,
                      p_SEXO           => :SEXO,
                      p_NROCGCCPF      => :NROCGCCPF,
                      p_DIGCGCCPF      => :DIGCGCCPF,
                      p_CIDADE         => :CIDADE,
                      p_UF             => :UF,
                      p_BAIRRO         => :BAIRRO,
                      p_LOGRADOURO     => :LOGRADOURO,
                      p_NROLOGRADOURO  => :NROLOGRADOURO,
                      p_FONEDDD1       => :FONEDDD1,
                      p_FONENRO1       => :FONENRO1,
                      p_EMAIL          => :EMAIL,
                      p_INSCRICAORG    => :INSCRICAORG,
                      p_DTAATIVACAO    => TO_DATE(:DTAATIVACAO, '{$dtaAtivacaoFormat}'),
                      s_sfx            => :S_SFX,
                      s_ico            => :S_ICO,
                      s_tiporet        => :S_TIPORET,
                      s_msg            => :S_MSG
                  );
                END;";

        $st = $conn->prepare(mg_with_schema($sql));
        $st->bindValue(':NOMERAZAO', $nomeRazao);
        $st->bindValue(':FANTASIA', $fantasia !== '' ? $fantasia : null);
        $st->bindValue(':PALAVRACHAVE', $palavraChave !== '' ? $palavraChave : null);
        $st->bindValue(':CEP', $cep !== '' ? preg_replace('/\D+/', '', $cep) : null);
        $st->bindValue(':FISICAJURIDICA', $fisicaJuridica);
        $st->bindValue(':SEXO', $sexo);
        $st->bindValue(':NROCGCCPF', $nroCgcCpf);
        $st->bindValue(':DIGCGCCPF', $digCgcCpf);
        $st->bindValue(':CIDADE', $cidade);
        $st->bindValue(':UF', $uf);
        $st->bindValue(':BAIRRO', $bairro !== '' ? $bairro : null);
        $st->bindValue(':LOGRADOURO', $logradouro !== '' ? $logradouro : null);
        $st->bindValue(':NROLOGRADOURO', $nroLogradouro !== '' ? $nroLogradouro : null);
        $st->bindValue(':FONEDDD1', $foneDdd1 !== '' ? preg_replace('/\D+/', '', $foneDdd1) : null);
        $st->bindValue(':FONENRO1', $foneNro1 !== '' ? preg_replace('/\D+/', '', $foneNro1) : null);
        $st->bindValue(':EMAIL', $email !== '' ? $email : null);
        $st->bindValue(':INSCRICAORG', $inscricaoRg !== '' ? $inscricaoRg : null);
        $st->bindValue(':DTAATIVACAO', $dtaAtivacao);

        $pkgSfx = '';
        $pkgIco = '';
        $pkgTipoRet = '';
        $pkgMsg = '';
        desp_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        $st->execute();
        $pkgResult = desp_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);

        $fornecedorPayload = [
            'value' => $nomeRazao,
            'text' => $fantasia !== '' && $fantasia !== $nomeRazao ? ($nomeRazao . ' (' . $fantasia . ')') : $nomeRazao,
            'nomerazao' => $nomeRazao,
            'fantasia' => $fantasia,
            'documento' => $documento,
            'mensagem' => $pkgResult['s_msg'],
            'pkg' => $pkgResult,
        ];

        if (($pkgResult['s_tiporet'] ?? '') === 'S') {
            jexit(true, ['dados' => $fornecedorPayload]);
        }

        if (($pkgResult['s_tiporet'] ?? '') === 'A' && stripos((string)$pkgResult['s_msg'], 'FORNECEDOR JA CADASTRADO') !== false) {
            $fornecedorPayload['ja_existia'] = true;
            jexit(true, ['dados' => $fornecedorPayload]);
        }

        jexit(false, [], $pkgResult['s_msg'] !== '' ? $pkgResult['s_msg'] : 'Falha ao cadastrar fornecedor.');
    }

    // ============================================
    // CRIAR DESPESA
    // ============================================
    if ($action === 'create') {
        // Lógica robusta para parse monetário (BR e Internacional)
        $valorRaw = str_replace(['R$', ' '], '', $req['valor'] ?? '0');
        if (strpos($valorRaw, ',') !== false) {
            // Formato brasileiro: 1.234,56 -> 1234.56
            $valorRaw = str_replace('.', '', $valorRaw);
            $valorRaw = str_replace(',', '.', $valorRaw);
        }
        $vlr = (float) $valorRaw;

        $forn = trim($req['estabelecimento'] ?? '');
        $data = trim($req['data_despesa'] ?? '');
        $tipo = (int) ($req['categoria'] ?? 0);
        $venc = trim($req['vencimento'] ?? '');
        $obs = trim($req['comentario'] ?? '');

        // Suporta múltiplos centros de custo enviados como JSON array em 'centros_custo'
        $centros_custo_raw = [];
        if (!empty($req['centros_custo'])) {
            $centros_custo_raw = json_decode($req['centros_custo'], true);
        }
        // Fallback: campo legado 'centro_custo' (1º CC)
        if (empty($centros_custo_raw)) {
            $cc_str = trim($req['centro_custo'] ?? '');
            if ($cc_str !== '') $centros_custo_raw = [$cc_str];
        }

        if ($tipo === 0)
            jexit(false, [], 'Categoria é obrigatória.');
        if ($vlr <= 0)
            jexit(false, [], 'Valor inválido.');
        if (empty($centros_custo_raw))
            jexit(false, [], 'Centro de custo é obrigatório.');

        // 1º CC → usado no insert principal (MEGAG_DESP)
        $centro_custo = desp_extract_centro_custo($centros_custo_raw[0]);
        if ($centro_custo <= 0) {
            jexit(false, [], 'Centro de custo invalido.');
        }
        $cod_politica = resolve_politica_despesa($conn, $centro_custo);

        // USUARIO logado: para testes, enviamos 1 se não for numero
        $usr_solicitante = $sessionSeqUsuario;
        $submissionFingerprint = desp_request_fingerprint($req, $usr_solicitante);
        desp_duplicate_submission_guard($submissionFingerprint);
        $duplicateDespesa = desp_find_recent_duplicate(
            $conn,
            $usr_solicitante,
            (float)$vlr,
            $forn,
            $data,
            $tipo,
            $obs,
            $centro_custo
        );
        if ($duplicateDespesa) {
            jexit(false, [], 'Ja existe uma despesa identica gravada ha poucos instantes (EXP-' . (int)$duplicateDespesa['CODDESPESA'] . '). Confira a lista antes de reenviar.');
        }

        // Upload verification
        $uploadedFiles = [];
        $rawFiles = array_merge(
            normalize_uploaded_files('arquivo'),
            normalize_uploaded_files('arquivo[]')
        );
        $uploadDir = __DIR__ . '/../uploads/';
        if (!empty($rawFiles) && !is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($rawFiles as $file) {
            $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                jexit(false, [], 'Erro ao processar um dos arquivos enviados.');
            }

            $ext = pathinfo((string)$file['name'], PATHINFO_EXTENSION);
            $safeExt = $ext !== '' ? '.' . strtolower($ext) : '';
            $storedName = 'despesa_' . time() . '_' . rand(1000, 9999) . '_' . count($uploadedFiles) . $safeExt;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
                jexit(false, [], 'Erro ao salvar um dos arquivos no servidor.');
            }

            $uploadedFiles[] = [
                'original_name' => (string)$file['name'],
                'stored_name' => $storedName,
                'type' => (string)($file['type'] ?? ''),
            ];
        }

        $fileNameParam = $uploadedFiles[0]['stored_name'] ?? null;

        $sql = "BEGIN
                  " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP(
                      p_USUARIOSOLICITANTE   => :USU,
                      p_CODTIPODESPESA       => :TIPO,
                      p_PAGO                 => 'N',
                      p_VLRRATDESPESA        => :VLR,
                      p_FORNECEDOR           => :FORN,
                      p_NOMEARQUIVO          => :NOMEARQ,
                      p_OBSERVACAO           => :OBS,
                      p_CENTROCUSTO          => :CC,
                      p_STATUS               => 'LANCADO',
                      p_DESCRICAOCENTROCUSTO => NULL,
                      p_CODPOLITICA          => :CODPOL,
                      p_DTAVENCIMENTO        => TO_DATE(:VENC, 'YYYY-MM-DD'),
                      p_DTADESPESA           => TO_DATE(:DTADESP, 'YYYY-MM-DD'),
                      p_CODDESPESA_OUT       => :OUT_ID,
                      s_sfx                  => :S_SFX,
                      s_ico                  => :S_ICO,
                      s_tiporet              => :S_TIPORET,
                      s_msg                  => :S_MSG
                  );
                END;";

        $st = $conn->prepare(mg_with_schema($sql));
        $st->bindValue(':USU', $usr_solicitante);
        $st->bindValue(':TIPO', $tipo);
        $st->bindValue(':VLR', (float) $vlr);
        $st->bindValue(':FORN', $forn);
        $st->bindValue(':NOMEARQ', $fileNameParam);
        $st->bindValue(':OBS', $obs);
        $st->bindValue(':CC', $centro_custo);
        $st->bindValue(':CODPOL', $cod_politica);
        $st->bindValue(':VENC', $venc !== '' ? $venc : null);
        $st->bindValue(':DTADESP', $data !== '' ? $data : null);

        $out_id = 0;
        $pkgSfx = '';
        $pkgIco = '';
        $pkgTipoRet = '';
        $pkgMsg = '';
        $st->bindParam(':OUT_ID', $out_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
        desp_bind_pkg_status($st, $pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);

        $st->execute();
        $pkgResult = desp_pkg_response($pkgSfx, $pkgIco, $pkgTipoRet, $pkgMsg);
        if (desp_pkg_failed($pkgResult)) {
            jexit(false, [], $pkgResult['s_msg'] !== '' ? $pkgResult['s_msg'] : 'Falha ao cadastrar despesa.');
        }

        // Se gravou a despesa e tem arquivo(s), grava na tabela de arquivos anexa (PKG: PRC_INS_MEGAG_DESP_ARQUIVO)
        if ($out_id > 0 && !empty($uploadedFiles)) {
            $sqlArq = "BEGIN
                        " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_ARQUIVO(
                            p_CODDESPESA     => :COD_DESP,
                            p_NOMEARQUIVO    => :NOME_ARQ,
                            p_TIPOARQUIVO    => :TIPO_ARQ,
                            p_CODARQUIVO_OUT => :OUT_ARQ,
                            s_sfx            => :S_SFX,
                            s_ico            => :S_ICO,
                            s_tiporet        => :S_TIPORET,
                            s_msg            => :S_MSG
                        );
                      END;";
            foreach ($uploadedFiles as $uploadedFile) {
                $stArq = $conn->prepare(mg_with_schema($sqlArq));
                $stArq->bindValue(':COD_DESP', $out_id);
                $stArq->bindValue(':NOME_ARQ', $uploadedFile['stored_name']);
                $stArq->bindValue(':TIPO_ARQ', $uploadedFile['type']);
                $out_arq_id = 0;
                $pkgArqSfx = '';
                $pkgArqIco = '';
                $pkgArqTipoRet = '';
                $pkgArqMsg = '';
                $stArq->bindParam(':OUT_ARQ', $out_arq_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                desp_bind_pkg_status($stArq, $pkgArqSfx, $pkgArqIco, $pkgArqTipoRet, $pkgArqMsg);
                $stArq->execute();
                $pkgArqResult = desp_pkg_response($pkgArqSfx, $pkgArqIco, $pkgArqTipoRet, $pkgArqMsg);
                if (desp_pkg_failed($pkgArqResult)) {
                    jexit(false, [], $pkgArqResult['s_msg'] !== '' ? $pkgArqResult['s_msg'] : 'Falha ao vincular arquivo da despesa.');
                }
            }
        }

        // -------------------------------------------------------
        // Valores individuais por CC (fornecidos pelo usuário)
        // -------------------------------------------------------
        $valores_rateio_raw = [];
        if (!empty($req['valores_rateio'])) {
            $valores_rateio_raw = json_decode($req['valores_rateio'], true);
        }

        // Inserir TODOS os centros de custo no MEGAG_DESP_RATEIO
        if ($out_id > 0 && !empty($centros_custo_raw)) {
            $qtd_ccs = count($centros_custo_raw);

            // Se o usuário forneceu valores individuais e o array tem o tamanho certo → usa-os
            $usa_valores_individuais = (
                !empty($valores_rateio_raw) &&
                count($valores_rateio_raw) === $qtd_ccs
            );

            // Fallback: divisão igualitária
            if (!$usa_valores_individuais) {
                $vlr_base   = round($vlr / $qtd_ccs, 2);
                $vlr_ultimo = round($vlr - ($vlr_base * ($qtd_ccs - 1)), 2);
            }

            $sqlRateio = "BEGIN
                            " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_RATEIO(
                                p_coddespesa         => :COD_DESP,
                                p_centrocusto        => :CC,
                                p_valorrateio        => :VLR_RAT,
                                p_codrateio          => :OUT_RAT,
                                s_sfx                => :S_SFX,
                                s_ico                => :S_ICO,
                                s_tiporet            => :S_TIPORET,
                                s_msg                => :S_MSG
                            );
                          END;";

            // Preparação fora do loop para performance e segurança
            $stRat = $conn->prepare(mg_with_schema($sqlRateio));

            foreach ($centros_custo_raw as $idx => $cc_item) {
                $cc_val = desp_extract_centro_custo($cc_item);

                if ($usa_valores_individuais) {
                    $vlr_rat = (float)($valores_rateio_raw[$idx] ?? 0);
                } else {
                    $vlr_rat = ($idx === $qtd_ccs - 1) ? $vlr_ultimo : $vlr_base;
                }

                $out_rat_id = 0;
                $pkgRatSfx = '';
                $pkgRatIco = '';
                $pkgRatTipoRet = '';
                $pkgRatMsg = '';

                $stRat->bindValue(':COD_DESP', (int)$out_id, PDO::PARAM_INT);
                $stRat->bindValue(':CC', $cc_val);
                $stRat->bindValue(':VLR_RAT', $vlr_rat);
                $stRat->bindParam(':OUT_RAT', $out_rat_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                desp_bind_pkg_status($stRat, $pkgRatSfx, $pkgRatIco, $pkgRatTipoRet, $pkgRatMsg);

                $stRat->execute();

                $pkgRatResult = desp_pkg_response($pkgRatSfx, $pkgRatIco, $pkgRatTipoRet, $pkgRatMsg);
                if (desp_pkg_failed($pkgRatResult)) {
                    jexit(false, [], $pkgRatResult['s_msg'] !== '' ? $pkgRatResult['s_msg'] : 'Falha ao gravar rateio da despesa.');
                }
            }
        }

        $conn->exec('COMMIT');

        if ($out_id > 0) {
            try {
                $sqlAproRecomp = "BEGIN " . mg_package('PKG_MEGAG_DESP_CADASTRO') . ".PRC_INS_MEGAG_DESP_APROVACAO(:ID, :S_SFX, :S_ICO, :S_TIPORET, :S_MSG); END;";
                $stApr = $conn->prepare(mg_with_schema($sqlAproRecomp));
                $stApr->bindValue(':ID', $out_id);
                $p_sfx = ''; $p_ico = ''; $p_tiporet = ''; $p_msg = '';
                desp_bind_pkg_status($stApr, $p_sfx, $p_ico, $p_tiporet, $p_msg);
                $stApr->execute();
            } catch (Exception $e) {
                // Erro silencioso no recalculo para nao travar a criacao
            }
        }

        $solicitanteLogin = resolve_loginid_by_sequsuario($conn, $usr_solicitante);
        notify_pending_approvers_for_despesa(
            $conn,
            (int)$out_id,
            $solicitanteLogin !== '' ? $solicitanteLogin : (string)$user,
            $forn,
            (float)$vlr
        );

        jexit(true, ['dados' => ['id' => $out_id, 'mensagem' => 'Despesa cadastrada com sucesso!']]);
    }

    // ============================================
    // ATUALIZAR STATUS DE APROVAÇÃO
    // ============================================
    if ($action === 'update_approval') {
        $id = (int)($req['id'] ?? 0);
        $status = trim($req['status'] ?? '');
        $pago = trim($req['pago'] ?? 'N');
        $obs = trim($req['observacao'] ?? '');
        $usr_aprovador = $sessionSeqUsuario;

        if (!$id || !$status) jexit(false, [], 'ID e Status sao obrigatorios.');

        $msg = process_approval_action(
            $conn,
            $id,
            $usr_aprovador,
            strtoupper($status),
            strtoupper($pago) === 'S' ? 'S' : 'N',
            $obs
        );
        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    // ============================================
    // LISTAR MINHAS DESPESAS (Para despesas.php)
    // ============================================
    if ($action === 'list_mine') {
        $usuariosSolicitante = resolve_list_mine_user_candidates($user, $sessionSeqUsuario);
        $placeholders = [];
        $paramsMine = [];
        foreach ($usuariosSolicitante as $idx => $seqSolicitante) {
            $ph = ':U' . $idx;
            $placeholders[] = $ph;
            $paramsMine[$ph] = $seqSolicitante;
        }

        $sql = "SELECT D.*,
                       TO_CHAR(D.DTAINCLUSAO, 'YYYY-MM-DD HH24:MI:SS') as DTAINCLUSAO_FORMAT,
                       TO_CHAR(D.DTAVENCIMENTO, 'YYYY-MM-DD') as DTAVENCIMENTO_FORMAT,
                       TO_CHAR(D.DTADESPESA, 'YYYY-MM-DD') as DTADESPESA_FORMAT,
                       (SELECT DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO C WHERE C.CENTRORESULTADO = D.CENTROCUSTO AND ROWNUM = 1) as DESC_CC,
                       (SELECT CENTRORESULTADO FROM CONSINCO.ABA_CENTRORESULTADO C WHERE C.CENTRORESULTADO = D.CENTROCUSTO AND ROWNUM = 1) as CODIGO_CC,
                       (SELECT DESCRICAO FROM CONSINCO.MEGAG_DESP_TIPO T WHERE T.CODTIPODESPESA = D.CODTIPODESPESA AND ROWNUM = 1) as DESC_TIPO,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_RATEIO R WHERE R.CODDESPESA = D.CODDESPESA) as QTD_RATEIO,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_ARQUIVO ARQ WHERE ARQ.CODDESPESA = D.CODDESPESA) as QTD_ARQUIVOS,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_APROVACAO A WHERE A.CODDESPESA = D.CODDESPESA) as QTD_APROVACOES
                FROM CONSINCO.MEGAG_DESP D 
                WHERE D.USUARIOSOLICITANTE IN (" . implode(', ', $placeholders) . ")
                ORDER BY D.DTAINCLUSAO DESC";
        $st = $conn->prepare(mg_with_schema($sql));
        $st->execute($paramsMine);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $metrics = [
            'total' => 0,
            'total_valor' => 0,
            'em_aprovacao' => 0,
            'em_aprovacao_valor' => 0,
            'reembolsado' => 0,
            'reembolsado_valor' => 0,
            'reprovado' => 0,
            'reprovado_valor' => 0
        ];

        foreach ($rows as &$r) {
            $v = (float) $r['VLRRATDESPESA'];
            
            // LÓGICA DE STATUS DINÂMICO:
            // Se o status é 'LANCADO' mas já existem registros de aprovação, consideramos 'EM_APROVACAO'
            if ($r['STATUS'] === 'LANCADO' && (int)$r['QTD_APROVACOES'] > 0) {
                $r['STATUS'] = 'EM_APROVACAO';
            }

            $metrics['total']++;
            $metrics['total_valor'] += $v;

            $status = strtoupper($r['STATUS'] ?? '');
            if ($status === 'LANCADO' || $status === 'EM_APROVACAO' || $status === 'APROVACAO') {
                $metrics['em_aprovacao']++;
                $metrics['em_aprovacao_valor'] += $v;
            } else if ($status === 'APROVADO' || strtoUpper($r['PAGO'] ?? '') === 'S' || $status === 'REEMBOLSADO') {
                $metrics['reembolsado']++;
                $metrics['reembolsado_valor'] += $v;
            } else if ($status === 'REJEITADO' || $status === 'REPROVADO') {
                $metrics['reprovado']++;
                $metrics['reprovado_valor'] += $v;
            }
        }

        jexit(true, ['dados' => ['dados' => $rows, 'metricas' => $metrics]]);
    }

    if ($action === 'list_approvals') {
        $usr_aprovador = $sessionSeqUsuario;

        // Query direta adaptada da lógica da procedure para evitar erros de cursor no driver pdo_oci
        $sql = "SELECT desp.*,
                       hist.CENTROCUSTO_APROVACAO,
                       hist.NIVEL_PENDENTE,
                       hist.STATUS_APROVADOR,
                       hist.TEM_PENDENTE,
                       hist.APROVOU_HOJE,
                       hist.REPROVOU_HOJE,
                       TO_CHAR(desp.DTAVENCIMENTO, 'YYYY-MM-DD') as DTAVENCIMENTO_FORMAT,
                       TO_CHAR(desp.DTADESPESA, 'YYYY-MM-DD') as DTADESPESA_FORMAT,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_APROVACAO A WHERE A.CODDESPESA = desp.CODDESPESA) as QTD_APROVACOES
                  FROM CONSINCO.MEGAG_DESP desp
                  JOIN (
                        SELECT apr.CODDESPESA,
                               MIN(CASE WHEN apr.STATUS = 'LANCADO' THEN apr.CENTROCUSTO END) AS CENTROCUSTO_APROVACAO,
                               MIN(CASE WHEN apr.STATUS = 'LANCADO' THEN apr.NIVEL_APROVACAO END) AS NIVEL_PENDENTE,
                               MAX(CASE WHEN apr.STATUS = 'LANCADO' THEN 1 ELSE 0 END) AS TEM_PENDENTE,
                               MAX(CASE WHEN apr.STATUS = 'APROVADO' AND TRUNC(apr.DTAACAO) = TRUNC(SYSDATE) THEN 1 ELSE 0 END) AS APROVOU_HOJE,
                               MAX(CASE WHEN apr.STATUS = 'REJEITADO' AND TRUNC(apr.DTAACAO) = TRUNC(SYSDATE) THEN 1 ELSE 0 END) AS REPROVOU_HOJE,
                               CASE
                                   WHEN MAX(CASE WHEN apr.STATUS = 'REJEITADO' THEN 1 ELSE 0 END) = 1 THEN 'REJEITADO'
                                   WHEN MAX(CASE WHEN apr.STATUS = 'LANCADO' THEN 1 ELSE 0 END) = 1 THEN 'LANCADO'
                                   WHEN MAX(CASE WHEN apr.STATUS = 'APROVADO' THEN 1 ELSE 0 END) = 1 THEN 'APROVADO'
                                   ELSE MAX(apr.STATUS)
                               END AS STATUS_APROVADOR
                          FROM CONSINCO.MEGAG_DESP_APROVACAO apr
                         WHERE apr.USUARIOAPROVADOR = :U
                         GROUP BY apr.CODDESPESA
                  ) hist
                    ON hist.CODDESPESA = desp.CODDESPESA
                 WHERE desp.USUARIOSOLICITANTE <> :U
                 ORDER BY hist.TEM_PENDENTE DESC,
                          desp.DTAINCLUSAO DESC,
                          hist.NIVEL_PENDENTE ASC NULLS LAST";
        
        $st = $conn->prepare(mg_with_schema($sql));
        $st->execute([':U' => $usr_aprovador]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer os dados com descrições (conforme esperado pelo frontend)
        foreach ($rows as &$r) {
            // Lógica de Status Dinâmico
            $r['STATUS'] = $r['STATUS_APROVADOR'] ?? $r['STATUS'];
            if ($r['STATUS'] === 'LANCADO' && (int)($r['TEM_PENDENTE'] ?? 0) === 1) {
                $r['STATUS'] = 'EM_APROVACAO';
            }

            $r['DTAINCLUSAO_FORMAT'] = date('Y-m-d H:i:s', strtotime($r['DTAINCLUSAO'] ?? ''));
            
            // Busca nome do solicitante
            $stUsr = $conn->prepare(mg_with_schema("SELECT NOME FROM CONSINCO.GE_USUARIO WHERE SEQUSUARIO = :S AND ROWNUM = 1"));
            $stUsr->execute([':S' => $r['USUARIOSOLICITANTE']]);
            $r['NOME_SOLICITANTE'] = $stUsr->fetchColumn() ?: 'Usuário';

            // Busca descrição do Centro de Custo
            $stCc = $conn->prepare(mg_with_schema("SELECT DESCRICAO, CENTRORESULTADO FROM CONSINCO.ABA_CENTRORESULTADO WHERE CENTRORESULTADO = :C AND ROWNUM = 1"));
            $stCc->execute([':C' => $r['CENTROCUSTO']]);
            $ccInfo = $stCc->fetch(PDO::FETCH_ASSOC) ?: [];
            $r['DESC_CC'] = $ccInfo['DESCRICAO'] ?? 'Centro de Custo';
            $r['CODIGO_CC'] = $ccInfo['CENTRORESULTADO'] ?? ($r['CENTROCUSTO'] ?? null);

            // A categoria já vem no campo DESCRICAO da tabela MEGAG_DESP
            $r['DESC_TIPO'] = $r['DESCRICAO'];
        }

        $metrics = [
            'pendentes' => 0,
            'aprovadas_hoje' => 0,
            'reprovadas_hoje' => 0
        ];

        foreach ($rows as $r) {
            if ((int)($r['TEM_PENDENTE'] ?? 0) === 1) {
                $metrics['pendentes']++;
            }
            if ((int)($r['APROVOU_HOJE'] ?? 0) === 1) {
                $metrics['aprovadas_hoje']++;
            }
            if ((int)($r['REPROVOU_HOJE'] ?? 0) === 1) {
                $metrics['reprovadas_hoje']++;
            }
        }

        jexit(true, ['dados' => ['dados' => $rows, 'metricas' => $metrics]]);
    }

    // ============================================
    // DASHBOARD / RELATÓRIOS (ITEM 2)
    // ============================================
    if ($action === 'get_dashboard_data') {
        // Geral: Total por Categoria
        $sqlCat = "SELECT T.DESCRICAO, SUM(D.VLRRATDESPESA) as TOTAL
                     FROM CONSINCO.MEGAG_DESP D
                     JOIN CONSINCO.MEGAG_DESP_TIPO T ON D.CODTIPODESPESA = T.CODTIPODESPESA
                    GROUP BY T.DESCRICAO";
        $stCat = $conn->prepare(mg_with_schema($sqlCat)); $stCat->execute();
        $byCategory = $stCat->fetchAll(PDO::FETCH_ASSOC);

        // Mensal: Evolução
        $sqlMes = "SELECT TO_CHAR(DTAINCLUSAO, 'MM/YYYY') as MES, SUM(VLRRATDESPESA) as TOTAL
                     FROM CONSINCO.MEGAG_DESP
                    GROUP BY TO_CHAR(DTAINCLUSAO, 'MM/YYYY')
                    ORDER BY TO_CHAR(DTAINCLUSAO, 'YYYY-MM')";
        $stMes = $conn->prepare(mg_with_schema($sqlMes)); $stMes->execute();
        $evolution = $stMes->fetchAll(PDO::FETCH_ASSOC);

        // Top Centros de Custo
        $sqlCC = "SELECT CENTROCUSTO, SUM(VLRRATDESPESA) as TOTAL
                    FROM CONSINCO.MEGAG_DESP
                   GROUP BY CENTROCUSTO
                   ORDER BY TOTAL DESC FETCH FIRST 5 ROWS ONLY";
        $stCC = $conn->prepare(mg_with_schema($sqlCC)); $stCC->execute();
        $topCC = $stCC->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => [
            'byCategory' => $byCategory,
            'evolution'  => $evolution,
            'topCC'      => $topCC
        ]]);
    }

    // ============================================
    // TRILHA DE AUDITORIA / HISTÓRICO (ITEM 3)
    // ============================================
    if ($action === 'get_history') {
        // Tenta pegar o ID de todas as fontes possíveis para garantir
        $id_desp = 0;
        if (isset($req['id'])) $id_desp = (int)$req['id'];
        elseif (isset($_POST['id'])) $id_desp = (int)$_POST['id'];
        elseif (isset($_GET['id'])) $id_desp = (int)$_GET['id'];

        if (!$id_desp) jexit(false, [], 'ID da despesa não fornecido.');

        $sql = "SELECT H.*, 
                       H.CODDESPESA,
                       H.NIVEL_APROVACAO,
                       TO_CHAR(H.DTAACAO, 'DD/MM/YYYY HH24:MI') as DTAACAO_FORMAT,
                       U.NOME as NOME_APROVADOR
                  FROM CONSINCO.MEGAG_DESP_APROVACAO H
                  LEFT JOIN CONSINCO.GE_USUARIO U ON H.USUARIOAPROVADOR = U.SEQUSUARIO
                 WHERE H.CODDESPESA = :ID
                 ORDER BY H.DTAACAO ASC";
        
        $st = $conn->prepare(mg_with_schema($sql));
        $st->execute([':ID' => $id_desp]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        // Retorna sucesso com os dados encontrados
        jexit(true, ['dados' => $rows]);
    }

    if ($action === 'get_attachments') {
        $id = (int)($req['id'] ?? 0);
        if (!$id) {
            jexit(false, [], 'ID da despesa inválido.');
        }

        $sql = "SELECT CODARQUIVO,
                       CODDESPESA,
                       NOMEARQUIVO,
                       TIPOARQUIVO,
                       TO_CHAR(DTAINCLUSAO, 'YYYY-MM-DD HH24:MI:SS') AS DTAINCLUSAO_FORMAT
                  FROM CONSINCO.MEGAG_DESP_ARQUIVO
                 WHERE CODDESPESA = :ID
                 ORDER BY CODARQUIVO";
        $st = $conn->prepare(mg_with_schema($sql));
        $st->execute([':ID' => $id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $stDesp = $conn->prepare(mg_with_schema("SELECT NOMEARQUIVO FROM CONSINCO.MEGAG_DESP WHERE CODDESPESA = :ID"));
            $stDesp->execute([':ID' => $id]);
            $fallback = $stDesp->fetch(PDO::FETCH_ASSOC);
            if (!empty($fallback['NOMEARQUIVO'])) {
                $rows[] = [
                    'CODARQUIVO' => 0,
                    'CODDESPESA' => $id,
                    'NOMEARQUIVO' => $fallback['NOMEARQUIVO'],
                    'TIPOARQUIVO' => '',
                    'DTAINCLUSAO_FORMAT' => null,
                ];
            }
        }

        jexit(true, ['dados' => $rows]);
    }

    // ============================================
    // RATEIO DE DESPESA
    // ============================================
    if ($action === 'get_rateio') {
        $id = (int)($req['id'] ?? 0);
        if (!$id) jexit(false, [], 'ID da despesa inválido.');

                $sql = "SELECT
                    R.CODRATEIO,
                    R.CODDESPESA,
                    R.CENTROCUSTO,
                    R.VALORRATEIO,
                    (SELECT AC.DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO AC
                     WHERE AC.CENTRORESULTADO = R.CENTROCUSTO AND ROWNUM = 1) AS DESC_CC,
                    (SELECT AC.CENTRORESULTADO FROM CONSINCO.ABA_CENTRORESULTADO AC
                     WHERE AC.CENTRORESULTADO = R.CENTROCUSTO AND ROWNUM = 1) AS CODIGO_CC
                FROM CONSINCO.MEGAG_DESP_RATEIO R
                WHERE R.CODDESPESA = :ID
                ORDER BY R.CENTROCUSTO";
        $st = $conn->prepare(mg_with_schema($sql));
        $st->execute([':ID' => $id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        jexit(true, ['dados' => $rows]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}



