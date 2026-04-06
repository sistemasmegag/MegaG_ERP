<?php
require_once __DIR__ . '/../routes/check_session.php';

$pathConexaoCandidates = [
    __DIR__ . '/../db_config/db_connect.php',
    __DIR__ . '/../../db_config/db_connect.php',
    __DIR__ . '/config/db_connect.php'
];
$pathConexao = null;
foreach ($pathConexaoCandidates as $cand) {
    if (file_exists($cand)) {
        $pathConexao = $cand;
        break;
    }
}

if (!$pathConexao) {
    http_response_code(500);
    exit('Arquivo de conexão não encontrado.');
}

require_once $pathConexao;
require_once __DIR__ . '/mg_api_bootstrap.php';

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        exit('ID da despesa inválido.');
    }

    $st = $conn->prepare(mg_with_schema("SELECT NOMEARQUIVO
                            FROM CONSINCO.MEGAG_DESP_ARQUIVO
                           WHERE CODDESPESA = :ID
                           ORDER BY CODARQUIVO"));
    $st->execute([':ID' => $id]);
    $files = $st->fetchAll(PDO::FETCH_COLUMN);

    if (empty($files)) {
        $stFallback = $conn->prepare(mg_with_schema("SELECT NOMEARQUIVO FROM CONSINCO.MEGAG_DESP WHERE CODDESPESA = :ID"));
        $stFallback->execute([':ID' => $id]);
        $fallback = $stFallback->fetchColumn();
        if ($fallback) {
            $files = [$fallback];
        }
    }

    if (empty($files)) {
        http_response_code(404);
        exit('Nenhum anexo encontrado para esta despesa.');
    }

    $uploadDir = realpath(__DIR__ . '/../uploads');
    if (!$uploadDir) {
        http_response_code(500);
        exit('Diretório de uploads não encontrado.');
    }

    $validFiles = [];
    foreach ($files as $fileName) {
        $baseName = basename((string)$fileName);
        $fullPath = realpath($uploadDir . DIRECTORY_SEPARATOR . $baseName);
        if ($fullPath && str_starts_with($fullPath, $uploadDir) && is_file($fullPath)) {
            $validFiles[] = ['name' => $baseName, 'path' => $fullPath];
        }
    }

    if (empty($validFiles)) {
        http_response_code(404);
        exit('Os anexos não foram localizados no servidor.');
    }

    if (count($validFiles) === 1) {
        $single = $validFiles[0];
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $single['name'] . '"');
        header('Content-Length: ' . filesize($single['path']));
        readfile($single['path']);
        exit;
    }

    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('ZipArchive não está disponível neste servidor.');
    }

    $tmpDir = sys_get_temp_dir();
    $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'despesa_' . $id . '_' . uniqid('', true) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        exit('Não foi possível gerar o arquivo ZIP.');
    }

    foreach ($validFiles as $file) {
        $zip->addFile($file['path'], $file['name']);
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="documentacao_despesa_' . $id . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erro ao preparar download: ' . $e->getMessage());
}
