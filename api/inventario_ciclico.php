<?php
require_once __DIR__ . '/../routes/check_session.php';
require_once __DIR__ . '/mg_api_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function cyc_json(bool $ok, $dados = null, ?string $erro = null, int $httpCode = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($httpCode);
    echo json_encode([
        'sucesso' => $ok,
        'dados' => $ok ? $dados : null,
        'erro' => $ok ? null : $erro,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function cyc_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalido no body.');
    }

    return is_array($json) ? $json : [];
}

function cyc_action(array $req): string
{
    return strtolower(trim((string)($req['action'] ?? 'help')));
}

function cyc_pkg_inv_cic(): string
{
    return cyc_web_schema() . '.MEGAG_PKG_INVENTARIO_CICLICO';
}

function cyc_web_schema(): string
{
    if (defined('DB_WEBSCHEMA') && trim((string)DB_WEBSCHEMA) !== '') {
        return strtoupper(trim((string)DB_WEBSCHEMA));
    }

    return 'MEGAWEB';
}

function cyc_table(string $name): string
{
    return cyc_web_schema() . '.' . strtoupper(trim($name));
}

function cyc_normalize_text($value, int $max = 4000): string
{
    $text = trim((string)$value);
    return $max > 0 && strlen($text) > $max ? substr($text, 0, $max) : $text;
}

function cyc_parse_number($value): string
{
    if ($value === null || $value === '') {
        return '0';
    }

    $raw = trim((string)$value);
    if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (strpos($raw, ',') !== false) {
        $raw = str_replace(',', '.', $raw);
    }

    return is_numeric($raw) ? $raw : '0';
}

function cyc_parse_date($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    throw new Exception('Data invalida. Use YYYY-MM-DD ou DD/MM/YYYY.');
}

function cyc_numeric_input(array $req, array $keys, string $label): int
{
    foreach ($keys as $key) {
        if (isset($req[$key]) && trim((string)$req[$key]) !== '') {
            return (int)$req[$key];
        }
    }

    throw new Exception('Informe ' . $label . '.');
}

function cyc_out_string(): string
{
    return str_repeat(' ', 4000);
}

function cyc_fail_if_pkg_error(string $msg): void
{
    $msg = trim($msg);
    if (stripos($msg, 'ERRO') === 0) {
        throw new Exception($msg);
    }
}

function cyc_pretty_error(Throwable $e): string
{
    $msg = $e->getMessage();
    if (strpos($msg, 'SYS_C00399839') !== false) {
        return 'A tabela MEGAG_INV_CIC_UPLOAD_BASE esta com PK apenas em ID_UPLOAD. Para gerar um plano com mais de um item, o banco precisa permitir varias linhas com o mesmo ID_UPLOAD.';
    }
    if (strpos($msg, 'ORA-01400') !== false && strpos($msg, 'MEGAG_INV_CIC_PLANO') !== false && strpos($msg, 'ID_PLANO') !== false) {
        return 'A tabela MEGAG_INV_CIC_PLANO nao esta gerando ID_PLANO automaticamente. A package insere sem informar esse campo; precisa de identity, sequence/trigger ou ajuste na package.';
    }
    if (strpos($msg, 'ORA-01400') !== false && strpos($msg, 'MEGAG_INV_CIC_PLANO_ENDERECO') !== false && strpos($msg, 'ID_PLANO_END') !== false) {
        return 'A tabela MEGAG_INV_CIC_PLANO_ENDERECO nao esta gerando ID_PLANO_END automaticamente. A package insere sem informar esse campo; precisa de identity, sequence/trigger ou ajuste na package.';
    }
    return $msg;
}

function cyc_refcursor_rows(PDOStatement $cursor): array
{
    $rows = [];
    while ($row = $cursor->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function cyc_help(): array
{
    return [
        'package' => cyc_pkg_inv_cic(),
        'actions' => [
            'gerar_plano' => ['id_upload', 'nroempresa'],
            'registrar_contagem' => ['id_plano_end', 'cod_produtivo', 'seqproduto', 'qtd_contada', 'dtavalidade', 'tipo_bipage'],
            'comparar_contagem' => ['id_plano_end', 'nr_contagem'],
            'rel_sintetico' => ['id_plano'],
            'rel_analitico' => ['id_plano'],
        ],
    ];
}

function cyc_lookup_product(PDO $conn, string $seqProduto): array
{
    $seqProduto = cyc_normalize_text($seqProduto, 30);
    if ($seqProduto === '') {
        throw new Exception('Seqproduto nao informado.');
    }

    $sql = "SELECT SEQPRODUTO, DESCCOMPLETA
              FROM CONSINCO.MAP_PRODUTO
             WHERE SEQPRODUTO = :SEQPRODUTO
               AND ROWNUM = 1";
    $st = $conn->prepare($sql);
    $st->execute([':SEQPRODUTO' => $seqProduto]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Produto nao encontrado.');
    }

    return [
        'seqProduto' => (string)($row['SEQPRODUTO'] ?? $seqProduto),
        'descricao' => (string)($row['DESCCOMPLETA'] ?? ''),
    ];
}

function cyc_search_stock_items(PDO $conn, string $query, int $limit = 30): array
{
    $query = strtoupper(cyc_normalize_text($query, 120));
    if (strlen($query) < 2) {
        return [];
    }

    $limit = max(1, min(50, $limit));
    $like = '%' . $query . '%';

    $sql = "
        SELECT *
          FROM (
                SELECT nroempresa,
                       empresa,
                       seqproduto,
                       produto,
                       nivel2,
                       nivel3,
                       armazenagem,
                       tipo,
                       endereco,
                       TO_CHAR(dtavalidade, 'YYYY-MM-DD') AS dtavalidade,
                       TO_CHAR(dtavalidade, 'DD/MM/YYYY') AS dtavalidade_br,
                       TO_CHAR(dtarecebimento, 'YYYY-MM-DD') AS dtarecebimento,
                       saldo,
                       embalagem,
                       qtdembalagem,
                       pesobruto,
                       media_dia,
                       dias_venc,
                       custo_nf,
                       custo_liq,
                       seqendereco,
                       codrua,
                       nropredio,
                       nroapartamento,
                       nrosala
                  FROM " . cyc_table('MEGAG_VW_INV_IMPORT_PLANO') . "
                 WHERE (
                        TO_CHAR(seqproduto) LIKE :Q_PREFIX
                     OR TO_CHAR(seqendereco) = :Q_EXACT
                     OR UPPER(produto) LIKE :Q_LIKE
                     OR UPPER(endereco) LIKE :Q_LIKE_ADDR
                   )
                 ORDER BY endereco, produto, dtavalidade
               )
         WHERE ROWNUM <= {$limit}";

    $st = $conn->prepare($sql);
    $st->execute([
        ':Q_PREFIX' => $query . '%',
        ':Q_EXACT' => $query,
        ':Q_LIKE' => $like,
        ':Q_LIKE_ADDR' => $like,
    ]);

    return array_map(static function (array $row): array {
        return [
            'nroEmpresa' => (int)($row['NROEMPRESA'] ?? 0),
            'empresa' => (string)($row['EMPRESA'] ?? ''),
            'seqProduto' => (string)($row['SEQPRODUTO'] ?? ''),
            'produto' => (string)($row['PRODUTO'] ?? ''),
            'endereco' => (string)($row['ENDERECO'] ?? ''),
            'codRua' => (string)($row['CODRUA'] ?? ''),
            'nroPredio' => (string)($row['NROPREDIO'] ?? ''),
            'nroApartamento' => (string)($row['NROAPARTAMENTO'] ?? ''),
            'nroSala' => (string)($row['NROSALA'] ?? ''),
            'seqEndereco' => (int)($row['SEQENDERECO'] ?? 0),
            'dtaValidade' => (string)($row['DTAVALIDADE'] ?? ''),
            'dtaValidadeBr' => (string)($row['DTAVALIDADE_BR'] ?? ''),
            'dtaRecebimento' => (string)($row['DTARECEBIMENTO'] ?? ''),
            'saldo' => (float)($row['SALDO'] ?? 0),
            'embalagem' => (string)($row['EMBALAGEM'] ?? ''),
            'qtdEmbalagem' => (float)($row['QTDEMBALAGEM'] ?? 0),
            'nivel2' => trim((string)($row['NIVEL2'] ?? '')),
            'nivel3' => trim((string)($row['NIVEL3'] ?? '')),
            'armazenagem' => (string)($row['ARMAZENAGEM'] ?? ''),
            'tipo' => (string)($row['TIPO'] ?? ''),
            'pesoBruto' => (float)($row['PESOBRUTO'] ?? 0),
            'mediaDia' => (float)($row['MEDIA_DIA'] ?? 0),
            'diasVenc' => (int)($row['DIAS_VENC'] ?? 0),
            'custoNf' => (float)($row['CUSTO_NF'] ?? 0),
            'custoLiq' => (float)($row['CUSTO_LIQ'] ?? 0),
        ];
    }, $st->fetchAll(PDO::FETCH_ASSOC));
}

function cyc_search_productivos(PDO $conn, string $query): array
{
    $query = strtoupper(cyc_normalize_text($query, 80));
    if (strlen($query) < 2) {
        return [];
    }

    $like = '%' . $query . '%';
    $sql = "SELECT SEQUSUARIO, LOGINID, CODUSUARIO, NOME
              FROM CONSINCO.GE_USUARIO
             WHERE ROWNUM <= 20
               AND (
                    UPPER(NVL(LOGINID, '')) LIKE :Q1
                 OR UPPER(NVL(CODUSUARIO, '')) LIKE :Q2
                 OR UPPER(NVL(NOME, '')) LIKE :Q3
                 OR TO_CHAR(SEQUSUARIO) LIKE :Q4
               )
             ORDER BY NOME";
    $st = $conn->prepare($sql);
    $st->execute([':Q1' => $like, ':Q2' => $like, ':Q3' => $like, ':Q4' => $like]);

    return array_map(static function (array $row): array {
        $login = trim((string)($row['LOGINID'] ?? $row['CODUSUARIO'] ?? ''));
        return [
            'seqUsuario' => (int)($row['SEQUSUARIO'] ?? 0),
            'login' => $login,
            'nome' => (string)($row['NOME'] ?? ''),
        ];
    }, $st->fetchAll(PDO::FETCH_ASSOC));
}

function cyc_next_upload_id(PDO $conn): int
{
    $sql = 'SELECT NVL(MAX(id_upload), 0) + 1 FROM ' . cyc_table('MEGAG_INV_CIC_UPLOAD_BASE');
    return (int)$conn->query($sql)->fetchColumn();
}

function cyc_save_plan_from_form(PDO $conn, array $req): array
{
    $items = is_array($req['itens'] ?? null) ? $req['itens'] : [];
    if (!$items) {
        throw new Exception('Adicione ao menos um endereco/produto ao plano.');
    }

    $nroEmpresa = (int)($req['nroEmpresa'] ?? $req['nroempresa'] ?? 0);
    if ($nroEmpresa <= 0) {
        foreach ($items as $item) {
            $nroEmpresa = (int)($item['nroEmpresa'] ?? 0);
            if ($nroEmpresa > 0) {
                break;
            }
        }
    }
    if ($nroEmpresa <= 0) {
        throw new Exception('Nao foi possivel identificar a empresa. Selecione itens pela busca de estoque.');
    }

    try {
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
        }

        $idUpload = cyc_next_upload_id($conn);
        $sql = 'INSERT INTO ' . cyc_table('MEGAG_INV_CIC_UPLOAD_BASE') . '(
                    id_upload, seqendereco, codrua, nropredio, nroapartamento, nrosala,
                    seqproduto, descproduto, qtdembalagem, qtdatual, dtavalidade,
                    dtarecebimento, nroempresa, status
                ) VALUES (
                    :id_upload, :seqendereco, :codrua, :nropredio, :nroapartamento, :nrosala,
                    :seqproduto, :descproduto, :qtdembalagem, :qtdatual, TO_DATE(:dtavalidade, \'YYYY-MM-DD\'),
                    TO_DATE(:dtarecebimento, \'YYYY-MM-DD\'), :nroempresa, \'A\'
                )';
        $st = $conn->prepare($sql);

        $count = 0;
        foreach ($items as $item) {
            $seqEndereco = (int)($item['seqEndereco'] ?? 0);
            $seqProduto = (int)($item['codProduto'] ?? $item['seqProduto'] ?? 0);
            if ($seqEndereco <= 0 || $seqProduto <= 0) {
                continue;
            }

            $st->execute([
                ':id_upload' => $idUpload,
                ':seqendereco' => $seqEndereco,
                ':codrua' => cyc_normalize_text($item['codRua'] ?? $item['rua'] ?? '', 20),
                ':nropredio' => cyc_normalize_text($item['nroPredio'] ?? '', 20),
                ':nroapartamento' => cyc_normalize_text($item['nroApartamento'] ?? '', 20),
                ':nrosala' => cyc_normalize_text($item['nroSala'] ?? '', 20),
                ':seqproduto' => $seqProduto,
                ':descproduto' => cyc_normalize_text($item['descricao'] ?? '', 300),
                ':qtdembalagem' => cyc_parse_number($item['qtdEmbalagem'] ?? 1),
                ':qtdatual' => cyc_parse_number($item['quantidadeBase'] ?? 0),
                ':dtavalidade' => cyc_parse_date($item['validadeBase'] ?? null),
                ':dtarecebimento' => cyc_parse_date($item['dtaRecebimento'] ?? null),
                ':nroempresa' => $nroEmpresa,
            ]);
            $count++;
        }

        if ($count === 0) {
            throw new Exception('Nenhum item valido para gravar. Use a busca de estoque para trazer SEQENDERECO.');
        }

        $data = cyc_gerar_plano($conn, ['id_upload' => $idUpload, 'nroempresa' => $nroEmpresa]);
        $data['itens'] = $count;
        return $data;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

function cyc_list_plans(PDO $conn): array
{
    $sql = "SELECT p.id_plano,
                   p.id_upload,
                   p.nroempresa,
                   p.status,
                   TO_CHAR(p.dt_criacao, 'YYYY-MM-DD HH24:MI:SS') AS gerado_em,
                   COUNT(pe.id_plano_end) AS qtd_itens,
                   COUNT(DISTINCT pe.lote) AS qtd_grupos
              FROM " . cyc_table('MEGAG_INV_CIC_PLANO') . " p
              LEFT JOIN " . cyc_table('MEGAG_INV_CIC_PLANO_ENDERECO') . " pe
                ON pe.id_plano = p.id_plano
             GROUP BY p.id_plano, p.id_upload, p.nroempresa, p.status, p.dt_criacao
             ORDER BY p.id_plano DESC";
    return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function cyc_fetch_plan_payload(PDO $conn, int $idPlano): array
{
    $sqlPlan = "SELECT id_plano, id_upload, nroempresa, status,
                       TO_CHAR(dt_criacao, 'YYYY-MM-DD HH24:MI:SS') AS gerado_em
                  FROM " . cyc_table('MEGAG_INV_CIC_PLANO') . "
                 WHERE id_plano = :id_plano";
    $stPlan = $conn->prepare($sqlPlan);
    $stPlan->execute([':id_plano' => $idPlano]);
    $plan = $stPlan->fetch(PDO::FETCH_ASSOC);
    if (!$plan) {
        throw new Exception('Plano nao encontrado.');
    }

    $sqlItems = "SELECT pe.id_plano_end,
                        pe.seqendereco,
                        pe.codrua,
                        pe.nropredio,
                        pe.lote,
                        pe.status,
                        pe.nr_contagem_max,
                        ub.nroapartamento,
                        ub.nrosala,
                        ub.seqproduto,
                        ub.descproduto,
                        ub.qtdatual,
                        ub.qtdembalagem,
                        TO_CHAR(ub.dtavalidade, 'YYYY-MM-DD') AS dtavalidade
                   FROM " . cyc_table('MEGAG_INV_CIC_PLANO_ENDERECO') . " pe
                   JOIN " . cyc_table('MEGAG_INV_CIC_PLANO') . " p
                     ON p.id_plano = pe.id_plano
                   JOIN " . cyc_table('MEGAG_INV_CIC_UPLOAD_BASE') . " ub
                     ON ub.id_upload = p.id_upload
                    AND ub.seqendereco = pe.seqendereco
                  WHERE pe.id_plano = :id_plano
                  ORDER BY pe.codrua, pe.nropredio, ub.seqproduto";
    $stItems = $conn->prepare($sqlItems);
    $stItems->execute([':id_plano' => $idPlano]);

    return ['plano' => $plan, 'itens' => $stItems->fetchAll(PDO::FETCH_ASSOC)];
}

function cyc_gerar_plano(PDO $conn, array $req): array
{
    $idUpload = cyc_numeric_input($req, ['idUpload', 'id_upload', 'p_id_upload', 'upload'], 'o ID do upload');
    $nroEmpresa = cyc_numeric_input($req, ['nroEmpresa', 'nroempresa', 'p_nroempresa', 'empresa'], 'a empresa');
    $pkg = cyc_pkg_inv_cic();

    $sql = "BEGIN
                {$pkg}.prc_gerar_plano_inv_cic(
                    p_id_upload  => :p_id_upload,
                    p_nroempresa => :p_nroempresa,
                    p_id_plano   => :p_id_plano,
                    p_msg        => :p_msg
                );
            END;";

    $st = $conn->prepare($sql);
    $st->bindParam(':p_id_upload', $idUpload, PDO::PARAM_INT);
    $st->bindParam(':p_nroempresa', $nroEmpresa, PDO::PARAM_INT);

    $idPlano = 0;
    $msg = cyc_out_string();
    $st->bindParam(':p_id_plano', $idPlano, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);
    $st->bindParam(':p_msg', $msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $st->execute();

    $msg = trim($msg);
    cyc_fail_if_pkg_error($msg);

    return [
        'idPlano' => (int)$idPlano,
        'idUpload' => $idUpload,
        'nroEmpresa' => $nroEmpresa,
        'mensagem' => $msg,
    ];
}

function cyc_registrar_contagem(PDO $conn, array $req): array
{
    $idPlanoEnd = cyc_numeric_input($req, ['idPlanoEnd', 'id_plano_end', 'p_id_plano_end'], 'o ID do endereco do plano');
    $codProdutivo = cyc_normalize_text($req['codProdutivo'] ?? $req['cod_produtivo'] ?? $req['p_cod_produtivo'] ?? '', 80);
    $seqProduto = cyc_numeric_input($req, ['seqProduto', 'seqproduto', 'p_seqproduto'], 'o produto');
    $qtdContada = cyc_parse_number($req['qtdContada'] ?? $req['qtd_contada'] ?? $req['p_qtd_contada'] ?? 0);
    $dtaValidade = cyc_parse_date($req['dtaValidade'] ?? $req['dtavalidade'] ?? $req['p_dtavalidade'] ?? null);
    $tipoBipage = cyc_normalize_text($req['tipoBipage'] ?? $req['tipo_bipage'] ?? $req['tipoBipagem'] ?? $req['p_tipo_bipage'] ?? 'MANUAL', 30);

    if ($codProdutivo === '') {
        throw new Exception('Informe o produtivo.');
    }

    $pkg = cyc_pkg_inv_cic();
    $sql = "DECLARE
                v_dtavalidade DATE;
            BEGIN
                v_dtavalidade := TO_DATE(:p_dtavalidade, 'YYYY-MM-DD');

                {$pkg}.prc_registrar_contagem_inv_cic(
                    p_id_plano_end  => :p_id_plano_end,
                    p_cod_produtivo => :p_cod_produtivo,
                    p_seqproduto    => :p_seqproduto,
                    p_qtd_contada   => :p_qtd_contada,
                    p_dtavalidade   => v_dtavalidade,
                    p_tipo_bipage   => :p_tipo_bipage,
                    p_id_contagem   => :p_id_contagem,
                    p_msg           => :p_msg
                );
            END;";

    $st = $conn->prepare($sql);
    $st->bindParam(':p_id_plano_end', $idPlanoEnd, PDO::PARAM_INT);
    $st->bindParam(':p_cod_produtivo', $codProdutivo, PDO::PARAM_STR);
    $st->bindParam(':p_seqproduto', $seqProduto, PDO::PARAM_INT);
    $st->bindParam(':p_qtd_contada', $qtdContada);
    $st->bindParam(':p_dtavalidade', $dtaValidade);
    $st->bindParam(':p_tipo_bipage', $tipoBipage, PDO::PARAM_STR);

    $idContagem = 0;
    $msg = cyc_out_string();
    $st->bindParam(':p_id_contagem', $idContagem, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 20);
    $st->bindParam(':p_msg', $msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $st->execute();

    $msg = trim($msg);
    cyc_fail_if_pkg_error($msg);

    return [
        'idContagem' => (int)$idContagem,
        'idPlanoEnd' => $idPlanoEnd,
        'mensagem' => $msg,
    ];
}

function cyc_comparar_contagem(PDO $conn, array $req): array
{
    $idPlanoEnd = cyc_numeric_input($req, ['idPlanoEnd', 'id_plano_end', 'p_id_plano_end'], 'o ID do endereco do plano');
    $nrContagem = cyc_numeric_input($req, ['nrContagem', 'nr_contagem', 'p_nr_contagem'], 'o numero da contagem');
    $pkg = cyc_pkg_inv_cic();

    $sql = "BEGIN
                {$pkg}.prc_comparar_contagem_inv_cic(
                    p_id_plano_end => :p_id_plano_end,
                    p_nr_contagem  => :p_nr_contagem,
                    p_divergente   => :p_divergente,
                    p_msg          => :p_msg
                );
            END;";

    $st = $conn->prepare($sql);
    $st->bindParam(':p_id_plano_end', $idPlanoEnd, PDO::PARAM_INT);
    $st->bindParam(':p_nr_contagem', $nrContagem, PDO::PARAM_INT);

    $divergente = str_repeat(' ', 10);
    $msg = cyc_out_string();
    $st->bindParam(':p_divergente', $divergente, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 10);
    $st->bindParam(':p_msg', $msg, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
    $st->execute();

    $divergente = trim($divergente);
    $msg = trim($msg);
    if ($divergente === 'E') {
        throw new Exception($msg);
    }
    cyc_fail_if_pkg_error($msg);

    return [
        'idPlanoEnd' => $idPlanoEnd,
        'nrContagem' => $nrContagem,
        'divergente' => $divergente,
        'mensagem' => $msg,
    ];
}

function cyc_relatorio(PDO $conn, array $req, string $tipo): array
{
    $idPlano = cyc_numeric_input($req, ['idPlano', 'id_plano', 'p_id_plano', 'id'], 'o ID do plano');
    $proc = $tipo === 'analitico' ? 'prc_rel_analitico_inv_cic' : 'prc_rel_sintetico_inv_cic';
    $pkg = cyc_pkg_inv_cic();

    $sql = "BEGIN
                {$pkg}.{$proc}(
                    p_id_plano => :p_id_plano,
                    p_cur      => :p_cur
                );
            END;";

    $st = $conn->prepare($sql);
    $st->bindParam(':p_id_plano', $idPlano, PDO::PARAM_INT);

    $cursor = $conn->prepare('SELECT 1 FROM DUAL');
    $st->bindParam(':p_cur', $cursor, PDO::PARAM_STMT);
    $st->execute();

    return [
        'idPlano' => $idPlano,
        'tipo' => $tipo,
        'linhas' => cyc_refcursor_rows($cursor),
    ];
}

try {
    $body = cyc_body();
    $req = array_merge($_GET, $body);
    $action = cyc_action($req);
    $conn = getConexaoPDO();

    switch ($action) {
        case 'help':
            cyc_json(true, cyc_help());
            break;
        case 'get_product':
            cyc_json(true, cyc_lookup_product($conn, (string)($req['seq'] ?? '')));
            break;
        case 'search_stock_items':
            cyc_json(true, cyc_search_stock_items($conn, (string)($req['q'] ?? ''), (int)($req['limit'] ?? 30)));
            break;
        case 'search_productivos':
            cyc_json(true, cyc_search_productivos($conn, (string)($req['q'] ?? '')));
            break;
        case 'list_plans':
        case 'list_released':
            cyc_json(true, cyc_list_plans($conn));
            break;
        case 'get_plan':
            cyc_json(true, cyc_fetch_plan_payload($conn, cyc_numeric_input($req, ['id', 'idPlano', 'id_plano'], 'o ID do plano')));
            break;
        case 'save_plan':
            cyc_json(true, cyc_save_plan_from_form($conn, $req));
            break;
        case 'release_plan':
        case 'reopen_plan':
            cyc_json(true, ['mensagem' => 'Status controlado pela package nova.', 'idPlano' => $req['id'] ?? null]);
            break;
        case 'gerar_plano':
        case 'gerar_plano_inv_cic':
        case 'generate_cycle_plan':
            cyc_json(true, cyc_gerar_plano($conn, $req));
            break;
        case 'registrar_contagem':
        case 'registrar_contagem_inv_cic':
        case 'register_cycle_count':
            cyc_json(true, cyc_registrar_contagem($conn, $req));
            break;
        case 'comparar_contagem':
        case 'comparar_contagem_inv_cic':
        case 'compare_cycle_count':
            cyc_json(true, cyc_comparar_contagem($conn, $req));
            break;
        case 'rel_sintetico':
        case 'rel_sintetico_inv_cic':
        case 'report_summary':
            cyc_json(true, cyc_relatorio($conn, $req, 'sintetico'));
            break;
        case 'rel_analitico':
        case 'rel_analitico_inv_cic':
        case 'report_detail':
            cyc_json(true, cyc_relatorio($conn, $req, 'analitico'));
            break;
        default:
            throw new Exception('Acao invalida: ' . $action);
    }
} catch (Throwable $e) {
    cyc_json(false, null, cyc_pretty_error($e), 400);
}
