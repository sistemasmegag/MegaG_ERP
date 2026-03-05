<?php
require_once __DIR__ . '/mg_api_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Fallback local: em alguns projetos o bootstrap não define mg_json().
 * Mantém compatibilidade sem quebrar outros ambientes.
 */
if (!function_exists('mg_json')) {
  function mg_json(array $data, int $httpCode = 200): void
  {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}

/**
 * Módulo Chamados - API única
 * Ex:
 *  /api/chamados.php?entity=ping
 *  /api/chamados.php?entity=chamados   (GET list / GET one / POST create / PUT update status/assign/close)
 *  /api/chamados.php?entity=mensagens  (GET list / POST add)
 *  /api/chamados.php?entity=portal     (POST request_token / POST login_by_token)
 *  /api/chamados.php?entity=email_inbound (POST register+process)
 */

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$entity = $_GET['entity'] ?? null;

/**
 * 🔒 Segurança:
 * - email_inbound: libera só com header X-MG-EMAIL-SECRET (job IMAP)
 * - demais entidades: exige permissão normal
 */
if ($entity === 'email_inbound') {
  $secret = $_SERVER['HTTP_X_MG_EMAIL_SECRET'] ?? '';
  if ($secret !== 'coloque-um-segredo-aqui') {
    mg_json(['sucesso' => false, 'erro' => 'Unauthorized'], 401);
  }
}
else {
  mg_need_permission('MEGACLICK'); // ou 'MEGACHAMADOS'
}

$conn = getConexaoPDO();
$PKG = mg_pkg('MEGAG_PKG_CHAMADO');

try {

  if (!$entity)
    throw new Exception('Parâmetro "entity" obrigatório.');

  switch ($entity) {

    case 'ping':
      mg_json(['sucesso' => true, 'pong' => true]);
      break;

    case 'chamados':
      handle_chamados($conn, $PKG, $method);
      break;

    case 'mensagens':
      handle_mensagens($conn, $PKG, $method);
      break;

    case 'anexos':
      handle_anexos($conn, $PKG, $method);
      break;

    case 'portal':
      handle_portal($conn, $PKG, $method);
      break;

    case 'email_inbound':
      handle_email_inbound($conn, $PKG, $method);
      break;

    default:
      throw new Exception('Entity inválida.');
  }
}
catch (Throwable $e) {

  try {
    if (isset($conn)) {
      $conn->rollBack();
    }
  }
  catch (Throwable $t) {
  }

  mg_json([
    'sucesso' => false,
    'erro' => $e->getMessage()
  ], 500);
}


/* =========================================================
 Helpers
 ========================================================= */

function mg_body_json(): array
{
  $raw = file_get_contents('php://input');
  if (!$raw)
    return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

function mg_out_buf(int $len = 4000): string
{
  return str_repeat(' ', $len);
}

function mg_rc(PDO $conn)
{
  return $conn->prepare("SELECT 1 FROM DUAL");
}

function parse_cod_publico_from_subject(?string $subject): ?string
{
  if (!$subject)
    return null;
  // Ex: [CH-2026-000123] qualquer coisa
  if (preg_match('/\[(CH-\d{4}-\d{6,})\]/i', $subject, $m)) {
    return strtoupper($m[1]);
  }
  return null;
}


/* =========================================================
 HANDLERS
 ========================================================= */

function handle_chamados(PDO $conn, string $PKG, string $method)
{

  if ($method === 'GET') {

    $id = $_GET['id_chamado'] ?? null;

    if ($id) {
      // GET one
      $stmt = $conn->prepare("
        BEGIN
          {$PKG}.proc_chamado_get(
            p_id_chamado => :p_id,
            o_cur        => :p_rc,
            r_tiporet    => :r_tiporet,
            r_codret     => :r_codret,
            r_msg        => :r_msg
          );
        END;
      ");

      $rc = mg_rc($conn);

      $r_tiporet = mg_out_buf(30);
      $r_codret = mg_out_buf(30);
      $r_msg = mg_out_buf(4000);

      $p_id = (int)$id;

      $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
      $stmt->bindParam(':p_rc', $rc, PDO::PARAM_STMT);
      $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
      $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
      $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

      $stmt->execute();
      $rows = $rc->fetchAll(PDO::FETCH_ASSOC);

      mg_json([
        'sucesso' => true,
        'tiporet' => trim($r_tiporet),
        'codret' => trim($r_codret),
        'msg' => trim($r_msg),
        'dados' => $rows
      ]);
      return;
    }

    // GET list
    $tipo_visao = strtoupper(trim((string)($_GET['tipo_visao'] ?? 'INTERNO'))); // INTERNO/EXTERNO
    $id_usuario_interno = (int)($_GET['id_usuario_interno'] ?? 0);
    $id_contato_externo = (int)($_GET['id_contato_externo'] ?? 0);
    $email_externo = trim((string)($_GET['email_externo'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $prioridade = trim((string)($_GET['prioridade'] ?? ''));
    $grupo = trim((string)($_GET['grupo_atendimento'] ?? ''));
    $resp = (int)($_GET['responsavel_id'] ?? 0);
    $texto = trim((string)($_GET['texto'] ?? ''));
    $dt_ini = $_GET['dt_ini'] ?? null; // YYYY-MM-DD
    $dt_fim = $_GET['dt_fim'] ?? null;

    /**
     * ⚠️ ORA-01008 (nem todas as variáveis são bindadas) no PDO_OCI:
     * - ocorre se existir placeholder no SQL que não foi bindado
     * - e também pode ocorrer com bindValue(null) em alguns ambientes
     * Solução: usar variáveis + bindParam para tudo e duplicar placeholders repetidos.
     *
     * ✅ Aqui usamos placeholders ÚNICOS (p_dt_ini / p_dt_fim) para evitar ORA-01008 no PDO_OCI
     * quando o mesmo placeholder aparece 2x no SQL.
     */
    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_chamado_list(
          p_tipo_visao         => :p_tipo_visao,
          p_id_usuario_interno => :p_uid,
          p_id_contato_externo => :p_cid,
          p_email_externo      => :p_email,
          p_status             => :p_status,
          p_prioridade         => :p_pri,
          p_grupo_atendimento  => :p_grupo,
          p_responsavel_id     => :p_resp,
          p_texto              => :p_texto,
          p_dt_ini             => TO_DATE(:p_dt_ini, 'YYYY-MM-DD'),
          p_dt_fim             => TO_DATE(:p_dt_fim, 'YYYY-MM-DD'),
          o_cur                => :p_rc,
          r_tiporet            => :r_tiporet,
          r_codret             => :r_codret,
          r_msg                => :r_msg
        );
      END;
    ");

    $rc = mg_rc($conn);

    $r_tiporet = mg_out_buf(30);
    $r_codret = mg_out_buf(30);
    $r_msg = mg_out_buf(4000);

    // Variáveis (bindParam em tudo, inclusive null)
    $p_tipo_visao = $tipo_visao;

    $p_uid = $id_usuario_interno ?: null;
    $p_cid = $id_contato_externo ?: null;

    $p_email = $email_externo ?: null;
    $p_status = $status ?: null;
    $p_pri = $prioridade ?: null;
    $p_grupo = $grupo ?: null;

    $p_resp = $resp ?: null;
    $p_texto = $texto ?: null;

    $p_dt_ini = $dt_ini ?: null;
    $p_dt_fim = $dt_fim ?: null;

    $stmt->bindParam(':p_tipo_visao', $p_tipo_visao, PDO::PARAM_STR, 20);

    if ($p_uid === null)
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);

    if ($p_cid === null)
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_INT);

    if ($p_email === null)
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_STR, 200);

    if ($p_status === null)
      $stmt->bindParam(':p_status', $p_status, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_status', $p_status, PDO::PARAM_STR, 30);

    if ($p_pri === null)
      $stmt->bindParam(':p_pri', $p_pri, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_pri', $p_pri, PDO::PARAM_STR, 30);

    if ($p_grupo === null)
      $stmt->bindParam(':p_grupo', $p_grupo, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_grupo', $p_grupo, PDO::PARAM_STR, 80);

    if ($p_resp === null)
      $stmt->bindParam(':p_resp', $p_resp, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_resp', $p_resp, PDO::PARAM_INT);

    if ($p_texto === null)
      $stmt->bindParam(':p_texto', $p_texto, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_texto', $p_texto, PDO::PARAM_STR, 200);

    if ($p_dt_ini === null)
      $stmt->bindParam(':p_dt_ini', $p_dt_ini, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_dt_ini', $p_dt_ini, PDO::PARAM_STR, 10);

    if ($p_dt_fim === null)
      $stmt->bindParam(':p_dt_fim', $p_dt_fim, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_dt_fim', $p_dt_fim, PDO::PARAM_STR, 10);

    $stmt->bindParam(':p_rc', $rc, PDO::PARAM_STMT);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();
    $rows = $rc->fetchAll(PDO::FETCH_ASSOC);

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'dados' => $rows
    ]);
    return;
  }

  if ($method === 'POST') {
    $j = mg_body_json();

    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_chamado_create(
          p_cod_empresa        => :p_cod_empresa,
          p_tipo_solicitante   => :p_tipo,
          p_id_usuario_interno => :p_uid,
          p_id_contato_externo => :p_cid,
          p_email_solicitante  => :p_email,
          p_titulo             => :p_titulo,
          p_descricao          => :p_desc,
          p_prioridade         => :p_pri,
          p_categoria          => :p_cat,
          p_subcategoria       => :p_subcat,
          p_modulo_origem      => :p_mod,
          p_referencia_tipo    => :p_ref_t,
          p_referencia_id      => :p_ref_i,
          p_grupo_atendimento  => :p_grupo,
          p_sla_minutos        => :p_sla,
          p_criado_por         => :p_criado_por,
          o_id_chamado         => :o_id,
          o_cod_publico        => :o_cod,
          r_tiporet            => :r_tiporet,
          r_codret             => :r_codret,
          r_msg                => :r_msg
        );
      END;
    ");

    $o_id = 0;
    $o_cod = mg_out_buf(30);

    $r_tiporet = mg_out_buf(30);
    $r_codret = mg_out_buf(30);
    $r_msg = mg_out_buf(4000);

    $p_cod_empresa = isset($j['cod_empresa']) ? (int)$j['cod_empresa'] : null;
    $p_tipo = strtoupper(trim((string)($j['tipo_solicitante'] ?? 'I')));

    $p_uid = isset($j['id_usuario_interno']) ? (int)$j['id_usuario_interno'] : null;
    $p_cid = isset($j['id_contato_externo']) ? (int)$j['id_contato_externo'] : null;

    $p_email = trim((string)($j['email_solicitante'] ?? '')) ?: null;
    $p_titulo = trim((string)($j['titulo'] ?? ''));

    $p_desc = $j['descricao'] ?? null;

    $p_pri = strtoupper(trim((string)($j['prioridade'] ?? 'MEDIA')));

    $p_cat = trim((string)($j['categoria'] ?? '')) ?: null;
    $p_subcat = trim((string)($j['subcategoria'] ?? '')) ?: null;
    $p_mod = trim((string)($j['modulo_origem'] ?? '')) ?: null;
    $p_ref_t = trim((string)($j['referencia_tipo'] ?? '')) ?: null;
    $p_ref_i = trim((string)($j['referencia_id'] ?? '')) ?: null;
    $p_grupo = trim((string)($j['grupo_atendimento'] ?? '')) ?: null;

    $p_sla = isset($j['sla_minutos']) ? (int)$j['sla_minutos'] : null;
    $p_criado_por = trim((string)($j['criado_por'] ?? 'API')) ?: 'API';

    if ($p_cod_empresa === null)
      $stmt->bindParam(':p_cod_empresa', $p_cod_empresa, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_cod_empresa', $p_cod_empresa, PDO::PARAM_INT);

    $stmt->bindParam(':p_tipo', $p_tipo, PDO::PARAM_STR, 1);

    if ($p_uid === null)
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);

    if ($p_cid === null)
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_INT);

    if ($p_email === null)
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_STR, 200);

    $stmt->bindParam(':p_titulo', $p_titulo, PDO::PARAM_STR, 200);

    if ($p_desc === null)
      $stmt->bindParam(':p_desc', $p_desc, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_desc', $p_desc, PDO::PARAM_STR, 4000);

    $stmt->bindParam(':p_pri', $p_pri, PDO::PARAM_STR, 20);

    if ($p_cat === null)
      $stmt->bindParam(':p_cat', $p_cat, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_cat', $p_cat, PDO::PARAM_STR, 80);

    if ($p_subcat === null)
      $stmt->bindParam(':p_subcat', $p_subcat, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_subcat', $p_subcat, PDO::PARAM_STR, 80);

    if ($p_mod === null)
      $stmt->bindParam(':p_mod', $p_mod, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_mod', $p_mod, PDO::PARAM_STR, 80);

    if ($p_ref_t === null)
      $stmt->bindParam(':p_ref_t', $p_ref_t, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ref_t', $p_ref_t, PDO::PARAM_STR, 80);

    if ($p_ref_i === null)
      $stmt->bindParam(':p_ref_i', $p_ref_i, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ref_i', $p_ref_i, PDO::PARAM_STR, 120);

    if ($p_grupo === null)
      $stmt->bindParam(':p_grupo', $p_grupo, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_grupo', $p_grupo, PDO::PARAM_STR, 80);

    if ($p_sla === null)
      $stmt->bindParam(':p_sla', $p_sla, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_sla', $p_sla, PDO::PARAM_INT);

    $stmt->bindParam(':p_criado_por', $p_criado_por, PDO::PARAM_STR, 60);

    $stmt->bindParam(':o_id', $o_id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
    $stmt->bindParam(':o_cod', $o_cod, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'id_chamado' => (int)$o_id,
      'cod_publico' => trim($o_cod)
    ]);
    return;
  }

  // PUT: ações (assign / status / close)
  if ($method === 'PUT') {
    $j = mg_body_json();
    $acao = strtoupper(trim((string)($j['acao'] ?? '')));

    $id = (int)($j['id_chamado'] ?? 0);
    if (!$id)
      throw new Exception('id_chamado obrigatório.');

    $r_tiporet = mg_out_buf(30);
    $r_codret = mg_out_buf(30);
    $r_msg = mg_out_buf(4000);

    if ($acao === 'ASSIGN') {
      $stmt = $conn->prepare("
        BEGIN
          {$PKG}.proc_chamado_assign(
            p_id_chamado             => :p_id,
            p_responsavel_id_usuario => :p_resp,
            p_atualizado_por         => :p_user,
            r_tiporet                => :r_tiporet,
            r_codret                 => :r_codret,
            r_msg                    => :r_msg
          );
        END;
      ");

      $resp = (int)($j['responsavel_id_usuario'] ?? 0);
      if (!$resp)
        throw new Exception('responsavel_id_usuario obrigatório.');

      $p_id = $id;
      $p_resp = $resp;
      $p_user = trim((string)($j['atualizado_por'] ?? 'API')) ?: 'API';

      $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
      $stmt->bindParam(':p_resp', $p_resp, PDO::PARAM_INT);
      $stmt->bindParam(':p_user', $p_user, PDO::PARAM_STR, 60);
    }
    elseif ($acao === 'STATUS') {
      $stmt = $conn->prepare("
        BEGIN
          {$PKG}.proc_chamado_change_status(
            p_id_chamado     => :p_id,
            p_status         => :p_status,
            p_atualizado_por => :p_user,
            r_tiporet        => :r_tiporet,
            r_codret         => :r_codret,
            r_msg            => :r_msg
          );
        END;
      ");

      $status = trim((string)($j['status'] ?? ''));
      if (!$status)
        throw new Exception('status obrigatório.');

      $p_id = $id;
      $p_status = $status;
      $p_user = trim((string)($j['atualizado_por'] ?? 'API')) ?: 'API';

      $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
      $stmt->bindParam(':p_status', $p_status, PDO::PARAM_STR, 30);
      $stmt->bindParam(':p_user', $p_user, PDO::PARAM_STR, 60);
    }
    elseif ($acao === 'CLOSE') {
      $stmt = $conn->prepare("
        BEGIN
          {$PKG}.proc_chamado_close(
            p_id_chamado     => :p_id,
            p_atualizado_por => :p_user,
            r_tiporet        => :r_tiporet,
            r_codret         => :r_codret,
            r_msg            => :r_msg
          );
        END;
      ");

      $p_id = $id;
      $p_user = trim((string)($j['atualizado_por'] ?? 'API')) ?: 'API';

      $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
      $stmt->bindParam(':p_user', $p_user, PDO::PARAM_STR, 60);
    }
    else {
      throw new Exception('acao inválida. Use ASSIGN / STATUS / CLOSE');
    }

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
    ]);
    return;
  }

  throw new Exception('Método não suportado em chamados.');
}

function handle_mensagens(PDO $conn, string $PKG, string $method)
{

  if ($method === 'GET') {
    $id = (int)($_GET['id_chamado'] ?? 0);
    if (!$id)
      throw new Exception('id_chamado obrigatório.');

    $incluir = strtoupper(trim((string)($_GET['incluir_internas'] ?? 'N')));
    if ($incluir !== 'S')
      $incluir = 'N';

    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_msg_list(
          p_id_chamado       => :p_id,
          p_incluir_internas => :p_inc,
          o_cur              => :p_rc,
          r_tiporet          => :r_tiporet,
          r_codret           => :r_codret,
          r_msg              => :r_msg
        );
      END;
    ");

    $rc = mg_rc($conn);

    $r_tiporet = mg_out_buf(30);
    $r_codret = mg_out_buf(30);
    $r_msg = mg_out_buf(4000);

    $p_id = $id;
    $p_inc = $incluir;

    $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
    $stmt->bindParam(':p_inc', $p_inc, PDO::PARAM_STR, 1);
    $stmt->bindParam(':p_rc', $rc, PDO::PARAM_STMT);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();
    $rows = $rc->fetchAll(PDO::FETCH_ASSOC);

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'dados' => $rows
    ]);
    return;
  }

  if ($method === 'POST') {
    $j = mg_body_json();
    $id = (int)($j['id_chamado'] ?? 0);
    if (!$id)
      throw new Exception('id_chamado obrigatório.');

    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_msg_add(
          p_id_chamado         => :p_id,
          p_canal              => :p_canal,
          p_remetente_tipo     => :p_tipo,
          p_id_usuario_interno => :p_uid,
          p_id_contato_externo => :p_cid,
          p_email_remetente    => :p_email,
          p_mensagem           => :p_msg,
          p_interna_sn         => :p_interna,
          o_id_mensagem        => :o_id_msg,
          r_tiporet            => :r_tiporet,
          r_codret             => :r_codret,
          r_msg                => :r_msg
        );
      END;
    ");

    $o_id_msg = 0;

    $r_tiporet = mg_out_buf(30);
    $r_codret = mg_out_buf(30);
    $r_msg = mg_out_buf(4000);

    $p_id = $id;
    $p_canal = strtoupper(trim((string)($j['canal'] ?? 'WEB_INTERNO')));
    $p_tipo = strtoupper(trim((string)($j['remetente_tipo'] ?? 'I')));

    $p_uid = isset($j['id_usuario_interno']) ? (int)$j['id_usuario_interno'] : null;
    $p_cid = isset($j['id_contato_externo']) ? (int)$j['id_contato_externo'] : null;

    $p_email = trim((string)($j['email_remetente'] ?? '')) ?: null;
    $p_msg = (string)($j['mensagem'] ?? '');

    $p_interna = strtoupper(trim((string)($j['interna_sn'] ?? 'N'))) === 'S' ? 'S' : 'N';

    $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
    $stmt->bindParam(':p_canal', $p_canal, PDO::PARAM_STR, 30);
    $stmt->bindParam(':p_tipo', $p_tipo, PDO::PARAM_STR, 1);

    if ($p_uid === null)
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);

    if ($p_cid === null)
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_cid', $p_cid, PDO::PARAM_INT);

    if ($p_email === null)
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_email', $p_email, PDO::PARAM_STR, 200);

    $stmt->bindParam(':p_msg', $p_msg, PDO::PARAM_STR, 4000);
    $stmt->bindParam(':p_interna', $p_interna, PDO::PARAM_STR, 1);

    $stmt->bindParam(':o_id_msg', $o_id_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'id_mensagem' => (int)$o_id_msg
    ]);
    return;
  }

  throw new Exception('Método não suportado em mensagens.');
}

function handle_anexos(PDO $conn, string $PKG, string $method)
{
  // Aqui você decide o storage (filesystem/S3/minio/etc).
  // Esse handler fica pronto quando definirmos onde salvar o arquivo.
  throw new Exception('anexos: implemente após definir storage (filesystem/minio/s3).');
}

function handle_portal(PDO $conn, string $PKG, string $method)
{
  if ($method !== 'POST')
    throw new Exception('portal aceita apenas POST.');

  $j = mg_body_json();
  $acao = strtoupper(trim((string)($j['acao'] ?? '')));

  $r_tiporet = mg_out_buf(30);
  $r_codret = mg_out_buf(30);
  $r_msg = mg_out_buf(4000);

  if ($acao === 'REQUEST_TOKEN') {
    $email = trim((string)($j['email'] ?? ''));
    if (!$email)
      throw new Exception('email obrigatório.');

    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_portal_request_token(
          p_email       => :p_email,
          p_ip          => :p_ip,
          p_user_agent  => :p_ua,
          o_id_contato  => :o_id_contato,
          o_token_plain => :o_token,
          o_expira_em   => :o_expira,
          r_tiporet     => :r_tiporet,
          r_codret      => :r_codret,
          r_msg         => :r_msg
        );
      END;
    ");

    $o_id_contato = 0;
    $o_token = mg_out_buf(200);
    $o_expira = mg_out_buf(40);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt->bindParam(':p_email', $email, PDO::PARAM_STR, 200);

    if ($ip === null)
      $stmt->bindParam(':p_ip', $ip, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ip', $ip, PDO::PARAM_STR, 60);

    if ($ua === null)
      $stmt->bindParam(':p_ua', $ua, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ua', $ua, PDO::PARAM_STR, 255);

    $stmt->bindParam(':o_id_contato', $o_id_contato, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
    $stmt->bindParam(':o_token', $o_token, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 200);
    $stmt->bindParam(':o_expira', $o_expira, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 40);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'id_contato' => (int)$o_id_contato,
      'token' => trim($o_token), // você envia por email no backend de envio
      'expira_em' => trim($o_expira)
    ]);
    return;
  }

  if ($acao === 'LOGIN_BY_TOKEN') {
    $token = trim((string)($j['token'] ?? ''));
    if (!$token)
      throw new Exception('token obrigatório.');

    $stmt = $conn->prepare("
      BEGIN
        {$PKG}.proc_portal_login_by_token(
          p_token_plain => :p_token,
          p_ip          => :p_ip,
          p_user_agent  => :p_ua,
          o_id_contato  => :o_id,
          o_email       => :o_email,
          o_nome        => :o_nome,
          r_tiporet     => :r_tiporet,
          r_codret      => :r_codret,
          r_msg         => :r_msg
        );
      END;
    ");

    $o_id = 0;
    $o_email = mg_out_buf(200);
    $o_nome = mg_out_buf(120);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt->bindParam(':p_token', $token, PDO::PARAM_STR, 200);

    if ($ip === null)
      $stmt->bindParam(':p_ip', $ip, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ip', $ip, PDO::PARAM_STR, 60);

    if ($ua === null)
      $stmt->bindParam(':p_ua', $ua, PDO::PARAM_NULL);
    else
      $stmt->bindParam(':p_ua', $ua, PDO::PARAM_STR, 255);

    $stmt->bindParam(':o_id', $o_id, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
    $stmt->bindParam(':o_email', $o_email, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 200);
    $stmt->bindParam(':o_nome', $o_nome, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 120);

    $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
    $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

    $stmt->execute();

    mg_json([
      'sucesso' => true,
      'tiporet' => trim($r_tiporet),
      'codret' => trim($r_codret),
      'msg' => trim($r_msg),
      'id_contato' => (int)$o_id,
      'email' => trim($o_email),
      'nome' => trim($o_nome),
    ]);
    return;
  }

  throw new Exception('portal: acao inválida. Use REQUEST_TOKEN / LOGIN_BY_TOKEN');
}

function handle_email_inbound(PDO $conn, string $PKG, string $method)
{
  if ($method !== 'POST')
    throw new Exception('email_inbound aceita apenas POST.');

  $j = mg_body_json();

  $message_id = trim((string)($j['message_id'] ?? ''));
  $from = trim((string)($j['from'] ?? ''));
  $subject = (string)($j['subject'] ?? '');

  if (!$message_id)
    throw new Exception('message_id obrigatório.');
  if (!$from)
    throw new Exception('from obrigatório.');

  // 1) registra inbound (idempotente por MESSAGE_ID)
  $stmt = $conn->prepare("
    BEGIN
      {$PKG}.proc_email_inbound_register(
        p_message_id     => :p_mid,
        p_in_reply_to    => :p_irt,
        p_references_hdr => :p_ref,
        p_from_email     => :p_from,
        p_to_email       => :p_to,
        p_subject        => :p_subj,
        p_raw_headers    => :p_hdr,
        p_raw_body       => :p_body,
        o_id_inbound     => :o_inb,
        r_tiporet        => :r_tiporet,
        r_codret         => :r_codret,
        r_msg            => :r_msg
      );
    END;
  ");

  $o_inb = 0;
  $r_tiporet = mg_out_buf(30);
  $r_codret = mg_out_buf(30);
  $r_msg = mg_out_buf(4000);

  $p_mid = $message_id;
  $p_from = $from;

  $p_irt = trim((string)($j['in_reply_to'] ?? '')) ?: null;
  $p_ref = $j['references'] ?? null;
  $p_to = trim((string)($j['to'] ?? '')) ?: null;
  $p_subj = $subject ?: null;
  $p_hdr = $j['raw_headers'] ?? null;
  $p_body = $j['raw_body'] ?? null;

  $stmt->bindParam(':p_mid', $p_mid, PDO::PARAM_STR, 255);

  if ($p_irt === null)
    $stmt->bindParam(':p_irt', $p_irt, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_irt', $p_irt, PDO::PARAM_STR, 255);

  if ($p_ref === null)
    $stmt->bindParam(':p_ref', $p_ref, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_ref', $p_ref, PDO::PARAM_STR, 2000);

  $stmt->bindParam(':p_from', $p_from, PDO::PARAM_STR, 200);

  if ($p_to === null)
    $stmt->bindParam(':p_to', $p_to, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_to', $p_to, PDO::PARAM_STR, 200);

  if ($p_subj === null)
    $stmt->bindParam(':p_subj', $p_subj, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_subj', $p_subj, PDO::PARAM_STR, 400);

  if ($p_hdr === null)
    $stmt->bindParam(':p_hdr', $p_hdr, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_hdr', $p_hdr, PDO::PARAM_STR, 4000);

  if ($p_body === null)
    $stmt->bindParam(':p_body', $p_body, PDO::PARAM_NULL);
  else
    $stmt->bindParam(':p_body', $p_body, PDO::PARAM_STR, 4000);

  $stmt->bindParam(':o_inb', $o_inb, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);

  $stmt->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
  $stmt->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
  $stmt->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

  $stmt->execute();

  // 2) processa inbound: cria chamado ou adiciona msg
  $stmt2 = $conn->prepare("
    BEGIN
      {$PKG}.proc_email_inbound_process(
        p_id_inbound      => :p_inb,
        p_usuario_sistema => :p_user,
        o_id_chamado      => :o_id_chamado,
        o_acao            => :o_acao,
        r_tiporet         => :r_tiporet,
        r_codret          => :r_codret,
        r_msg             => :r_msg
      );
    END;
  ");

  $o_id_chamado = 0;
  $o_acao = mg_out_buf(20);

  $p_inb = (int)$o_inb;
  $p_user = 'EMAIL_JOB';

  $stmt2->bindParam(':p_inb', $p_inb, PDO::PARAM_INT);
  $stmt2->bindParam(':p_user', $p_user, PDO::PARAM_STR, 60);

  $stmt2->bindParam(':o_id_chamado', $o_id_chamado, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);
  $stmt2->bindParam(':o_acao', $o_acao, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 20);

  $stmt2->bindParam(':r_tiporet', $r_tiporet, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
  $stmt2->bindParam(':r_codret', $r_codret, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 30);
  $stmt2->bindParam(':r_msg', $r_msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);

  $stmt2->execute();

  mg_json([
    'sucesso' => true,
    'tiporet' => trim($r_tiporet),
    'codret' => trim($r_codret),
    'msg' => trim($r_msg),
    'id_inbound' => (int)$o_inb,
    'acao' => trim($o_acao),
    'id_chamado' => (int)$o_id_chamado,
  ]);
}
