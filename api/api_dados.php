<?php
// api/api_dados.php
header('Content-Type: application/json; charset=utf-8');

/*
 |------------------------------------------------------------
 | Nunca quebrar JSON com Notice/Warning
 |------------------------------------------------------------
*/
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../routes/check_session.php';

/*
 |------------------------------------------------------------
 | Sessão (evita session_start duplicado)
 |------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {

    /*
     |------------------------------------------------------------
     | 0) Usuário logado
     |------------------------------------------------------------
    */
    $usuarioLogado =
        $_SESSION['usuario']
        ?? $_SESSION['user']
        ?? $_SESSION['nome']
        ?? $_SESSION['login']
        ?? 'SYSTEM';

    $usuarioLogado = trim((string)$usuarioLogado);
    if ($usuarioLogado === '') $usuarioLogado = 'SYSTEM';

    /*
     |------------------------------------------------------------
     | 1) Conexão Oracle (robusta)
     |------------------------------------------------------------
    */
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
        throw new Exception("Arquivo de conexão não encontrado.");
    }

    require_once $pathConexao;

    if (!isset($conn) || !$conn) {
        throw new Exception("Falha na conexão com o banco.");
    }

    if ($conn instanceof PDO) {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    /*
     |------------------------------------------------------------
     | 2) Helpers
     |------------------------------------------------------------
    */
    $getColumns = function(string $owner, string $obj) use ($conn): array {
        $stmt = $conn->prepare("
            SELECT COLUMN_NAME
            FROM ALL_TAB_COLUMNS
            WHERE OWNER = :o AND TABLE_NAME = :t
            ORDER BY COLUMN_ID
        ");
        $stmt->execute([
            ':o' => strtoupper($owner),
            ':t' => strtoupper($obj),
        ]);
        return array_map('strtoupper', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    };

    $pick = function(array $cols, array $cands): ?string {
        foreach ($cands as $c) {
            $c = strtoupper($c);
            if (in_array($c, $cols, true)) return $c;
        }
        return null;
    };

    $isValidOracleIdent = function(string $name): bool {
        // Identificador Oracle simples (sem aspas): LETRA/_ no começo, depois LETRA/NUM/_
        return (bool)preg_match('/^[A-Z_][A-Z0-9_]*$/', strtoupper($name));
    };

    /*
     |------------------------------------------------------------
     | 3) Fonte dos tipos (VIEW do usuário)
     |------------------------------------------------------------
    */
    $OWNER_VIEW = 'CONSINCO';
    $VIEW_TIPOS = 'MEGAG_VW_TABS_IMPORTACAOUSU';

    $colsView = $getColumns($OWNER_VIEW, $VIEW_TIPOS);
    if (!$colsView) {
        throw new Exception("View {$OWNER_VIEW}.{$VIEW_TIPOS} não encontrada.");
    }

    // Pelo seu print: CODTABELA, DESCRICAO, NOMEARQUIVO, SQLEXECUTE, CODUSUARIO
    $V_COL_TIPO  = $pick($colsView, ['CODTABELA','TIPO','TABELA','TAB']);
    $V_COL_LABEL = $pick($colsView, ['DESCRICAO','DESCR','NOME','LABEL','TITULO']);
    $V_COL_USER  = $pick($colsView, ['CODUSUARIO','USUINCLUSAO','USUARIO','LOGIN','USU','SEQPESSOA']);

    if (!$V_COL_TIPO) throw new Exception("View de tipos sem coluna CODTABELA/TIPO.");
    if (!$V_COL_USER) throw new Exception("View de tipos sem coluna de usuário (ex: CODUSUARIO).");

    /*
     |------------------------------------------------------------
     | 4) LISTAR TIPOS (combo) - filtrado pelo usuário
     |------------------------------------------------------------
    */
    if (($_GET['action'] ?? '') === 'list_tipos') {

        $orderBy = $V_COL_LABEL ? $V_COL_LABEL : $V_COL_TIPO;

        $sql = "SELECT DISTINCT {$V_COL_TIPO} AS TIPO";
        if ($V_COL_LABEL) $sql .= ", {$V_COL_LABEL} AS DESCRICAO";
        $sql .= "
            FROM {$OWNER_VIEW}.{$VIEW_TIPOS}
            WHERE {$V_COL_USER} = :usu
              AND {$V_COL_TIPO} IS NOT NULL
            ORDER BY {$orderBy}
        ";

        $st = $conn->prepare($sql);
        $st->execute([':usu' => $usuarioLogado]);

        $tipos = [];
        foreach ($st->fetchAll() as $r) {
            $v = trim((string)($r['TIPO'] ?? ''));
            if ($v === '') continue;

            $d = trim((string)($r['DESCRICAO'] ?? ''));
            $tipos[] = [
                'value' => $v,
                'label' => $d ? "{$d} ({$v})" : $v
            ];
        }

        echo json_encode([
            'sucesso' => true,
            'tipos' => $tipos,
            'meta' => [
                'usuario' => $usuarioLogado,
                'view' => "{$OWNER_VIEW}.{$VIEW_TIPOS}",
                'col_tipo' => $V_COL_TIPO,
                'col_label' => $V_COL_LABEL,
                'col_user' => $V_COL_USER
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /*
     |------------------------------------------------------------
     | 5) Filtros (tipo + data obrigatórios)
     |------------------------------------------------------------
    */
    $tipo   = trim((string)($_GET['tipo'] ?? ''));
    $data   = trim((string)($_GET['dataInclusao'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $usuarioFiltro = trim((string)($_GET['usuario'] ?? '')); // opcional: campo na tela (mas sempre vai ser limitado ao usuário logado)

    if ($tipo === '' || $data === '') {
        echo json_encode([
            'sucesso' => true,
            'meta' => [
                'mensagem' => 'Informe tipo e dataInclusao para pesquisar.'
            ],
            'dados' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $data)) {
        throw new Exception("Data de inclusão inválida. Formato esperado: YYYY-MM-DD.");
    }

    if ($status !== '' && !in_array($status, ['S','E','C','P'], true)) {
        throw new Exception("Status inválido. Use: S, E, C ou P.");
    }

    // Valida o tipo para evitar injection
    $tipoUpper = strtoupper($tipo);
    if (!$isValidOracleIdent($tipoUpper)) {
        throw new Exception("Tipo inválido (nome de tabela inválido).");
    }

    /*
     |------------------------------------------------------------
     | 6) Confirma se o tipo pertence ao usuário (pela VIEW)
     |------------------------------------------------------------
    */
    $chk = $conn->prepare("
        SELECT COUNT(1) AS QTD
        FROM {$OWNER_VIEW}.{$VIEW_TIPOS}
        WHERE {$V_COL_USER} = :usu
          AND {$V_COL_TIPO} = :tipo
    ");
    $chk->execute([
        ':usu' => $usuarioLogado,
        ':tipo' => $tipoUpper
    ]);
    $qtd = (int)($chk->fetch()['QTD'] ?? 0);

    if ($qtd <= 0) {
        throw new Exception("Tipo não permitido para este usuário: {$tipoUpper}");
    }

    /*
     |------------------------------------------------------------
     | 7) Agora consulta a TABELA REAL do tipo (dados)
     |------------------------------------------------------------
    */
    $OWNER_DATA = 'CONSINCO';
    $TABLE_DATA = $tipoUpper;

    $colsData = $getColumns($OWNER_DATA, $TABLE_DATA);
    if (!$colsData) {
        throw new Exception("Tabela {$OWNER_DATA}.{$TABLE_DATA} não encontrada ou sem colunas visíveis.");
    }

    // Colunas padrão na tabela destino
    $COL_STATUS = $pick($colsData, ['STATUS']);

    // Usuário na tabela (tenta várias possibilidades)
    $COL_USER = $pick($colsData, [
        'USUINCLUSAO','USUARIO','LOGIN','USU','USU_INCLUSAO',
        'CODUSUARIO','SEQUSUARIO','SEQPESSOA',
        'USULANCTO'
    ]);

    // Data inclusão na tabela
    $COL_DTAINC = $pick($colsData, [
        'DTAINCLUSAO','DTINCLUSAO','DATAINCLUSAO',
        'DTA_INCLUSAO','DT_INCLUSAO','DATA_INCLUSAO'
    ]);

    // Data processamento/importação (opcional)
    $COL_DTAPROC = $pick($colsData, [
        'DTAIMPORTACAO','DTIMPORTACAO',
        'DTAPROCESSAMENTO','DTPROCESSAMENTO',
        'DTAIMPOTACAO','DTIMPOTACAO',
        'DTA_PROC','DT_PROC'
    ]);

    if (!$COL_DTAINC) {
        throw new Exception("Tabela {$OWNER_DATA}.{$TABLE_DATA} não possui coluna de data de inclusão (DTAINCLUSAO...).");
    }

    /*
     |------------------------------------------------------------
     | 8) Monta SQL (limite 5000)
     |------------------------------------------------------------
    */
    $params = [
        ':dta' => $data
    ];

    $sql = "
        SELECT *
        FROM (
            SELECT
                ROWNUM AS ID,
                t.*,
                TO_CHAR(t.{$COL_DTAINC}, 'DD/MM/YYYY HH24:MI:SS') AS DTAINCLUSAO_FMT
    ";

    if ($COL_DTAPROC) {
        $sql .= ",
                TO_CHAR(t.{$COL_DTAPROC}, 'DD/MM/YYYY HH24:MI:SS') AS DTAIMPORTACAO_FMT
        ";
    }

    $sql .= "
            FROM {$OWNER_DATA}.{$TABLE_DATA} t
            WHERE TRUNC(t.{$COL_DTAINC}) = TO_DATE(:dta,'YYYY-MM-DD')
    ";

    // Filtro por usuário:
    // regra: sempre limitar ao usuário logado se existir coluna de usuário na tabela.
    if ($COL_USER) {
        // Se o sistema grava exatamente o usuário, usa igualdade;
        // Se houver divergências (ex: ADMIN vs admin), usa upper trim.
        $sql .= " AND UPPER(TRIM(t.{$COL_USER})) = UPPER(:usu_logado)";
        $params[':usu_logado'] = $usuarioLogado;

        // (Opcional) se você quiser permitir o campo "Usuário de Inclusão" como LIKE dentro do universo do usuário logado
        // normalmente não faz sentido porque já é o próprio usuário, mas mantenho caso você queira filtrar por outra representação
        if ($usuarioFiltro !== '' && strtoupper($usuarioFiltro) !== strtoupper($usuarioLogado)) {
            $sql .= " AND UPPER(TRIM(t.{$COL_USER})) LIKE UPPER(:usu_like)";
            $params[':usu_like'] = '%' . $usuarioFiltro . '%';
        }
    }

    // Filtro status se existir coluna
    if ($status !== '' && $COL_STATUS) {
        $sql .= " AND t.{$COL_STATUS} = :status";
        $params[':status'] = $status;
    }

    // ordenação
    $sql .= " ORDER BY t.{$COL_DTAINC} DESC
        )
        WHERE ROWNUM <= 5000
    ";

    $st = $conn->prepare($sql);
    $st->execute($params);

    echo json_encode([
        'sucesso' => true,
        'meta' => [
            'usuario' => $usuarioLogado,
            'tipo' => $tipoUpper,
            'data' => $data,
            'view_tipos' => "{$OWNER_VIEW}.{$VIEW_TIPOS}",
            'tabela_dados' => "{$OWNER_DATA}.{$TABLE_DATA}",
            'col_status' => $COL_STATUS,
            'col_usuario' => $COL_USER,
            'col_dtainclusao' => $COL_DTAINC,
            'col_dtaprocessamento' => $COL_DTAPROC,
            'limite' => 5000
        ],
        'dados' => $st->fetchAll()
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
