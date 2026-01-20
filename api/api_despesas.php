<?php
require_once __DIR__ . '/../routes/check_session.php';

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config/db_connect.php';

function jexit($ok, $data = [], $erro = null){
    echo json_encode([
        'sucesso' => (bool)$ok,
        'dados'   => $ok ? ($data['dados'] ?? null) : null,
        'historico' => $ok ? ($data['historico'] ?? null) : null,
        'erro'    => $ok ? null : ($erro ?? 'Erro desconhecido'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function body_json(){
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function require_login(){
    if (empty($_SESSION['logado']) || empty($_SESSION['usuario'])) {
        jexit(false, [], 'Sessão expirada. Faça login novamente.');
    }
}

function can($app, $acao){
    // Se seu projeto já tem temPermissao(), use.
    if (function_exists('temPermissao')) {
        return temPermissao($app, $acao);
    }
    // fallback: se não existir, libera (não recomendado, mas evita travar)
    return true;
}

function hist(PDO $conn, $despesaId, $acao, $statusAntes, $statusDepois, $obs, $usuario){
    $sql = "
        INSERT INTO MEGAG_CRM_DESPESAS_HIST
        (ID, DESPESA_ID, ACAO, STATUS_ANTES, STATUS_DEPOIS, OBS, USUARIO, DT_EVENTO)
        VALUES
        (MEGAG_CRM_DESPESAS_HIST_SEQ.NEXTVAL, :DESPESA_ID, :ACAO, :SA, :SD, :OBS, :USU, SYSDATE)
    ";
    $st = $conn->prepare($sql);
    $st->execute([
        ':DESPESA_ID' => (int)$despesaId,
        ':ACAO' => $acao,
        ':SA' => $statusAntes,
        ':SD' => $statusDepois,
        ':OBS' => $obs,
        ':USU' => $usuario
    ]);
}

function fetch_one(PDO $conn, $id){
    $st = $conn->prepare("
        SELECT
          ID,
          SOLICITANTE,
          SOLICITANTE_NOME,
          GESTOR,
          GESTOR_NOME,
          CENTRO_CUSTO,
          CATEGORIA,
          FORNECEDOR,
          DESCRICAO,
          VALOR,
          TO_CHAR(DATA_DESPESA,'YYYY-MM-DD') AS DATA_DESPESA,
          FORMA_PGTO,
          STATUS,
          MOTIVO_REPROVACAO,
          TO_CHAR(DT_CRIACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_CRIACAO,
          TO_CHAR(DT_ATUALIZACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_ATUALIZACAO,
          TO_CHAR(DT_APROVACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_APROVACAO,
          TO_CHAR(DT_REPROVACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_REPROVACAO,
          USU_ULT_ACAO
        FROM MEGAG_CRM_DESPESAS
        WHERE ID = :ID
    ");
    $st->execute([':ID' => (int)$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function fetch_hist(PDO $conn, $id){
    $st = $conn->prepare("
        SELECT
          ID,
          DESPESA_ID,
          ACAO,
          STATUS_ANTES,
          STATUS_DEPOIS,
          OBS,
          USUARIO,
          TO_CHAR(DT_EVENTO,'YYYY-MM-DD HH24:MI:SS') AS DT_EVENTO
        FROM MEGAG_CRM_DESPESAS_HIST
        WHERE DESPESA_ID = :ID
        ORDER BY ID ASC
    ");
    $st->execute([':ID' => (int)$id]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

try {
    require_login();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user = $_SESSION['usuario'];

    $req = body_json();
    $action = $req['action'] ?? '';

    if ($action === 'list_mine') {
        if (!can('DESPESAS','VER')) jexit(false, [], 'Sem permissão: DESPESAS/VER');

        $status = trim((string)($req['status'] ?? ''));
        $sql = "
            SELECT
              ID,
              SOLICITANTE,
              GESTOR,
              CENTRO_CUSTO,
              CATEGORIA,
              DESCRICAO,
              VALOR,
              TO_CHAR(DATA_DESPESA,'YYYY-MM-DD') AS DATA_DESPESA,
              STATUS,
              TO_CHAR(DT_CRIACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_CRIACAO
            FROM MEGAG_CRM_DESPESAS
            WHERE SOLICITANTE = :U
        ";
        $params = [':U' => $user];

        if ($status !== '') {
            $sql .= " AND STATUS = :S ";
            $params[':S'] = $status;
        }

        $sql .= " ORDER BY ID DESC ";

        $st = $conn->prepare($sql);
        $st->execute($params);

        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'list_approve') {
        if (!can('DESPESAS_APROVACAO','VER')) jexit(false, [], 'Sem permissão: DESPESAS_APROVACAO/VER');

        $status = trim((string)($req['status'] ?? ''));
        $busca  = trim((string)($req['busca'] ?? ''));

        $sql = "
            SELECT
              ID,
              SOLICITANTE,
              GESTOR,
              CENTRO_CUSTO,
              CATEGORIA,
              VALOR,
              TO_CHAR(DATA_DESPESA,'YYYY-MM-DD') AS DATA_DESPESA,
              STATUS,
              TO_CHAR(DT_CRIACAO,'YYYY-MM-DD HH24:MI:SS') AS DT_CRIACAO
            FROM MEGAG_CRM_DESPESAS
            WHERE GESTOR = :G
        ";
        $params = [':G' => $user];

        if ($status !== '') {
            $sql .= " AND STATUS = :S ";
            $params[':S'] = $status;
        }

        if ($busca !== '') {
            // busca por ID ou solicitante (case insensitive)
            $sql .= " AND (TO_CHAR(ID) = :BID OR UPPER(SOLICITANTE) LIKE '%' || UPPER(:BUSCA) || '%') ";
            $params[':BID'] = $busca;
            $params[':BUSCA'] = $busca;
        }

        $sql .= " ORDER BY ID DESC ";

        $st = $conn->prepare($sql);
        $st->execute($params);

        jexit(true, ['dados' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'create') {
        if (!can('DESPESAS','CRIAR')) jexit(false, [], 'Sem permissão: DESPESAS/CRIAR');

        $data = trim((string)($req['data_despesa'] ?? ''));
        $valor = $req['valor'] ?? null;
        $gestor = trim((string)($req['gestor'] ?? ''));

        if ($data === '') jexit(false, [], 'Data da despesa é obrigatória.');
        if ($valor === null || $valor === '' || !is_numeric($valor)) jexit(false, [], 'Valor inválido.');
        if ($gestor === '') jexit(false, [], 'Gestor é obrigatório.');

        $sql = "
            INSERT INTO MEGAG_CRM_DESPESAS
            (ID, SOLICITANTE, GESTOR, CENTRO_CUSTO, CATEGORIA, FORNECEDOR, DESCRICAO,
             VALOR, DATA_DESPESA, FORMA_PGTO, STATUS, DT_CRIACAO, DT_ATUALIZACAO, USU_ULT_ACAO)
            VALUES
            (MEGAG_CRM_DESPESAS_SEQ.NEXTVAL, :SOL, :GES, :CC, :CAT, :FORN, :DESC,
             :VAL, TO_DATE(:DATA,'YYYY-MM-DD'), :FP, 'P', SYSDATE, SYSDATE, :USU)
            RETURNING ID INTO :RID
        ";

        $st = $conn->prepare($sql);

        $rid = 0;
        $st->bindParam(':SOL', $user);
        $st->bindParam(':GES', $gestor);
        $st->bindValue(':CC', $req['centro_custo'] ?? null);
        $st->bindValue(':CAT', $req['categoria'] ?? null);
        $st->bindValue(':FORN', $req['fornecedor'] ?? null);
        $st->bindValue(':DESC', $req['descricao'] ?? null);
        $st->bindValue(':VAL', (float)$valor);
        $st->bindParam(':DATA', $data);
        $st->bindValue(':FP', $req['forma_pgto'] ?? null);
        $st->bindParam(':USU', $user);
        $st->bindParam(':RID', $rid, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);

        $st->execute();

        hist($conn, $rid, 'CRIAR', null, 'P', 'Criado e enviado para aprovação.', $user);

        jexit(true, ['dados' => ['id' => $rid]]);
    }

    if ($action === 'detail') {
        if (!can('DESPESAS','VER') && !can('DESPESAS_APROVACAO','VER')) jexit(false, [], 'Sem permissão para ver detalhes.');

        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) jexit(false, [], 'ID inválido.');

        $d = fetch_one($conn, $id);
        if (!$d) jexit(false, [], 'Despesa não encontrada.');

        // segurança: só quem criou ou gestor ou admin (se temPermissao for completo)
        $isOwner = ($d['SOLICITANTE'] === $user);
        $isGestor = ($d['GESTOR'] === $user);
        $isAdmin = (function_exists('temPermissao') && temPermissao('ADMIN','ALL'));

        if (!$isOwner && !$isGestor && !$isAdmin) {
            jexit(false, [], 'Você não tem permissão para ver esta despesa.');
        }

        $h = fetch_hist($conn, $id);

        jexit(true, ['dados' => $d, 'historico' => $h]);
    }

    if ($action === 'update') {
        if (!can('DESPESAS','EDITAR')) jexit(false, [], 'Sem permissão: DESPESAS/EDITAR');

        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) jexit(false, [], 'ID inválido.');

        $d = fetch_one($conn, $id);
        if (!$d) jexit(false, [], 'Despesa não encontrada.');

        if ($d['SOLICITANTE'] !== $user) jexit(false, [], 'Você só pode editar suas próprias despesas.');
        if ($d['STATUS'] !== 'P') jexit(false, [], 'Somente despesas pendentes podem ser editadas.');

        $data = trim((string)($req['data_despesa'] ?? ''));
        $valor = $req['valor'] ?? null;
        $gestor = trim((string)($req['gestor'] ?? ''));

        if ($data === '') jexit(false, [], 'Data é obrigatória.');
        if ($valor === null || $valor === '' || !is_numeric($valor)) jexit(false, [], 'Valor inválido.');
        if ($gestor === '') jexit(false, [], 'Gestor é obrigatório.');

        $sql = "
            UPDATE MEGAG_CRM_DESPESAS
            SET
              GESTOR = :GES,
              CENTRO_CUSTO = :CC,
              CATEGORIA = :CAT,
              FORNECEDOR = :FORN,
              DESCRICAO = :DESC,
              VALOR = :VAL,
              DATA_DESPESA = TO_DATE(:DATA,'YYYY-MM-DD'),
              FORMA_PGTO = :FP,
              DT_ATUALIZACAO = SYSDATE,
              USU_ULT_ACAO = :USU
            WHERE ID = :ID
        ";
        $st = $conn->prepare($sql);
        $st->execute([
            ':GES' => $gestor,
            ':CC' => $req['centro_custo'] ?? null,
            ':CAT' => $req['categoria'] ?? null,
            ':FORN' => $req['fornecedor'] ?? null,
            ':DESC' => $req['descricao'] ?? null,
            ':VAL' => (float)$valor,
            ':DATA' => $data,
            ':FP' => $req['forma_pgto'] ?? null,
            ':USU' => $user,
            ':ID' => $id
        ]);

        hist($conn, $id, 'EDITAR', $d['STATUS'], $d['STATUS'], 'Dados alterados pelo solicitante.', $user);

        jexit(true, ['dados' => ['ok' => true]]);
    }

    if ($action === 'cancel') {
        if (!can('DESPESAS','CANCELAR')) jexit(false, [], 'Sem permissão: DESPESAS/CANCELAR');

        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) jexit(false, [], 'ID inválido.');

        $d = fetch_one($conn, $id);
        if (!$d) jexit(false, [], 'Despesa não encontrada.');

        if ($d['SOLICITANTE'] !== $user) jexit(false, [], 'Você só pode cancelar suas próprias despesas.');
        if ($d['STATUS'] !== 'P') jexit(false, [], 'Somente despesas pendentes podem ser canceladas.');

        $st = $conn->prepare("
            UPDATE MEGAG_CRM_DESPESAS
            SET STATUS = 'C', DT_ATUALIZACAO = SYSDATE, USU_ULT_ACAO = :USU
            WHERE ID = :ID
        ");
        $st->execute([':USU' => $user, ':ID' => $id]);

        hist($conn, $id, 'CANCELAR', $d['STATUS'], 'C', 'Cancelada pelo solicitante.', $user);

        jexit(true, ['dados' => ['ok' => true]]);
    }

    if ($action === 'approve') {
        if (!can('DESPESAS_APROVACAO','APROVAR')) jexit(false, [], 'Sem permissão: DESPESAS_APROVACAO/APROVAR');

        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) jexit(false, [], 'ID inválido.');

        $d = fetch_one($conn, $id);
        if (!$d) jexit(false, [], 'Despesa não encontrada.');

        if ($d['GESTOR'] !== $user) jexit(false, [], 'Você não é o gestor responsável desta despesa.');
        if ($d['STATUS'] !== 'P') jexit(false, [], 'Somente despesas pendentes podem ser aprovadas.');

        $st = $conn->prepare("
            UPDATE MEGAG_CRM_DESPESAS
            SET STATUS = 'A',
                DT_APROVACAO = SYSDATE,
                DT_ATUALIZACAO = SYSDATE,
                USU_ULT_ACAO = :USU,
                MOTIVO_REPROVACAO = NULL
            WHERE ID = :ID
        ");
        $st->execute([':USU' => $user, ':ID' => $id]);

        hist($conn, $id, 'APROVAR', $d['STATUS'], 'A', 'Aprovada pelo gestor.', $user);

        jexit(true, ['dados' => ['ok' => true]]);
    }

    if ($action === 'reject') {
        if (!can('DESPESAS_APROVACAO','REPROVAR')) jexit(false, [], 'Sem permissão: DESPESAS_APROVACAO/REPROVAR');

        $id = (int)($req['id'] ?? 0);
        $motivo = trim((string)($req['motivo'] ?? ''));

        if ($id <= 0) jexit(false, [], 'ID inválido.');
        if ($motivo === '') jexit(false, [], 'Motivo é obrigatório.');

        $d = fetch_one($conn, $id);
        if (!$d) jexit(false, [], 'Despesa não encontrada.');

        if ($d['GESTOR'] !== $user) jexit(false, [], 'Você não é o gestor responsável desta despesa.');
        if ($d['STATUS'] !== 'P') jexit(false, [], 'Somente despesas pendentes podem ser reprovadas.');

        $st = $conn->prepare("
            UPDATE MEGAG_CRM_DESPESAS
            SET STATUS = 'R',
                DT_REPROVACAO = SYSDATE,
                DT_ATUALIZACAO = SYSDATE,
                USU_ULT_ACAO = :USU,
                MOTIVO_REPROVACAO = :M
            WHERE ID = :ID
        ");
        $st->execute([':USU' => $user, ':M' => $motivo, ':ID' => $id]);

        hist($conn, $id, 'REPROVAR', $d['STATUS'], 'R', $motivo, $user);

        jexit(true, ['dados' => ['ok' => true]]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
