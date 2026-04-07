<?php

if (!function_exists('mg_project_root')) {
    function mg_project_root(): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('mg_db_config_candidates')) {
    function mg_db_config_candidates(): array
    {
        static $candidates = null;

        if ($candidates !== null) {
            return $candidates;
        }

        $projectRoot = mg_project_root();
        $parentRoot = dirname($projectRoot);
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') : '';

        $raw = [
            $projectRoot . '/db_config/db_connect.php',
            $parentRoot . '/db_config/db_connect.php',
            $projectRoot . '/config/db_connect.php',
            $projectRoot . '/config/db.php',
        ];

        if ($documentRoot !== '') {
            $raw[] = $documentRoot . '/db_config/db_connect.php';
        }

        $normalized = [];
        foreach ($raw as $path) {
            $clean = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (!in_array($clean, $normalized, true)) {
                $normalized[] = $clean;
            }
        }

        $candidates = $normalized;
        return $candidates;
    }
}

if (!function_exists('mg_db_config_path')) {
    function mg_db_config_path(): string
    {
        static $resolved = null;

        if ($resolved !== null) {
            return $resolved;
        }

        foreach (mg_db_config_candidates() as $candidate) {
            if (is_file($candidate)) {
                $resolved = $candidate;
                return $resolved;
            }
        }

        throw new RuntimeException(
            'Arquivo de conexão não encontrado. Tentei: ' . implode(' | ', mg_db_config_candidates())
        );
    }
}

if (!function_exists('mg_load_db_config')) {
    function mg_load_db_config(): string
    {
        $path = mg_db_config_path();
        require_once $path;
        return $path;
    }
}

if (!function_exists('mg_get_global_pdo')) {
    function mg_get_global_pdo(): PDO
    {
        mg_load_db_config();

        global $conn;

        if (!isset($conn) || !($conn instanceof PDO)) {
            if (!defined('DB_CONN_STR') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_OPT')) {
                throw new RuntimeException('Conexão PDO não inicializada e constantes de banco ausentes.');
            }

            $conn = new PDO(DB_CONN_STR, DB_USER, DB_PASSWORD, DB_OPT);
        }

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
        $conn->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

        return $conn;
    }
}

if (!function_exists('mg_db_schema_name')) {
    function mg_db_schema_name(): string
    {
        mg_load_db_config();
        $schema = defined('DB_SCHEMA') ? strtoupper(trim((string)DB_SCHEMA)) : '';
        if ($schema === '') {
            throw new RuntimeException('Constante DB_SCHEMA nao definida ou vazia.');
        }

        return $schema;
    }
}

if (!function_exists('mg_db_debug_context')) {
    function mg_db_debug_context(): array
    {
        mg_load_db_config();

        return [
            'config_path' => mg_db_config_path(),
            'schema' => defined('DB_SCHEMA') ? (string)DB_SCHEMA : null,
            'user' => defined('DB_USER') ? (string)DB_USER : null,
            'service' => defined('DB_SERVICESID') ? (string)DB_SERVICESID : null,
            'conn' => defined('DB_CONN_STR') ? (string)DB_CONN_STR : null,
        ];
    }
}
