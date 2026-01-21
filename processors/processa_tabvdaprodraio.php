<?php
// processa_tabvdaprodraio.php
/* ESSE PROCESSADOR, RECEBE A TABELA QUE FOI FEITO O UPLOAD NA PÁGINA CUSTO_COMERCIALIZACAO */
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
    $importTableBase = 'MEGAG_IMP_TABVDAPRODRAIO';

    // Tabela completa com owner (como estava no seu código)
    $table = "{$owner}.{$importTableBase}"; // <-- se o nome real for outro, ajuste aqui

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

    // Colunas obrigatórias (aceitando variações)
    $required = [
        'NROTABVENDA' => ['NROTABVENDA','NROTABVENDA','NROTAB','NROTABVDA'],
        'SEQPRODUTO'  => ['SEQPRODUTO','SEQPROD','PRODUTO','SEQ_PRODUTO'],
        'RAIO'        => ['RAIO'],
        'PERAD'       => ['PERAD','PERCAD','PERAD%','PERCENTUAL','PERCENTUALAD'],
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

    // Monta MERGE dinâmico
    $setParts = [];
    $insCols  = [];
    $insVals  = [];

    // sempre estes:
    $setParts[] = "t.PERAD = :PERAD";
    $insCols[]  = "PERAD";
    $insVals[]  = ":PERAD";

    // extras opcionais
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

    // chaves
    $insKeyCols = ["NROTABVENDA","SEQPRODUTO","RAIO"];
    foreach ($insKeyCols as $c) {
        if (!$has($c)) {
            throw new Exception("A tabela no Oracle não possui a coluna esperada: {$c}");
        }
        $insCols[] = $c;
        $insVals[] = ":{$c}";
    }

    $sql = "
        MERGE INTO {$table} t
        USING (
            SELECT
                :NROTABVENDA AS NROTABVENDA,
                :SEQPRODUTO  AS SEQPRODUTO,
                :RAIO        AS RAIO
            FROM dual
        ) s
        ON (
            t.NROTABVENDA = s.NROTABVENDA
            AND t.SEQPRODUTO = s.SEQPRODUTO
            AND t.RAIO = s.RAIO
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

        $nroTab = $get('NROTABVENDA');
        $seqProd = $get('SEQPRODUTO');
        $raio = $get('RAIO');
        $perad = $get('PERAD');

        // pula linhas vazias
        if ($nroTab === null && $seqProd === null && $raio === null && $perad === null) {
            continue;
        }

        // validação básica
        if ($nroTab === null || $seqProd === null || $raio === null || $perad === null) {
            $fail++;
            sse_send("Linha {$r}: campos obrigatórios vazios (NROTABVENDA/SEQPRODUTO/RAIO/PERAD).", 'aviso');
            continue;
        }

        try {
            $params = [
                ':NROTABVENDA' => (int)$nroTab,
                ':SEQPRODUTO'  => (int)$seqProd,
                ':RAIO'        => (int)$raio,
                ':PERAD'       => (float)str_replace(',', '.', (string)$perad),
                ':USU'         => $usuario,
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
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_tabvdaprodraio') from dual;
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
        // Não interrompe tudo, mas avisa e deixa log
        sse_send("Falha na rotina pós-importação: " . $e->getMessage(), 'erro');
    }

    sse_send("Finalizado. Sucesso: {$ok} | Falhas: {$fail}", $fail ? 'aviso' : 'sucesso');
    sse_close();

} catch (Exception $e) {
    sse_send("ERRO CRÍTICO: " . $e->getMessage(), 'erro');
    sse_close();
}
