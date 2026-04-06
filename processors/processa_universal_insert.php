<?php
// processors/processa_universal_insert.php
// Processador Universal (SSE) - INSERT puro + pós-import (megag_fn_tabs_importacao_sqlexec)
// Uso: processors/processa_universal_insert.php?tipo=lanctocomissao&arquivo=xxxx.xlsx

// ======================================================================
// 0) SSE HEADERS + Flush
// ======================================================================
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
// 1) DEBUG PHP (se quiser desligar depois)
// ======================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        'd/m/Y',
        'd/m/y',
        'Y-m-d',
        'd-m-Y',
        'd-m-y',
        'Y/m/d',
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
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

    $ts = strtotime(str_replace('/', '-', $txt));
    if ($ts !== false) return date('Y-m-d H:i:s', $ts);

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

function to_string($value): ?string {
    if ($value === null) return null;
    $s = trim((string)$value);
    return $s === '' ? null : $s;
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
                'required' => false,
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
                'required' => false,
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
    // Colunas (print):
    // CODMETA, SEQPRODUTO, COMPRADOR, CODPERIODO, META, CODEMPRESA, CODSETOR,
    // DTAINCLUSAO, USUINCLUSAO, STATUS, RESIMPORTACAO, DTAIMPORTACAO
    // ==================================================================
    'bi_metas' => [
        'owner' => 'CONSINCO',
        'table' => 'MEGAG_IMP_BI_METAS',
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
                'db' => 'SEQPRODUTO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['SEQPRODUTO','SEQ_PRODUTO','PRODUTO','CODPRODUTO','COD_PRODUTO']
            ],
            [
                'db' => 'COMPRADOR',
                'type' => 'string',
                'required' => true,
                'aliases' => ['COMPRADOR','BUYER','USUARIOCOMPRADOR','USUARIO_COMPRADOR']
            ],
            [
                'db' => 'CODPERIODO',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODPERIODO','COD_PERIODO','PERIODO','IDPERIODO','PERIODOID']
            ],
            [
                'db' => 'META',
                'type' => 'number',
                'required' => true,
                'aliases' => ['META','VALORMETA','VALOR_META','VLRMETA']
            ],
            [
                'db' => 'CODEMPRESA',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODEMPRESA','COD_EMPRESA','EMPRESA','IDEMPRESA']
            ],
            [
                'db' => 'CODSETOR',
                'type' => 'number',
                'required' => true,
                'aliases' => ['CODSETOR','COD_SETOR','SETOR','IDSETOR']
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
    $pathConexaoCandidates = [];

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
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = (int)$sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    sse_send("Planilha carregada. Linhas detectadas: {$highestRow}", 'sucesso');

    // ==================================================================
    // 5.3) Lê cabeçalho e monta map
    // ==================================================================
    $headerRowNum = (int)($cfg['header_row'] ?? 1);
    $headerRow = $sheet->rangeToArray("A{$headerRowNum}:{$highestCol}{$headerRowNum}", null, true, false)[0] ?? [];

    $headerMap = [];
    foreach ($headerRow as $idx => $name) {
        $key = normalize_header((string)$name);
        if ($key !== '') $headerMap[$key] = $idx;
    }

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
            // TO_DATE(:COL, 'YYYY-MM-DD')
            $insertVals[] = "TO_DATE(:{$dbCol}, 'YYYY-MM-DD')";
            $bindIsRawDate[$dbCol] = 'date';
        } elseif ($type === 'datetime') {
            // TO_DATE(:COL, 'YYYY-MM-DD HH24:MI:SS')
            $insertVals[] = "TO_DATE(:{$dbCol}, 'YYYY-MM-DD HH24:MI:SS')";
            $bindIsRawDate[$dbCol] = 'datetime';
        } else {
            $insertVals[] = ":{$dbCol}";
        }

        $bindTypes[$dbCol] = $type;
        $bindFromExcel[$dbCol] = $excelIdx; // pode ser null
    }

    // padrões staging automáticos (se existirem na tabela)
    // STATUS default 'P'
    if ($has('STATUS') && !in_array('STATUS', $insertCols, true)) {
        $insertCols[] = 'STATUS';
        $insertVals[] = "'P'";
    }

    // RESIMPORTACAO default NULL (conforme sua tabela)
    if ($has('RESIMPORTACAO') && !in_array('RESIMPORTACAO', $insertCols, true)) {
        $insertCols[] = 'RESIMPORTACAO';
        $insertVals[] = "NULL";
    }

    // alguns ambientes podem ter RESIMPOTACAO (typo histórico) em outras tabelas
    if ($has('RESIMPOTACAO') && !in_array('RESIMPOTACAO', $insertCols, true)) {
        $insertCols[] = 'RESIMPOTACAO';
        $insertVals[] = "NULL";
    }

    // DTAINCLUSAO default SYSDATE
    if ($has('DTAINCLUSAO') && !in_array('DTAINCLUSAO', $insertCols, true)) {
        $insertCols[] = 'DTAINCLUSAO';
        $insertVals[] = "SYSDATE";
    }

    // USUINCLUSAO (ou outro) vindo da sessão, se a config pediu e a coluna existe
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

    // ==================================================================
    // 5.6) Processa linhas (INSERT puro)
    // ==================================================================
    $startRow = (int)($cfg['start_row'] ?? 2);
    sse_send("Cabeçalho OK. Processando linhas ({$startRow}..{$highestRow})", 'sistema');

    $ok = 0;
    $fail = 0;

    $conn->beginTransaction();

    for ($r = $startRow; $r <= $highestRow; $r++) {

        $rowArr = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, true, false)[0] ?? [];

        // detecta linha vazia "total"
        $allNull = true;
        foreach ($rowArr as $v) {
            if ($v !== null && trim((string)$v) !== '') { $allNull = false; break; }
        }
        if ($allNull) continue;

        $params = [];

        // monta binds para colunas vindas do excel
        foreach ($bindFromExcel as $dbCol => $excelIdx) {

            $raw = ($excelIdx === null) ? null : ($rowArr[$excelIdx] ?? null);
            $type = $bindTypes[$dbCol] ?? 'string';

            if ($type === 'number') {
                $val = to_number($raw);
                $params[":{$dbCol}"] = $val;
            } elseif ($type === 'date') {
                $val = to_oracle_date_str($raw);
                $params[":{$dbCol}"] = $val;
            } elseif ($type === 'datetime') {
                $val = to_oracle_datetime_str($raw);
                $params[":{$dbCol}"] = $val;
            } else {
                $val = to_string($raw);
                $params[":{$dbCol}"] = $val;
            }
        }

        // injeta usuário logado se a coluna estiver no INSERT
        if ($sessUserCol && in_array($sessUserCol, $insertCols, true)) {
            $params[":{$sessUserCol}"] = $usuarioLogado;
        }

        // valida obrigatórios configurados (se required=true)
        $invalid = false;
        foreach ($cfg['columns'] as $colCfg) {
            $dbCol = strtoupper($colCfg['db']);
            $required = (bool)($colCfg['required'] ?? false);
            if (!$required) continue;

            $type = strtolower((string)($colCfg['type'] ?? 'string'));
            $v = $params[":{$dbCol}"] ?? null;

            // regra simples:
            // - string: não pode ser null/'' (a to_string já vira null)
            // - number: não pode ser null
            // - date/datetime: não pode ser null
            if ($type === 'string') {
                if ($v === null) { $invalid = true; break; }
            } else {
                if ($v === null) { $invalid = true; break; }
            }
        }

        if ($invalid) {
            $fail++;
            sse_send("Linha {$r}: campos obrigatórios inválidos (verifique colunas requeridas).", 'aviso');
            continue;
        }

        try {
            $stmtInsert->execute($params);
            $ok++;

            if ($ok % 50 === 0) {
                sse_send("Processadas {$ok} linhas com sucesso...", 'sistema');
            }

        } catch (Exception $e) {
            $fail++;
            sse_send("Linha {$r}: erro ao gravar -> " . $e->getMessage(), 'erro');
        }
    }

    // commit do staging
    $conn->commit();

    sse_send("----------------------------------", "info");
    sse_send("IMPORTAÇÃO FINALIZADA (staging)!", $fail ? 'aviso' : 'sucesso');
    sse_send("Sucessos: {$ok} | Falhas: {$fail}", $fail ? 'aviso' : 'sucesso');

    // ==================================================================
    // 5.7) Pós-import: sempre executa megag_fn_tabs_importacao_sqlexec
    // ==================================================================
    try {
        sse_send("Executando rotina pós-importação: consinco.megag_fn_tabs_importacao_sqlexec...", 'sistema');

        // Função recebe nome da tabela em minúsculo (como você já usa)
        $importTableFnLower = strtolower($tableBase);

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
        sse_send("Falha na rotina pós-importação: " . $e->getMessage(), 'erro');
    }

    sse_send("Processo finalizado pelo servidor.", 'sucesso');
    sse_close();

} catch (Exception $e) {
    sse_send("ERRO CRÍTICO: " . $e->getMessage(), 'erro');
    sse_close();
}
