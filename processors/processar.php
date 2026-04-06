<?php

// Configurações para STREAM (Não faça cache e envie linha a linha)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Desabilita buffer do PHP/Apache para enviar output instantâneo
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// Função auxiliar para enviar mensagem pro JS
function enviarLog($msg, $tipo = 'info') {
    echo "data: " . json_encode(['msg' => $msg, 'tipo' => $tipo]) . "\n\n";
    flush(); // Força o envio
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ======================================================================
// 0) SESSÃO (para capturar o usuário logado)
// ======================================================================
session_start();

// Usuário logado que fez a importação (fallback seguro)
$usuarioLogado = $_SESSION['usuario'] ?? 'SYSTEM';

// ===============================
// CONEXÃO ORACLE (caminho robusto)
// ===============================
$pathConexaoCandidates = [];

// 1) Dentro do projeto (padrão atual)
$pathConexaoCandidates[] = __DIR__ . '/../db_config/db_connect.php';
$pathConexaoCandidates[] = dirname(__DIR__) . '/db_config/db_connect.php';

// 2) Se existir algum config alternativo
$pathConexaoCandidates[] = dirname(__DIR__) . '/config/db_connect.php';
$pathConexaoCandidates[] = dirname(__DIR__) . '/config/db.php';

// 3) Se você tiver db_config fora do projeto
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $pathConexaoCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db_config/db_connect.php';
}

$pathConexao = null;
foreach ($pathConexaoCandidates as $cand) {
    if (file_exists($cand)) { $pathConexao = $cand; break; }
}

if ($pathConexao === null) {
    throw new Exception(
        "Arquivo de configuração de banco não encontrado. Tentei: " . implode(" | ", $pathConexaoCandidates)
    );
}

require_once $pathConexao;

if (!isset($conn) || !$conn) {
    throw new Exception("Falha na conexão com banco.");
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
$conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

// ======================================================================
// 2) VERIFICA UPLOAD
// ======================================================================

if (!isset($_GET['arquivo'])) {
    file_put_contents("log_processar.txt", "ERRO UPLOAD: Nenhum arquivo GET informado\n", FILE_APPEND);
    enviarLog("Arquivo não informado.", "erro");
    die("Nenhum arquivo enviado na requisição.");
}

file_put_contents("log_processar.txt", "INICIANDO SCRIPT PARA ARQUIVO: " . $_GET['arquivo'] . "\n", FILE_APPEND);

// ======================================================================
// 2.1) DEFINIÇÃO DA TABELA (IMPORTANTE PARA CHAMAR A FUNÇÃO AO FINAL)
//     -> aqui você define qual tabela este processador alimenta.
//     -> nos outros "processa_*" você vai mudar só esse valor.
// ======================================================================
$importTable = 'megag_imp_setormetacapac'; // <--- TABELA DE DESTINO DESTE PROCESSO

// ======================================================================
// 3) CARREGA EXCEL
// ======================================================================

require_once dirname(__DIR__) . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

file_put_contents("log_processar.txt", "LENDO EXCEL...\n", FILE_APPEND);
enviarLog("Lendo planilha... (Aguarde)", "sistema");

// Melhor usar DIRECTORY_SEPARATOR para evitar erros entre Windows/Linux
$arquivo_tmp = dirname(__DIR__) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $_GET['arquivo'];

file_put_contents("log_processar.txt", "CAMINHO: $arquivo_tmp\n", FILE_APPEND);

if (!file_exists($arquivo_tmp)) {
    die("Arquivo não encontrado no servidor: " . $arquivo_tmp);
}

try {
    $spreadsheet = IOFactory::load($arquivo_tmp);
    $worksheet = $spreadsheet->getActiveSheet();
    enviarLog("Planilha carregada. Iniciando processamento.", "sucesso");
} catch (Exception $e) {
    file_put_contents("log_processar.txt", "ERRO LEITURA EXCEL: ".$e->getMessage()."\n", FILE_APPEND);
    enviarLog("Erro Excel: " . $e->getMessage(), "erro");
    die("Erro lendo Excel");
}

file_put_contents("log_processar.txt", "EXCEL LIDO COM SUCESSO\n", FILE_APPEND);

// ======================================================================
// 4) PREPARE INSERT (ANTES DO LOOP)
// ======================================================================

// Utilizando a constante DB_SCHEMA conforme seu padrão
$sql = "INSERT INTO ".DB_SCHEMA.".$importTable (
            seqsetor,
            turno,
            dta,
            peso_meta,
            peso_capac,
            usuinclusao
        ) VALUES (
            :v_seqsetor,
            :v_turno,
            TO_DATE(:v_dta, 'YYYY-MM-DD'),
            :v_peso_meta,
            :v_peso_capac,
            :v_usuinclusao
        )";

try {
    $stmt = $conn->prepare($sql);
} catch (PDOException $e) {
    die("Erro ao preparar SQL: " . $e->getMessage());
}

// ======================================================================
// 5) FUNÇÃO DE LIMPEZA (CRUCIAL PARA CORRIGIR ORA-01722)
// ======================================================================
function limparNumero($val) {
    if ($val === null || $val === '') return null;
    $val = (string)$val;
    $val = trim($val);
    if (strpos($val, ',') !== false) {
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);
    }
    $val = preg_replace('/[^0-9.-]/', '', $val);
    if ($val === '' || $val === '.') return null;
    return $val;
}

// ======================================================================
// 6) PROCESSA LINHAS
// ======================================================================

$linhaInicial = 2;
$ultimaLinha = $worksheet->getHighestRow();

file_put_contents("log_processar.txt", "ULTIMA LINHA DETECTADA: $ultimaLinha\n", FILE_APPEND);
enviarLog("Processando $ultimaLinha linhas...", "info");

$registros = 0;
$erros = 0;

// Iniciamos transação para garantir integridade (opcional, mas recomendado)
$conn->beginTransaction();

for ($row = $linhaInicial; $row <= $ultimaLinha; $row++) {

    // Lê os dados da linha
    // Aplicamos limparNumero no seqsetor também para garantir que é numérico
    $v_seqsetor_raw = $worksheet->getCell("A{$row}")->getValue();
    $v_seqsetor     = limparNumero($v_seqsetor_raw);

    // TRAVA DE SEGURANÇA: Se a coluna A (seqsetor) estiver vazia, para o loop.
    if (empty($v_seqsetor)) {
        break;
    }

    $v_turno       = trim((string)$worksheet->getCell("B{$row}")->getValue());
    $v_dta_raw     = $worksheet->getCell("C{$row}")->getValue();

    // Aplicamos a função de limpeza robusta aqui para evitar ORA-01722
    $v_peso_meta   = limparNumero($worksheet->getCell("D{$row}")->getValue());
    $v_peso_capac  = limparNumero($worksheet->getCell("E{$row}")->getValue());

    // ==================================================================
    // AJUSTE: USUINCLUSAO deve ser o usuário logado que fez a importação
    // (não vem mais do Excel / Coluna F)
    // ==================================================================
    $v_usuinclusao = $usuarioLogado;

    file_put_contents("log_processar.txt", "PROCESSANDO LINHA $row\n", FILE_APPEND);

    // Tratamento de Data
    if (is_numeric($v_dta_raw)) {
        $v_dta = date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($v_dta_raw));
    } else {
        // Se estiver vazio, usa data atual ou tenta converter string
        $v_dta = !empty($v_dta_raw) ? date('Y-m-d', strtotime(str_replace('/', '-', $v_dta_raw))) : date('Y-m-d');
    }

    try {
        // Executa o Insert passando os valores
        $stmt->execute([
            ':v_seqsetor'    => $v_seqsetor,
            ':v_turno'       => $v_turno,
            ':v_dta'         => $v_dta,
            ':v_peso_meta'   => $v_peso_meta,
            ':v_peso_capac'  => $v_peso_capac,
            ':v_usuinclusao' => $v_usuinclusao
        ]);

        $registros++;

        // Atualiza o front a cada 50 linhas
        if ($registros % 50 == 0) {
            enviarLog("Linha $row processada...", "info");
        }

    } catch (PDOException $e) {
        $erros++;
        // Loga o erro mas não para o script
        $erroMsg = "ERRO INSERT LINHA $row: ".$e->getMessage();
        file_put_contents("log_processar.txt", $erroMsg."\n", FILE_APPEND);
        enviarLog("Erro Linha $row: verifique o log.", "erro");
    }
}

// ======================================================================
// 7) COMMIT + EXECUÇÃO DA FUNÇÃO PÓS-IMPORTAÇÃO
// ======================================================================

try {
    // Confirma as alterações no banco
    $conn->commit();

    file_put_contents("log_processar.txt", "IMPORTACAO FINALIZADA. TOTAL: $registros\n", FILE_APPEND);

    enviarLog("----------------------------------", "info");
    enviarLog("IMPORTAÇÃO FINALIZADA!", "sucesso");
    enviarLog("Sucessos: $registros | Falhas: $erros", ($erros > 0 ? "aviso" : "sucesso"));

    // ============================
    // EXECUTA FUNÇÃO APÓS IMPORTAR
    // select megag_fn_tabs_importacao_sqlexec('megag_imp_setormetacapac') from dual;
    // ============================

    enviarLog("Executando rotina pós-importação (consinco.megag_fn_tabs_importacao_sqlexec)...", "sistema");

    // Segurança: garante nome de tabela "limpo" para evitar qualquer injection acidental
    $importTableClean = preg_replace('/[^a-zA-Z0-9_]/', '', $importTable);

    $sqlFn = "SELECT consinco.megag_fn_tabs_importacao_sqlexec(:p_table) AS RET FROM dual";
    $stmtFn = $conn->prepare($sqlFn);
    $stmtFn->execute([':p_table' => $importTableClean]);

    // Tenta ler retorno (se a função retornar algo)
    $ret = $stmtFn->fetch(PDO::FETCH_ASSOC);
    $retMsg = '';

    if (is_array($ret)) {
        // pode vir como RET, ou índice 0, depende do driver/config
        if (isset($ret['RET'])) $retMsg = (string)$ret['RET'];
        elseif (isset($ret['ret'])) $retMsg = (string)$ret['ret'];
        else {
            $first = array_values($ret);
            $retMsg = isset($first[0]) ? (string)$first[0] : '';
        }
    }

    file_put_contents("log_processar.txt", "POS-IMPORT FUNCAO OK: ".$importTableClean." RET: ".$retMsg."\n", FILE_APPEND);

    if ($retMsg !== '') {
        enviarLog("Rotina pós-importação finalizada. Retorno: ".$retMsg, "sucesso");
    } else {
        enviarLog("Rotina pós-importação finalizada com sucesso.", "sucesso");
    }

} catch (Exception $e) {

    // Se qualquer coisa falhar aqui, tenta rollback (se ainda houver transação ativa)
    try {
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
    } catch (Exception $e2) {
        // não interrompe; só loga
        file_put_contents("log_processar.txt", "ERRO ROLLBACK: ".$e2->getMessage()."\n", FILE_APPEND);
    }

    $msg = "ERRO POS-IMPORT / COMMIT: ".$e->getMessage();
    file_put_contents("log_processar.txt", $msg."\n", FILE_APPEND);
    enviarLog($msg, "erro");
}

// ======================================================================
// 8) FECHA SSE
// ======================================================================

// Manda comando pro JS fechar a conexão (SSE)
echo "event: close\n";
echo "data: end\n\n";
flush();

?>
