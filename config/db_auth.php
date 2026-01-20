<?php
// db_auth.php

function getAuthConnection() {
    // Configurações do Banco da Consinco (Login)
    $host = '10.14.35.106';
    $port = '1521';
    $user = 'MEGAWEB';
    $pass = 'HOMOLOGA'; 
    $service = 'consinco.vmnetwork.vcnmegag.oraclevcn.com';
    
    // String de Conexão TNS completa
    $tns = "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SERVICE_NAME=$service)))";
    
    $dsn = "oci:dbname=" . $tns . ";charset=UTF8";

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_CASE => PDO::CASE_UPPER
        ];
        
        return new PDO($dsn, $user, $pass, $options);

    } catch (PDOException $e) {
        // Se der erro, retorna null ou lança exceção controlada
        // Para debug: file_put_contents('log_auth.txt', $e->getMessage());
        return null;
    }
}
?>