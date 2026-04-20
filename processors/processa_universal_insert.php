<?php
require_once __DIR__ . '/../bootstrap/db.php';
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // nginx

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { @ob_end_flush(); }
@ob_implicit_flush(1);

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

// ======================================================================
// 1) DEBUG + RECURSOS (para planilhas grandes)
// ======================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@set_time_limit(0); 
@ini_set('memory_limit', '1024M');

// ======================================================================
// 2) SESSÃO (usuário logado)
// ======================================================================
session_start();

$usuarioLogado = $_SESSION['usuario']
    ?? $_SESSION['user']
    ?? $_SESSION['nome']
    ?? $_SESSION['login']
    ?? 'SYSTEM';

$usuarioLogado = trim((string)$usuarioLogado);
if ($usuarioLogado === '') $usuarioLogado = 'SYSTEM';

// ======================================================================
// 3) HELPERS (header normalize + conversions)
// ======================================================================
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

    // remove separadores comuns
    $v = str_replace([' ', '-', '.', '/', '\\', "\t", "\n", "\r"], '', $v);

    return $v;
}

/** Para colunas de DATA PURA no banco (TO_DATE(X, 'YYYY-MM-DD')) */
function to_oracle_date_str($value): ?string {
    if ($value === null || $value === '') return null;

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_numeric($value)) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    $txt = trim((string)$value);
    if ($txt === '') return null;

    $txt = str_replace('T', ' ', $txt);

    $fmts = [
        'd/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y', 'd-m-y', 'Y/m/d',
        'd/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i',
    ];

    foreach ($fmts as $f) {
        $dt = DateTime::createFromFormat($f, $txt);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }

    $ts = strtotime(str_replace('/', '-', $txt));
    if ($ts !== false) return date('Y-m-d', $ts);

    return null;
}

/** Para colunas de DATA E HORA no banco (TO_DATE(X, 'YYYY-MM-DD HH24:MI:SS')) */
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
        'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
        'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
    ];

    foreach ($fmts as $f) {
        $dt = DateTime::createFromFormat($f, $txt);
        if ($dt instanceof DateTime) {
            if (strlen($txt) <= 10) $dt->setTime(0,0,0);
            return $dt->format('Y-m-d H:i:s');
        }
    }

    $ts = strtotime(str_replace('/', '-', $txt));
    if ($ts !== false) return date('Y-m-d H:i:s', $ts);

    return null;
}

function to_number($value): ?float {
    if ($value === null || $value === '') return null;

    if (is_int($value) || is_float($value)) {
        return (float)$value;
    }

    $txt = trim((string)$value);
    if ($txt === '') return null;

    // Se já veio em formato numérico simples, respeita como está.
    if (is_numeric($txt)) {
        return (float)$txt;
    }

    $hasComma = strpos($txt, ',') !== false;
    $hasDot = strpos($txt, '.') !== false;

    // pt-BR: "1.234,56" -> "1234.56"
    if ($hasComma && $hasDot) {
        $txt = str_replace('.', '', $txt);
        $txt = str_replace(',', '.', $txt);
        return is_numeric($txt) ? (float)$txt : null;
    }

    // decimal com vírgula: "269,53" -> "269.53"
    if ($hasComma) {
        $txt = str_replace(',', '.', $txt);
        return is_numeric($txt) ? (float)$txt : null;
    }

    // decimal com ponto: "269.53" -> 269.53
    return is_numeric($txt) ? (float)$txt : null;
}

function to_string($value): ?string {
    if ($value === null) return null;
    $s = trim((string)$value);
    return $s === '' ? null : $s;
}

function sheet_header_map(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $headerRowNum): array {
    $highestCol = $sheet->getHighestColumn();
    $headerRow = $sheet->rangeToArray("A{$headerRowNum}:{$highestCol}{$headerRowNum}", null, true, false)[0] ?? [];
    $headerMap = [];
    foreach ($headerRow as $idx => $name) {
        $key = normalize_header((string)$name);
        if ($key !== '') $headerMap[$key] = $idx;
    }
    return $headerMap;
}

function sheet_header_keys_preview(array $headerMap): string {
    $keys = array_keys($headerMap);
    if (!$keys) {
        return '(vazio)';
    }
    return implode(', ', array_slice($keys, 0, 12));
}

function sheet_matches_required_headers(array $headerMap, array $columns): bool {
    foreach ($columns as $colCfg) {
        $required = (bool)($colCfg['required'] ?? false);
        if (!$required) {
            continue;
        }

        $found = false;
        foreach ((array)($colCfg['aliases'] ?? []) as $alias) {
            $key = normalize_header((string)$alias);
            if ($key !== '' && isset($headerMap[$key])) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }
    }

    return true;
}

// ======================================================================
// 4) CONFIGS DOS IMPORTS (aqui vamos adicionando "import por import")
// ======================================================================
//
// Cada config define:
// - owner/table
// - colunas obrigatórias do Excel (aliases)
// - mapping de tipos
// - coluna opcional para usuário logado (ex: USUINCLUSAO)
// - comportamento padrão de staging: STATUS='P', DTAINCLUSAO=SYSDATE, RESIMPORTACAO=NULL, etc. (se existirem na tabela)
//
$configs = [

    // ==================================================================
    // 4.1) Cargas/Metas Operacional (Setor/Turno/Meta/Capac)
    // Tabela: CONSINCO.MEGAG_IMP_SETORMETACAPAC
    // ==================================================================
    'setormetacapac' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_SETORMETACAPAC',
        'start_row' => 2,
        'header_row' => 1,

        // se a tabela tiver essa coluna, preenche com usuário logado
        'session_user_column' => 'USUINCLUSAO',

        // mapeamento de colunas do excel -> coluna do banco
        // (aliases aceitam variações no cabeçalho)
        'columns' => [
            [
                'db' => 'SEQSETOR',
                'type' => 'number',
                'required' => true,
                'aliases' => ['SEQSETOR','SETOR','CODSETOR','COD_SETOR']
            ],
            [
                'db' => 'TURNO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['TURNO']
            ],
            [
                'db' => 'DTA',
                'type' => 'date',
                'required' => true,
                'aliases' => ['DTA','DATA','DATAREF','DATA_REF']
            ],
            [
                'db' => 'PESO_META',
                'type' => 'number',
                'required' => true,
                'aliases' => ['PESO_META','META','PESOMETA']
            ],
            [
                'db' => 'PESO_CAPAC',
                'type' => 'number',
                'required' => true,
                'aliases' => ['PESO_CAPAC','CAPAC','PESOCAPAC','CAPACIDADE']
            ],
        ],
    ],

    // ==================================================================
    // 4.2) Lançamento Comissão (staging)
    // Tabela: CONSINCO.MEGAG_IMP_LANCTOCOMISSAO
    // Conforme sua imagem (CODEVENTO, SEQPESSOA, DTAHREMISSAO, OBSERVACAO, VLRTOTAL, STATUS, RESIMPORTACAO)
    // ==================================================================
    'lanctocomissao' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_LANCTOCOMISSAO',
        'start_row' => 2,
        'header_row' => 1,

        // CORREÇÃO:
        // Essa tabela tem USUINCLUSAO NOT NULL (ORA-01400).
        // Então precisamos preencher com o usuário logado.
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'CODEVENTO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODEVENTO','COD_EVENTO','EVENTO','CODIGOEVENTO']
            ],
            [
                'db' => 'SEQPESSOA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['SEQPESSOA','SEQ_PESSOA','PESSOA','CODPESSOA']
            ],
            [
                'db' => 'DTAHREMISSAO',
                'type' => 'datetime',
                'required' => true,
                'aliases' => ['DTAHREMISSAO','DTAHR_EMISSAO','DTAEMISSAO','DTEMISSAO','DATAEMISSAO','DATA_HORA_EMISSAO']
            ],
            [
                'db' => 'OBSERVACAO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['OBSERVACAO','OBSERVAÇÃO','OBS','OBSERV']
            ],
            [
                'db' => 'VLRTOTAL',
                'type' => 'number',
                'required' => true,
                'aliases' => ['VLRTOTAL','VLR_TOTAL','TOTAL','VALORTOTAL','VALOR_TOTAL']
            ],
        ],
    ],

    // ==================================================================
    // 4.3) Metas (staging)
    // Tabela: CONSINCO.MEGAG_IMP_METAS
    // Colunas na tabela (pela sua print):
    // CODMETA | CODVENDEDOR | CODPERIODO | META | CODREGIAO | SEGMENTO | TIPORETIRA | CATEGORIA | SEQPRODUTO | STATUS | RESIMPOTACAO | USUINCLUSAO | DTAINCLUSAO
    // ==================================================================
    'metas' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_METAS',
        'start_row' => 2,
        'header_row' => 1,

        // Aqui é o ponto mais importante deste import:
        // a tabela TEM USUINCLUSAO e (pelo comportamento que você quer) deve vir do usuário logado
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'CODMETA',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODMETA','COD_META','META_COD','CODIGO_META']
            ],
            [
                'db' => 'CODVENDEDOR',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODVENDEDOR','COD_VENDEDOR','VENDEDOR','CODIGO_VENDEDOR']
            ],
            [
                'db' => 'CODPERIODO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODPERIODO','COD_PERIODO','PERIODO','CODIGO_PERIODO']
            ],
            [
                'db' => 'META',
                'type' => 'number',
                'required' => true,
                'aliases' => ['META','VALOR_META','VLRMETA','VLR_META']
            ],
            [
                'db' => 'CODREGIAO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODREGIAO','COD_REGIAO','REGIAO','CODIGO_REGIAO']
            ],
            [
                'db' => 'SEGMENTO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['SEGMENTO']
            ],
            [
                'db' => 'TIPORETIRA',
                'type' => 'string',
                'required' => false,
                'aliases' => ['TIPORETIRA','TIPO_RETIRA','TIPO_RETIRADA','RETIRA']
            ],
            [
                'db' => 'CATEGORIA',
                'type' => 'string',
                'required' => false,
                'aliases' => ['CATEGORIA','CATEG']
            ],
            [
                'db' => 'SEQPRODUTO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO','SEQ','SEQUENCIALPRODUTO']
            ],
        ],
    ],

    // ==================================================================
    // 4.4) Metas Faixas
    // Tabela: CONSINCO.MEGAG_IMP_METAS_FAIXAS
    // Colunas (print):
    // CODPERIODO, CODVENDEDOR, CODMETA, CODFAIXA,
    // DESCFAIXA, DESCFAIXARCA, DESCFATURAMENTO,
    // FAIXAINI, FAIXAFIM, GANHO,
    // RESIMPOTACAO, STATUS, DTAINCLUSAO
    // ==================================================================
    'metas_faixas' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_METAS_FAIXAS',
        'start_row' => 2,
        'header_row' => 1,

        // pela sua tabela (print) NÃO aparece USUINCLUSAO
        'session_user_column' => null,

        'columns' => [
            [
                'db' => 'CODPERIODO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODPERIODO','PERIODO','COD_PERIODO']
            ],
            [
                'db' => 'CODVENDEDOR',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODVENDEDOR','VENDEDOR','COD_VENDEDOR']
            ],
            [
                'db' => 'CODMETA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODMETA','META','COD_META']
            ],
            [
                'db' => 'CODFAIXA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODFAIXA','FAIXA','COD_FAIXA']
            ],
            [
                'db' => 'DESCFAIXA',
                'type' => 'string',
                'required' => false,
                'aliases' => ['DESCFAIXA','DESCRICAOFAIXA','DESC_FAIXA','DESCRICAO_FAIXA']
            ],
            [
                'db' => 'DESCFAIXARCA',
                'type' => 'string',
                'required' => false,
                'aliases' => ['DESCFAIXARCA','DESC_FAIXARCA','FAIXARCA','FAIXA_RCA']
            ],
            [
                'db' => 'DESCFATURAMENTO',
                'type' => 'string',
                'required' => false,
                'aliases' => ['DESCFATURAMENTO','DESC_FATURAMENTO','FATURAMENTO','DESC_FATUR']
            ],
            [
                'db' => 'FAIXAINI',
                'type' => 'number',
                'required' => true,
                'aliases' => ['FAIXAINI','FAIXA_INI','INICIO','INI']
            ],
            [
                'db' => 'FAIXAFIM',
                'type' => 'number',
                'required' => true,
                'aliases' => ['FAIXAFIM','FAIXA_FIM','FIM']
            ],
            [
                'db' => 'GANHO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['GANHO','VLRGANHO','VALORGANHO','VALOR_GANHO']
            ],
        ],
    ],

    // ==================================================================
    // 4.5) Metas GAP
    // Tabela: CONSINCO.MEGAG_IMP_METAS_GAP
    // Colunas (print):
    // CODPERIODO, CODMETA, GAP, RESIMPOTACAO, STATUS, USUINCLUSAO
    // ==================================================================
    'metas_gap' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_METAS_GAP',
        'start_row' => 2,
        'header_row' => 1,

        // essa tabela tem USUINCLUSAO (print), então preenche com usuário logado
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'CODPERIODO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODPERIODO','PERIODO','COD_PERIODO']
            ],
            [
                'db' => 'CODMETA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODMETA','META','COD_META']
            ],
            [
                'db' => 'GAP',
                'type' => 'number',
                'required' => true,
                'aliases' => ['GAP','VLRGAP','VALORGAP','VALOR_GAP']
            ],
        ],
    ],

    // ==================================================================
    // 4.6) Metas Perspectiva
    // Tabela: CONSINCO.MEGAG_IMP_METAS_PERSPECT
    // Colunas (print):
    // CODMETA, PERSPEC, DATA, STATUS, RESIMPOTACAO, USUINCLUSAO, DTAINCLUSAO
    // ==================================================================
    'metas_perspec' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_METAS_PERSPECT',
        'start_row' => 2,
        'header_row' => 1,

        // essa tabela tem USUINCLUSAO, então preenche com usuário logado
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'CODMETA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODMETA','META','COD_META']
            ],
            [
                'db' => 'PERSPEC',
                'type' => 'string',
                'required' => true,
                'aliases' => ['PERSPEC','PERSPECTIVA','PERSPECT','PERSPE']
            ],
            [
                'db' => 'DATA',
                'type' => 'date',
                'required' => true,
                'aliases' => ['DATA','DTA','DT','DATAREF','DATA_REF']
            ],
        ],
    ],

    // ==================================================================
    // 4.7) Tabela Venda Produto Raio
    // Tabela: CONSINCO.MEGAG_IMP_TABVDAPRODRAIO
    // Colunas (print):
    // NROTABVENDA, SEQPRODUTO, RAIO, PERAD, USUINCLUSAO, DTAINCLUSAO,
    // USUALTERACAO, STATUS, RESIMPOTACAO
    // ==================================================================
    'tabvdaprodraio' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_TABVDAPRODRAIO',
        'start_row' => 2,
        'header_row' => 1,

        // Aqui são 2 campos de usuário
        'session_user_columns' => ['USUINCLUSAO', 'USUALTERACAO'],

        'columns' => [
            [
                'db' => 'NROTABVENDA',
                'type' => 'number',
                'required' => true,
                'aliases' => ['NROTABVENDA','NRO_TABVENDA','TABVENDA','TABELAVENDA','NROTABELAVENDA']
            ],
            [
                'db' => 'SEQPRODUTO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO','CODPRODUTO','COD_PRODUTO']
            ],
            [
                'db' => 'RAIO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['RAIO']
            ],
            [
                'db' => 'PERAD',
                'type' => 'number',
                'required' => true,
                'aliases' => ['PERAD','PER_AD','PERCENTUAL','PERC','PER']
            ],
        ],
    ],

    // ==================================================================
    // 4.8) Rep C Comissão (staging)
    // Tabela: CONSINCO.MEGAG_IMP_REPCCCOMISSAO
    // Colunas (print):
    // NROCOMISSAOEVENTO, NROREPRESENTANTE, DTALANCAMENTO, VALOR, HISTORICO,
    // DTAINCLUSAO, USUINCLUSAO, STATUS, RESIMPORTACAO, DTAIMPORTACAO
    // ==================================================================
    'repcccomissao' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_REPCCCOMISSAO',
        'start_row' => 2,
        'header_row' => 1,

        // preenche com o usuário logado
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'NROCOMISSAOEVENTO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['NROCOMISSAOEVENTO','NRO_COMISSAO_EVENTO','COMISSAOEVENTO','COMISSAO_EVENTO']
            ],
            [
                'db' => 'NROREPRESENTANTE',
                'type' => 'number',
                'required' => true,
                'aliases' => ['NROREPRESENTANTE','NRO_REPRESENTANTE','REPRESENTANTE','CODREPRESENTANTE','COD_REPRESENTANTE']
            ],
            [
                'db' => 'DTALANCAMENTO',
                'type' => 'date',
                'required' => true,
                'aliases' => ['DTALANCAMENTO','DTA_LANCAMENTO','DATA','DT']
            ],
            [
                'db' => 'VALOR',
                'type' => 'number',
                'required' => true,
                'aliases' => ['VALOR','VLR','VLRVALOR','VALOR_TOTAL','TOTAL']
            ],
            [
                'db' => 'HISTORICO',
                'type' => 'string',
                'required' => false,
                'aliases' => ['HISTORICO','HISTÓRICO','OBS','OBSERVACAO','OBSERVAÇÃO','DESCRICAO','DESCRIÇÃO']
            ],
        ],
    ],

    // ==================================================================
    // 4.9) BI Metas
    // Tabela: CONSINCO.MEGAG_IMP_BI_METAS
    // Colunas esperadas pela tela imp_bi_metas:
    // CODMETA, CODVENDEDOR, CODPERIODO, META, CODREGIAO, SEGMENTO,
    // TIPORETIRA, CATEGORIA, SEQPRODUTO, DTAATUALIZACAO
    // ==================================================================
    'bi_metas' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_BI_METAS',
        'start_row' => 2,
        'header_row' => 1,
        'upsert_keys' => ['CODMETA', 'SEQPRODUTO', 'COMPRADOR', 'CODPERIODO', 'CODEMP', 'CODSETOR'],

        // usuário logado
        'session_user_column' => null,

        'columns' => [
            [
                'db' => 'CODMETA',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODMETA','COD_META','METAID','IDMETA']
            ],
            [
                'db' => 'COMPRADOR',
                'type' => 'string',
                'required' => true,
                'aliases' => ['COMPRADOR','CODVENDEDOR','COD_VENDEDOR','VENDEDOR','CODIGO_VENDEDOR']
            ],
            [
                'db' => 'CODPERIODO',
                'type' => 'string',
                'required' => true,
                'aliases' => ['CODPERIODO','COD_PERIODO','PERIODO','IDPERIODO','PERIODOID','COOPERIODO']
            ],
            [
                'db' => 'META',
                'type' => 'number',
                'required' => true,
                'aliases' => ['META','VALORMETA','VALOR_META','VLRMETA']
            ],
            [
                'db' => 'SEQPRODUTO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO','CODPRODUTO','COD_PRODUTO']
            ],
            [
                'db' => 'CODEMP',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODEMP','CODEMPRESA','EMPRESA','COD_EMPRESA']
            ],
            [
                'db' => 'CODSETOR',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODSETOR','SETOR','COD_SETOR']
            ],
        ],
    ],

    // ==================================================================
    // 5.0) BI Metas - Perspectiva
    // Tabela: CONSINCO.MEGAG_IMP_BI_METAS_PERSPECT
    // Colunas (print):
    // CODMETA, PERSPEC, DATA, DTAINCLUSAO, USUINCLUSAO, STATUS, RESIMPORTACAO, DTAIMPORTACAO
    // ==================================================================
    'bi_metas_perspect' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_BI_METAS_PERSPECT',
        'start_row' => 2,
        'header_row' => 1,

        // usuário logado
        'session_user_column' => 'USUINCLUSAO',

        'columns' => [
            [
                'db' => 'CODMETA',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODMETA','COD_META','METAID','IDMETA']
            ],
            [
                'db' => 'PERSPEC',
                'type' => 'string',
                'required' => true,
                'aliases' => ['PERSPEC','PERSPECT','PERSPECTIVA','PERSPECTIVE']
            ],
            [
                'db' => 'DATA',
                'type' => 'date',
                'required' => true,
                'aliases' => ['DATA','DTA','DT','DATAREF','DATA_REF']
            ],
        ],
    ],

    // ==================================================================
    // 6.1) Campanhas - Cadastro Básico
    // Tabela: CONSINCO.MEGAG_CAMPFORN
    // ==================================================================
    'camp_campanha' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_CAMPFORN',
        'start_row' => 2,
        'header_row' => 1,
        'session_user_column' => 'USUINCLUSAO',
        'upsert_keys' => ['CODCAMPANHA'],
        'columns' => [
            ['db' => 'CODCAMPANHA', 'type' => 'number', 'required' => true, 'aliases' => ['CODCAMPANHA','ID','CODIGO']],
            ['db' => 'CAMPANHA', 'type' => 'string', 'required' => true, 'aliases' => ['CAMPANHA','NOME','DESCRICAO']],
            ['db' => 'DTAINICIAL', 'type' => 'date', 'required' => true, 'aliases' => ['DTAINICIAL','DATA_INICIO','INICIO','DT_INI']],
            ['db' => 'DTAFINAL', 'type' => 'date', 'required' => true, 'aliases' => ['DTAFINAL','DATA_FIM','FIM','DT_FIM']],
            ['db' => 'QTDMINMETAS', 'type' => 'number', 'required' => false, 'aliases' => ['QTDMINMETAS','MIN_METAS','QTD_MIN']],
            ['db' => 'TIPOPREMIO', 'type' => 'string', 'required' => false, 'aliases' => ['TIPOPREMIO','TIPO']],
            ['db' => 'PREMIOUNICO', 'type' => 'string', 'required' => false, 'aliases' => ['PREMIOUNICO','UNICO']],
        ],
    ],

    // ==================================================================
    // 6.2) Campanhas - Prêmios por Grupo
    // Tabela: CONSINCO.MEGAG_CAMPFORNGRPREM
    // ==================================================================
    'camp_premios' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_CAMPFORNGRPREM',
        'start_row' => 2,
        'header_row' => 1,
        'upsert_keys' => ['CODCAMPANHA', 'CODGRUPO', 'POSICAO'],
        'columns' => [
            ['db' => 'CODCAMPANHA', 'type' => 'number', 'required' => true, 'aliases' => ['CODCAMPANHA','ID_CAMPANHA']],
            ['db' => 'CODGRUPO', 'type' => 'string', 'required' => true, 'aliases' => ['CODGRUPO','GRUPO','G']],
            ['db' => 'PREMIODESC', 'type' => 'string', 'required' => true, 'aliases' => ['PREMIODESC','PREMIO','DESC_PREMIO']],
            ['db' => 'VLRPREMIO', 'type' => 'number', 'required' => true, 'aliases' => ['VLRPREMIO','VALOR','PRECO']],
            ['db' => 'POSICAO', 'type' => 'number', 'required' => true, 'aliases' => ['POSICAO','RANK','ORDEM']],
        ],
    ],

    // ==================================================================
    // 6.3) Campanhas - Metas por Representante
    // Tabela: CONSINCO.MEGAG_CAMPFORNMETAREP
    // ==================================================================
    'camp_metarep' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_CAMPFORNMETAREP',
        'start_row' => 2,
        'header_row' => 1,
        'upsert_keys' => ['CODCAMPANHA', 'CODMETA', 'CODREPRESENTANTE'],
        'columns' => [
            ['db' => 'CODCAMPANHA', 'type' => 'number', 'required' => true, 'aliases' => ['CODCAMPANHA','ID_CAMPANHA']],
            ['db' => 'CODMETA', 'type' => 'number', 'required' => true, 'aliases' => ['CODMETA','META_ID']],
            ['db' => 'CODGRUPO', 'type' => 'string', 'required' => true, 'aliases' => ['CODGRUPO','GRUPO']],
            ['db' => 'CODREPRESENTANTE', 'type' => 'number', 'required' => true, 'aliases' => ['CODREPRESENTANTE','REPRESENTANTE','RCA']],
            ['db' => 'META', 'type' => 'number', 'required' => true, 'aliases' => ['META','VALOR_META','VLR_META']],
        ],
    ],

    // ==================================================================
    // 6.4) Campanhas - Metas por Produto
    // Tabela: CONSINCO.MEGAG_CAMPFORNMETAPROD
    // ==================================================================
    'camp_metaprod' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_CAMPFORNMETAPROD',
        'start_row' => 2,
        'header_row' => 1,
        'upsert_keys' => ['CODCAMPANHA', 'CODMETA', 'CODPRODUTO'],
        'columns' => [
            ['db' => 'CODCAMPANHA', 'type' => 'number', 'required' => true, 'aliases' => ['CODCAMPANHA','ID_CAMPANHA']],
            ['db' => 'CODMETA', 'type' => 'number', 'required' => true, 'aliases' => ['CODMETA','META_ID']],
            ['db' => 'CODPRODUTO', 'type' => 'number', 'required' => true, 'aliases' => ['CODPRODUTO','PRODUTO','SEQPRODUTO']],
        ],
    ],

    // ==================================================================
    // 6.5) Campanhas - Vínculo Campanha x Meta
    // Tabela: CONSINCO.MEGAG_CAMPFORNCAMPMETA
    // ==================================================================
    'camp_campmeta' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_CAMPFORNCAMPMETA',
        'start_row' => 2,
        'header_row' => 1,
        'upsert_keys' => ['CODCAMPANHA', 'CODMETA'],
        'columns' => [
            ['db' => 'CODCAMPANHA', 'type' => 'number', 'required' => true, 'aliases' => ['CODCAMPANHA','ID_CAMPANHA']],
            ['db' => 'CODMETA', 'type' => 'number', 'required' => true, 'aliases' => ['CODMETA','META_ID']],
        ],
    ],
];

// ======================================================================
// 5.1) MAIN
// ======================================================================
try {

    if (empty($_SESSION['logado'])) {
        throw new Exception('Sessão expirada. Faça login novamente.');
    }

    $tipo = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
    if ($tipo === '' || !isset($configs[$tipo])) {
        $disp = implode(', ', array_keys($configs));
        throw new Exception("Tipo de import inválido ou não configurado. Informe ?tipo=. Disponíveis: {$disp}");
    }

    $cfg = $configs[$tipo];
    $owner = strtoupper($cfg['owner']);
    $tableBase = strtoupper($cfg['table']);
    $tableFull = "{$owner}.{$tableBase}";

    $arquivo = isset($_GET['arquivo']) ? basename((string)$_GET['arquivo']) : '';
    if ($arquivo === '') throw new Exception('Parâmetro "arquivo" não informado.');

    // ==================================================================
    // 5.1) Conexão Oracle (caminho robusto)
    // ==================================================================
    $pathConexaoCandidates = [mg_db_config_path()];

    // Dentro do projeto
    $pathConexaoCandidates[] = __DIR__ . '/../db_config/db_connect.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/db_config/db_connect.php';

    // Alternativos
    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db_connect.php';
    $pathConexaoCandidates[] = dirname(__DIR__) . '/config/db.php';

    // Fora do projeto
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $pathConexaoCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db_config/db_connect.php';
    }

    $pathConexao = null;
    foreach ($pathConexaoCandidates as $cand) {
        if (file_exists($cand)) { $pathConexao = $cand; break; }
    }
    if ($pathConexao === null) {
        throw new Exception("Arquivo de configuração de banco não encontrado. Tentei: " . implode(" | ", $pathConexaoCandidates));
    }

    require_once $pathConexao;
    if (!isset($conn) || !$conn) throw new Exception("Falha na conexão com banco.");

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

    // ==================================================================
    // 5.2) Autoload + abrir planilha
    // ==================================================================
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoload)) throw new Exception('vendor/autoload.php não encontrado. Rode composer install.');
    require_once $autoload;

    // caminho do upload (padrão do seu upload.php: /uploads na raiz do projeto)
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $arquivo;

    if (!file_exists($filePath)) {
        throw new Exception("Arquivo não encontrado em uploads: {$arquivo}");
    }

    sse_send("Tipo: {$tipo}", 'sistema');
    sse_send("Tabela destino: {$tableFull}", 'sistema');
    sse_send("Abrindo planilha: {$arquivo}", 'sistema');

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $headerRowNum = (int)($cfg['header_row'] ?? 1);
    $sheet = $spreadsheet->getActiveSheet();
    $headerMap = sheet_header_map($sheet, $headerRowNum);
    sse_send("Aba ativa inicial: " . $sheet->getTitle() . " | cabecalho lido: " . sheet_header_keys_preview($headerMap), 'sistema');

    if (!sheet_matches_required_headers($headerMap, $cfg['columns'])) {
        foreach ($spreadsheet->getWorksheetIterator() as $candidateSheet) {
            $candidateHeaderMap = sheet_header_map($candidateSheet, $headerRowNum);
            sse_send("Verificando aba: " . $candidateSheet->getTitle() . " | cabecalho: " . sheet_header_keys_preview($candidateHeaderMap), 'sistema');
            if (sheet_matches_required_headers($candidateHeaderMap, $cfg['columns'])) {
                $sheet = $candidateSheet;
                $headerMap = $candidateHeaderMap;
                sse_send("Aba selecionada automaticamente: " . $sheet->getTitle(), 'sistema');
                break;
            }
        }
    }

    $highestRow = (int)$sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    sse_send("Planilha carregada. Linhas detectadas: {$highestRow}", 'sucesso');

    // ==================================================================
    // 5.3) Lê cabeçalho e monta map
    // ==================================================================

    // monta índices das colunas
    $colIndex = [];
    foreach ($cfg['columns'] as $colCfg) {
        $dbCol = strtoupper($colCfg['db']);
        $aliases = (array)($colCfg['aliases'] ?? []);
        $required = (bool)($colCfg['required'] ?? false);

        $found = null;
        foreach ($aliases as $a) {
            $k = normalize_header((string)$a);
            if (isset($headerMap[$k])) { $found = $headerMap[$k]; break; }
        }

        if ($found === null && $required) {
            throw new Exception("Coluna obrigatória não encontrada no Excel para {$dbCol}. Verifique o cabeçalho.");
        }

        $colIndex[$dbCol] = $found; // pode ser null (opcional)
    }

    // ==================================================================
    // 5.4) Descobre colunas existentes na tabela Oracle
    // ==================================================================
    $stmtCols = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
    ");
    $stmtCols->execute([
        ':own' => $owner,
        ':tab' => $tableBase
    ]);
    $dbCols = array_map('strtoupper', $stmtCols->fetchAll(PDO::FETCH_COLUMN));

    if (!$dbCols) {
        throw new Exception("Não encontrei colunas no ALL_TAB_COLUMNS para {$tableFull}. Confira OWNER/TABLE.");
    }

    $has = function(string $c) use ($dbCols): bool {
        return in_array(strtoupper($c), $dbCols, true);
    };

    // valida se todas as colunas DB do config existem
    foreach ($cfg['columns'] as $colCfg) {
        $dbCol = strtoupper($colCfg['db']);
        if (!$has($dbCol)) {
            throw new Exception("A tabela {$tableFull} não possui a coluna configurada: {$dbCol}");
        }
    }

    // ==================================================================
    // 5.5) Monta INSERT dinâmico (somente colunas existentes)
    // ==================================================================
    $insertCols = [];
    $insertVals = [];
    $bindTypes  = []; // controla conversão
    $bindFromExcel = []; // dbCol => excelIndex
    $bindIsRawDate = []; // date/datetime

    foreach ($cfg['columns'] as $colCfg) {
        $dbCol = strtoupper($colCfg['db']);
        $type = strtolower((string)($colCfg['type'] ?? 'string'));
        $excelIdx = $colIndex[$dbCol] ?? null;

        // se for opcional e não existe no excel, a gente simplesmente insere NULL (bind normal)
        $insertCols[] = $dbCol;

        if ($type === 'date') {
            $insertVals[] = "TO_DATE(:{$dbCol}, 'YYYY-MM-DD')";
            $bindIsRawDate[$dbCol] = 'date';
        } elseif ($type === 'datetime') {
            $insertVals[] = "TO_DATE(:{$dbCol}, 'YYYY-MM-DD HH24:MI:SS')";
            $bindIsRawDate[$dbCol] = 'datetime';
        } else {
            $insertVals[] = ":{$dbCol}";
        }

        $bindTypes[$dbCol] = $type;
        $bindFromExcel[$dbCol] = $excelIdx; // pode ser null
    }

    // padrões staging automáticos (se existirem na tabela)
    if ($has('STATUS') && !in_array('STATUS', $insertCols, true)) {
        $insertCols[] = 'STATUS';
        $insertVals[] = "'P'";
    }
    if ($has('RESIMPORTACAO') && !in_array('RESIMPORTACAO', $insertCols, true)) {
        $insertCols[] = 'RESIMPORTACAO';
        $insertVals[] = "NULL";
    }
    if ($has('RESIMPOTACAO') && !in_array('RESIMPOTACAO', $insertCols, true)) {
        $insertCols[] = 'RESIMPOTACAO';
        $insertVals[] = "NULL";
    }
    if ($has('DTAINCLUSAO') && !in_array('DTAINCLUSAO', $insertCols, true)) {
        $insertCols[] = 'DTAINCLUSAO';
        $insertVals[] = "SYSDATE";
    }

    $sessUserCol = $cfg['session_user_column'] ?? null;
    if ($sessUserCol) {
        $sessUserCol = strtoupper((string)$sessUserCol);
        if ($has($sessUserCol) && !in_array($sessUserCol, $insertCols, true)) {
            $insertCols[] = $sessUserCol;
            $insertVals[] = ":{$sessUserCol}";
        }
    }

    $sqlInsert = "INSERT INTO {$tableFull} (" . implode(", ", $insertCols) . ") VALUES (" . implode(", ", $insertVals) . ")";
    $stmtInsert = $conn->prepare($sqlInsert);

    $upsertKeys = array_map('strtoupper', (array)($cfg['upsert_keys'] ?? []));
    $stmtWrite = $stmtInsert;
    $writeVerb = 'Inserindo';

    if ($upsertKeys) {
        foreach ($upsertKeys as $keyCol) {
            if (!in_array($keyCol, $insertCols, true)) {
                throw new Exception("Chave de upsert nÃ£o encontrada nas colunas configuradas: {$keyCol}");
            }
        }

        $sourceCols = [];
        foreach ($insertCols as $idx => $dbCol) {
            $expr = $insertVals[$idx];
            $sourceCols[] = "{$expr} AS {$dbCol}";
        }

        $onParts = [];
        foreach ($upsertKeys as $keyCol) {
            $onParts[] = "t.{$keyCol} = src.{$keyCol}";
        }

        $updateParts = [];
        foreach ($insertCols as $dbCol) {
            if (in_array($dbCol, $upsertKeys, true)) {
                continue;
            }
            if ($dbCol === 'DTAINCLUSAO') {
                continue;
            }
            $updateParts[] = "t.{$dbCol} = src.{$dbCol}";
        }

        $sqlMerge = "MERGE INTO {$tableFull} t
            USING (
                SELECT " . implode(", ", $sourceCols) . "
                FROM dual
            ) src
            ON (" . implode(' AND ', $onParts) . ")";

        if ($updateParts) {
            $sqlMerge .= "
            WHEN MATCHED THEN UPDATE SET " . implode(", ", $updateParts);
        }

        $sqlMerge .= "
            WHEN NOT MATCHED THEN
                INSERT (" . implode(", ", $insertCols) . ")
                VALUES (" . implode(", ", array_map(static fn($c) => "src.{$c}", $insertCols)) . ")";

        $stmtWrite = $conn->prepare($sqlMerge);
        $writeVerb = 'Gravando';
    }

    // ==================================================================
    // 5.6) Processa linhas
    // ==================================================================
    $startRow = (int)($cfg['start_row'] ?? 2);
    sse_send("Cabeçalho OK. Processando linhas ({$startRow}..{$highestRow})", 'sistema');

    $ok = 0;
    $fail = 0;
    $conn->beginTransaction();

    for ($r = $startRow; $r <= $highestRow; $r++) {
        if ($r % 500 === 0) {
            sse_send("{$writeVerb} linha {$r} de {$highestRow}...", 'sistema');
        }

        $rowArr = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, true, false)[0] ?? [];
        $allNull = true;
        foreach ($rowArr as $v) {
            if ($v !== null && trim((string)$v) !== '') { $allNull = false; break; }
        }
        if ($allNull) continue;

        $params = [];
        foreach ($bindFromExcel as $dbCol => $excelIdx) {
            // Verifica se existe um valor fixo via GET (ex: ?fixed_CODCAMPANHA=123)
            $fixedKey = "fixed_" . $dbCol;
            if (isset($_GET[$fixedKey])) {
                $raw = $_GET[$fixedKey];
            } else {
                $raw = ($excelIdx === null) ? null : ($rowArr[$excelIdx] ?? null);
            }
            
            $type = $bindTypes[$dbCol] ?? 'string';

            if ($type === 'number') {
                $params[":{$dbCol}"] = to_number($raw);
            } elseif ($type === 'date') {
                $params[":{$dbCol}"] = to_oracle_date_str($raw);
            } elseif ($type === 'datetime') {
                $params[":{$dbCol}"] = to_oracle_datetime_str($raw);
            } else {
                $params[":{$dbCol}"] = to_string($raw);
            }
        }

        if ($sessUserCol && in_array($sessUserCol, $insertCols, true)) {
            $params[":{$sessUserCol}"] = $usuarioLogado;
        }

        $invalid = false;
        $missingCol = '';
        foreach ($cfg['columns'] as $colCfg) {
            $dbCol = strtoupper($colCfg['db']);
            if (!($colCfg['required'] ?? false)) continue;
            if (($params[":{$dbCol}"] ?? null) === null) {
                $invalid = true;
                $missingCol = $dbCol;
                break;
            }
        }

        if ($invalid) {
            $fail++;
            if ($fail <= 50) sse_send("Linha {$r}: coluna '{$missingCol}' vazia.", 'aviso');
            continue;
        }

        try {
            $stmtWrite->execute($params);
            $ok++;
        } catch (Exception $e) {
            $fail++;
            sse_send("Linha {$r}: erro -> " . $e->getMessage(), 'erro');
        }
    }

    $conn->commit();
    sse_send("IMPORTAÇÃO FINALIZADA! Sucessos: {$ok} | Falhas: {$fail}", $fail ? 'aviso' : 'sucesso');

    try {
        sse_send("Executando rotina pós-importação...", 'sistema');
        $stmtFn = $conn->prepare("SELECT consinco.megag_fn_tabs_importacao_sqlexec(:p_table) AS RET FROM dual");
        $stmtFn->execute([':p_table' => strtolower($tableBase)]);
        sse_send("Rotina finalizada.", 'sucesso');
    } catch (Exception $e) {
        sse_send("Erro na rotina pós-importação: " . $e->getMessage(), 'erro');
    }

    sse_send("Processo finalizado.", 'sucesso');
    sse_close();

} catch (Exception $e) {
    sse_send("ERRO CRÍTICO: " . $e->getMessage(), 'erro');
    sse_close();
}
