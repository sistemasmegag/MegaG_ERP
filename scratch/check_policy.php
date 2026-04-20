<?php
require_once __DIR__ . '/../bootstrap/db.php';
require_once __DIR__ . '/../api/mg_api_bootstrap.php';
$pathConexao = mg_db_config_path();
require_once $pathConexao;

try {
    $cc_ti = 1040112001;
    $cc_almox = 1040125004;
    
    echo "Verificando configuracao de aprovadores:\n";
    
    $sql = "SELECT P.CENTROCUSTO, P.CODPOLITICA, U.NOME, P.NIVEL_APROVACAO 
            FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO P
            JOIN CONSINCO.GE_USUARIO U ON P.SEQUSUARIO = U.SEQUSUARIO
            WHERE P.CENTROCUSTO IN (:TI, :ALMOX)
            ORDER BY P.CENTROCUSTO, P.NIVEL_APROVACAO";
            
    $st = $conn->prepare(mg_with_schema($sql));
    $st->execute([':TI' => $cc_ti, ':ALMOX' => $cc_almox]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "Nenhum configuracao encontrada para estes CCs.\n";
    } else {
        foreach ($rows as $r) {
            echo "CC: {$r['CENTROCUSTO']} | Politica: {$r['CODPOLITICA']} | Usuario: {$r['NOME']} | Nivel: {$r['NIVEL_APROVACAO']}\n";
        }
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
