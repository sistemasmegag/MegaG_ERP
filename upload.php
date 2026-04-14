<?php
session_start();

if (!isset($_SESSION['logado'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function upload_shorthand_to_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;

    switch ($unit) {
        case 'g':
            return (int)($number * 1024 * 1024 * 1024);
        case 'm':
            return (int)($number * 1024 * 1024);
        case 'k':
            return (int)($number * 1024);
        default:
            return (int)$number;
    }
}

function upload_bytes_to_human(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

function upload_error_message(int $code): string
{
    $uploadMax = ini_get('upload_max_filesize') ?: 'desconhecido';
    $postMax = ini_get('post_max_size') ?: 'desconhecido';

    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "O arquivo excede o limite do servidor. upload_max_filesize={$uploadMax}, post_max_size={$postMax}.";
        case UPLOAD_ERR_FORM_SIZE:
            return 'O arquivo excede o limite aceito pelo formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'O upload foi enviado apenas parcialmente. Tente novamente.';
        case UPLOAD_ERR_NO_FILE:
            return 'Nenhum arquivo foi enviado.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'A pasta temporaria de upload nao esta disponivel no servidor.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'O servidor nao conseguiu gravar o arquivo no disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Uma extensao do PHP interrompeu o upload.';
        default:
            return 'Erro no upload (code ' . $code . ').';
    }
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['arquivo'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Arquivo nao enviado']);
    exit;
}

$file = $_FILES['arquivo'];
if ((int)$file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['sucesso' => false, 'erro' => upload_error_message((int)$file['error'])]);
    exit;
}

$uploadLimitBytes = min(
    upload_shorthand_to_bytes((string)(ini_get('upload_max_filesize') ?: '0')),
    upload_shorthand_to_bytes((string)(ini_get('post_max_size') ?: '0'))
);
if ($uploadLimitBytes > 0 && (int)$file['size'] > $uploadLimitBytes) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'O arquivo excede o limite do servidor (' . upload_bytes_to_human($uploadLimitBytes) . ').',
    ]);
    exit;
}

$allowedExt = ['xls', 'xlsx', 'xlsm'];
$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Extensao nao permitida. Use xls, xlsx ou xlsm.']);
    exit;
}

$timestamp = date('Ymd_His');
$rand = bin2hex(random_bytes(4));
$filename = $timestamp . '_' . $rand . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Falha ao mover arquivo para uploads/.']);
    exit;
}

@chmod($dest, 0644);

echo json_encode([
    'sucesso' => true,
    'arquivo' => $filename,
    'original' => $file['name'],
]);
exit;
