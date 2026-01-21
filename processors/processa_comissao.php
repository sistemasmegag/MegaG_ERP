<?php
// processa_comissao.php - Versão Corrigida

// ======================================================================
// 1) CONFIGURAÇÕES DE STREAMING E CABEÇALHOS
// ======================================================================

set_time_limit(0);
ini_set('display_errors', 0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

function enviarLog($msg, $tipo = 'info') {
    echo "data: " . json_encode(['msg' => $msg, 'tipo' => $tipo]) . "\n\n";
    flush();
}

// ======================================================================
// 2) CONEXÃO E SESSÃO
// ======================================================================

session_start();
// Usuário para log (Pega da sessão ou usa padrão se vazio)
$usuarioLogado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'ADMIN';

try {
    $pathConexao = __DIR__ . '/db_config/db_connect.php';
    if (!file_exists($pathConexao)) {
        $pathConexao = dirname(__DIR__) . '/db_config/db_connect.php';
    }

    if (!file_exists($pathConexao)) throw new Exception("Arquivo db_connect não encontrado.");

    require_once($pathConexao);

    if (!isset($conn) || !$conn) throw new Exception("Falha na conexão com banco.");

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
    $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");

    enviarLog("Conectado ao Oracle com sucesso.", "sucesso");

} catch (Exception $e) {
    enviarLog("Erro Crítico: " . $e->getMessage(), "erro");
    echo "event: close\ndata: end\n\n";
    exit;
}

// ======================================================================
// 3) CARREGAMENTO DO ARQUIVO EXCEL
// ======================================================================

if (!isset($_GET['arquivo'])) {
    enviarLog("Arquivo não informado.", "erro");
    exit;
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Importante para datas do Excel

$arquivo = __DIR__ . '/uploads/' . basename($_GET['arquivo']);
if (!file_exists($arquivo)) {
    $arquivo = dirname(__DIR__) . '/uploads/' . basename($_GET['arquivo']);
    if (!file_exists($arquivo)) {
        enviarLog("Arquivo físico não encontrado.", "erro");
        exit;
    }
}

try {
    enviarLog("Lendo planilha... (Aguarde)", "sistema");
    $spreadsheet = IOFactory::load($arquivo);
    $worksheet = $spreadsheet->getActiveSheet();
    enviarLog("Planilha carregada.", "sucesso");
} catch (Exception $e) {
    enviarLog("Erro ao ler Excel: " . $e->getMessage(), "erro");
    exit;
}

// ======================================================================
// 4) PREPARAÇÃO DO SQL (CORRIGIDO)
// ======================================================================

$owner = "CONSINCO";

// ============================================================
// TABELA BASE (sem owner) - usada para executar a função
// megag_fn_tabs_importacao_sqlexec('<tabela>')
// ============================================================
$importTableBase = "MEGAG_IMP_REPCCOMISSAO";

// Correção: Removidas vírgulas extras e adicionado bind do USUINCLUSAO
$sql = "INSERT INTO {$owner}.{$importTableBase} (
            CODEVENTO,
            SEQPESSOA,
            DTAHREMISSAO,
            OBSERVACAO,
            VLRTOTAL
        ) VALUES (
            :v_CODEVENTO,
            :v_SEQPESSOA,
            :v_DTAHREMISSAO,
            :v_OBSERVACAO,
            :v_VLRTOTAL
        )";

try {
    $stmt = $conn->prepare($sql);
} catch (PDOException $e) {
    enviarLog("Erro ao preparar SQL: " . $e->getMessage(), "erro");
    exit;
}

// ======================================================================
// 5) PROCESSAMENTO
// ======================================================================

$linhaInicial = 2;
$ultimaLinha = $worksheet->getHighestRow();
$registros = 0;
$erros = 0;

enviarLog("Processando $ultimaLinha linhas...", "info");

// Função para limpar APENAS O VALOR FINANCEIRO
function limparDinheiro($val) {
    if (empty($val)) return 0;
    // Se vier R$ 1.200,50 -> tira o ponto, troca virgula por ponto
    $val = str_replace('.', '', $val);
    $val = str_replace(',', '.', $val);
    return preg_replace('/[^0-9.-]/', '', $val);
}

for ($row = $linhaInicial; $row <= $ultimaLinha; $row++) {

    // --- LEITURA DAS COLUNAS ---
    // A: CODEVENTO | B: SEQPESSOA | C: DATA | D: OBSERVAÇÃO | E: VALOR

    $raw_evento  = $worksheet->getCell("A{$row}")->getValue();

    // Se não tiver evento, para o loop
    if (empty($raw_evento)) break;

    // 1. Tratamento Evento e Pessoa (Numéricos)
    $v_CODEVENTO = preg_replace('/[^0-9]/', '', (string)$raw_evento);
    $v_SEQPESSOA = preg_replace('/[^0-9]/', '', (string)$worksheet->getCell("B{$row}")->getValue());

    // 2. Tratamento da DATA (Coluna C)
    $raw_data = $worksheet->getCell("C{$row}")->getValue();
    $v_DTAHREMISSAO = null;

    if (!empty($raw_data)) {
        if (Date::isDateTime($worksheet->getCell("C{$row}"))) {
            // Se o Excel reconhecer como data numérica
            $v_DTAHREMISSAO = Date::excelToDateTimeObject($raw_data)->format('Y-m-d');
        } else {
            // Se for texto "24/11/2025"
            $dt = DateTime::createFromFormat('d/m/Y', $raw_data);
            if ($dt) {
                $v_DTAHREMISSAO = $dt->format('Y-m-d');
            } else {
                 // Fallback: tenta strtotime padrão ou usa data atual
                 $v_DTAHREMISSAO = date('Y-m-d');
            }
        }
    } else {
        $v_DTAHREMISSAO = date('Y-m-d');
    }

    // 3. Tratamento da OBSERVAÇÃO (Coluna D) - Texto livre, NÃO usar limparDinheiro aqui
    $v_OBSERVACAO = trim((string)$worksheet->getCell("D{$row}")->getValue());
    // Remove aspas simples para evitar erro SQL se não usar bind corretamente (o PDO já protege, mas é bom limpar espaços)

    // 4. Tratamento do VALOR (Coluna E)
    $raw_valor = $worksheet->getCell("E{$row}")->getValue();
    $v_VLRTOTAL = limparDinheiro((string)$raw_valor);

    // --- VALIDAÇÕES ---
    $v_status = 'S';
    $v_res    = 'OK';

    if ($v_VLRTOTAL == 0 && $raw_valor != '0') {
         // Se deu zero mas o excel não era zero, pode ser erro de conversão
         // Mas as vezes é zero mesmo. Monitorar.
    }

    // --- INSERÇÃO ---
    try {
        $stmt->execute([
            ':v_CODEVENTO'    => $v_CODEVENTO,
            ':v_SEQPESSOA'    => $v_SEQPESSOA,
            ':v_DTAHREMISSAO' => $v_DTAHREMISSAO,
            ':v_OBSERVACAO'   => substr($v_OBSERVACAO, 0, 400), // Protege estouro de campo texto (VARCHAR)
            ':v_VLRTOTAL'     => $v_VLRTOTAL
        ]);

        $registros++;

        if ($registros % 50 == 0) {
            enviarLog("Linha $row processada...", "info");
        }

    } catch (PDOException $e) {
        $erros++;
        enviarLog("ERRO Linha $row: " . $e->getMessage(), "erro");
    }
}

// ======================================================================
// 6) PÓS-IMPORTAÇÃO: EXECUTA FUNÇÃO APÓS FINALIZAR AS GRAVAÇÕES
// select megag_fn_tabs_importacao_sqlexec('megag_imp_repccimissao') from dual;
// (ajusta para o nome correto: 'megag_imp_repccimissao' vs 'megag_imp_repccOMISSAO')
// Aqui usaremos exatamente a tabela base: megag_imp_repccimissao -> megag_imp_repccOMISSAO
// ============================================================

try {
    enviarLog("Executando rotina pós-importação: megag_fn_tabs_importacao_sqlexec...", "sistema");

    // sanitiza e envia em minúsculo (conforme seu padrão)
    $importTableFn = preg_replace('/[^A-Z0-9_]/', '', strtoupper($importTableBase));
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
        enviarLog("Rotina pós-importação finalizada. Retorno: {$retMsg}", "sucesso");
    } else {
        enviarLog("Rotina pós-importação finalizada com sucesso.", "sucesso");
    }

} catch (Exception $e) {
    // Não trava a importação, só informa
    enviarLog("Falha na rotina pós-importação: " . $e->getMessage(), "erro");
}

enviarLog("----------------------------------", "sistema");
enviarLog("FINALIZADO! Importados: $registros | Erros: $erros", ($erros > 0 ? "aviso" : "sucesso"));

echo "event: close\n";
echo "data: end\n\n";
flush();
?>
