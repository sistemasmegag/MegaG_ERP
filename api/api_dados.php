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
require_once __DIR__ . '/../bootstrap/db.php';

/*
 |------------------------------------------------------------
 | Sessao (evita session_start duplicado)
 |------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {

    /*
     |------------------------------------------------------------
     | 0) Usuario logado
     |------------------------------------------------------------
    */
    $usuarioLogado =
        $_SESSION['usuario']
        ?? $_SESSION['user']
        ?? $_SESSION['nome']
        ?? $_SESSION['login']
        ?? 'SYSTEM';

    $usuarioLogado = trim((string)$usuarioLogado);
    if ($usuarioLogado === '') {
        $usuarioLogado = 'SYSTEM';
    }

    /*
     |------------------------------------------------------------
     | 1) Conexao Oracle (robusta)
     |------------------------------------------------------------
    */
    $conn = mg_get_global_pdo();

    /*
     |------------------------------------------------------------
     | 2) Helpers
     |------------------------------------------------------------
    */
    $getColumns = function (string $owner, string $obj) use ($conn): array {
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

    $pick = function (array $cols, array $cands): ?string {
        foreach ($cands as $c) {
            $c = strtoupper($c);
            if (in_array($c, $cols, true)) {
                return $c;
            }
        }
        return null;
    };

    $isValidOracleIdent = function (string $name): bool {
        return (bool)preg_match('/^[A-Z_][A-Z0-9_]*$/', strtoupper($name));
    };

    /*
     |------------------------------------------------------------
     | 3) Fonte dos tipos (VIEW do usuario)
     |------------------------------------------------------------
    */
    $OWNER_VIEW = mg_db_schema_name();
    $VIEW_TIPOS = 'MEGAG_VW_TABS_IMPORTACAOUSU';

    $colsView = $getColumns($OWNER_VIEW, $VIEW_TIPOS);
    if (!$colsView) {
        throw new Exception("View {$OWNER_VIEW}.{$VIEW_TIPOS} nao encontrada.");
    }

    $V_COL_TIPO = $pick($colsView, ['CODTABELA', 'TIPO', 'TABELA', 'TAB']);
    $V_COL_LABEL = $pick($colsView, ['DESCRICAO', 'DESCR', 'NOME', 'LABEL', 'TITULO']);
    $V_COL_USER = $pick($colsView, ['CODUSUARIO', 'USUINCLUSAO', 'USUARIO', 'LOGIN', 'USU', 'SEQPESSOA']);

    if (!$V_COL_TIPO) {
        throw new Exception('View de tipos sem coluna CODTABELA/TIPO.');
    }
    if (!$V_COL_USER) {
        throw new Exception('View de tipos sem coluna de usuario (ex: CODUSUARIO).');
    }

    /*
     |------------------------------------------------------------
     | 4) LISTAR TIPOS (combo) - filtrado pelo usuario
     |------------------------------------------------------------
    */
    if (($_GET['action'] ?? '') === 'list_tipos') {
        $orderBy = $V_COL_LABEL ? $V_COL_LABEL : $V_COL_TIPO;

        $sql = "SELECT DISTINCT {$V_COL_TIPO} AS TIPO";
        if ($V_COL_LABEL) {
            $sql .= ", {$V_COL_LABEL} AS DESCRICAO";
        }
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
            if ($v === '') {
                continue;
            }

            $d = trim((string)($r['DESCRICAO'] ?? ''));
            $tipos[] = [
                'value' => $v,
                'label' => $d ? "{$d} ({$v})" : $v,
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
                'col_user' => $V_COL_USER,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /*
     |------------------------------------------------------------
     | 5) Filtros (tipo + data obrigatorios)
     |------------------------------------------------------------
    */
    $tipo = trim((string)($_GET['tipo'] ?? ''));
    $data = trim((string)($_GET['dataInclusao'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $usuarioFiltro = trim((string)($_GET['usuario'] ?? ''));

    if ($tipo === '' || $data === '') {
        echo json_encode([
            'sucesso' => true,
            'meta' => [
                'mensagem' => 'Informe tipo e dataInclusao para pesquisar.',
            ],
            'dados' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $data)) {
        throw new Exception('Data de inclusao invalida. Formato esperado: YYYY-MM-DD.');
    }

    if ($status !== '' && !in_array($status, ['S', 'E', 'C', 'P'], true)) {
        throw new Exception('Status invalido. Use: S, E, C ou P.');
    }

    $tipoUpper = strtoupper($tipo);
    if (!$isValidOracleIdent($tipoUpper)) {
        throw new Exception('Tipo invalido (nome de tabela invalido).');
    }

    /*
     |------------------------------------------------------------
     | 6) Consulta na tabela real do tipo
     |------------------------------------------------------------
    */
    $OWNER_DATA = 'CONSINCO';
    $TABLE_DATA = $tipoUpper;

    $colsData = $getColumns($OWNER_DATA, $TABLE_DATA);
    if (!$colsData) {
        throw new Exception("Tabela {$OWNER_DATA}.{$TABLE_DATA} nao encontrada ou sem colunas visiveis.");
    }

    $COL_STATUS = $pick($colsData, ['STATUS']);

    $COL_USER = $pick($colsData, [
        'USUINCLUSAO', 'USUARIO', 'LOGIN', 'USU', 'USU_INCLUSAO',
        'CODUSUARIO', 'SEQUSUARIO', 'SEQPESSOA', 'USULANCTO',
    ]);

    $COL_DTAINC = $pick($colsData, [
        'DTAINCLUSAO', 'DTINCLUSAO', 'DATAINCLUSAO',
        'DTA_INCLUSAO', 'DT_INCLUSAO', 'DATA_INCLUSAO',
    ]);

    $COL_DTAALT = $pick($colsData, [
        'DTAATUALIZACAO', 'DTATUALIZACAO',
        'DTA_ATUALIZACAO', 'DTA_ALTERACAO',
        'DATAATUALIZACAO', 'DATA_ATUALIZACAO',
        'DATA', 'DTA',
    ]);

    $COL_DTAPROC = $pick($colsData, [
        'DTAIMPORTACAO', 'DTIMPORTACAO',
        'DTAPROCESSAMENTO', 'DTPROCESSAMENTO',
        'DTAIMPOTACAO', 'DTIMPOTACAO',
        'DTA_PROC', 'DT_PROC',
    ]);

    $COL_DATE_FILTER = $COL_DTAINC ?: ($COL_DTAALT ?: $COL_DTAPROC);

    /*
     |------------------------------------------------------------
     | 7) Monta SQL (limite 5000)
     |------------------------------------------------------------
    */
    $params = [];

    $sql = "
        SELECT *
        FROM (
            SELECT
                ROWNUM AS ID,
                t.*
            FROM {$OWNER_DATA}.{$TABLE_DATA} t
            WHERE 1=1
    ";

    if ($COL_DATE_FILTER) {
        $sql .= " AND TRUNC(t.{$COL_DATE_FILTER}) = TO_DATE(:dta,'YYYY-MM-DD')";
        $params[':dta'] = $data;
    }

    if ($COL_USER) {
        $sql .= " AND UPPER(TRIM(t.{$COL_USER})) = UPPER(:usu_logado)";
        $params[':usu_logado'] = $usuarioLogado;

        if ($usuarioFiltro !== '' && strtoupper($usuarioFiltro) !== strtoupper($usuarioLogado)) {
            $sql .= " AND UPPER(TRIM(t.{$COL_USER})) LIKE UPPER(:usu_like)";
            $params[':usu_like'] = '%' . $usuarioFiltro . '%';
        }
    }

    if ($status !== '' && $COL_STATUS) {
        $sql .= " AND t.{$COL_STATUS} = :status";
        $params[':status'] = $status;
    }

    if ($COL_DATE_FILTER) {
        $sql .= " ORDER BY t.{$COL_DATE_FILTER} DESC";
    } elseif ($COL_STATUS) {
        $sql .= " ORDER BY t.{$COL_STATUS} DESC";
    }

    $sql .= "
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
            'col_data_alternativa' => $COL_DTAALT,
            'col_dtaprocessamento' => $COL_DTAPROC,
            'col_data_filtro' => $COL_DATE_FILTER,
            'limite' => 5000,
        ],
        'dados' => $st->fetchAll(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
