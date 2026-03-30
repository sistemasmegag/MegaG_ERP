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

function resolve_politica_despesa(PDO $conn, int $centroCusto): int
{
    $sql = "SELECT DISTINCT CODPOLITICA
              FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO
             WHERE CENTROCUSTO = :CC
             ORDER BY CODPOLITICA";
    $st = $conn->prepare($sql);
    $st->bindValue(':CC', $centroCusto);
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
        $stForn = $conn->prepare($sql);
        $like = '%' . $q . '%';
        $stForn->bindValue(':Q', $like);
        $stForn->execute();

        jexit(true, ['dados' => $stForn->fetchAll(PDO::FETCH_ASSOC)]);
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
        $cc_parts = explode('|', $centros_custo_raw[0]);
        $centro_custo = (int) ($cc_parts[0] ?? 0);
        $seq_cc = (int) ($cc_parts[1] ?? 0);
        $cod_politica = resolve_politica_despesa($conn, $centro_custo);

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
                      p_USUARIOSOLICITANTE   => :USU,
                      p_CODTIPODESPESA       => :TIPO,
                      p_PAGO                 => 'N',
                      p_VLRRATDESPESA        => :VLR,
                      p_FORNECEDOR           => :FORN,
                      p_NOMEARQUIVO          => :NOMEARQ,
                      p_OBSERVACAO           => :OBS,
                      p_SEQCENTRORESULTADO   => :SEQCC,
                      p_CENTROCUSTO          => :CC,
                      p_STATUS               => 'LANCADO',
                      p_DESCRICAOCENTROCUSTO => NULL,
                      p_CODPOLITICA          => :CODPOL,
                      p_DTAVENCIMENTO        => TO_DATE(:VENC, 'YYYY-MM-DD'),
                      p_DTADESPESA           => TO_DATE(:DTADESP, 'YYYY-MM-DD'),
                      p_CODDESPESA_OUT       => :OUT_ID
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
        $st->bindValue(':CODPOL', $cod_politica);
        $st->bindValue(':VENC', $venc !== '' ? $venc : null);
        $st->bindValue(':DTADESP', $data !== '' ? $data : null);

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
                            CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_INS_MEGAG_DESP_RATEIO(
                                p_coddespesa         => :COD_DESP,
                                p_seqcentroresultado => :SEQ_CC,
                                p_centrocusto        => :CC,
                                p_valorrateio        => :VLR_RAT,
                                p_codrateio          => :OUT_RAT
                            );
                          END;";

            foreach ($centros_custo_raw as $idx => $cc_item) {
                $parts   = explode('|', $cc_item);
                $cc_val  = (int)($parts[0] ?? 0);
                $seq_val = (int)($parts[1] ?? 0);

                if ($usa_valores_individuais) {
                    $vlr_rat = (float)($valores_rateio_raw[$idx] ?? 0);
                } else {
                    $vlr_rat = ($idx === $qtd_ccs - 1) ? $vlr_ultimo : $vlr_base;
                }

                $stRat = $conn->prepare($sqlRateio);
                $stRat->bindValue(':COD_DESP', $out_id);
                $stRat->bindValue(':SEQ_CC',   $seq_val);
                $stRat->bindValue(':CC',        $cc_val);
                $stRat->bindValue(':VLR_RAT',  $vlr_rat);
                $out_rat_id = 0;
                $stRat->bindParam(':OUT_RAT', $out_rat_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 32);
                $stRat->execute();
            }
        }

        // Commit da transação pois os PL/SQL anônimos não dão autocommit nativamente no PDO OCI
        $conn->exec('COMMIT');

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
        $usr_aprovador = is_numeric($user) ? (int)$user : 1;

        if (!$id || !$status) jexit(false, [], 'ID e Status são obrigatórios.');

        $sql = "BEGIN CONSINCO.PKG_MEGAG_DESP_CADASTRO.PRC_UPD_MEGAG_DESP_APROVACAO(
                    p_coddespesa  => :ID,
                    p_sequsuario  => :USU,
                    p_status      => :STATUS,
                    p_pago        => :PAGO,
                    p_observacao  => :OBS,
                    p_msg_retorno => :MSG
                ); END;";
        $st = $conn->prepare($sql);
        $st->bindValue(':ID', $id);
        $st->bindValue(':USU', $usr_aprovador);
        $st->bindValue(':STATUS', $status);
        $st->bindValue(':PAGO', $pago);
        $st->bindValue(':OBS', $obs);
        $msg = '';
        $st->bindParam(':MSG', $msg, PDO::PARAM_STR, 4000);
        $st->execute();

        if (strpos(strtoupper($msg), 'ERRO') !== false) jexit(false, [], $msg);
        jexit(true, ['dados' => ['mensagem' => $msg]]);
    }

    // ============================================
    // LISTAR MINHAS DESPESAS (Para despesas.php)
    // ============================================
    if ($action === 'list_mine') {
        $usr_solicitante = is_numeric($user) ? (int) $user : 1;

        $sql = "SELECT D.*, 
                       TO_CHAR(D.DTAINCLUSAO, 'YYYY-MM-DD HH24:MI:SS') as DTAINCLUSAO_FORMAT,
                       TO_CHAR(D.DTAVENCIMENTO, 'YYYY-MM-DD') as DTAVENCIMENTO_FORMAT,
                       TO_CHAR(D.DTADESPESA, 'YYYY-MM-DD') as DTADESPESA_FORMAT,
                       (SELECT DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO C WHERE C.CENTRORESULTADO = D.CENTROCUSTO AND ROWNUM = 1) as DESC_CC,
                       (SELECT DESCRICAO FROM CONSINCO.MEGAG_DESP_TIPO T WHERE T.CODTIPODESPESA = D.CODTIPODESPESA AND ROWNUM = 1) as DESC_TIPO,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_RATEIO R WHERE R.CODDESPESA = D.CODDESPESA) as QTD_RATEIO,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_APROVACAO A WHERE A.CODDESPESA = D.CODDESPESA) as QTD_APROVACOES
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
        $usr_aprovador = is_numeric($user) ? (int) $user : 1;

        // Query direta adaptada da lógica da procedure para evitar erros de cursor no driver pdo_oci
        $sql = "WITH CC_DESPESA AS (
                    SELECT CODDESPESA, CENTROCUSTO FROM CONSINCO.MEGAG_DESP_RATEIO
                    UNION
                    SELECT d.CODDESPESA, d.CENTROCUSTO FROM CONSINCO.MEGAG_DESP d
                    WHERE NOT EXISTS (SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
                )
                SELECT DISTINCT 
                       desp.*,
                       (SELECT COUNT(*) FROM CONSINCO.MEGAG_DESP_APROVACAO A WHERE A.CODDESPESA = desp.CODDESPESA) as QTD_APROVACOES
                FROM CONSINCO.MEGAG_DESP desp
                JOIN CC_DESPESA cc ON cc.CODDESPESA = desp.CODDESPESA
                JOIN CONSINCO.MEGAG_DESP_APROVADORES a ON a.CENTROCUSTO = cc.CENTROCUSTO
                JOIN CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p ON p.CODGRUPO = a.CODGRUPO AND p.CENTROCUSTO = a.CENTROCUSTO
                WHERE desp.STATUS NOT IN ('APROVADO', 'REJEITADO')
                  AND desp.USUARIOSOLICITANTE <> :U
                  AND a.SEQUSUARIO = :U
                  AND NOT EXISTS (
                      SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO apr
                      WHERE apr.CODDESPESA = cc.CODDESPESA
                        AND apr.CENTROCUSTO = cc.CENTROCUSTO
                        AND apr.USUARIOAPROVADOR = :U
                  )
                  AND p.NIVEL_APROVACAO <= (
                      SELECT NVL(MAX(apr_nivel.NIVEL_APROVACAO), 0) + 1
                      FROM CONSINCO.MEGAG_DESP_APROVACAO apr_nivel
                      WHERE apr_nivel.CODDESPESA = cc.CODDESPESA
                        AND apr_nivel.CENTROCUSTO = cc.CENTROCUSTO
                        AND apr_nivel.STATUS = 'APROVADO'
                  )
                ORDER BY desp.DTAINCLUSAO DESC";
        
        $st = $conn->prepare($sql);
        $st->execute([':U' => $usr_aprovador]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer os dados com descrições (conforme esperado pelo frontend)
        foreach ($rows as &$r) {
            // Lógica de Status Dinâmico
            if ($r['STATUS'] === 'LANCADO' && (int)$r['QTD_APROVACOES'] > 0) {
                $r['STATUS'] = 'EM_APROVACAO';
            }

            $r['DTAINCLUSAO_FORMAT'] = date('Y-m-d H:i:s', strtotime($r['DTAINCLUSAO'] ?? ''));
            
            // Busca nome do solicitante
            $stUsr = $conn->prepare("SELECT NOME FROM CONSINCO.GE_USUARIO WHERE SEQUSUARIO = :S AND ROWNUM = 1");
            $stUsr->execute([':S' => $r['USUARIOSOLICITANTE']]);
            $r['NOME_SOLICITANTE'] = $stUsr->fetchColumn() ?: 'Usuário';

            // Busca descrição do Centro de Custo
            $stCc = $conn->prepare("SELECT DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO WHERE CENTRORESULTADO = :C AND ROWNUM = 1");
            $stCc->execute([':C' => $r['CENTROCUSTO']]);
            $r['DESC_CC'] = $stCc->fetchColumn() ?: 'Centro de Custo';

            // A categoria já vem no campo DESCRICAO da tabela MEGAG_DESP
            $r['DESC_TIPO'] = $r['DESCRICAO'];
        }

        $metrics = [
            'pendentes' => count($rows),
            'aprovadas_hoje' => 0, 
            'reprovadas_hoje' => 0
        ];

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
        $stCat = $conn->prepare($sqlCat); $stCat->execute();
        $byCategory = $stCat->fetchAll(PDO::FETCH_ASSOC);

        // Mensal: Evolução
        $sqlMes = "SELECT TO_CHAR(DTAINCLUSAO, 'MM/YYYY') as MES, SUM(VLRRATDESPESA) as TOTAL
                     FROM CONSINCO.MEGAG_DESP
                    GROUP BY TO_CHAR(DTAINCLUSAO, 'MM/YYYY')
                    ORDER BY TO_CHAR(DTAINCLUSAO, 'YYYY-MM')";
        $stMes = $conn->prepare($sqlMes); $stMes->execute();
        $evolution = $stMes->fetchAll(PDO::FETCH_ASSOC);

        // Top Centros de Custo
        $sqlCC = "SELECT CENTROCUSTO, SUM(VLRRATDESPESA) as TOTAL
                    FROM CONSINCO.MEGAG_DESP
                   GROUP BY CENTROCUSTO
                   ORDER BY TOTAL DESC FETCH FIRST 5 ROWS ONLY";
        $stCC = $conn->prepare($sqlCC); $stCC->execute();
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
        
        $st = $conn->prepare($sql);
        $st->execute([':ID' => $id_desp]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        // Retorna sucesso com os dados encontrados
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
                    R.SEQCENTRORESULTADO,
                    R.VALORRATEIO,
                    (SELECT AC.DESCRICAO FROM CONSINCO.ABA_CENTRORESULTADO AC
                     WHERE AC.CENTRORESULTADO = R.CENTROCUSTO AND ROWNUM = 1) AS DESC_CC
                FROM CONSINCO.MEGAG_DESP_RATEIO R
                WHERE R.CODDESPESA = :ID
                ORDER BY R.CENTROCUSTO";
        $st = $conn->prepare($sql);
        $st->execute([':ID' => $id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        jexit(true, ['dados' => $rows]);
    }

    jexit(false, [], 'Ação inválida.');

} catch (Exception $e) {
    jexit(false, [], $e->getMessage());
}
