<?php
// upload.php
session_start();

// Opcional: checar sessão
if (!isset($_SESSION['logado'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Diretório de armazenamento (relativo à raiz). Garanta permissão de escrita.
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['arquivo'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Arquivo não enviado']);
    exit;
}

$file = $_FILES['arquivo'];

// Erros básicos de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $msg = 'Erro no upload (code ' . $file['error'] . ')';
    echo json_encode(['sucesso' => false, 'erro' => $msg]);
    exit;
}

// Validação de extensão
$allowedExt = ['xls', 'xlsx', 'xlsm'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Extensão não permitida. Use xls/xlsx']);
    exit;
}

// Gerar nome único
$timestamp = date('Ymd_His');
$rand = bin2hex(random_bytes(4));
$filename = $timestamp . '_' . $rand . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Falha ao mover arquivo para uploads/']);
    exit;
}

// Opcional: ajustar permissão do arquivo
@chmod($dest, 0644);

// Retornar sucesso com nome do arquivo (apenas o nome, não caminho absoluto)
echo json_encode(['sucesso' => true, 'arquivo' => $filename, 'original' => $file['name']]);
exit;
