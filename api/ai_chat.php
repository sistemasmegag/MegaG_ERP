<?php
require_once __DIR__ . '/../routes/check_session.php';
require_once __DIR__ . '/mg_api_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$localConfig = __DIR__ . '/../includes/openai.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

function ai_json(bool $ok, $data = null, ?string $error = null, int $httpCode = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($httpCode);
    echo json_encode([
        'success' => $ok,
        'data' => $ok ? $data : null,
        'error' => $ok ? null : $error,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function ai_body(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);
    return is_array($json) ? $json : [];
}

function ai_user(): string
{
    $user = trim((string)($_SESSION['usuario'] ?? $_SESSION['loginid'] ?? $_SESSION['user'] ?? 'SISTEMA'));
    return $user !== '' ? $user : 'SISTEMA';
}

function ai_provider(): string
{
    if (defined('AI_PROVIDER') && trim((string)AI_PROVIDER) !== '') {
        $provider = strtolower(trim((string)AI_PROVIDER));
    } else {
        $env = getenv('AI_PROVIDER');
        $provider = is_string($env) && trim($env) !== '' ? strtolower(trim($env)) : 'openai';
    }

    return in_array($provider, ['openai', 'gemini'], true) ? $provider : 'openai';
}

function ai_api_key(?string $provider = null): string
{
    $provider = $provider ?: ai_provider();

    if ($provider === 'gemini') {
        if (defined('GEMINI_API_KEY') && trim((string)GEMINI_API_KEY) !== '') {
            return trim((string)GEMINI_API_KEY);
        }
        $env = getenv('GEMINI_API_KEY');
        return is_string($env) ? trim($env) : '';
    }

    if (defined('OPENAI_API_KEY') && trim((string)OPENAI_API_KEY) !== '') {
        return trim((string)OPENAI_API_KEY);
    }

    $env = getenv('OPENAI_API_KEY');
    return is_string($env) ? trim($env) : '';
}

function ai_model(?string $provider = null): string
{
    $provider = $provider ?: ai_provider();

    if ($provider === 'gemini') {
        if (defined('GEMINI_MODEL') && trim((string)GEMINI_MODEL) !== '') {
            return trim((string)GEMINI_MODEL);
        }
        $env = getenv('GEMINI_MODEL');
        return is_string($env) && trim($env) !== '' ? trim($env) : 'gemini-2.5-flash';
    }

    if (defined('OPENAI_MODEL') && trim((string)OPENAI_MODEL) !== '') {
        return trim((string)OPENAI_MODEL);
    }

    $env = getenv('OPENAI_MODEL');
    return is_string($env) && trim($env) !== '' ? trim($env) : 'gpt-5.1';
}

function ai_text($value, int $max = 4000): string
{
    $text = trim((string)$value);
    if ($max > 0 && mb_strlen($text, 'UTF-8') > $max) {
        $text = mb_substr($text, 0, $max, 'UTF-8');
    }
    return $text;
}

function ai_extract_names_from_sql(string $content): array
{
    $items = [];
    if (preg_match_all('/\b(PROCEDURE|FUNCTION)\s+([A-Z0-9_]+)\s*\(/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $items[] = strtoupper($m[1]) . ' ' . strtoupper($m[2]);
        }
    }
    return $items;
}

function ai_repo_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $root = dirname(__DIR__);
    $sqlFiles = array_merge(
        glob($root . '/PKG/*.sql') ?: [],
        glob($root . '/*.sql') ?: []
    );

    $objects = [];
    foreach ($sqlFiles as $file) {
        $content = @file_get_contents($file);
        if (!is_string($content)) {
            continue;
        }
        foreach (ai_extract_names_from_sql($content) as $name) {
            $objects[$name] = basename($file);
        }
    }

    $pages = [];
    foreach ((glob($root . '/pages/*.php') ?: []) as $file) {
        $pages[] = basename($file, '.php');
    }
    sort($pages);

    $apis = [];
    foreach ((glob($root . '/api/*.php') ?: []) as $file) {
        $name = basename($file);
        $content = @file_get_contents($file);
        $actions = [];
        if (is_string($content)) {
            if (preg_match_all("/case\s+['\"]([^'\"]+)['\"]\s*:/i", $content, $m)) {
                $actions = array_merge($actions, $m[1]);
            }
            if (preg_match_all("/action\s*={2,3}\s*['\"]([^'\"]+)['\"]/i", $content, $m)) {
                $actions = array_merge($actions, $m[1]);
            }
        }
        $apis[$name] = array_values(array_unique($actions));
    }

    $catalog = [
        'procedures' => $objects,
        'pages' => $pages,
        'apis' => $apis,
    ];
    return $catalog;
}

function ai_live_context(): array
{
    $ctx = [
        'schema' => null,
        'inventario_ciclico' => [],
        'tasks' => [],
        'despesas' => [],
    ];

    try {
        $conn = getConexaoPDO();
        $ctx['schema'] = mg_schema();

        try {
            $sql = 'SELECT STATUS, COUNT(*) AS QTD FROM ' . mg_table('MEGAG_INV_PLANOS') . ' GROUP BY STATUS ORDER BY STATUS';
            $ctx['inventario_ciclico']['planos_por_status'] = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $ctx['inventario_ciclico']['erro'] = 'Modulo ainda sem tabelas ou sem acesso.';
        }

        try {
            $sql = "SELECT COUNT(*) AS QTD FROM " . mg_table('MEGAG_TASK_TASKS') . " WHERE NVL(ativo, 'S') = 'S'";
            $ctx['tasks']['ativas'] = (int)($conn->query($sql)->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $ctx['tasks']['erro'] = 'Tabelas de tasks indisponiveis para resumo.';
        }

        try {
            $sql = "SELECT STATUS, COUNT(*) AS QTD, SUM(NVL(VLRRATDESPESA, 0)) AS VALOR
                      FROM " . mg_table('MEGAG_DESP') . "
                     GROUP BY STATUS
                     ORDER BY STATUS";
            $ctx['despesas']['por_status'] = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $ctx['despesas']['erro'] = 'Tabelas de despesas indisponiveis para resumo.';
        }
    } catch (Throwable $e) {
        $ctx['erro_conexao'] = $e->getMessage();
    }

    return $ctx;
}

function ai_normalize_intent_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $from = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç'];
    $to =   ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'];
    return str_replace($from, $to, $text);
}

function ai_intent_has(string $text, array $needles): bool
{
    $text = ai_normalize_intent_text($text);
    foreach ($needles as $needle) {
        if (strpos($text, ai_normalize_intent_text($needle)) !== false) {
            return true;
        }
    }
    return false;
}

function ai_session_seq_usuario(PDO $conn): int
{
    $sessionUser = ai_user();
    if (is_numeric($sessionUser)) {
        return (int)$sessionUser;
    }

    $user = strtoupper(trim($sessionUser));
    if ($user === '') {
        return 0;
    }

    $queries = [
        "SELECT SEQUSUARIO FROM " . mg_table('GE_USUARIO') . " WHERE UPPER(LOGINID) = :U AND ROWNUM = 1",
        "SELECT SEQUSUARIO FROM " . mg_table('GE_USUARIO') . " WHERE UPPER(CODUSUARIO) = :U AND ROWNUM = 1",
        "SELECT SEQUSUARIO FROM " . mg_table('GE_USUARIO') . " WHERE UPPER(LOGIN) = :U AND ROWNUM = 1",
        "SELECT SEQUSUARIO FROM " . mg_table('GE_USUARIO') . " WHERE UPPER(NOME) = :U AND ROWNUM = 1",
    ];

    foreach ($queries as $sql) {
        try {
            $st = $conn->prepare($sql);
            $st->execute([':U' => $user]);
            $seq = (int)($st->fetchColumn() ?: 0);
            if ($seq > 0) {
                return $seq;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return 0;
}

function ai_tool_my_expenses(PDO $conn, int $seqUsuario): array
{
    if ($seqUsuario <= 0) {
        return ['erro' => 'Nao foi possivel identificar o usuario logado no cadastro do ERP.'];
    }

    $sql = "SELECT *
              FROM (
                    SELECT D.CODDESPESA,
                           D.FORNECEDOR,
                           D.DESCRICAO,
                           D.STATUS,
                           D.PAGO,
                           D.VLRRATDESPESA,
                           TO_CHAR(D.DTAINCLUSAO, 'DD/MM/YYYY HH24:MI') AS DTAINCLUSAO,
                           TO_CHAR(D.DTADESPESA, 'DD/MM/YYYY') AS DTADESPESA,
                           TO_CHAR(D.DTAVENCIMENTO, 'DD/MM/YYYY') AS DTAVENCIMENTO,
                           (SELECT COUNT(1)
                              FROM " . mg_table('MEGAG_DESP_APROVACAO') . " A
                             WHERE A.CODDESPESA = D.CODDESPESA
                               AND A.STATUS = 'LANCADO') AS APROVACOES_PENDENTES,
                           (SELECT COUNT(1)
                              FROM " . mg_table('MEGAG_DESP_ARQUIVO') . " ARQ
                             WHERE ARQ.CODDESPESA = D.CODDESPESA) AS QTD_ANEXOS
                      FROM " . mg_table('MEGAG_DESP') . " D
                     WHERE D.USUARIOSOLICITANTE = :USUARIO
                     ORDER BY D.DTAINCLUSAO DESC
                   )
             WHERE ROWNUM <= 10";

    $st = $conn->prepare($sql);
    $st->execute([':USUARIO' => $seqUsuario]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function ai_tool_pending_approvals(PDO $conn, int $seqUsuario): array
{
    if ($seqUsuario <= 0) {
        return ['erro' => 'Nao foi possivel identificar o usuario logado no cadastro do ERP.'];
    }

    $sql = "SELECT *
              FROM (
                    SELECT DISTINCT D.CODDESPESA,
                           D.FORNECEDOR,
                           D.DESCRICAO,
                           D.STATUS,
                           D.VLRRATDESPESA,
                           A.NIVEL_APROVACAO,
                           TO_CHAR(D.DTAINCLUSAO, 'DD/MM/YYYY HH24:MI') AS DTAINCLUSAO
                      FROM " . mg_table('MEGAG_DESP_APROVACAO') . " A
                      JOIN " . mg_table('MEGAG_DESP') . " D
                        ON D.CODDESPESA = A.CODDESPESA
                     WHERE A.USUARIOAPROVADOR = :USUARIO
                       AND A.STATUS = 'LANCADO'
                     ORDER BY D.DTAINCLUSAO DESC
                   )
             WHERE ROWNUM <= 10";

    $st = $conn->prepare($sql);
    $st->execute([':USUARIO' => $seqUsuario]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function ai_tool_my_notifications(PDO $conn): array
{
    $user = ai_user();
    $sql = "SELECT *
              FROM (
                    SELECT ID,
                           TIPO,
                           TITULO,
                           MENSAGEM,
                           LIDA,
                           TO_CHAR(CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM
                      FROM " . mg_table('MEGAG_TASK_NOTIFICACOES') . "
                     WHERE UPPER(USUARIO) = UPPER(:USUARIO)
                     ORDER BY LIDA ASC, CRIADO_EM DESC, ID DESC
                   )
             WHERE ROWNUM <= 8";

    $st = $conn->prepare($sql);
    $st->execute([':USUARIO' => $user]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function ai_tool_inventory_plans(PDO $conn): array
{
    $sql = "SELECT *
              FROM (
                    SELECT P.ID_PLANO,
                           P.DESCRICAO,
                           P.DEPOSITO,
                           P.CONTAGEM_ATUAL,
                           P.STATUS,
                           TO_CHAR(P.GERADO_EM, 'DD/MM/YYYY HH24:MI') AS GERADO_EM,
                           TO_CHAR(P.LIBERADO_EM, 'DD/MM/YYYY HH24:MI') AS LIBERADO_EM,
                           (SELECT COUNT(1)
                              FROM " . mg_table('MEGAG_INV_PLANO_ITENS') . " I
                             WHERE I.ID_PLANO = P.ID_PLANO) AS QTD_ITENS
                      FROM " . mg_table('MEGAG_INV_PLANOS') . " P
                     ORDER BY P.GERADO_EM DESC
                   )
             WHERE ROWNUM <= 10";

    $st = $conn->prepare($sql);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function ai_agent_context(string $message, string $module): array
{
    $context = [
        'modo_agente' => 'leitura',
        'usuario' => ai_user(),
        'ferramentas_executadas' => [],
        'dados' => [],
        'avisos' => [],
    ];

    $intent = ai_normalize_intent_text($message . ' ' . $module);
    $wantsExpenses = ai_intent_has($intent, ['reembolso', 'despesa', 'pagamento', 'pago', 'minhas despesas', 'status']);
    $wantsApprovals = ai_intent_has($intent, ['aprovacao', 'aprovar', 'pendente para mim', 'minhas aprovacoes']);
    $wantsNotifications = ai_intent_has($intent, ['notificacao', 'notificacoes', 'avisos', 'sininho']);
    $wantsInventory = ai_intent_has($intent, ['inventario', 'plano', 'contagem', 'coletor']);
    $wantsExpenseDraft = ai_intent_has($intent, [
        'quero lancar',
        'quero lançar',
        'lancar uma despesa',
        'lançar uma despesa',
        'lancar despesa',
        'lançar despesa',
        'lanca uma despesa',
        'lança uma despesa',
        'lanca despesa',
        'lança despesa',
        'lanca pra mim',
        'lança pra mim',
        'pode lancar',
        'pode lançar',
        'confirmar lancamento',
        'confirmar lançamento',
        'nova despesa',
        'despesa nova',
        'novo reembolso',
        'reembolso novo',
        'quero reembolso',
        'lancar reembolso',
        'lançar reembolso',
        'lanca reembolso',
        'lança reembolso',
        'criar despesa',
        'cadastrar despesa',
        'preparar despesa',
        'rascunho de despesa',
        'registrar despesa',
        'registrar reembolso'
    ]);

    $existingDraft = $_SESSION['ai_expense_draft'] ?? null;
    if (is_array($existingDraft) && ai_intent_has($intent, ['fornecedor', 'estabelecimento', 'valor', 'categoria', 'centro de custo', 'cc', 'vencimento', 'data', 'observacao', 'comentario', 'anexo', 'cancelar rascunho', 'limpar rascunho', 'lanca', 'lança', 'confirmar', 'pode lancar', 'pode lançar'])) {
        $wantsExpenseDraft = true;
    }

    if ($wantsExpenseDraft) {
        $context['modo_agente'] = 'rascunho';
        $context['dados']['rascunho_lancamento_despesa'] = ai_expense_draft_update_from_message($message);
        $context['ferramentas_executadas'][] = 'preparar_lancamento_despesa';
    }

    if (!$wantsExpenses && !$wantsApprovals && !$wantsNotifications && !$wantsInventory && !$wantsExpenseDraft) {
        return $context;
    }

    try {
        $conn = getConexaoPDO();
        $seqUsuario = ai_session_seq_usuario($conn);

        if ($wantsExpenses) {
            try {
                $context['dados']['minhas_despesas_recentes'] = ai_tool_my_expenses($conn, $seqUsuario);
                $context['ferramentas_executadas'][] = 'minhas_despesas_recentes';
            } catch (Throwable $e) {
                $context['avisos'][] = 'Falha ao consultar despesas: ' . $e->getMessage();
            }
        }

        if ($wantsApprovals) {
            try {
                $context['dados']['aprovacoes_pendentes_para_mim'] = ai_tool_pending_approvals($conn, $seqUsuario);
                $context['ferramentas_executadas'][] = 'aprovacoes_pendentes_para_mim';
            } catch (Throwable $e) {
                $context['avisos'][] = 'Falha ao consultar aprovacoes: ' . $e->getMessage();
            }
        }

        if ($wantsNotifications) {
            try {
                $context['dados']['minhas_notificacoes'] = ai_tool_my_notifications($conn);
                $context['ferramentas_executadas'][] = 'minhas_notificacoes';
            } catch (Throwable $e) {
                $context['avisos'][] = 'Falha ao consultar notificacoes: ' . $e->getMessage();
            }
        }

        if ($wantsInventory) {
            try {
                $context['dados']['planos_inventario_recentes'] = ai_tool_inventory_plans($conn);
                $context['ferramentas_executadas'][] = 'planos_inventario_recentes';
            } catch (Throwable $e) {
                $context['avisos'][] = 'Falha ao consultar inventario: ' . $e->getMessage();
            }
        }
    } catch (Throwable $e) {
        $context['avisos'][] = 'Falha ao iniciar ferramentas do agente: ' . $e->getMessage();
    }

    return $context;
}

function ai_enrich_message_with_agent_context(string $message, array $agentContext): string
{
    if (empty($agentContext['ferramentas_executadas']) && empty($agentContext['avisos'])) {
        return $message;
    }

    return $message
        . "\n\n[CONTEXTO DO AGENTE - DADOS REAIS CONSULTADOS NO ERP]\n"
        . json_encode($agentContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)
        . "\n[/CONTEXTO DO AGENTE]\n"
        . "Use os dados reais acima para responder. Se vierem listas vazias, diga que nao encontrou registros recentes para o usuario logado. Nao invente valores.";
}

function ai_money_br($value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function ai_expense_draft_empty(): array
{
    return [
        'tipo' => 'lancamento_despesa',
        'status' => 'coletando',
        'campos' => [
            'moeda' => 'BRL',
            'estabelecimento' => '',
            'valor' => '',
            'data_despesa' => '',
            'vencimento' => '',
            'categoria' => '',
            'centros_custo' => [],
            'valores_rateio' => [],
            'politica' => '',
            'comentario' => '',
            'anexo' => 'pendente_upload_manual',
        ],
        'faltando' => [],
        'atualizado_em' => date('c'),
    ];
}

function ai_expense_draft_get(): array
{
    $draft = $_SESSION['ai_expense_draft'] ?? null;
    if (!is_array($draft)) {
        return ai_expense_draft_empty();
    }
    $default = ai_expense_draft_empty();
    $draft['campos'] = array_merge($default['campos'], is_array($draft['campos'] ?? null) ? $draft['campos'] : []);
    return array_merge($default, $draft);
}

function ai_expense_draft_save(array $draft): array
{
    $draft['faltando'] = ai_expense_draft_missing($draft);
    $draft['status'] = empty($draft['faltando']) ? 'pronto_para_revisao' : 'coletando';
    $draft['atualizado_em'] = date('c');
    $_SESSION['ai_expense_draft'] = $draft;
    return $draft;
}

function ai_expense_draft_reset(): array
{
    unset($_SESSION['ai_expense_draft']);
    return ai_expense_draft_empty();
}

function ai_parse_money_value(string $text): string
{
    if (preg_match('/(?:r\$\s*)?(\d{1,3}(?:\.\d{3})*,\d{2}|\d+(?:[,.]\d{1,2})?)/i', $text, $m)) {
        $raw = str_replace(['R$', ' '], '', $m[1]);
        if (strpos($raw, ',') !== false) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }
        return number_format((float)$raw, 2, '.', '');
    }
    return '';
}

function ai_parse_date_value(string $text): string
{
    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    if (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})\b/', $text, $m)) {
        $year = strlen($m[3]) === 2 ? ('20' . $m[3]) : $m[3];
        return sprintf('%04d-%02d-%02d', (int)$year, (int)$m[2], (int)$m[1]);
    }
    if (ai_intent_has($text, ['hoje'])) {
        return date('Y-m-d');
    }
    if (ai_intent_has($text, ['ontem'])) {
        return date('Y-m-d', strtotime('-1 day'));
    }
    return '';
}

function ai_extract_after_patterns(string $text, array $patterns, int $max = 120): string
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $m)) {
            $value = trim((string)($m[1] ?? ''));
            $value = preg_split('/(?:,|\se\s|\scom\s|\sno valor\s|\svalor\s|\sdata\s|\svencimento\s|\scategoria\s|\scentro de custo\s)/i', $value)[0] ?? $value;
            return ai_text($value, $max);
        }
    }
    return '';
}

function ai_expense_draft_missing(array $draft): array
{
    $campos = is_array($draft['campos'] ?? null) ? $draft['campos'] : [];
    $required = ai_expense_draft_required_fields();

    $missing = [];
    foreach ($required as $key => $label) {
        $value = $campos[$key] ?? null;
        if (is_array($value)) {
            if (empty($value)) {
                $missing[] = $label;
            }
        } elseif (trim((string)$value) === '') {
            $missing[] = $label;
        }
    }
    return $missing;
}

function ai_expense_draft_required_fields(): array
{
    return [
        'valor' => 'valor',
        'estabelecimento' => 'fornecedor/estabelecimento',
        'data_despesa' => 'data da despesa',
        'categoria' => 'categoria',
        'centros_custo' => 'centro de custo',
        'politica' => 'politica',
    ];
}

function ai_expense_draft_next_missing_key(array $draft): string
{
    $campos = is_array($draft['campos'] ?? null) ? $draft['campos'] : [];
    foreach (ai_expense_draft_required_fields() as $key => $label) {
        $value = $campos[$key] ?? null;
        if (is_array($value)) {
            if (empty($value)) {
                return $key;
            }
        } elseif (trim((string)$value) === '') {
            return $key;
        }
    }
    return '';
}

function ai_expense_draft_question_for(string $key): string
{
    $questions = [
        'valor' => 'Qual foi o valor da despesa?',
        'estabelecimento' => 'Qual e o fornecedor ou estabelecimento?',
        'data_despesa' => 'Qual e a data da despesa?',
        'categoria' => 'Qual e a categoria da despesa?',
        'centros_custo' => 'Qual e o centro de custo?',
        'politica' => 'Qual politica deve ser usada nesse reembolso?',
    ];
    return $questions[$key] ?? 'Qual informacao devo preencher agora?';
}

function ai_expense_draft_apply_field(array $campos, string $key, string $text): array
{
    $value = trim($text);
    if ($value === '') {
        return $campos;
    }

    if ($key === 'valor') {
        $parsed = ai_parse_money_value($value);
        if ($parsed !== '') {
            $campos['valor'] = $parsed;
        }
        return $campos;
    }

    if ($key === 'data_despesa' || $key === 'vencimento') {
        $parsed = ai_parse_date_value($value);
        if ($parsed !== '') {
            $campos[$key] = $parsed;
        }
        return $campos;
    }

    if ($key === 'centros_custo') {
        $campos['centros_custo'] = [ai_text($value, 120)];
        return $campos;
    }

    if (array_key_exists($key, $campos)) {
        $campos[$key] = ai_text($value, $key === 'comentario' ? 500 : 160);
    }
    return $campos;
}

function ai_expense_draft_update_from_message(string $message): array
{
    $draft = ai_expense_draft_get();
    $campos = is_array($draft['campos'] ?? null) ? $draft['campos'] : ai_expense_draft_empty()['campos'];
    $text = trim($message);

    if (ai_intent_has($text, ['cancelar rascunho', 'limpar rascunho', 'apagar rascunho', 'recomecar despesa'])) {
        return ai_expense_draft_reset();
    }

    $nextMissing = ai_expense_draft_next_missing_key($draft);
    $hasFieldLabel = preg_match('/\b(fornecedor|estabelecimento|valor|data|categoria|tipo|centro de custo|centro|cc|politica|política|vencimento|comentario|comentário|observacao|observação)\b/iu', $text);
    if ($nextMissing !== '' && !$hasFieldLabel) {
        $campos = ai_expense_draft_apply_field($campos, $nextMissing, $text);
        $draft['campos'] = $campos;
        return ai_expense_draft_save($draft);
    }

    $valor = ai_parse_money_value($text);
    if ($valor !== '') {
        $campos['valor'] = $valor;
    }

    $dataDespesa = '';
    if (preg_match('/(?:data da despesa|data|em|dia)\s+(\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}|\d{4}-\d{2}-\d{2}|hoje|ontem)/i', $text, $m)) {
        $dataDespesa = ai_parse_date_value($m[1]);
    } else {
        $dataDespesa = ai_parse_date_value($text);
    }
    if ($dataDespesa !== '') {
        $campos['data_despesa'] = $dataDespesa;
    }

    if (preg_match('/(?:vencimento|vence|venc)\s+(?:em\s+)?(\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}|\d{4}-\d{2}-\d{2})/i', $text, $m)) {
        $campos['vencimento'] = ai_parse_date_value($m[1]);
    }

    $fornecedor = ai_extract_after_patterns($text, [
        '/(?:fornecedor|estabelecimento|loja|empresa|posto|mercado)\s+(?:e\s+|eh\s+|é\s+|foi\s+|:)?\s*([a-z0-9 çãõáéíóúâêô\-\.&\/]+?)(?:\s+(?:valor|data|categoria|centro|cc|vencimento)\b|$)/iu',
        '/(?:comprei|gastei|paguei)\s+(?:no|na|em|para)\s+([a-z0-9 çãõáéíóúâêô\-\.&\/]+?)(?:\s+(?:valor|data|categoria|centro|cc|vencimento)\b|$)/iu',
    ], 120);
    if ($fornecedor !== '') {
        $campos['estabelecimento'] = $fornecedor;
    }

    $categoria = '';
    if (preg_match('/(?:categoria|tipo)\s*(?:e\s+|eh\s+|é\s+|:)?\s*([^,;\n\r]+?)(?=\s*(?:,|;|\n|\r|$|\bcentro\b|\bcc\b|\bvalor\b|\bdata\b|\bvencimento\b))/iu', $text, $m)) {
        $categoria = ai_text($m[1], 80);
    }
    if ($categoria !== '') {
        $campos['categoria'] = $categoria;
    }

    if (preg_match('/(?:centro de custo|cc|centro)\s+(?:e\s+|eh\s+|é\s+|:)?\s*([0-9]+(?:\s*[-|]\s*[a-z0-9 çãõáéíóúâêô\-\.&\/]+)?)/iu', $text, $m)) {
        $cc = trim($m[1]);
        if ($cc !== '') {
            $campos['centros_custo'] = [$cc];
        }
    }

    if (preg_match('/(?:politica|política)\s*(?:e\s+|eh\s+|Ã©\s+|é\s+|:)?\s*([^,;\n\r]+?)(?=\s*(?:,|;|\n|\r|$|\bcentro\b|\bcc\b|\bvalor\b|\bdata\b|\bvencimento\b|\bcategoria\b))/iu', $text, $m)) {
        $politica = ai_text($m[1], 160);
        if ($politica !== '') {
            $campos['politica'] = $politica;
        }
    }

    $comentario = ai_extract_after_patterns($text, [
        '/(?:observacao|observação|comentario|comentário|motivo)\s+(?:e\s+|eh\s+|é\s+|:)?\s*(.+)$/iu',
    ], 500);
    if ($comentario !== '') {
        $campos['comentario'] = $comentario;
    }

    $draft['campos'] = $campos;
    return ai_expense_draft_save($draft);
}

function ai_expense_draft_answer(array $draft): string
{
    $campos = is_array($draft['campos'] ?? null) ? $draft['campos'] : [];
    $faltando = is_array($draft['faltando'] ?? null) ? $draft['faltando'] : ai_expense_draft_missing($draft);

    $lines = ["Preparei um rascunho de lançamento de despesa. Ainda nao lancei nada no ERP."];
    $lines[] = "";
    $lines[] = "Resumo do rascunho:";
    $lines[] = "- Moeda: " . (($campos['moeda'] ?? '') !== '' ? $campos['moeda'] : 'BRL');
    $lines[] = "- Fornecedor: " . (($campos['estabelecimento'] ?? '') !== '' ? $campos['estabelecimento'] : 'pendente');
    $lines[] = "- Valor: " . (($campos['valor'] ?? '') !== '' ? ai_money_br($campos['valor']) : 'pendente');
    $lines[] = "- Data da despesa: " . (($campos['data_despesa'] ?? '') !== '' ? $campos['data_despesa'] : 'pendente');
    $lines[] = "- Vencimento: " . (($campos['vencimento'] ?? '') !== '' ? $campos['vencimento'] : 'nao informado');
    $lines[] = "- Categoria: " . (($campos['categoria'] ?? '') !== '' ? $campos['categoria'] : 'pendente');
    $ccs = is_array($campos['centros_custo'] ?? null) ? $campos['centros_custo'] : [];
    $lines[] = "- Centro de custo: " . ($ccs ? implode(', ', $ccs) : 'pendente');
    $lines[] = "- Politica: " . (($campos['politica'] ?? '') !== '' ? $campos['politica'] : 'pendente');
    $lines[] = "- Observacao: " . (($campos['comentario'] ?? '') !== '' ? $campos['comentario'] : 'nao informada');
    $lines[] = "- Anexo: devera ser incluido manualmente na tela de despesa nesta primeira etapa";
    $lines[] = "";

    if ($faltando) {
        $nextKey = ai_expense_draft_next_missing_key($draft);
        $lines[] = "Vamos preencher um item por vez.";
        $lines[] = ai_expense_draft_question_for($nextKey);
    } else {
        $lines[] = "O rascunho esta pronto para revisao. O proximo passo sera abrir a tela de Despesas com esses campos preenchidos e, depois, criarmos o botao de Confirmar lancamento.";
    }

    return implode("\n", $lines);
}

function ai_agent_local_answer(array $agentContext): string
{
    $dados = is_array($agentContext['dados'] ?? null) ? $agentContext['dados'] : [];
    $parts = [];

    if (isset($dados['rascunho_lancamento_despesa']) && is_array($dados['rascunho_lancamento_despesa'])) {
        return ai_expense_draft_answer($dados['rascunho_lancamento_despesa']);
    }

    if (array_key_exists('minhas_despesas_recentes', $dados)) {
        $rows = is_array($dados['minhas_despesas_recentes']) ? $dados['minhas_despesas_recentes'] : [];
        if (!$rows) {
            $parts[] = "Consultei suas despesas no ERP e nao encontrei registros recentes para o usuario logado.";
        } else {
            $lines = ["Consultei suas despesas recentes no ERP. Ultimos registros:"];
            foreach (array_slice($rows, 0, 5) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = $row['CODDESPESA'] ?? '-';
                $fornecedor = trim((string)($row['FORNECEDOR'] ?? 'Fornecedor nao informado'));
                $status = trim((string)($row['STATUS'] ?? 'SEM STATUS'));
                $pago = strtoupper(trim((string)($row['PAGO'] ?? 'N'))) === 'S' ? 'pago' : 'nao pago';
                $valor = ai_money_br($row['VLRRATDESPESA'] ?? 0);
                $pendentes = (int)($row['APROVACOES_PENDENTES'] ?? 0);
                $extra = $pendentes > 0 ? ", {$pendentes} aprovacao(oes) pendente(s)" : '';
                $lines[] = "- EXP-{$id}: {$fornecedor}, {$valor}, status {$status}, {$pago}{$extra}.";
            }
            $lines[] = "Para ver detalhes, abra Despesas e procure pelo codigo EXP correspondente.";
            $parts[] = implode("\n", $lines);
        }
    }

    if (array_key_exists('aprovacoes_pendentes_para_mim', $dados)) {
        $rows = is_array($dados['aprovacoes_pendentes_para_mim']) ? $dados['aprovacoes_pendentes_para_mim'] : [];
        if (!$rows) {
            $parts[] = "Consultei suas aprovacoes e nao encontrei despesas pendentes para voce.";
        } else {
            $lines = ["Aprovacoes pendentes para voce:"];
            foreach (array_slice($rows, 0, 5) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = $row['CODDESPESA'] ?? '-';
                $fornecedor = trim((string)($row['FORNECEDOR'] ?? 'Fornecedor nao informado'));
                $nivel = $row['NIVEL_APROVACAO'] ?? '-';
                $valor = ai_money_br($row['VLRRATDESPESA'] ?? 0);
                $lines[] = "- EXP-{$id}: {$fornecedor}, {$valor}, nivel {$nivel}.";
            }
            $parts[] = implode("\n", $lines);
        }
    }

    if (array_key_exists('minhas_notificacoes', $dados)) {
        $rows = is_array($dados['minhas_notificacoes']) ? $dados['minhas_notificacoes'] : [];
        if (!$rows) {
            $parts[] = "Consultei suas notificacoes e nao encontrei avisos recentes.";
        } else {
            $lines = ["Suas notificacoes recentes:"];
            foreach (array_slice($rows, 0, 5) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $titulo = trim((string)($row['TITULO'] ?? 'Notificacao'));
                $lida = strtoupper(trim((string)($row['LIDA'] ?? 'N'))) === 'S' ? 'lida' : 'nao lida';
                $lines[] = "- {$titulo} ({$lida}).";
            }
            $parts[] = implode("\n", $lines);
        }
    }

    if (array_key_exists('planos_inventario_recentes', $dados)) {
        $rows = is_array($dados['planos_inventario_recentes']) ? $dados['planos_inventario_recentes'] : [];
        if (!$rows) {
            $parts[] = "Consultei o inventario ciclico e nao encontrei planos recentes.";
        } else {
            $lines = ["Planos recentes de inventario:"];
            foreach (array_slice($rows, 0, 5) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = $row['ID_PLANO'] ?? '-';
                $desc = trim((string)($row['DESCRICAO'] ?? 'Sem descricao'));
                $status = trim((string)($row['STATUS'] ?? 'SEM STATUS'));
                $qtd = (int)($row['QTD_ITENS'] ?? 0);
                $lines[] = "- {$id}: {$desc}, status {$status}, {$qtd} item(ns).";
            }
            $parts[] = implode("\n", $lines);
        }
    }

    if (!$parts) {
        $warnings = $agentContext['avisos'] ?? [];
        if ($warnings) {
            return "Nao consegui usar a IA externa agora e tambem houve avisos ao consultar o ERP:\n- " . implode("\n- ", array_map('strval', $warnings));
        }
        return "Nao consegui usar a IA externa agora. Tente novamente em alguns instantes ou verifique a quota/chave do provedor configurado.";
    }

    return implode("\n\n", $parts)
        . "\n\nObservacao: respondi com resumo local do agente porque o provedor de IA externo retornou erro de quota/limite no momento.";
}

function ai_system_prompt(string $module): string
{
    $catalog = ai_repo_catalog();
    $live = ai_live_context();

    $procedures = [];
    foreach ($catalog['procedures'] as $name => $file) {
        $procedures[] = $name . ' [' . $file . ']';
        if (count($procedures) >= 120) {
            break;
        }
    }

    $apiLines = [];
    foreach ($catalog['apis'] as $api => $actions) {
        $apiLines[] = $api . ': ' . ($actions ? implode(', ', array_slice($actions, 0, 30)) : 'sem actions mapeadas');
    }

    return "Voce e o Assistente IA interno do ERP MegaG, em portugues do Brasil.\n"
        . "Ajude operadores, aprovadores e administradores a entender processos do ERP, telas, APIs, procedures e fluxos.\n"
        . "Modulo em foco: " . ($module !== '' ? $module : 'geral') . ". Usuario logado: " . ai_user() . ".\n\n"
        . "Regras de seguranca:\n"
        . "- Nunca invente dados que nao estejam no contexto. Quando nao souber, diga o caminho provavel para verificar.\n"
        . "- Nao escreva SQL destrutivo e nao oriente alteracoes diretas em tabela sem validação humana.\n"
        . "- Para acoes operacionais, explique a tela/API/procedure adequada e avise quando precisar de permissao.\n"
        . "- Quando houver CONTEXTO DO AGENTE, ele veio de consultas reais e seguras no ERP; use esses dados para responder objetivamente.\n"
        . "- O agente ainda esta em modo leitura: nao diga que executou criacao, aprovacao, pagamento, exclusao ou lancamento.\n"
        . "- Se o usuario pedir aprovacao, exclusao, liberacao, pagamento ou cancelamento, responda com checklist e peca confirmacao humana no ERP.\n"
        . "- Seja pratico, objetivo e use nomes reais dos modulos quando aparecerem no contexto.\n\n"
        . "Paginas ERP: " . implode(', ', array_slice($catalog['pages'], 0, 80)) . "\n\n"
        . "APIs e actions: " . implode(' | ', array_slice($apiLines, 0, 40)) . "\n\n"
        . "Packages/procedures/funcoes conhecidos: " . implode(' | ', $procedures) . "\n\n"
        . "Resumo operacional de leitura: " . json_encode($live, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}

function ai_response_text(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return trim($response['output_text']);
    }

    $parts = [];
    foreach (($response['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $parts[] = (string)$content['text'];
            }
        }
    }

    return trim(implode("\n", $parts));
}

function ai_gemini_response_text(array $response): string
{
    $parts = [];
    foreach (($response['candidates'] ?? []) as $candidate) {
        foreach (($candidate['content']['parts'] ?? []) as $part) {
            if (isset($part['text'])) {
                $parts[] = (string)$part['text'];
            }
        }
    }
    return trim(implode("\n", $parts));
}

function ai_gemini_fallback_models(): array
{
    $raw = '';
    if (defined('GEMINI_FALLBACK_MODELS')) {
        $raw = (string)GEMINI_FALLBACK_MODELS;
    } else {
        $env = getenv('GEMINI_FALLBACK_MODELS');
        $raw = is_string($env) ? $env : '';
    }

    if (trim($raw) === '') {
        $raw = 'gemini-2.0-flash-lite,gemini-2.0-flash';
    }

    $models = [];
    foreach (explode(',', $raw) as $model) {
        $model = trim($model);
        if ($model !== '' && !in_array($model, $models, true)) {
            $models[] = $model;
        }
    }
    return $models;
}

function ai_gemini_should_retry(int $code, string $message): bool
{
    if (in_array($code, [429, 500, 502, 503, 504], true)) {
        return true;
    }

    $msg = strtolower($message);
    return strpos($msg, 'high demand') !== false
        || strpos($msg, 'try again later') !== false
        || strpos($msg, 'temporar') !== false
        || strpos($msg, 'unavailable') !== false
        || strpos($msg, 'quota') !== false;
}

function ai_missing_key_response(string $provider): array
{
    $keyName = $provider === 'gemini' ? 'GEMINI_API_KEY' : 'OPENAI_API_KEY';
    return [
        'provider' => $provider,
        'model' => null,
        'configured' => false,
        'text' => "Ainda falta configurar a chave da IA. Em `includes/openai.local.php`, defina `AI_PROVIDER` como `" . $provider . "` e informe `" . $keyName . "`. Enquanto isso, ja mapeei o ERP: consigo ver packages, paginas e APIs locais para montar o contexto do assistente.",
    ];
}

function ai_call_openai(string $message, string $module, array $history, array $agentContext = []): array
{
    $provider = 'openai';
    $key = ai_api_key($provider);
    if ($key === '') {
        return ai_missing_key_response($provider);
    }

    if (!function_exists('curl_init')) {
        throw new Exception('Extensao PHP cURL nao esta habilitada.');
    }

    $input = [];
    foreach (array_slice($history, -8) as $turn) {
        $input[] = [
            'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
            'content' => (string)$turn['content'],
        ];
    }
    $input[] = ['role' => 'user', 'content' => ai_enrich_message_with_agent_context($message, $agentContext)];

    $payload = [
        'model' => ai_model($provider),
        'instructions' => ai_system_prompt($module),
        'input' => $input,
        'reasoning' => ['effort' => 'none'],
        'text' => ['verbosity' => 'medium'],
        'max_output_tokens' => 1400,
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        throw new Exception('Falha ao chamar OpenAI: ' . ($err ?: 'sem resposta'));
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new Exception('Resposta invalida da OpenAI.');
    }
    if ($code >= 400) {
        $msg = $json['error']['message'] ?? ('HTTP ' . $code);
        throw new Exception('OpenAI: ' . $msg);
    }

    $text = ai_response_text($json);
    if ($text === '') {
        $text = 'Recebi resposta da IA, mas nao consegui extrair o texto.';
    }

    return [
        'provider' => $provider,
        'model' => ai_model($provider),
        'configured' => true,
        'response_id' => $json['id'] ?? null,
        'text' => $text,
        'agent_context' => $agentContext,
    ];
}

function ai_call_gemini(string $message, string $module, array $history, array $agentContext = []): array
{
    $provider = 'gemini';
    $key = ai_api_key($provider);
    if ($key === '') {
        return ai_missing_key_response($provider);
    }

    if (!function_exists('curl_init')) {
        throw new Exception('Extensao PHP cURL nao esta habilitada.');
    }

    $contents = [];
    foreach (array_slice($history, -8) as $turn) {
        $contents[] = [
            'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => (string)$turn['content']]],
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => ai_enrich_message_with_agent_context($message, $agentContext)]]];

    $payload = [
        'systemInstruction' => [
            'parts' => [['text' => ai_system_prompt($module)]],
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 1400,
        ],
    ];

    $models = array_values(array_unique(array_merge([ai_model($provider)], ai_gemini_fallback_models())));
    $lastError = '';

    foreach ($models as $model) {
        $tryPayload = $payload;
        if (strpos($model, 'gemini-2.5') === 0) {
            $tryPayload['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $key,
            ],
            CURLOPT_POSTFIELDS => json_encode($tryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            $lastError = 'Falha ao chamar Gemini: ' . ($err ?: 'sem resposta');
            if ($model !== end($models)) {
                continue;
            }
            throw new Exception($lastError);
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            $lastError = 'Resposta invalida do Gemini.';
            if ($model !== end($models)) {
                continue;
            }
            throw new Exception($lastError);
        }

        if ($code >= 400) {
            $msg = $json['error']['message'] ?? ('HTTP ' . $code);
            $lastError = 'Gemini ' . $model . ': ' . $msg;
            if (ai_gemini_should_retry($code, $msg) && $model !== end($models)) {
                continue;
            }
            throw new Exception($lastError);
        }

        $text = ai_gemini_response_text($json);
        if ($text === '') {
            $text = 'Recebi resposta do Gemini, mas nao consegui extrair o texto.';
        }

        return [
            'provider' => $provider,
            'model' => $model,
            'configured' => true,
            'response_id' => null,
            'text' => $text,
            'agent_context' => $agentContext,
        ];
    }

    throw new Exception($lastError ?: 'Gemini indisponivel no momento.');
}

function ai_call_provider(string $message, string $module, array $history): array
{
    $agentContext = ai_agent_context($message, $module);
    $isExpenseDraft = in_array('preparar_lancamento_despesa', $agentContext['ferramentas_executadas'] ?? [], true);

    if ($isExpenseDraft) {
        return [
            'provider' => 'local-agent',
            'model' => 'erp-tools',
            'configured' => true,
            'response_id' => null,
            'text' => ai_agent_local_answer($agentContext),
            'agent_context' => $agentContext,
        ];
    }

    try {
        return ai_provider() === 'gemini'
            ? ai_call_gemini($message, $module, $history, $agentContext)
            : ai_call_openai($message, $module, $history, $agentContext);
    } catch (Throwable $e) {
        $hasAgentData = !empty($agentContext['ferramentas_executadas']) || !empty($agentContext['avisos']);
        if (!$hasAgentData) {
            throw $e;
        }

        return [
            'provider' => ai_provider(),
            'model' => ai_model(),
            'configured' => true,
            'response_id' => null,
            'text' => ai_agent_local_answer($agentContext),
            'agent_context' => $agentContext,
            'provider_error' => $e->getMessage(),
        ];
    }
}

try {
    if (empty($_SESSION['logado']) && empty($_SESSION['usuario']) && empty($_SESSION['loginid'])) {
        ai_json(false, null, 'Sessao expirada. Faca login novamente.', 401);
    }

    $body = ai_body();
    $action = strtolower(ai_text($body['action'] ?? 'chat', 30));

    if ($action === 'context') {
        ai_json(true, [
            'catalog' => ai_repo_catalog(),
            'live' => ai_live_context(),
            'provider' => ai_provider(),
            'model' => ai_model(),
            'configured' => ai_api_key() !== '',
        ]);
    }

    if ($action === 'reset') {
        $_SESSION['ai_chat_history'] = [];
        ai_json(true, ['ok' => true]);
    }

    $message = ai_text($body['message'] ?? '', 2500);
    $module = ai_text($body['module'] ?? 'geral', 80);
    if ($message === '') {
        throw new Exception('Digite uma pergunta para o assistente.');
    }

    $history = is_array($_SESSION['ai_chat_history'] ?? null) ? $_SESSION['ai_chat_history'] : [];
    $result = ai_call_provider($message, $module, $history);

    $history[] = ['role' => 'user', 'content' => $message, 'at' => date('c')];
    $history[] = ['role' => 'assistant', 'content' => $result['text'], 'at' => date('c')];
    $_SESSION['ai_chat_history'] = array_slice($history, -16);

    ai_json(true, [
        'answer' => $result['text'],
        'provider' => $result['provider'] ?? ai_provider(),
        'model' => $result['model'],
        'configured' => $result['configured'],
        'response_id' => $result['response_id'] ?? null,
        'agent_tools' => $result['agent_context']['ferramentas_executadas'] ?? [],
        'agent_warnings' => $result['agent_context']['avisos'] ?? [],
        'agent_draft' => $result['agent_context']['dados']['rascunho_lancamento_despesa'] ?? null,
        'provider_error' => $result['provider_error'] ?? null,
    ]);
} catch (Throwable $e) {
    ai_json(false, null, $e->getMessage(), 400);
}
