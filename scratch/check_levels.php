<?php
require_once __DIR__ . '/../bootstrap/db.php';
require_once __DIR__ . '/../api/mg_api_bootstrap.php';
$pathConexao = mg_db_config_path();
require_once $pathConexao;

try {
    $cc_almox = 1040125004;
    
    echo "Pesquisando niveis de aprovacao configurados:\n";
    
    $sql = "SELECT COUNT(*) as QTD FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO WHERE CENTROCUSTO = :CC";
    $st = $conn->prepare(mg_with_schema($sql));
    $st->execute([':CC' => $cc_almox]);
    $qtd = $st->fetchColumn();
    
    echo "Total de níveis configurados para o CC $cc_almox: $qtd\n";
    
    if ($qtd == 0) {
        echo "\nAVISO: Este centro de custo NAO tem nenhum nível de aprovação configurado na tabela MEGAG_DESP_POLIT_CENTRO_CUSTO.\n";
        echo "A aprovação de rateio só funcionará se você configurar os níveis para este CC na tela de Políticas.\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
