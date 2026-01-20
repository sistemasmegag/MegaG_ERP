<?php
// api/api_dados.php
header('Content-Type: application/json');

try {
    // ==================================================================
    // 1) CONEXÃO (caminho robusto)
    // ==================================================================
    $pathConexaoCandidates = [];

    // 1) Raiz do servidor (htdocs) - se você mantém db_config fora do projeto
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $pathConexaoCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db_config/db_connect.php';
    }

    // 2) Dentro do projeto (novo padrão)
    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db_connect.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/db_config/db_connect.php';

    $pathConexao = null;
    foreach ($pathConexaoCandidates as $cand) {
        if (file_exists($cand)) {
            $pathConexao = $cand;
            break;
        }
    }

    if ($pathConexao === null) {
        throw new Exception(
            "Arquivo de conexão não encontrado. Tentei: " . implode(" | ", $pathConexaoCandidates)
        );
    }

    require_once($pathConexao);

    if (!isset($conn) || !$conn) {
        throw new Exception("Falha na conexão.");
    }

    if ($conn instanceof PDO) {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    // Configurações Oracle
    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    // ==================================================================
    // 2) FILTROS
    // ==================================================================
    $tipo   = $_GET['tipo'] ?? 'PADRAO';
    $data   = $_GET['data'] ?? '';
    $setor  = $_GET['setor'] ?? '';
    $turno  = $_GET['turno'] ?? '';
    $status = $_GET['status'] ?? '';

    // Filtros do TABVDAPRODRAIO
    $nroTabVenda = $_GET['nroTabVenda'] ?? '';
    $seqProduto  = $_GET['seqProduto'] ?? '';
    $raio        = $_GET['raio'] ?? '';

    $params = [];
    $sql = "";

    // ==================================================================
    // 3) CONSTRUÇÃO DA QUERY
    // ==================================================================

    if ($tipo === 'COMISSAO') {

    // TABELA REAL (conforme print): CONSINCO.MEGAG_IMP_REPCCCOMISSAO
    // Mapeamento para manter compatível com o front:
    // CODEVENTO   -> NROCOMISSAOEVENTO
    // SEQPESSOA   -> NROREPRESENTANTE
    // DATA_REF    -> DTALANCAMENTO
    // VLRTOTAL    -> VALOR
    // OBSERVACAO  -> HISTORICO
    // STATUS      -> STATUS
    // MSG_LOG     -> RESIMPOTACAO
    // DTAINCLUSAO -> DTAINCLUSAO (formatado)

    $sql = "SELECT
                ROWNUM AS ID,
                t.NROCOMISSAOEVENTO AS CODEVENTO,
                t.NROREPRESENTANTE  AS SEQPESSOA,
                TO_CHAR(t.DTALANCAMENTO, 'YYYY-MM-DD') AS DATA_REF,
                t.VALOR AS VLRTOTAL,
                t.HISTORICO AS OBSERVACAO,
                t.STATUS AS STATUS,
                t.RESIMPOTACAO AS MSG_LOG,
                TO_CHAR(t.DTAINCLUSAO, 'DD/MM/YYYY HH24:MI') AS DTAINCLUSAO
            FROM CONSINCO.MEGAG_IMP_REPCCCOMISSAO t
            WHERE 1=1 ";

    // Filtro por data (usa DTALANCAMENTO)
    if (!empty($data)) {
        $sql .= " AND TRUNC(t.DTALANCAMENTO) = TO_DATE(:data, 'YYYY-MM-DD') ";
        $params[':data'] = $data;
    }

    // Filtro por status (essa tabela TEM STATUS)
    if (!empty($status)) {
        $sql .= " AND t.STATUS = :status ";
        $params[':status'] = $status;
    }

        $sql .= " ORDER BY ROWNUM DESC FETCH FIRST 200 ROWS ONLY";

    } else if ($tipo === 'TABVDAPRODRAIO') {

        $sql = "SELECT
                    ROWNUM AS ID,
                    NROTABVENDA,
                    SEQPRODUTO,
                    RAIO,
                    PERAD,
                    STATUS,
                    OBS AS MSG_LOG,
                    TO_CHAR(DTAINCLUSAO, 'DD/MM/YYYY HH24:MI') AS DTAINCLUSAO,
                    TO_CHAR(DTAPROCESSAMENTO, 'YYYY-MM-DD') AS DATA_REF
                FROM CONSINCO.MEGAG_IMP_TABVDAPRODRAIO
                WHERE 1=1 ";

        if (!empty($data)) {
            $sql .= " AND TRUNC(DTAPROCESSAMENTO) = TO_DATE(:data, 'YYYY-MM-DD') ";
            $params[':data'] = $data;
        }

        if (!empty($status)) {
            $sql .= " AND STATUS = :status ";
            $params[':status'] = $status;
        }

        if (!empty($nroTabVenda)) {
            $sql .= " AND NROTABVENDA = :nroTabVenda ";
            $params[':nroTabVenda'] = $nroTabVenda;
        }

        if (!empty($seqProduto)) {
            $sql .= " AND SEQPRODUTO = :seqProduto ";
            $params[':seqProduto'] = $seqProduto;
        }

        if (!empty($raio)) {
            $sql .= " AND RAIO = :raio ";
            $params[':raio'] = $raio;
        }

        $sql .= " ORDER BY DTAINCLUSAO DESC FETCH FIRST 200 ROWS ONLY";

    } else {

        $sql = "SELECT 
                    ROWNUM AS ID, 
                    SEQSETOR, 
                    TURNO, 
                    TO_CHAR(DTA, 'YYYY-MM-DD') AS DTA,
                    TO_CHAR(DTA, 'YYYY-MM-DD') AS DATA_REF,
                    PESO_META, 
                    PESO_CAPAC,
                    TO_CHAR(DTAINCLUSAO, 'DD/MM/YYYY HH24:MI') AS DTAINCLUSAO
                FROM CONSINCO.MEGAG_IMP_SETORMETACAPAC 
                WHERE 1=1 ";

        if (!empty($data)) {
            $sql .= " AND TRUNC(DTA) = TO_DATE(:data, 'YYYY-MM-DD') ";
            $params[':data'] = $data;
        }

        if (!empty($setor)) {
            $sql .= " AND SEQSETOR = :setor ";
            $params[':setor'] = $setor;
        }

        if (!empty($turno)) {
            $sql .= " AND TURNO = :turno ";
            $params[':turno'] = $turno;
        }

        if (!empty($status)) {
            $sql .= " AND STATUS = :status ";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY DTAINCLUSAO DESC FETCH FIRST 200 ROWS ONLY";
    }

    // ==================================================================
    // 4) EXECUÇÃO
    // ==================================================================
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso' => true,
        'dados' => $dados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
