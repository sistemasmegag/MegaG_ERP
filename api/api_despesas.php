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
if (!$pathConexao) {
    jexit(false, [], "Arquivo de conexão não encontrado!");
}
require_once $pathConexao;

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

    // $conn é a PDO de Oracle presente no db_connect.php (esperado)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user = $_SESSION['usuario']; // ou outro identificador (NUMBER) se for o caso

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
        $st = $conn->prepare("SELECT CODTIPODESPESA, DESCRICAO FROM CONSINCO.MEGAG_DESP_TIPO ORDER BY DESCRICAO");
        $st->execute();
        $tipos = $st->fetchAll(PDO::FETCH_ASSOC);

        // Obter centros de custo da tabela oficial ABA_CENTRORESULTADO 
        // (conforme procedure PRC_LIST_MEGAG_DESP_CENTRORESULTADO)
        $stCc = $conn->prepare("SELECT CENTRORESULTADO AS CENTROCUSTO, SEQCENTRORESULTADO, DESCRICAO AS NOME FROM CONSINCO.ABA_CENTRORESULTADO ORDER BY DESCRICAO");
        $stCc->execute();
        $ccs = $stCc->fetchAll(PDO::FETCH_ASSOC);

        jexit(true, ['dados' => ['tipos' => $tipos, 'ccs' => $ccs]]);
    }

    // ============================================
    // CRIAR DESPESA
    // ============================================
    if ($action === 'create') {
        $vlr = str_replace(['R$', '.', ' '], '', $req['valor'] ?? '0');
        $vlr = str_replace(',', '.', $vlr);

        $forn = trim($req['estabelecimento'] ?? '');
        $data = trim($req['data_despesa'] ?? '');
        $tipo = (int) ($req['categoria'] ?? 0);
        $cc_str = trim($req['centro_custo'] ?? '');
        $venc = trim($req['vencimento'] ?? '');
        $obs = trim($req['comentario'] ?? '');

        if ($tipo === 0)
            jexit(false, [], 'Categoria é obrigatória.');
        if ($vlr <= 0)
            jexit(false, [], 'Valor inválido.');
        if ($cc_str === '')
            jexit(false, [], 'Centro de custo é obrigatório.');

        $cc_parts = explode('|', $cc_str);
        $centro_custo = (int) ($cc_parts[0] ?? 0);
        $seq_cc = (int) ($cc_parts[1] ?? 0);

        // USUARIO logado: para testes, enviamos 1 se não for numero
        $usr_solicitante = is_numeric($user) ? (int) $user : 1;

        // Upload verification
        $fileNameParam = null;
        $tipoArquivo = null;
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $fileNameParam = 'despesa_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $tipoArquivo = $_FILES['arquivo']['type'];

            if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadDir . $fileNameParam)) {
                jexit(false, [], 'Erro ao salvar o arquivo no servidor.');
            }
        }

        $sql = "BEGIN
                  CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP(
                      p_USUARIOSOLICITANTE => :USU,
                      p_USUARIOAPROVADOR   => NULL,
                      p_CODTIPODESPESA     => :TIPO,
                      p_PAGO               => 'N',
                      p_VLRRATDESPESA      => :VLR,
                      p_FORNECEDOR         => :FORN,
                      p_NOMEARQUIVO        => :NOMEARQ,
                      p_OBSERVACAO         => :OBS,
                      p_SEQCENTRORESULTADO => :SEQCC,
                      p_CENTROCUSTO        => :CC,
                      p_STATUS             => 'LANCADO',
                      p_CODDESPESA_OUT     => :OUT_ID
                  );
                END;";

        $st = $conn->prepare($sql);
        $st->bindValue(':USU', $usr_solicitante);
        $st->bindValue(':TIPO', $tipo);
        $st->bindValue(':VLR', (float) $vlr);
        $st->bindValue(':FORN', $forn);
        $st->bindValue(':NOMEARQ', $fileNameParam);
        $st->bindValue(':OBS', $obs);
        $st->bindValue(':SEQCC', $seq_cc);
        $st->bindValue(':CC', $centro_custo);

        $out_id = 0;
        $st->bindParam(':OUT_ID', $out_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);

        $st->execute();

        // Se gravou a despesa e tem arquivo, grava na tabela de arquivos anexa (PKG: PRC_INS_MEGAG_DESP_ARQUIVO)
        if ($out_id > 0 && $fileNameParam) {
            $sqlArq = "BEGIN
                        CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_ARQUIVO(
                            p_CODDESPESA     => :COD_DESP,
                            p_NOMEARQUIVO    => :NOME_ARQ,
                            p_TIPOARQUIVO    => :TIPO_ARQ,
                            p_CODARQUIVO_OUT => :OUT_ARQ
                        );
                      END;";
            $stArq = $conn->prepare($sqlArq);
            $stArq->bindValue(':COD_DESP', $out_id);
            $stArq->bindValue(':NOME_ARQ', $fileNameParam);
            $stArq->bindValue(':TIPO_ARQ', $tipoArquivo);
            $out_arq_id = 0;
            $stArq->bindParam(':OUT_ARQ', $out_arq_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
            $stArq->execute();
        }

        // Commit da transação pois os PL/SQL anônimos não dão autocommit nativamente no PDO OCI
        $conn->exec('COMMIT');

        jexit(true, ['dados' => ['id' => $out_id, 'mensagem' => 'Despesa cadastrada com sucesso!']]);
    }

    // ============================================
    // LISTAR MINHAS DESPESAS (Para despesas.php)
    // ============================================
    if ($action === 'list_mine') {
        $usr_solicitante = is_numeric($user) ? (int) $user : 1;

        $sql = "SELECT D.*, 
                       (SELECT DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO C WHERE C.CENTRORESULTADO = D.CENTROCUSTO AND ROWNUM = 1) as DESC_CC
                FROM CONSINCO.MEGAG_DESP D 
                WHERE D.USUARIOSOLICITANTE = :U 
                ORDER BY D.DTAINCLUSAO DESC";
        $st = $conn->prepare($sql);
        $st->execute([':U' => $usr_solicitante]);
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

        foreach ($rows as $r) {
            $v = (float) $r['VLRRATDESPESA'];
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
        $usr_aprovador = is_numeric($user) ? (int) $user : 1;

        $sql = "SELECT desp.*,
                       (SELECT NOME FROM CONSINCO.GE_USUARIO U WHERE U.SEQUSUARIO = desp.USUARIOSOLICITANTE AND ROWNUM = 1) as NOME_SOLICITANTE,
                       (SELECT DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO C WHERE C.CENTRORESULTADO = desp.CENTROCUSTO AND ROWNUM = 1) as DESC_CC
                FROM CONSINCO.MEGAG_DESP desp
                INNER JOIN CONSINCO.MEGAG_DESP_APROVADORES aprov
                   ON desp.CENTROCUSTO = aprov.CENTROCUSTO
                WHERE (desp.STATUS = 'LANCADO' OR desp.STATUS = 'EM_APROVACAO' OR desp.STATUS = 'APROVACAO')
                  AND aprov.SEQUSUARIO = :U
                ORDER BY desp.DTAINCLUSAO DESC";
        $st = $conn->prepare($sql);
        $st->execute([':U' => $usr_aprovador]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $metrics = [
            'pendentes' => count($rows),
            'aprovadas_hoje' => 0, // Mockado por enquanto até haver log de ação
            'reprovadas_hoje' => 0
        ];

        jexit(true, ['dados' => ['dados' => $rows, 'metricas' => $metrics]]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
