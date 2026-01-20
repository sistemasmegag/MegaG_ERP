<?php

// CONFIGURAÇÃO DO BANCO REAL (10.14.35.106)

define('DB_HOST', '10.14.35.106');
define('DB_PORT', '1521');
define('DB_SCHEMA','consinco');
define('DB_WEBSCHEMA','megaweb');
define('DB_SID', 'CONSINCO');

define('DB_USER', 'MEGAWEB');           // Schema onde está a tabela
define('DB_PASSWORD', 'HOMOLOGA');      // Senha

define('DB_CHARSET','charset=UTF8');

define('DB_OPT', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_CASE => PDO::CASE_UPPER,
    PDO::ATTR_AUTOCOMMIT => false
]);

define('DB_SERVICESID','SERVICE_NAME=consinco.vmnetwork.vcnmegag.oraclevcn.com');

define(
    'DB_CONN_STR',
    'oci:dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=' . DB_HOST . ')
    (PORT=' . DB_PORT . ')))(CONNECT_DATA=(' . DB_SERVICESID . ')));' . DB_CHARSET
);

function getConexaoPDO() {
    try {
        return new PDO(DB_CONN_STR, DB_USER, DB_PASSWORD, DB_OPT);

    } catch(PDOException $e) {
        $ret = [
            'status' => 500,
            'msg' => 'Erro ao conectar no Oracle Produção: ' . $e->getMessage()
        ];

        header('Content-Type: application/json');
        echo json_encode($ret);
        exit();
    }
}

?>
