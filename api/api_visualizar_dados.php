<?php
// api/api_visualizar_dados.php
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

    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    // ==================================================================
    // 2) FILTROS FIXOS (gestor)
    // ==================================================================
    $tipo         = trim((string)($_GET['tipo'] ?? ''));          // vem do combo (megag_tabs_importacao)
    $usuario      = trim((string)($_GET['usuario'] ?? ''));       // usuário de inclusão
    $dataInclusao = trim((string)($_GET['dataInclusao'] ?? ''));  // YYYY-MM-DD
    $status       = trim((string)($_GET['status'] ?? ''));        // S/E/C/P

    // valida status
    if ($status !== '' && !in_array($status, ['S','E','C','P'], true)) {
        throw new Exception("Status inválido. Use S, E, C ou P.");
    }

    // ==================================================================
    // 3) LISTA PERMITIDA (anti SQL injection) via megag_tabs_importacao
    // ==================================================================
    $ownerTabs = 'CONSINCO';
    $tabTabs   = 'MEGAG_TABS_IMPORTACAO';

    // Descobre a coluna que guarda o "tipo" (nome da tabela)
    $stmtCols = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
    ");
    $stmtCols->execute([':own' => $ownerTabs, ':tab' => $tabTabs]);
    $colsTabs = array_map('strtoupper', $stmtCols->fetchAll(PDO::FETCH_COLUMN));
    if (!$colsTabs) throw new Exception("Não consegui ler {$ownerTabs}.{$tabTabs} (tabela inexistente ou sem permissão).");

    $hasTabs = fn($c) => in_array(strtoupper($c), $colsTabs, true);

    $tipoCandidates = ['TABELA','NOME_TABELA','TAB','TABELA_ORIGEM','NM_TABELA','TIPO','CODIGO','CODTAB'];
    $colTipo = null;
    foreach ($tipoCandidates as $c) { if ($hasTabs($c)) { $colTipo = $c; break; } }
    if (!$colTipo) {
        throw new Exception("{$ownerTabs}.{$tabTabs} não tem coluna de tipo/tabela. Procurei por: " . implode(', ', $tipoCandidates));
    }

    // busca lista permitida
    $stmtAllowed = $conn->prepare("
        SELECT DISTINCT TRIM({$colTipo}) AS TIPO
        FROM {$ownerTabs}.{$tabTabs}
        WHERE {$colTipo} IS NOT NULL
    ");
    $stmtAllowed->execute();
    $allowed = array_values(array_filter(array_map('trim', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN))));

    // normaliza: se vier sem owner, prefixa CONSINCO (mesma regra do combo)
    $normalizeTipo = function(string $t) use ($ownerTabs) {
        $t = trim($t);
        if ($t === '') return '';
        if (strpos($t, '.') === false) return $ownerTabs . '.' . $t;
        return $t;
    };
    $allowedNorm = array_map($normalizeTipo, $allowed);

    // se não veio tipo, tenta usar "Todos" com uma VIEW (se existir), senão exige tipo
    $tipoNorm = $normalizeTipo($tipo);
    if ($tipoNorm === '') {
        // tenta uma view padrão (se existir) para “todos os dados”
        // Se você tiver uma view consolidada, coloque o nome aqui.
        $tipoNorm = 'CONSINCO.MEGAG_VW_IMPORTACAO';
    }

    // valida se tipoNorm é permitido OU é a view padrão
    if ($tipoNorm !== 'CONSINCO.MEGAG_VW_IMPORTACAO' && !in_array($tipoNorm, $allowedNorm, true)) {
        throw new Exception("Tipo de dado inválido (não está em megag_tabs_importacao): {$tipoNorm}");
    }

    // ==================================================================
    // 4) DESCOBRE COLUNAS DA TABELA SELECIONADA (pra saber como filtrar)
    // ==================================================================
    // separa OWNER e TABLE
    $parts = explode('.', $tipoNorm, 2);
    $owner = strtoupper(trim($parts[0] ?? 'CONSINCO'));
    $tab   = strtoupper(trim($parts[1] ?? ''));

    if ($tab === '') throw new Exception("Tipo de dado inválido: {$tipoNorm}");

    // se for view, ALL_TAB_COLUMNS também funciona em muitos casos (depende permissão)
    $stmtCols2 = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
    ");
    $stmtCols2->execute([':own' => $owner, ':tab' => $tab]);
    $cols = array_map('strtoupper', $stmtCols2->fetchAll(PDO::FETCH_COLUMN));

    if (!$cols) {
        // caso da VIEW/objeto sem permissão no ALL_TAB_COLUMNS
        throw new Exception("Não consegui ler as colunas de {$owner}.{$tab}. Verifique permissão/objeto.");
    }

    $has = fn($c) => in_array(strtoupper($c), $cols, true);

    // colunas “padrão” para filtro (tenta várias)
    $dateColsCandidates = ['DTAINCLUSAO','DATAINCLUSAO','DTA_INCLUSAO','DTINCLUSAO','DTAINC','DT_INC'];
    $userColsCandidates = ['USUINCLUSAO','USULANCTO','USUARIO','USU','USU_INC','USUINCLUIU','USU_LANCTO'];

    $colDataInc = null;
    foreach ($dateColsCandidates as $c) { if ($has($c)) { $colDataInc = $c; break; } }

    $colUserInc = null;
    foreach ($userColsCandidates as $c) { if ($has($c)) { $colUserInc = $c; break; } }

    // status geralmente é STATUS
    $colStatus = $has('STATUS') ? 'STATUS' : null;

    // ==================================================================
    // 5) MONTA SQL: TRAGA TUDO (t.*) e filtre se der
    // ==================================================================
    $sql = "SELECT t.* FROM {$owner}.{$tab} t WHERE 1=1 ";
    $params = [];

    // status
    if ($status !== '' && $colStatus) {
        $sql .= " AND t.{$colStatus} = :status ";
        $params[':status'] = $status;
    }

    // usuário inclusão
    if ($usuario !== '' && $colUserInc) {
        $sql .= " AND UPPER(TRIM(t.{$colUserInc})) = UPPER(TRIM(:usuario)) ";
        $params[':usuario'] = $usuario;
    }

    // data inclusão (YYYY-MM-DD)
    if ($dataInclusao !== '' && $colDataInc) {
        $sql .= " AND TRUNC(t.{$colDataInc}) = TO_DATE(:dataInc, 'YYYY-MM-DD') ";
        $params[':dataInc'] = $dataInclusao;
    }

    // ordenação por data inclusão se existir, senão tenta STATUS, senão sem ordem
    if ($colDataInc) {
        $sql .= " ORDER BY t.{$colDataInc} DESC ";
    } elseif ($colStatus) {
        $sql .= " ORDER BY t.{$colStatus} DESC ";
    }

    $sql .= " FETCH FIRST 200 ROWS ONLY ";

    // ==================================================================
    // 6) EXECUTA
    // ==================================================================
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sucesso' => true, 'dados' => $dados]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
