<?php
// processors/processa_imp_bi_metas.php
/* Processador SSE do importador BI_METAS */

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
    $map = [
        'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
        'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
        'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
        'Ç'=>'C'
    ];
    $v = strtr($v, $map);
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

    $fmts = [
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd/m/Y',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
    ];

    foreach ($fmts as $f) {
        $dt = DateTime::createFromFormat($f, $txt);
        if ($dt instanceof DateTime) {
            if (strlen($txt) <= 10) $dt->setTime(0,0,0);
            return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
}

function to_number($value): ?float {
    if ($value === null || $value === '') return null;
    if (is_numeric($value)) return (float)$value;

    $txt = trim((string)$value);
    if ($txt === '') return null;

    $txt = str_replace('.', '', $txt);
    $txt = str_replace(',', '.', $txt);

    return is_numeric($txt) ? (float)$txt : null;
}

try {
    if (empty($_SESSION['logado'])) {
        throw new Exception('Sessão expirada. Faça login novamente.');
    }

    $arquivo = isset($_GET['arquivo']) ? basename($_GET['arquivo']) : '';
    if ($arquivo === '') throw new Exception('Parâmetro "arquivo" não informado.');

    // Igual ao seu modelo: assume /processors/uploads/
    // Se seu upload.php salva em /uploads na raiz, troque para: __DIR__ . '/../uploads/' . $arquivo;
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

    // Conexão Oracle (PDO)
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
    $importTableBase = 'MEGAG_IMP_BI_METAS';

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

    // Colunas obrigatórias (com aliases)
    $required = [
        'CODMETA'        => ['CODMETA','COD_META'],
        'CODVENDEDOR'    => ['CODVENDEDOR','COD_VENDEDOR','VENDEDOR'],
        'CODPERIODO'     => ['CODPERIODO','COD_PERIODO','PERIODO'],
        'META'           => ['META','VLRMETA','VALORMETA'],
        'CODREGIAO'      => ['CODREGIAO','COD_REGIAO','REGIAO'],
        'SEGMENTO'       => ['SEGMENTO','SEG'],
        'TIPORETIRA'     => ['TIPORETIRA','TIPO_RETIRA','TIPO'],
        'CATEGORIA'      => ['CATEGORIA','CAT'],
        'SEQPRODUTO'     => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO'],
        'DTAATUALIZACAO' => ['DTAATUALIZACAO','DTA_ATUALIZACAO','DTATUALIZACAO','DATAATUALIZACAO'],
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

    // ======== CHAVE DO MERGE (ajuste se precisar) ========
    $mergeKeys = ['CODMETA','CODVENDEDOR','CODPERIODO','CODREGIAO','SEQPRODUTO'];
    foreach ($mergeKeys as $c) {
        if (!$has($c)) throw new Exception("A tabela no Oracle não possui a coluna esperada (chave MERGE): {$c}");
    }

    // Campos de update (do excel)
    $updatable = ['META','SEGMENTO','TIPORETIRA','CATEGORIA','DTAATUALIZACAO'];
    foreach ($updatable as $c) {
        if (!$has($c)) {
            // se não existir no Oracle, não trava: só não atualiza/insere
            sse_send("Aviso: coluna {$c} não existe no Oracle, será ignorada.", 'aviso');
        }
    }

    // Monta MERGE dinâmico
    $setParts = [];
    $insCols  = [];
    $insVals  = [];

    // Updates do excel (somente se a coluna existe no Oracle)
    if ($has('META'))           $setParts[] = "t.META = :META";
    if ($has('SEGMENTO'))       $setParts[] = "t.SEGMENTO = :SEGMENTO";
    if ($has('TIPORETIRA'))     $setParts[] = "t.TIPORETIRA = :TIPORETIRA";
    if ($has('CATEGORIA'))      $setParts[] = "t.CATEGORIA = :CATEGORIA";
    if ($has('DTAATUALIZACAO')) $setParts[] = "t.DTAATUALIZACAO = TO_DATE(:DTAATUALIZACAO, 'YYYY-MM-DD HH24:MI:SS')";

    // opcionais (mesma pegada do seu)
    if ($has('STATUS')) {
        $setParts[] = "t.STATUS = 'S'";
        $insCols[]  = "STATUS";
        $insVals[]  = "'S'";
    }
    if ($has('MSG_LOG')) {
        $setParts[] = "t.MSG_LOG = NULL";
        $insCols[]  = "MSG_LOG";
        $insVals[]  = "NULL";
    }
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

    // INSERT: chaves sempre
    foreach ($mergeKeys as $c) {
        $insCols[] = $c;
        $insVals[] = ":{$c}";
    }

    // INSERT: campos do excel (somente se existe)
    if ($has('META'))           { $insCols[] = "META"; $insVals[] = ":META"; }
    if ($has('SEGMENTO'))       { $insCols[] = "SEGMENTO"; $insVals[] = ":SEGMENTO"; }
    if ($has('TIPORETIRA'))     { $insCols[] = "TIPORETIRA"; $insVals[] = ":TIPORETIRA"; }
    if ($has('CATEGORIA'))      { $insCols[] = "CATEGORIA"; $insVals[] = ":CATEGORIA"; }
    if ($has('DTAATUALIZACAO')) { $insCols[] = "DTAATUALIZACAO"; $insVals[] = "TO_DATE(:DTAATUALIZACAO, 'YYYY-MM-DD HH24:MI:SS')"; }

    // USING / ON para MERGE
    $usingSelect = [];
    foreach ($mergeKeys as $c) {
        $usingSelect[] = ":{$c} AS {$c}";
    }

    $onParts = [];
    foreach ($mergeKeys as $c) {
        $onParts[] = "t.{$c} = s.{$c}";
    }

    // Se não tiver nada pra atualizar (setParts vazio), evita SQL inválido
    if (empty($setParts)) {
        throw new Exception("Nenhuma coluna para UPDATE foi encontrada no Oracle (verifique a estrutura da tabela).");
    }

    $sql = "
        MERGE INTO {$table} t
        USING (
            SELECT " . implode(", ", $usingSelect) . "
            FROM dual
        ) s
        ON (" . implode(" AND ", $onParts) . ")
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

        $codmeta     = trim((string)$get('CODMETA'));
        $codvend     = trim((string)$get('CODVENDEDOR'));
        $codperiodo  = trim((string)$get('CODPERIODO'));
        $codregiao   = trim((string)$get('CODREGIAO'));
        $seqproduto  = trim((string)$get('SEQPRODUTO'));

        $meta        = to_number($get('META'));
        $segmento    = trim((string)$get('SEGMENTO'));
        $tiporetira  = trim((string)$get('TIPORETIRA'));
        $categoria   = trim((string)$get('CATEGORIA'));
        $dtAtual     = to_oracle_datetime_str($get('DTAATUALIZACAO'));

        // pula linha totalmente vazia
        if ($codmeta==='' && $codvend==='' && $codperiodo==='' && $codregiao==='' && $seqproduto===''
            && $meta===null && $segmento==='' && $tiporetira==='' && $categoria==='' && !$dtAtual) {
            continue;
        }

        // validação básica (chaves)
        if ($codmeta==='' || $codvend==='' || $codperiodo==='' || $codregiao==='' || $seqproduto==='') {
            $fail++;
            sse_send("Linha {$r}: chave incompleta (CODMETA/CODVENDEDOR/CODPERIODO/CODREGIAO/SEQPRODUTO).", 'aviso');
            continue;
        }

        // valida meta e data (se existem no Oracle)
        if ($has('META') && $meta === null) {
            $fail++;
            sse_send("Linha {$r}: META inválida.", 'aviso');
            continue;
        }
        if ($has('DTAATUALIZACAO') && !$dtAtual) {
            $fail++;
            sse_send("Linha {$r}: DTAATUALIZACAO inválida.", 'aviso');
            continue;
        }

        try {
            $params = [
                ':CODMETA'        => $codmeta,
                ':CODVENDEDOR'    => $codvend,
                ':CODPERIODO'     => $codperiodo,
                ':CODREGIAO'      => $codregiao,
                ':SEQPRODUTO'     => $seqproduto,
                ':META'           => $meta,
                ':SEGMENTO'       => $segmento,
                ':TIPORETIRA'     => $tiporetira,
                ':CATEGORIA'      => $categoria,
                ':DTAATUALIZACAO' => $dtAtual,
                ':USU'            => $usuario,
            ];

            // remove binds que não serão usados
            if (!$has('META')) unset($params[':META']);
            if (!$has('SEGMENTO')) unset($params[':SEGMENTO']);
            if (!$has('TIPORETIRA')) unset($params[':TIPORETIRA']);
            if (!$has('CATEGORIA')) unset($params[':CATEGORIA']);
            if (!$has('DTAATUALIZACAO')) unset($params[':DTAATUALIZACAO']);
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
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_bi_metas') from dual;
    // ============================================================

    try {
        sse_send("Executando rotina pós-importação: megag_fn_tabs_importacao_sqlexec...", 'sistema');

        // Sanitiza apenas para evitar qualquer caractere fora do padrão
        $importTableFn = preg_replace('/[^A-Z0-9_]/', '', strtoupper($importTableBase));

        // A função (como você mostrou) recebe o nome em minúsculo.
        // Se no seu Oracle ela for case-insensitive, tanto faz.
        // Aqui vou mandar em minúsculo conforme seu exemplo.
        $importTableFnLower = strtolower($importTableFn);

        $stmtFn = $conn->prepare("SELECT megag_fn_tabs_importacao_sqlexec(:p_table) AS RET FROM dual");
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
