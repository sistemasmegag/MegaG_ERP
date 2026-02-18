// echo __FILE__; exit;
<?php
require_once __DIR__ . '/mg_api_bootstrap.php';

mg_need_permission('MEGACLICK');

$conn = getConexaoPDO();
$PKG  = mg_pkg('MEGAG_PKG_TASK');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$entity = $_GET['entity'] ?? null;

header('Content-Type: application/json; charset=utf-8');

try {

    if (!$entity) {
        throw new Exception('Parâmetro "entity" obrigatório.');
    }

    switch ($entity) {

        case 'ping':
            handle_ping($conn);
            break;

        case 'spaces':
            handle_spaces($conn, $PKG, $method);
            break;

        case 'lists':
            handle_lists($conn, $PKG, $method);
            break;

        case 'tasks':
            handle_tasks($conn, $PKG, $method);
            break;

        case 'comments':
            handle_comments($conn, $PKG, $method);
            break;

        case 'files':
            handle_files($conn, $PKG, $method);
            break;

        default:
            throw new Exception('Entity inválida.');
    }
} catch (Throwable $e) {

    try {
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
    } catch (Throwable $t) {
        // ignora
    }

    mg_json_error($e->getMessage());
}

/**
 * Helpers
 */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if ($raw !== '' && $data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido no body.');
    }

    return is_array($data) ? $data : [];
}

/**
 * PING
 */
function handle_ping(PDO $conn)
{
    $sql = "
        SELECT 'OK - ' || TO_CHAR(SYSDATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG
        FROM DUAL
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    mg_json_success([
        'msg' => $row['MSG']
    ]);
}

/**
 * SPACES
 */
function handle_spaces(PDO $conn, string $PKG, string $method)
{
    if ($method === 'GET') {

        $only_active = $_GET['only_active'] ?? 'S';

        $sql = "
            SELECT id, nome, ativo, criado_por, criado_em
              FROM megag_task_spaces
             WHERE (:only_active <> 'S' OR ativo = 'S')
             ORDER BY nome
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':only_active', $only_active);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        mg_json_success($rows);
    }

    if ($method === 'POST') {

        $body = read_json_body();
        $nome       = $body['nome'] ?? null;
        $criado_por = $body['criado_por'] ?? null;

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
                v_id  NUMBER;
            BEGIN
                {$PKG}.proc_spaces_create(
                    p_nome       => :p_nome,
                    p_criado_por => :p_criado_por,
                    p_id         => v_id,
                    p_ok         => v_ok,
                    p_err        => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;

                :p_id := v_id;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_nome', $nome);
        $stmt->bindParam(':p_criado_por', $criado_por);

        $p_id = 0;
        $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['id' => (int)$p_id]);
    }

    if ($method === 'PATCH' || $method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) throw new Exception('Parâmetro "id" obrigatório.');

        $body = read_json_body();
        $ativo = $body['ativo'] ?? null;

        $conn->beginTransaction();

        $sql = "
            BEGIN
                {$PKG}.proc_spaces_set_ativo(
                    p_id    => :p_id,
                    p_ativo => :p_ativo,
                    p_ok    => :p_ok,
                    p_err   => :p_err
                );
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_id', $id);
        $stmt->bindParam(':p_ativo', $ativo);

        $p_ok = null;
        $p_err = null;
        $stmt->bindParam(':p_ok', $p_ok, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1);
        $stmt->bindParam(':p_err', $p_err, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

        $stmt->execute();

        if ($p_ok !== 'S') {
            throw new Exception($p_err ?: 'Erro ao atualizar space.');
        }

        $conn->commit();
        mg_json_success(['ok' => true]);
    }

    throw new Exception('Método não permitido para spaces.');
}

/**
 * LISTS
 */
function handle_lists(PDO $conn, string $PKG, string $method)
{
    if ($method === 'GET') {
        $space_id = isset($_GET['space_id']) ? (int)$_GET['space_id'] : 0;
        if ($space_id <= 0) throw new Exception('Parâmetro "space_id" obrigatório.');

        $sql = "
            SELECT id, space_id, nome, ordem, ativo, criado_por, criado_em
              FROM megag_task_lists
             WHERE space_id = :space_id
             ORDER BY ordem, nome
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':space_id', $space_id);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        mg_json_success($rows);
    }

    if ($method === 'POST') {
        $body = read_json_body();

        $space_id   = isset($body['space_id']) ? (int)$body['space_id'] : 0;
        $nome       = $body['nome'] ?? null;
        $ordem      = isset($body['ordem']) ? (int)$body['ordem'] : null;
        $criado_por = $body['criado_por'] ?? null;

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
                v_id  NUMBER;
            BEGIN
                {$PKG}.proc_lists_create(
                    p_space_id   => :p_space_id,
                    p_nome       => :p_nome,
                    p_ordem      => :p_ordem,
                    p_criado_por => :p_criado_por,
                    p_id         => v_id,
                    p_ok         => v_ok,
                    p_err        => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;

                :p_id := v_id;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_space_id', $space_id);
        $stmt->bindParam(':p_nome', $nome);
        $stmt->bindParam(':p_ordem', $ordem);
        $stmt->bindParam(':p_criado_por', $criado_por);

        $p_id = 0;
        $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['id' => (int)$p_id]);
    }

    if ($method === 'PATCH' || $method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) throw new Exception('Parâmetro "id" obrigatório.');

        $body = read_json_body();
        $ativo = $body['ativo'] ?? null;

        $conn->beginTransaction();

        $sql = "
            BEGIN
                {$PKG}.proc_lists_set_ativo(
                    p_id    => :p_id,
                    p_ativo => :p_ativo,
                    p_ok    => :p_ok,
                    p_err   => :p_err
                );
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_id', $id);
        $stmt->bindParam(':p_ativo', $ativo);

        $p_ok = null;
        $p_err = null;
        $stmt->bindParam(':p_ok', $p_ok, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1);
        $stmt->bindParam(':p_err', $p_err, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

        $stmt->execute();

        if ($p_ok !== 'S') {
            throw new Exception($p_err ?: 'Erro ao atualizar list.');
        }

        $conn->commit();
        mg_json_success(['ok' => true]);
    }

    throw new Exception('Método não permitido para lists.');
}

/**
 * TASKS
 */
function handle_tasks(PDO $conn, string $PKG, string $method)
{
    if ($method === 'GET') {

        $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        $list_id = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;

        // DETALHE (SEM REFCURSOR)
        if ($task_id > 0) {

            $sql = "
                SELECT id,
                       list_id,
                       titulo,
                       DBMS_LOB.SUBSTR(descricao, 4000, 1) AS descricao,
                       status,
                       prioridade,
                       tags,
                       responsavel,
                       data_entrega,
                       criado_por,
                       criado_em,
                       atualizado_por,
                       atualizado_em
                  FROM megag_task_tasks
                 WHERE id = :task_id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            mg_json_success($row ?: null);
            return;
        }

        // LISTAGEM
        if ($list_id <= 0) {
            throw new Exception('Informe "list_id" (para listar) ou "task_id" (para obter).');
        }

        $sql = "
            SELECT id, list_id, titulo, status, prioridade, responsavel, data_entrega, tags, criado_por, criado_em
              FROM megag_task_tasks
             WHERE list_id = :list_id
             ORDER BY criado_em DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        mg_json_success($rows);
        return;
    }

    if ($method === 'POST') {
        $body = read_json_body();

        $list_id      = isset($body['list_id']) ? (int)$body['list_id'] : 0;
        $titulo       = $body['titulo'] ?? null;
        $descricao    = $body['descricao'] ?? null;
        $status       = $body['status'] ?? 'TODO';
        $prioridade   = $body['prioridade'] ?? 'MED';
        $tags         = $body['tags'] ?? null;
        $responsavel  = $body['responsavel'] ?? null;
        $data_entrega = $body['data_entrega'] ?? null;
        $criado_por   = $body['criado_por'] ?? null;

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
                v_id  NUMBER;
            BEGIN
                {$PKG}.proc_tasks_create(
                    p_list_id      => :p_list_id,
                    p_titulo       => :p_titulo,
                    p_descricao    => :p_descricao,
                    p_status       => :p_status,
                    p_prioridade   => :p_prioridade,
                    p_tags         => :p_tags,
                    p_responsavel  => :p_responsavel,
                    p_data_entrega => :p_data_entrega,
                    p_criado_por   => :p_criado_por,
                    p_id           => v_id,
                    p_ok           => v_ok,
                    p_err          => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;

                :p_id := v_id;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_list_id', $list_id);
        $stmt->bindParam(':p_titulo', $titulo);
        $stmt->bindValue(':p_descricao', $descricao, PDO::PARAM_STR);
        $stmt->bindParam(':p_status', $status);
        $stmt->bindParam(':p_prioridade', $prioridade);
        $stmt->bindParam(':p_tags', $tags);
        $stmt->bindParam(':p_responsavel', $responsavel);
        $stmt->bindParam(':p_data_entrega', $data_entrega);
        $stmt->bindParam(':p_criado_por', $criado_por);

        $p_id = 0;
        $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['id' => (int)$p_id]);
        return;
    }

    // MOVE (PATCH com action=move)
    if ($method === 'PATCH' && (($_GET['action'] ?? '') === 'move')) {
        $body = read_json_body();

        $task_id = isset($body['task_id']) ? (int)$body['task_id'] : 0;
        $status  = $body['status'] ?? null;
        $user    = $body['user'] ?? null;

        if ($task_id <= 0) throw new Exception('task_id obrigatório.');
        if (!$status) throw new Exception('status obrigatório.');
        if (!$user) throw new Exception('user obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
            BEGIN
                {$PKG}.proc_tasks_move(
                    p_task_id => :p_task_id,
                    p_status  => :p_status,
                    p_user    => :p_user,
                    p_ok      => v_ok,
                    p_err     => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_task_id', $task_id);
        $stmt->bindParam(':p_status', $status);
        $stmt->bindParam(':p_user', $user);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['ok' => true]);
        return;
    }

    // UPDATE (PUT/PATCH comum) - usa proc_tasks_update
    if (($method === 'PUT') || ($method === 'PATCH' && (($_GET['action'] ?? '') !== 'move'))) {

        $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        if ($task_id <= 0) throw new Exception('Parâmetro "task_id" obrigatório.');

        $body = read_json_body();

        $titulo       = $body['titulo'] ?? null;
        $descricao    = $body['descricao'] ?? null;
        $prioridade   = $body['prioridade'] ?? 'MED';
        $tags         = $body['tags'] ?? null;
        $responsavel  = $body['responsavel'] ?? null;
        $data_entrega = $body['data_entrega'] ?? null;
        $user         = $body['user'] ?? null;

        if (!$user) throw new Exception('Campo "user" obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
            BEGIN
                {$PKG}.proc_tasks_update(
                    p_task_id      => :p_task_id,
                    p_titulo       => :p_titulo,
                    p_descricao    => :p_descricao,
                    p_prioridade   => :p_prioridade,
                    p_tags         => :p_tags,
                    p_responsavel  => :p_responsavel,
                    p_data_entrega => :p_data_entrega,
                    p_user         => :p_user,
                    p_ok           => v_ok,
                    p_err          => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_task_id', $task_id);
        $stmt->bindParam(':p_titulo', $titulo);
        $stmt->bindValue(':p_descricao', $descricao, PDO::PARAM_STR);
        $stmt->bindParam(':p_prioridade', $prioridade);
        $stmt->bindParam(':p_tags', $tags);
        $stmt->bindParam(':p_responsavel', $responsavel);
        $stmt->bindParam(':p_data_entrega', $data_entrega);
        $stmt->bindParam(':p_user', $user);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['ok' => true]);
        return;
    }

    if ($method === 'DELETE') {

        $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        $user    = $_GET['user'] ?? null;

        if ($task_id <= 0) throw new Exception('Parâmetro "task_id" obrigatório.');
        if (!$user) throw new Exception('Parâmetro "user" obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
            BEGIN
                {$PKG}.proc_tasks_delete(
                    p_task_id => :p_task_id,
                    p_user    => :p_user,
                    p_ok      => v_ok,
                    p_err     => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_task_id', $task_id);
        $stmt->bindParam(':p_user', $user);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['ok' => true]);
        return;
    }

    throw new Exception('Método não permitido para tasks.');
}

// COMMENTS + FILES (tem que ficar no MESMO /api/tasks.php)
// =====================================================

function handle_comments(PDO $conn, string $PKG, string $method)
{
    // =========================
    // GET: lista comentários de uma task
    // /api/tasks.php?entity=comments&task_id=1
    // =========================
    if ($method === 'GET') {

        $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        if ($task_id <= 0) {
            throw new Exception('Parâmetro "task_id" obrigatório.');
        }

        // 1) Tenta via PKG (refcursor)
        try {
            $sql = "BEGIN {$PKG}.proc_comments_list(
                        p_task_id => :p_task_id,
                        p_ok      => :p_ok,
                        p_err     => :p_err,
                        p_rc      => :p_rc
                    ); END;";

            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':p_task_id', $task_id);

            // buffers OUT (IMPORTANTE no PDO_OCI)
            $p_ok  = str_repeat(' ', 1);
            $p_err = str_repeat(' ', 4000);

            $stmt->bindParam(':p_ok',  $p_ok,  PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 1);
            $stmt->bindParam(':p_err', $p_err, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

            // cursor precisa ser um statement "dummy" preparado
            $p_rc = $conn->prepare("SELECT 1 FROM DUAL");
            $stmt->bindParam(':p_rc', $p_rc, PDO::PARAM_STMT);

            $stmt->execute();

            if (trim($p_ok) !== 'S') {
                throw new Exception(trim($p_err) ?: 'Erro ao listar comentários.');
            }

            $rows = [];
            while ($row = $p_rc->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = $row;
            }

            mg_json_success($rows);
            return;
        } catch (Throwable $e) {
            // 2) fallback seguro: SELECT direto (se o refcursor der problema no ambiente)
            $sql = "
                SELECT id,
                       task_id,
                       DBMS_LOB.SUBSTR(comentario, 4000, 1) AS comentario,
                       criado_por,
                       criado_em
                  FROM megag_task_comments
                 WHERE task_id = :task_id
                 ORDER BY criado_em DESC, id DESC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            mg_json_success($rows);
            return;
        }
    }

    // =========================
    // POST: cria comentário
    // /api/tasks.php?entity=comments
    // body: {"task_id":1,"comentario":"...","criado_por":"Felipe"}
    // =========================
    if ($method === 'POST') {

        $body = read_json_body();

        $task_id    = isset($body['task_id']) ? (int)$body['task_id'] : 0;
        $comentario = $body['comentario'] ?? null;
        $criado_por = $body['criado_por'] ?? null;

        if ($task_id <= 0) throw new Exception('task_id obrigatório.');
        if (!$comentario) throw new Exception('comentario obrigatório.');
        if (!$criado_por) throw new Exception('criado_por obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
                v_id  NUMBER;
            BEGIN
                {$PKG}.proc_comments_create(
                    p_task_id    => :p_task_id,
                    p_comentario => :p_comentario,
                    p_criado_por => :p_criado_por,
                    p_id         => v_id,
                    p_ok         => v_ok,
                    p_err        => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;

                :p_id := v_id;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_task_id', $task_id);
        $stmt->bindValue(':p_comentario', $comentario, PDO::PARAM_STR);
        $stmt->bindParam(':p_criado_por', $criado_por);

        $p_id = 0;
        $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['id' => (int)$p_id]);
        return;
    }

    // =========================
    // DELETE: exclui comentário
    // /api/tasks.php?entity=comments&comment_id=10&user=Felipe
    // =========================
    if ($method === 'DELETE') {

        $comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
        $user       = $_GET['user'] ?? null;

        if ($comment_id <= 0) throw new Exception('Parâmetro "comment_id" obrigatório.');
        if (!$user) throw new Exception('Parâmetro "user" obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
            BEGIN
                {$PKG}.proc_comments_delete(
                    p_comment_id => :p_comment_id,
                    p_user       => :p_user,
                    p_ok         => v_ok,
                    p_err        => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_comment_id', $comment_id);
        $stmt->bindParam(':p_user', $user);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['ok' => true]);
        return;
    }

    throw new Exception('Método não permitido para comments.');
}

function handle_files(PDO $conn, string $PKG, string $method)
{
    // =====================================================
    // LISTAR ANEXOS (GET) - SEM REFCURSOR (evita ORA-01008 no PDO_OCI)
    // GET /api/tasks.php?entity=files&task_id=1
    // =====================================================
    if ($method === 'GET' && (($_GET['action'] ?? '') === '')) {

        $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
        if ($task_id <= 0) throw new Exception('Parâmetro "task_id" obrigatório.');

        $sql = "
            SELECT
                id,
                task_id,
                file_name,
                mime_type,
                file_size,
                criado_por,
                criado_em
            FROM megag_task_files
            WHERE task_id = :task_id
            ORDER BY criado_em DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        mg_json_success($rows);
        return;
    }

    // =====================================================
    // DOWNLOAD (GET)
    // GET /api/tasks.php?entity=files&action=download&file_id=123
    // =====================================================
    if ($method === 'GET' && (($_GET['action'] ?? '') === 'download')) {

        $file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        if ($file_id <= 0) throw new Exception('Parâmetro "file_id" obrigatório.');

        $sql = "
            SELECT file_name,
                   mime_type,
                   file_size,
                   file_blob
              FROM megag_task_files
             WHERE id = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $file_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception('Anexo não encontrado.');
        }

        $filename = $row['FILE_NAME'] ?? 'arquivo';
        $mime     = $row['MIME_TYPE'] ?: 'application/octet-stream';
        $blob     = $row['FILE_BLOB'];

        if (ob_get_length()) {
            @ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('X-Content-Type-Options: nosniff');

        // PDO_OCI pode retornar LOB como stream resource
        if (is_resource($blob)) {
            fpassthru($blob);
        } else {
            echo $blob;
        }
        exit;
    }

    // =====================================================
    // UPLOAD (POST)
    // POST /api/tasks.php?entity=files&action=upload
    // form-data: task_id, user, file
    // =====================================================
    if ($method === 'POST' && (($_GET['action'] ?? '') === 'upload')) {

        $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        $user    = $_POST['user'] ?? null;

        if ($task_id <= 0) throw new Exception('task_id obrigatório.');
        if (!$user) throw new Exception('user obrigatório.');
        if (!isset($_FILES['file'])) throw new Exception('Arquivo "file" obrigatório.');

        $f = $_FILES['file'];

        if (!empty($f['error'])) {
            throw new Exception('Erro no upload: ' . $f['error']);
        }

        $tmp  = $f['tmp_name'];
        $name = $f['name'] ?? 'arquivo';
        $size = (int)($f['size'] ?? 0);
        $mime = $f['type'] ?? 'application/octet-stream';

        // ✅ IMPORTANTE (PDO_OCI): LOB precisa ser stream resource
        $lob = @fopen($tmp, 'rb');
        if ($lob === false) {
            throw new Exception('Falha ao abrir arquivo para leitura.');
        }

        $conn->beginTransaction();

        $sql = "
            INSERT INTO megag_task_files
                (id, task_id, file_name, mime_type, file_size, file_blob, criado_por, criado_em)
            VALUES
                (seq_megag_task_files.NEXTVAL, :task_id, :file_name, :mime_type, :file_size, :file_blob, :criado_por, SYSDATE)
            RETURNING id INTO :new_id
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $stmt->bindParam(':file_name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':mime_type', $mime, PDO::PARAM_STR);
        $stmt->bindParam(':file_size', $size, PDO::PARAM_INT);

        // ✅ bind do LOB como stream
        $stmt->bindParam(':file_blob', $lob, PDO::PARAM_LOB);

        $stmt->bindParam(':criado_por', $user, PDO::PARAM_STR);

        $new_id = 0;
        $stmt->bindParam(':new_id', $new_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);

        $stmt->execute();
        $conn->commit();

        @fclose($lob);

        mg_json_success(['id' => (int)$new_id]);
        return;
    }

    // =====================================================
    // DELETE (DELETE) - via PKG
    // DELETE /api/tasks.php?entity=files&file_id=123&user=Felipe
    // =====================================================
    if ($method === 'DELETE') {

        $file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
        $user    = $_GET['user'] ?? null;

        if ($file_id <= 0) throw new Exception('Parâmetro "file_id" obrigatório.');
        if (!$user) throw new Exception('Parâmetro "user" obrigatório.');

        $conn->beginTransaction();

        $sql = "
            DECLARE
                v_ok  VARCHAR2(1);
                v_err VARCHAR2(4000);
            BEGIN
                {$PKG}.proc_files_delete(
                    p_file_id => :p_file_id,
                    p_user    => :p_user,
                    p_ok      => v_ok,
                    p_err     => v_err
                );

                IF v_ok <> 'S' THEN
                    RAISE_APPLICATION_ERROR(-20000, v_err);
                END IF;
            END;
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':p_file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindParam(':p_user', $user, PDO::PARAM_STR);

        $stmt->execute();
        $conn->commit();

        mg_json_success(['ok' => true]);
        return;
    }

    throw new Exception('Método não permitido para files.');
}
