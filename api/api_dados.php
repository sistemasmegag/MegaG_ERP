<?php
// api/api_dados.php
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

    // Configurações Oracle
    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    // ==================================================================
    // 2) UTIL: Descobrir colunas de uma tabela (ALL_TAB_COLUMNS)
    // ==================================================================
    $getTableColumns = function(string $owner, string $tableName) use ($conn): array {
        $stmt = $conn->prepare("
            SELECT COLUMN_NAME
            FROM ALL_TAB_COLUMNS
            WHERE OWNER = :own
              AND TABLE_NAME = :tab
            ORDER BY COLUMN_ID
        ");
        $stmt->execute([
            ':own' => strtoupper($owner),
            ':tab' => strtoupper($tableName),
        ]);
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $cols = array_map('strtoupper', $cols ?: []);
        return $cols;
    };

    $hasCol = function(array $cols, string $name): bool {
        return in_array(strtoupper($name), $cols, true);
    };

    $pickFirstExisting = function(array $cols, array $candidates): ?string {
        foreach ($candidates as $c) {
            if (in_array(strtoupper($c), $cols, true)) return strtoupper($c);
        }
        return null;
    };

    // ==================================================================
    // 3) LISTAR TIPOS (vem da MEGAG_TABS_IMPORTACAO)
    // ==================================================================
    $action = $_GET['action'] ?? '';

    if ($action === 'list_tipos') {

        $ownerTabs = 'CONSINCO';
        $tabTabs   = 'MEGAG_TABS_IMPORTACAO';

        $colsTabs = $getTableColumns($ownerTabs, $tabTabs);

        // Coluna que identifica a tabela/“tipo”
        $colTipo = $pickFirstExisting($colsTabs, [
            'CODTABELA','CODTAB','TABELA','NOME_TABELA','NM_TABELA','TAB','TABELA_ORIGEM'
        ]);

        // Coluna de descrição/label
        $colDesc = $pickFirstExisting($colsTabs, [
            'DESCRICAO','DESCR','DESCR_TABELA','DESCRICAO_TABELA','NOME','NOM
E'
        ]);

        if (!$colTipo) {
            throw new Exception("CONSINCO.MEGAG_TABS_IMPORTACAO não tem coluna identificadora do tipo/tabela. Procurei por: CODTABELA, CODTAB, TABELA, NOME_TABELA, NM_TABELA, TAB, TABELA_ORIGEM. Colunas existentes: " . implode(', ', $colsTabs));
        }

        // Monta SELECT com as colunas existentes
        $select = "SELECT " . $colTipo . " AS TIPO";
        if ($colDesc) $select .= ", " . $colDesc . " AS DESCRICAO";
        $select .= " FROM {$ownerTabs}.{$tabTabs} WHERE {$colTipo} IS NOT NULL";

        // Ordenação amigável
        if ($colDesc) $select .= " ORDER BY " . $colDesc;
        else $select .= " ORDER BY " . $colTipo;

        $stmt = $conn->prepare($select);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $tipos = [];
        foreach ($rows as $r) {
            $val = trim((string)($r['TIPO'] ?? ''));
            if ($val === '') continue;

            $desc = trim((string)($r['DESCRICAO'] ?? ''));
            $label = $desc !== '' ? "{$desc} ({$val})" : $val;

            $tipos[] = [
                'value' => $val,
                'label' => $label,
            ];
        }

        echo json_encode([
            'sucesso' => true,
            'tipos' => $tipos
        ]);
        exit;
    }

    // ==================================================================
    // 4) FILTROS FIXOS (conforme gestor)
    // ==================================================================
    $tipo         = trim((string)($_GET['tipo'] ?? ''));
    $usuario      = trim((string)($_GET['usuario'] ?? ''));
    $dataInclusao = trim((string)($_GET['dataInclusao'] ?? ''));
    $status       = trim((string)($_GET['status'] ?? ''));

    // status permitido: S/E/C/P
    if ($status !== '' && !in_array($status, ['S','E','C','P'], true)) {
        throw new Exception("Status inválido. Use: S, E, C ou P.");
    }

    // ==================================================================
    // 5) VALIDAÇÃO DO TIPO via whitelist (MEGAG_TABS_IMPORTACAO)
    //    (evita SQL injection em nome de tabela)
    // ==================================================================
    $ownerTabs = 'CONSINCO';
    $tabTabs   = 'MEGAG_TABS_IMPORTACAO';
    $colsTabs  = $getTableColumns($ownerTabs, $tabTabs);

    $colTipo = $pickFirstExisting($colsTabs, [
        'CODTABELA','CODTAB','TABELA','NOME_TABELA','NM_TABELA','TAB','TABELA_ORIGEM'
    ]);

    if (!$colTipo) {
        throw new Exception("CONSINCO.MEGAG_TABS_IMPORTACAO não tem coluna identificadora do tipo/tabela. Colunas existentes: " . implode(', ', $colsTabs));
    }

    // Se o tipo estiver vazio, tenta pegar o primeiro da lista (pra não quebrar a tela)
    if ($tipo === '') {
        $stmtFirst = $conn->prepare("SELECT {$colTipo} AS TIPO FROM {$ownerTabs}.{$tabTabs} WHERE {$colTipo} IS NOT NULL FETCH FIRST 1 ROWS ONLY");
        $stmtFirst->execute();
        $first = $stmtFirst->fetch(PDO::FETCH_ASSOC);
        $tipo = trim((string)($first['TIPO'] ?? ''));
    }

    if ($tipo === '') {
        throw new Exception("Nenhum tipo encontrado na MEGAG_TABS_IMPORTACAO.");
    }

    // Confirma se o tipo existe na MEGAG_TABS_IMPORTACAO
    $stmtChk = $conn->prepare("SELECT COUNT(1) AS QTD FROM {$ownerTabs}.{$tabTabs} WHERE {$colTipo} = :t");
    $stmtChk->execute([':t' => $tipo]);
    $qtd = (int)($stmtChk->fetch(PDO::FETCH_ASSOC)['QTD'] ?? 0);

    if ($qtd <= 0) {
        throw new Exception("Tipo informado não existe na MEGAG_TABS_IMPORTACAO: {$tipo}");
    }

    // ==================================================================
    // 6) CONSULTA DINÂMICA NA TABELA SELECIONADA (traz toda a tabela)
    // ==================================================================
    $ownerData = 'CONSINCO';
    $tableName = $tipo; // vindo da whitelist (MEGAG_TABS_IMPORTACAO)

    // Descobre colunas reais da tabela destino
    $colsData = $getTableColumns($ownerData, $tableName);
    if (empty($colsData)) {
        throw new Exception("Tabela {$ownerData}.{$tableName} não encontrada ou sem colunas visíveis.");
    }

    // Colunas padrão (variações)
    $colStatus = $pickFirstExisting($colsData, ['STATUS']);
    $colUser   = $pickFirstExisting($colsData, ['USULANCTO','USUINCLUSAO','USUARIOINCLUSAO','USUARIO_INCLUSAO','USU_INCLUSAO','USUARIO']);
    $colDtaInc = $pickFirstExisting($colsData, ['DTAINCLUSAO','DTA_INCLUSAO','DATAINCLUSAO','DATA_INCLUSAO','DT_INCLUSAO']);

    // (Opcional) colunas de “resultado/log”
    $colRes    = $pickFirstExisting($colsData, ['MSG_LOG','RESIMPOTACAO','RESULTADO','RESULT','RES','OBS','OBSERVACAO','OBSERVAÇÃO','LOG']);

    // Monta SQL
    $params = [];
    $sql = "SELECT ROWNUM AS ID, t.* FROM {$ownerData}.{$tableName} t WHERE 1=1";

    // Status
    if ($status !== '' && $colStatus) {
        $sql .= " AND t.{$colStatus} = :status";
        $params[':status'] = $status;
    }

    // Usuário inclusão (LIKE)
    if ($usuario !== '' && $colUser) {
        $sql .= " AND UPPER(TRIM(t.{$colUser})) LIKE UPPER(:usuario)";
        $params[':usuario'] = '%' . $usuario . '%';
    }

    // Data inclusão
    if ($dataInclusao !== '' && $colDtaInc) {
        $sql .= " AND TRUNC(t.{$colDtaInc}) = TO_DATE(:dtaInc, 'YYYY-MM-DD')";
        $params[':dtaInc'] = $dataInclusao;
    }

    // Ordenação preferida (data inclusão desc, senão ID desc)
    if ($colDtaInc) {
        $sql .= " ORDER BY t.{$colDtaInc} DESC";
    } else {
        $sql .= " ORDER BY ID DESC";
    }

    // Limite
    $sql .= " FETCH FIRST 200 ROWS ONLY";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso' => true,
        'meta' => [
            'tipo' => $tipo,
            'tabela' => "{$ownerData}.{$tableName}",
            'col_status' => $colStatus,
            'col_usuario' => $colUser,
            'col_dtainclusao' => $colDtaInc,
            'col_resultado' => $colRes,
        ],
        'dados' => $dados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
