<?php
// api/api_tipos_importacao.php
header('Content-Type: application/json');

try {
    // ==================================================================
    // 1) CONEXÃO (caminho robusto)
    // ==================================================================
    $pathConexaoCandidates = [];

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $pathConexaoCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db_config/db_connect.php';
    }

    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db_connect.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/db_config/db_connect.php';

    $pathConexao = null;
    foreach ($pathConexaoCandidates as $cand) {
        if (file_exists($cand)) { $pathConexao = $cand; break; }
    }
    if ($pathConexao === null) {
        throw new Exception("Arquivo de conexão não encontrado. Tentei: " . implode(" | ", $pathConexaoCandidates));
    }

    require_once($pathConexao);

    if (!isset($conn) || !$conn) throw new Exception("Falha na conexão.");

    if ($conn instanceof PDO) {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    // Oracle session
    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    // ==================================================================
    // 2) DESCOBRE COLUNAS DA megag_tabs_importacao
    // ==================================================================
    $owner = 'CONSINCO';
    $tab   = 'MEGAG_TABS_IMPORTACAO'; // <-- padrão pedido pelo gestor

    $stmtCols = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
    ");
    $stmtCols->execute([':own' => $owner, ':tab' => $tab]);
    $cols = array_map('strtoupper', $stmtCols->fetchAll(PDO::FETCH_COLUMN));

    if (!$cols) {
        throw new Exception("Não encontrei a tabela {$owner}.{$tab} ou sem permissão para ler ALL_TAB_COLUMNS.");
    }

    $has = fn($c) => in_array(strtoupper($c), $cols, true);

    // ==================================================================
    // 3) ESCOLHE COLUNAS PARA "TIPO" e "DESCRICAO" (flexível)
    // ==================================================================
    $tipoCandidates = ['TABELA','NOME_TABELA','TAB','TABELA_ORIGEM','NM_TABELA','TIPO','CODIGO','CODTAB'];
    $descCandidates = ['DESCRICAO','DESC','DSC','NOME','TITULO','ROTULO','LABEL','DS_TABELA'];

    $colTipo = null;
    foreach ($tipoCandidates as $c) { if ($has($c)) { $colTipo = $c; break; } }

    $colDesc = null;
    foreach ($descCandidates as $c) { if ($has($c)) { $colDesc = $c; break; } }

    if (!$colTipo) {
        throw new Exception("A {$owner}.{$tab} não possui uma coluna identificadora do tipo/tabela. Procurei por: " . implode(', ', $tipoCandidates));
    }

    // Se não tiver descrição, usa a própria coluna de tipo como descrição
    if (!$colDesc) $colDesc = $colTipo;

    // ==================================================================
    // 4) QUERY
    // ==================================================================
    // Aqui retorna sempre no formato esperado pelo front:
    // [{TIPO: 'CONSINCO.MEGAG_IMP_...', DESCRICAO: '...'}]
    $sql = "
        SELECT
            TRIM({$colTipo}) AS TIPO,
            TRIM({$colDesc}) AS DESCRICAO
        FROM {$owner}.{$tab}
        WHERE {$colTipo} IS NOT NULL
        ORDER BY TRIM({$colDesc})
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll();

    // (Opcional) normaliza: se vier "MEGAG_IMP_X" sem owner, prefixa com CONSINCO.
    foreach ($dados as &$d) {
        $t = trim((string)($d['TIPO'] ?? ''));
        if ($t !== '' && strpos($t, '.') === false) {
            $d['TIPO'] = $owner . '.' . $t;
        }
        $d['DESCRICAO'] = trim((string)($d['DESCRICAO'] ?? $d['TIPO']));
    }

    echo json_encode(['sucesso' => true, 'dados' => $dados]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
