<?php

// ==========================================
// Blindagem: evita "lixo" antes do JSON (BOM, warnings, echos acidentais)
// ==========================================
if (ob_get_level() === 0) {
    ob_start();
}

// força sempre UTF-8 e JSON quando for endpoint
// (se sua página HTML usar isso, não afeta porque ela não chama mg_json_* normalmente)
ini_set('default_charset', 'UTF-8');

// ==========================================

require_once __DIR__ . '/../bootstrap/db.php';

/* |-------------------------------------------------------------------------- | Conexão PDO (padrão projeto) |-------------------------------------------------------------------------- */

function getConexaoPDO()
{
    try {
        return mg_get_global_pdo();
    }
    catch (PDOException $e) {

        $ret = [
            'status' => 401,
            'tiporet' => 'E',
            'codret' => 'ERRODBGERAL',
            'ico' => 'error',
            'msg' => $e->getMessage(),
            'qtd' => 1
        ];

        echo json_encode($ret);
        exit();
    }
}

/* |-------------------------------------------------------------------------- | JSON padrão API nova |-------------------------------------------------------------------------- */

function mg_json_success($data = null)
{
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function mg_json_error($msg)
{
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => $msg
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* |-------------------------------------------------------------------------- | Permissão (por enquanto liberada) |-------------------------------------------------------------------------- */

function mg_need_permission($perm)
{
    // Depois podemos integrar com megag_pkg_seguranca
    return true;
}

function mg_schema(): string
{
    return mg_db_schema_name();
}

function mg_table(string $object): string
{
    return mg_schema() . '.' . strtoupper(trim($object));
}

function mg_sequence(string $object): string
{
    return mg_schema() . '.' . strtoupper(trim($object));
}

function mg_package(string $object): string
{
    return mg_schema() . '.' . strtoupper(trim($object));
}

function mg_with_schema(string $sql): string
{
    return str_replace(
        ['{{SCHEMA}}.', 'CONSINCO.'],
        [mg_schema() . '.', mg_schema() . '.'],
        $sql
    );
}

/* |-------------------------------------------------------------------------- | Helper para nome de package |-------------------------------------------------------------------------- */

function mg_pkg($pkg)
{
    return mg_package($pkg);
}
