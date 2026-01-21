<?php
// processa_metas_faixas.php
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
    $importTableBase = 'MEGAG_IMP_METAS_FAIXAS';

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
    $norm = function($v){
        $v = strtoupper(trim((string)$v));
        $v = str_replace([' ', '-', '.', '/', '\\'], '', $v);
        return $v;
    };

    $headerMap = [];
    foreach ($headerRow as $idx => $name) {
        $key = $norm($name);
        if ($key !== '') $headerMap[$key] = $idx;
    }

    // Colunas obrigatórias (aliases)
    $required = [
        'CODPERIODO'      => ['CODPERIODO','COD_PERIODO','PERIODO'],
        'CODVENDEDOR'     => ['CODVENDEDOR','COD_VENDEDOR','VENDEDOR'],
        'CODMETA'         => ['CODMETA','COD_META'],
        'CODFAIXA'        => ['CODFAIXA','COD_FAIXA','FAIXA'],
        'DESCFAIXA'       => ['DESCFAIXA','DESCRICAOFAIXA','DESCRFAIXA'],
        'DESCFAIXARCA'    => ['DESCFAIXARCA','DESCFAIXA_RCA','FAIXARCA','DESC_RCA'],
        'DESCFATURAMENTO' => ['DESCFATURAMENTO','DESC_FATURAMENTO','FATURAMENTO','DESCFAT'],
        'FAIXAINI'        => ['FAIXAINI','FAIXA_INI','INICIO','INICIAL'],
        'FAIXAFIM'        => ['FAIXAFIM','FAIXA_FIM','FIM','FINAL'],
        'GANHO'           => ['GANHO','VLRGANHO','VALORGANHO'],
        'DATAATAULIZACAO' => ['DATAATAULIZACAO','DATAATUALIZACAO','DTAATUALIZACAO','DTATUALIZACAO','ATUALIZACAO'],
        // SEQPRODUTO está na lista de campos, então tratamos como obrigatório
        'SEQPRODUTO'      => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO'],
    ];

    $colIndex = [];
    foreach ($required as $canonical => $aliases) {
        $found = null;
        foreach ($aliases as $a) {
            $k = $norm($a);
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

    // chaves do merge (ajuste se necessário)
    $mergeKeys = ['CODPERIODO','CODVENDEDOR','CODMETA','CODFAIXA','SEQPRODUTO'];
    foreach ($mergeKeys as $c) {
        if (!$has($c)) {
            throw new Exception("A tabela no Oracle não possui a coluna esperada (chave MERGE): {$c}");
        }
    }

    // Monta MERGE dinâmico
    $setParts = [];
    $insCols  = [];
    $insVals  = [];

    // updates do excel (se existir no oracle)
    $updCols = ['DESCFAIXA','DESCFAIXARCA','DESCFATURAMENTO','FAIXAINI','FAIXAFIM','GANHO','DATAATAULIZACAO'];
    foreach ($updCols as $c) {
        if (!$has($c)) {
            sse_send("Aviso: coluna {$c} não existe no Oracle, será ignorada.", 'aviso');
            continue;
        }

        if ($c === 'DATAATAULIZACAO') {
            $setParts[] = "t.{$c} = TO_DATE(:{$c}, 'YYYY-MM-DD HH24:MI:SS')";
        } else {
            $setParts[] = "t.{$c} = :{$c}";
        }
    }

    // opcionais (igual seu padrão)
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

    // INSERT: demais (se existir)
    foreach ($updCols as $c) {
        if (!$has($c)) continue;
        $insCols[] = $c;
        $insVals[] = ($c === 'DATAATAULIZACAO')
            ? "TO_DATE(:{$c}, 'YYYY-MM-DD HH24:MI:SS')"
            : ":{$c}";
    }

    // USING/ON
    $usingSel = [];
    foreach ($mergeKeys as $c) $usingSel[] = ":{$c} AS {$c}";

    $onParts = [];
    foreach ($mergeKeys as $c) $onParts[] = "t.{$c} = s.{$c}";

    if (empty($setParts)) {
        throw new Exception("Nenhuma coluna para UPDATE encontrada (verifique a estrutura da tabela).");
    }

    $sql = "
        MERGE INTO {$table} t
        USING (
            SELECT " . implode(", ", $usingSel) . " FROM dual
        ) s
        ON (" . implode(" AND ", $onParts) . ")
        WHEN MATCHED THEN
            UPDATE SET " . implode(", ", $setParts) . "
        WHEN NOT MATCHED THEN
            INSERT (" . implode(", ", $insCols) . ")
            VALUES (" . implode(", ", $insVals) . ")
    ";

    $stmtMerge = $conn->prepare($sql);

    // helpers parse
    $toDateStr = function($value): ?string {
        if ($value === null || $value === '') return null;

        if ($value instanceof DateTimeInterface) return $value->format('Y-m-d H:i:s');

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
    };

    $toNum = function($value): ?float {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float)$value;
        $txt = trim((string)$value);
        if ($txt === '') return null;
        $txt = str_replace('.', '', $txt);
        $txt = str_replace(',', '.', $txt);
        return is_numeric($txt) ? (float)$txt : null;
    };

    $ok = 0;
    $fail = 0;

    sse_send("Cabeçalho OK. Processando linhas (2..{$highestRow})", 'sistema');

    for ($r = 2; $r <= $highestRow; $r++) {
        $rowArr = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, true, false)[0] ?? [];

        $get = function(string $col) use ($rowArr, $colIndex){
            $i = $colIndex[$col];
            return $rowArr[$i] ?? null;
        };

        // chaves
        $codperiodo  = $get('CODPERIODO');
        $codvendedor = $get('CODVENDEDOR');
        $codmeta     = $get('CODMETA');
        $codfaixa    = $get('CODFAIXA');
        $seqproduto  = $get('SEQPRODUTO');

        // demais
        $descfaixa    = trim((string)$get('DESCFAIXA'));
        $descfaixarca = trim((string)$get('DESCFAIXARCA'));
        $descfat      = trim((string)$get('DESCFATURAMENTO'));

        $faixaini = $toNum($get('FAIXAINI'));
        $faixafim = $toNum($get('FAIXAFIM'));
        $ganho    = $toNum($get('GANHO'));

        $dtAtual  = $toDateStr($get('DATAATAULIZACAO'));

        // pula linha vazia
        $isEmpty = ($codperiodo===null||$codperiodo==='') &&
                   ($codvendedor===null||$codvendedor==='') &&
                   ($codmeta===null||$codmeta==='') &&
                   ($codfaixa===null||$codfaixa==='') &&
                   ($seqproduto===null||$seqproduto==='') &&
                   ($descfaixa==='') && ($descfaixarca==='') && ($descfat==='') &&
                   ($faixaini===null) && ($faixafim===null) && ($ganho===null) &&
                   (!$dtAtual);
        if ($isEmpty) continue;

        // valida chave
        if ($codperiodo===null || $codvendedor===null || $codmeta===null || $codfaixa===null || $seqproduto===null) {
            $fail++;
            sse_send("Linha {$r}: chave incompleta (CODPERIODO/CODVENDEDOR/CODMETA/CODFAIXA/SEQPRODUTO).", 'aviso');
            continue;
        }

        // valida data se coluna existe no Oracle
        if ($has('DATAATAULIZACAO') && !$dtAtual) {
            $fail++;
            sse_send("Linha {$r}: DATAATAULIZACAO inválida.", 'aviso');
            continue;
        }

        try {
            $params = [
                ':CODPERIODO'      => (string)$codperiodo,
                ':CODVENDEDOR'     => (string)$codvendedor,
                ':CODMETA'         => (string)$codmeta,
                ':CODFAIXA'        => (string)$codfaixa,
                ':SEQPRODUTO'      => (string)$seqproduto,
                ':DESCFAIXA'       => $descfaixa,
                ':DESCFAIXARCA'    => $descfaixarca,
                ':DESCFATURAMENTO' => $descfat,
                ':FAIXAINI'        => $faixaini,
                ':FAIXAFIM'        => $faixafim,
                ':GANHO'           => $ganho,
                ':DATAATAULIZACAO' => $dtAtual,
                ':USU'             => $usuario,
            ];

            // remove binds que não existem no Oracle
            foreach (['DESCFAIXA','DESCFAIXARCA','DESCFATURAMENTO','FAIXAINI','FAIXAFIM','GANHO','DATAATAULIZACAO'] as $c) {
                if (!$has($c)) unset($params[":{$c}"]);
            }
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
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_metas_faixas') from dual;
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
