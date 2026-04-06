<?php
// processa_imp_metas_perspec.php
session_start();

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no'); // nginx
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');

function sse_send(string $msg, string $tipo = 'sistema'): void {
    echo "data: " . json_encode(['msg' => $msg, 'tipo' => $tipo], JSON_UNESCAPED_UNICODE) . "\n\n";
    @ob_flush();
    @flush();
}

function sse_close(): void {
    echo "event: close\n";
    echo "data: {}\n\n";
    @ob_flush();
    @flush();
}

function normalize_header(string $v): string {
    $v = strtoupper(trim((string)$v));
    $v = str_replace([' ', '-', '.', '/', '\\'], '', $v);
    return $v;
}

function to_oracle_datetime_str($value): ?string {
    if ($value === null || $value === '') return null;

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if (is_numeric($value)) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    $txt = trim((string)$value);
    if ($txt === '') return null;
    $txt = str_replace('T', ' ', $txt);

    foreach (['d/m/Y H:i:s','d/m/Y H:i','d/m/Y','Y-m-d H:i:s','Y-m-d H:i','Y-m-d'] as $f) {
        $dt = DateTime::createFromFormat($f, $txt);
        if ($dt instanceof DateTime) {
            if (strlen($txt) <= 10) $dt->setTime(0,0,0);
            return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
}

try {
    if (empty($_SESSION['logado'])) {
        throw new Exception('Sessão expirada. Faça login novamente.');
    }

    $arquivo = isset($_GET['arquivo']) ? basename($_GET['arquivo']) : '';
    if ($arquivo === '') throw new Exception('Parâmetro "arquivo" não informado.');

    // Mantido seu padrão (ajuste se necessário para ../uploads)
    $filePath = __DIR__ . '/uploads/' . $arquivo;
    if (!file_exists($filePath)) {
        throw new Exception("Arquivo não encontrado em uploads: {$arquivo}");
    }

    // Autoload composer (PhpSpreadsheet)
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new Exception('vendor/autoload.php não encontrado. Rode composer install.');
    }
    require_once $autoload;

    // Conexão Oracle
    $dbCfg = __DIR__ . '/db_config/db_connect.php';
    if (!file_exists($dbCfg)) {
        throw new Exception('db_config/db_connect.php não encontrado.');
    }
    require_once $dbCfg;
    if (!isset($conn) || !$conn) throw new Exception('Falha ao conectar no Oracle.');

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $owner = 'CONSINCO';

    // ============================================================
    // TABELA BASE (sem owner) - usada para:
    // 1) validar colunas no ALL_TAB_COLUMNS
    // 2) executar a função megag_fn_tabs_importacao_sqlexec('<tabela>')
    // ============================================================
    $importTableBase = 'MEGAG_IMP_METAS_PERSPEC';

    // Tabela completa com owner (como estava no seu código)
    $table = "{$owner}.{$importTableBase}";

    $usuario = $_SESSION['usuario'] ?? 'SYSTEM';

    sse_send("Abrindo planilha: {$arquivo}", 'sistema');

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = (int)$sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    // Header (linha 1)
    $headerRow = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0] ?? [];
    $headerMap = [];
    foreach ($headerRow as $idx => $name) {
        $key = normalize_header((string)$name);
        if ($key !== '') $headerMap[$key] = $idx;
    }

    // Colunas obrigatórias (aliases)
    $required = [
        'CODMETA'     => ['CODMETA','COD_META'],
        'PERSPEC'     => ['PERSPEC','PERSPECT','PERSPECTIVA'],
        'DATA'        => ['DATA','DT','DTA'],
        'STATUS'      => ['STATUS','SITUACAO'],
        'ATUALIZACAO' => ['ATUALIZACAO','ATUALIZAÇÃO','DTAATUALIZACAO','DTATUALIZACAO','DATAATUALIZACAO','ATUALIZADOEM'],
    ];

    $colIndex = [];
    foreach ($required as $canonical => $aliases) {
        $found = null;
        foreach ($aliases as $a) {
            $k = normalize_header($a);
            if (isset($headerMap[$k])) { $found = $headerMap[$k]; break; }
        }
        if ($found === null) {
            throw new Exception("Coluna obrigatória não encontrada no Excel: {$canonical}. Verifique o cabeçalho.");
        }
        $colIndex[$canonical] = $found;
    }

    // Colunas existentes no Oracle
    $stmtCols = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
    ");
    $stmtCols->execute([
        ':own' => $owner,
        ':tab' => $importTableBase
    ]);
    $dbCols = array_map('strtoupper', $stmtCols->fetchAll(PDO::FETCH_COLUMN));
    $has = fn($c) => in_array(strtoupper($c), $dbCols, true);

    // chaves do merge
    foreach (['CODMETA','PERSPEC'] as $c) {
        if (!$has($c)) throw new Exception("A tabela no Oracle não possui a coluna esperada (chave MERGE): {$c}");
    }

    // Monta MERGE dinâmico
    $setParts = [];
    $insCols  = [];
    $insVals  = [];

    if ($has('DATA'))        $setParts[] = "t.DATA = TO_DATE(:DATA, 'YYYY-MM-DD HH24:MI:SS')";
    if ($has('STATUS'))      $setParts[] = "t.STATUS = :STATUS";
    if ($has('ATUALIZACAO')) $setParts[] = "t.ATUALIZACAO = TO_DATE(:ATUALIZACAO, 'YYYY-MM-DD HH24:MI:SS')";

    if ($has('USULANCTO')) {
        $setParts[] = "t.USULANCTO = :USU";
        $insCols[]  = "USULANCTO";
        $insVals[]  = ":USU";
    }
    if ($has('DTAINCLUSAO')) {
        $setParts[] = "t.DTAINCLUSAO = SYSDATE";
        $insCols[]  = "DTAINCLUSAO";
        $insVals[]  = "SYSDATE";
    }
    if ($has('MSG_LOG')) {
        $setParts[] = "t.MSG_LOG = NULL";
        $insCols[]  = "MSG_LOG";
        $insVals[]  = "NULL";
    }

    // INSERT chaves
    $insCols[] = "CODMETA";  $insVals[] = ":CODMETA";
    $insCols[] = "PERSPEC";  $insVals[] = ":PERSPEC";

    // INSERT demais
    if ($has('DATA'))        { $insCols[] = "DATA";        $insVals[] = "TO_DATE(:DATA, 'YYYY-MM-DD HH24:MI:SS')"; }
    if ($has('STATUS'))      { $insCols[] = "STATUS";      $insVals[] = ":STATUS"; }
    if ($has('ATUALIZACAO')) { $insCols[] = "ATUALIZACAO"; $insVals[] = "TO_DATE(:ATUALIZACAO, 'YYYY-MM-DD HH24:MI:SS')"; }

    if (empty($setParts)) {
        throw new Exception("Nenhuma coluna para UPDATE foi encontrada no Oracle (verifique a estrutura da tabela).");
    }

    $sql = "
        MERGE INTO {$table} t
        USING (
            SELECT
                :CODMETA AS CODMETA,
                :PERSPEC AS PERSPEC
            FROM dual
        ) s
        ON (
            t.CODMETA = s.CODMETA
            AND t.PERSPEC = s.PERSPEC
        )
        WHEN MATCHED THEN
            UPDATE SET " . implode(", ", $setParts) . "
        WHEN NOT MATCHED THEN
            INSERT (" . implode(", ", $insCols) . ")
            VALUES (" . implode(", ", $insVals) . ")
    ";

    $stmtMerge = $conn->prepare($sql);

    $ok = 0;
    $fail = 0;

    sse_send("Cabeçalho OK. Processando linhas (2..{$highestRow})", 'sistema');

    for ($r = 2; $r <= $highestRow; $r++) {
        $rowArr = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, true, false)[0] ?? [];

        $get = function(string $col) use ($rowArr, $colIndex){
            $i = $colIndex[$col];
            return $rowArr[$i] ?? null;
        };

        $codmeta = trim((string)$get('CODMETA'));
        $perspec = trim((string)$get('PERSPEC'));
        $data    = to_oracle_datetime_str($get('DATA'));
        $status  = trim((string)$get('STATUS'));
        $atual   = to_oracle_datetime_str($get('ATUALIZACAO'));

        // pula linha vazia
        if ($codmeta==='' && $perspec==='' && !$data && $status==='' && !$atual) continue;

        // valida chave
        if ($codmeta==='' || $perspec==='') {
            $fail++;
            sse_send("Linha {$r}: campos obrigatórios vazios (CODMETA/PERSPEC).", 'aviso');
            continue;
        }

        if ($has('DATA') && !$data) {
            $fail++;
            sse_send("Linha {$r}: DATA inválida.", 'aviso');
            continue;
        }
        if ($has('ATUALIZACAO') && !$atual) {
            $fail++;
            sse_send("Linha {$r}: ATUALIZACAO inválida.", 'aviso');
            continue;
        }

        try {
            $params = [
                ':CODMETA'     => $codmeta,
                ':PERSPEC'     => $perspec,
                ':DATA'        => $data,
                ':STATUS'      => $status,
                ':ATUALIZACAO' => $atual,
                ':USU'         => $usuario,
            ];

            if (!$has('DATA')) unset($params[':DATA']);
            if (!$has('STATUS')) unset($params[':STATUS']);
            if (!$has('ATUALIZACAO')) unset($params[':ATUALIZACAO']);
            if (!$has('USULANCTO')) unset($params[':USU']);

            $stmtMerge->execute($params);
            $ok++;

            if ($ok % 50 === 0) {
                sse_send("Processadas {$ok} linhas com sucesso...", 'sistema');
            }
        } catch (Exception $e) {
            $fail++;
            sse_send("Linha {$r}: erro ao gravar -> " . $e->getMessage(), 'erro');
        }
    }

    // ============================================================
    // PÓS-IMPORTAÇÃO: EXECUTA FUNÇÃO APÓS FINALIZAR AS GRAVAÇÕES
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_metas_perspec') from dual;
    // ============================================================

    try {
        sse_send("Executando rotina pós-importação: consinco.megag_fn_tabs_importacao_sqlexec...", 'sistema');

        // Sanitiza apenas para evitar qualquer caractere fora do padrão
        $importTableFn = preg_replace('/[^A-Z0-9_]/', '', strtoupper($importTableBase));

        // A função (como você mostrou) recebe o nome em minúsculo.
        // Se no seu Oracle ela for case-insensitive, tanto faz.
        // Aqui vou mandar em minúsculo conforme seu exemplo.
        $importTableFnLower = strtolower($importTableFn);

        $stmtFn = $conn->prepare("SELECT consinco.megag_fn_tabs_importacao_sqlexec(:p_table) AS RET FROM dual");
        $stmtFn->execute([':p_table' => $importTableFnLower]);

        $ret = $stmtFn->fetch(PDO::FETCH_ASSOC);
        $retMsg = '';

        if (is_array($ret)) {
            if (isset($ret['RET'])) $retMsg = (string)$ret['RET'];
            elseif (isset($ret['ret'])) $retMsg = (string)$ret['ret'];
            else {
                $first = array_values($ret);
                $retMsg = isset($first[0]) ? (string)$first[0] : '';
            }
        }

        if ($retMsg !== '') {
            sse_send("Rotina pós-importação finalizada. Retorno: {$retMsg}", 'sucesso');
        } else {
            sse_send("Rotina pós-importação finalizada com sucesso.", 'sucesso');
        }

    } catch (Exception $e) {
        // Não interrompe tudo, mas avisa
        sse_send("Falha na rotina pós-importação: " . $e->getMessage(), 'erro');
    }

    sse_send("Finalizado. Sucesso: {$ok} | Falhas: {$fail}", $fail ? 'aviso' : 'sucesso');
    sse_close();

} catch (Exception $e) {
    sse_send("ERRO CRÍTICO: " . $e->getMessage(), 'erro');
    sse_close();
}
