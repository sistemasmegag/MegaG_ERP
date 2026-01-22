<?php
// processa_lanctocomissao.php
/* ESSE PROCESSADOR RECEBE A TABELA QUE FOI FEITO O UPLOAD NA PÁGINA imp_lanctocomissao */
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

    // remove acentos básicos (pra bater OBSERVAÇÃO/OBSERVACAO)
    $map = [
        'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
        'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
        'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
        'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
        'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
        'Ç'=>'C'
    ];
    $v = strtr($v, $map);

    // remove separadores
    $v = str_replace([' ', '-', '.', '/', '\\'], '', $v);
    return $v;
}

/**
 * Converte valor de Excel em string "YYYY-MM-DD HH24:MI:SS" (ou null)
 */
function to_oracle_datetime_str($value): ?string {
    if ($value === null || $value === '') return null;

    // DateTime direto
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    // Excel serial number
    if (is_numeric($value)) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    // texto
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

    // "1.234,56" -> "1234.56"
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

    // !!! ATENÇÃO !!!
    // Mantido igual ao seu modelo. Se seu upload.php salva em outro local, ajuste aqui:
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
    $importTableBase = 'MEGAG_IMP_LANCTOCOMISSAO';

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

    // Lê header (linha 1)
    $headerRow = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0] ?? [];

    $headerMap = [];
    foreach ($headerRow as $idx => $name) {
        $key = normalize_header((string)$name);
        if ($key !== '') $headerMap[$key] = $idx;
    }

    // Colunas obrigatórias (aceitando variações)
    $required = [
        'CODEVENTO'   => ['CODEVENTO','COD_EVENTO','EVENTO','CODIGOEVENTO'],
        'SEQPESSOA'   => ['SEQPESSOA','SEQ_PESSOA','PESSOA','CODPESSOA'],
        'DTAHREMISSAO'=> ['DTAHREMISSAO','DTAHR_EMISSAO','DTAEMISSAO','DTEMISSAO','DATAEMISSAO','DATA_HORA_EMISSAO'],
        'OBSERVACAO'  => ['OBSERVACAO','OBSERVAÇÃO','OBS','OBSERV'],
        'VLRTOTAL'    => ['VLRTOTAL','VLR_TOTAL','TOTAL','VALORTOTAL','VALOR_TOTAL'],
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

    // Descobre colunas existentes no Oracle (pra inserir só o que existe)
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

    // checa se as colunas chave existem no Oracle
    $keyCols = ['CODEVENTO','SEQPESSOA','DTAHREMISSAO'];
    foreach ($keyCols as $c) {
        if (!$has($c)) throw new Exception("A tabela no Oracle não possui a coluna esperada: {$c}");
    }
    if (!$has('VLRTOTAL')) throw new Exception("A tabela no Oracle não possui a coluna esperada: VLRTOTAL");
    if (!$has('OBSERVACAO') && !$has('OBSERVAÇÃO')) {
        // normalmente será OBSERVACAO sem acento no Oracle
        // mas deixo a checagem flexível só por segurança
        // se existir com acento (pouco provável), você pode ajustar abaixo
    }

    // define qual coluna usar no Oracle para observação
    $dbObsCol = $has('OBSERVACAO') ? 'OBSERVACAO' : ($has('OBSERVAÇÃO') ? 'OBSERVAÇÃO' : 'OBSERVACAO');

    // Monta MERGE dinâmico
    $setParts = [];
    $insCols  = [];
    $insVals  = [];

    // campos do excel (sempre)
    $setParts[] = "t.{$dbObsCol} = :OBSERVACAO";
    $setParts[] = "t.VLRTOTAL = :VLRTOTAL";

    $insCols[] = $dbObsCol;
    $insVals[] = ":OBSERVACAO";

    $insCols[] = "VLRTOTAL";
    $insVals[] = ":VLRTOTAL";

    // extras opcionais (mesma pegada do seu)
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

    // inserts das chaves
    foreach ($keyCols as $c) {
        $insCols[] = $c;
        $insVals[] = ":{$c}";
    }

    // Atenção: DTAHREMISSAO no MERGE está como bind e convertido no SELECT do USING
    // Assim funciona bem com chave data/hora.
    $sql = "
        MERGE INTO {$table} t
        USING (
            SELECT
                :CODEVENTO AS CODEVENTO,
                :SEQPESSOA AS SEQPESSOA,
                TO_DATE(:DTAHREMISSAO, 'YYYY-MM-DD HH24:MI:SS') AS DTAHREMISSAO
            FROM dual
        ) s
        ON (
            t.CODEVENTO = s.CODEVENTO
            AND t.SEQPESSOA = s.SEQPESSOA
            AND t.DTAHREMISSAO = s.DTAHREMISSAO
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

        $codevento = $get('CODEVENTO');
        $seqpessoa = $get('SEQPESSOA');
        $dtEmissao = $get('DTAHREMISSAO');
        $obs       = $get('OBSERVACAO');
        $vlrTotal  = $get('VLRTOTAL');

        // pula linhas vazias
        if ($codevento === null && $seqpessoa === null && $dtEmissao === null && $obs === null && $vlrTotal === null) {
            continue;
        }

        $codevento = trim((string)$codevento);
        $seqpessoa = trim((string)$seqpessoa);
        $obs       = trim((string)$obs);

        $dtStr = to_oracle_datetime_str($dtEmissao);
        $vlr   = to_number($vlrTotal);

        // validação básica (obrigatórios)
        if ($codevento === '' || $seqpessoa === '' || !$dtStr || $vlr === null) {
            $fail++;
            sse_send("Linha {$r}: campos obrigatórios inválidos (CODEVENTO/SEQPESSOA/DTAHREMISSAO/VLRTOTAL).", 'aviso');
            continue;
        }

        try {
            $params = [
                ':CODEVENTO'    => $codevento,
                ':SEQPESSOA'    => $seqpessoa,
                ':DTAHREMISSAO' => $dtStr,
                ':OBSERVACAO'   => $obs,
                ':VLRTOTAL'     => $vlr,
                ':USU'          => $usuario,
            ];

            // Remove :USU se coluna não existe (evita bind inútil)
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
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_lanctocomissao') from dual;
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
