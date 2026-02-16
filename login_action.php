<?php
// login_action.php
// Responsável por autenticar no Oracle e carregar permissões.
// Este sistema utiliza sessão (check_session.php / APIs). Portanto:
// - Inicia sessão
// - Grava variáveis esperadas em $_SESSION
// - Também devolve JSON para o front

session_start();
header('Content-Type: application/json; charset=utf-8');

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$usuario = isset($input['usuario']) ? strtoupper(trim($input['usuario'])) : '';
$senha   = isset($input['senha']) ? (string)$input['senha'] : '';

if ($usuario === '' || $senha === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Preencha usuário e senha.']);
    exit;
}

try {
    // =========================
    // CONEXÃO (caminho robusto)
    // =========================
    $pathConexaoCandidates = [];

    // 1) Raiz do servidor (htdocs)
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $pathConexaoCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db_config/db_connect.php';
    }

    // 2) Fallback: se algum dia você mover o db_config para dentro do projeto
    $pathConexaoCandidates[] = dirname(__DIR__, 1) . '/db_config/db_connect.php';

    // 3) Fallback extra
    $pathConexaoCandidates[] = dirname(__DIR__, 1) . '/config/db_connect.php';

    $pathConexao = null;
    foreach ($pathConexaoCandidates as $cand) {
        if (file_exists($cand)) {
            $pathConexao = $cand;
            break;
        }
    }

    if ($pathConexao === null) {
        throw new Exception(
            "Arquivo de configuração de banco não encontrado. Tentei: " .
            implode(" | ", $pathConexaoCandidates)
        );
    }

    require_once($pathConexao);

    if (!isset($conn) || !$conn) {
        throw new Exception("Falha na conexão com banco Consinco.");
    }

    if ($conn instanceof PDO) {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    // Normaliza schema
    $schema = defined('DB_SCHEMA') ? strtoupper((string)DB_SCHEMA) : '';
    if ($schema === '') {
        throw new Exception("Constante DB_SCHEMA não definida ou vazia.");
    }

    // =========================
    // 1) AUTENTICAÇÃO
    // =========================
    $sqlAuth = "SELECT {$schema}.megag_fn_validasenhac5(:u, :p) AS RESULTADO FROM DUAL";
    $stmtAuth = $conn->prepare($sqlAuth);
    $stmtAuth->bindValue(':u', $usuario);
    $stmtAuth->bindValue(':p', $senha);
    $stmtAuth->execute();
    $rowAuth = $stmtAuth->fetch();

    if (
        !isset($rowAuth['RESULTADO']) ||
        $rowAuth['RESULTADO'] === null ||
        $rowAuth['RESULTADO'] === '0' ||
        strtoupper((string)$rowAuth['RESULTADO']) === 'N'
    ) {
        echo json_encode(['sucesso' => false, 'erro' => 'Usuário ou senha inválidos.']);
        exit;
    }

    // =========================
    // 2) PERMISSÕES + MENU (VIEW)
    // =========================
    // A view é o "source of truth" do que o usuário pode ver.
    // Se o usuário está na view com aquela aplicação, ele tem acesso.
    $sqlBase = "
        SELECT
            a.codmodulo        AS CODMODULO,
            a.modulo           AS MODULO,
            a.codaplicacao     AS CODAPLICACAO,
            a.aplicacao        AS aplicacao,
            a.codusuario       AS CODUSUARIO,
            a.linkmenu         AS LINKMENU,
            a.ordem_aplicacao  AS ORDEM_APLICACAO,
            a.ordem_modulo     AS ORDEM_MODULO
        FROM %s a
        WHERE a.codusuario = :u
        ORDER BY a.ordem_modulo, a.ordem_aplicacao
    ";

    // tenta com schema e sem schema (sinônimo)
    $viewCandidates = [
        $schema . '.MEGAG_VW_PERMISSOESUSUGRUPO',
        'MEGAG_VW_PERMISSOESUSUGRUPO',
    ];

    $acessos = null;
    $lastPdoError = null;

    foreach ($viewCandidates as $viewName) {
        try {
            $sqlAcessos = sprintf($sqlBase, $viewName);
            $stmtAcessos = $conn->prepare($sqlAcessos);
            $stmtAcessos->bindValue(':u', $usuario);
            $stmtAcessos->execute();
            $acessos = $stmtAcessos->fetchAll();
            $lastPdoError = null;
            break;
        } catch (PDOException $e) {
            $lastPdoError = $e;
        }
    }

    if ($acessos === null) {
        $dbUser = null;
        try {
            $stmtWho = $conn->query("SELECT USER AS USUARIO_CONECTADO FROM DUAL");
            $who = $stmtWho->fetch();
            $dbUser = $who['USUARIO_CONECTADO'] ?? null;
        } catch (Exception $ignored) {}

        $msgExtra = $dbUser ? " (Usuário conectado no Oracle: {$dbUser})" : "";
        $msgErro  = $lastPdoError ? $lastPdoError->getMessage() : "Tabela/View não encontrada ou sem permissão";
        throw new Exception("Falha ao consultar permissões na VIEW{$msgExtra}: {$msgErro}");
    }

    if (!$acessos || count($acessos) === 0) {
        echo json_encode(['sucesso' => false, 'erro' => 'Usuário sem permissões para acessar o sistema.']);
        exit;
    }

    // =========================
    // 2.1) PERMISSÕES (SIMPLES)
    // =========================
    // Se existe CODAPLICACAO na view para o usuário -> tem permissão.
    // Não existe mais "LER/CRIAR/EDITAR/EXCLUIR" aqui.
    $permissoesArray = [];
    foreach ($acessos as $row) {
        $chave = isset($row['CODAPLICACAO']) ? (string)$row['CODAPLICACAO'] : '';
        if ($chave === '') continue;
        $permissoesArray[$chave] = true;
    }

    // =========================
    // 2.2) MENU (guardar TODOS os campos da view na sessão)
    // =========================
    // Aqui guardamos exatamente o que vem da view, para o sidebar montar o menu 100% dinâmico.
    // Remove duplicados por CODAPLICACAO (a view pode ter repetição por usuário/grupo dependendo da modelagem)
    $menuAppsByCod = [];
    foreach ($acessos as $row) {
        $cod = (string)($row['CODAPLICACAO'] ?? '');
        if ($cod === '') continue;

        if (!isset($menuAppsByCod[$cod])) {
            $menuAppsByCod[$cod] = [
                'CODMODULO'       => (string)($row['CODMODULO'] ?? ''),
                'MODULO'          => (string)($row['MODULO'] ?? ''),
                'CODAPLICACAO'    => (string)($row['CODAPLICACAO'] ?? ''),
                'APLICACAO'       => (string)($row['APLICACAO'] ?? ''),
                'LINKMENU'        => (string)($row['LINKMENU'] ?? ''),
                'ORDEM_APLICACAO' => (string)($row['ORDEM_APLICACAO'] ?? ''),
                'ORDEM_MODULO'    => (string)($row['ORDEM_MODULO'] ?? ''),
            ];
        }
    }

    $menuApps = array_values($menuAppsByCod);

    // =========================
    // 3) NÍVEL
    // =========================
    $idGrupo = null;
    $nivelSistema = 'USER';

    $adminsWhitelist = ['CONSINCO'];
    if (in_array($usuario, $adminsWhitelist, true)) {
        $nivelSistema = 'ADMIN';
    }

    // =========================
    // 4) SESSÃO
    // =========================
    $_SESSION['logado']      = true;
    $_SESSION['usuario']     = $usuario;
    $_SESSION['nivel']       = $nivelSistema;
    $_SESSION['id_grupo']    = $idGrupo;

    // permissões simples
    $_SESSION['permissoes']  = $permissoesArray;

    // menu completo vindo da view
    $_SESSION['menu_apps']   = $menuApps;

    echo json_encode([
        'sucesso'    => true,
        'usuario'    => $usuario,
        'nivel'      => $nivelSistema,
        'id_grupo'   => $idGrupo,
        'permissoes' => $permissoesArray,
        'menu_apps'  => $menuApps,
    ]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>