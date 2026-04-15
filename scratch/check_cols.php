<?php
require_once __DIR__ . '/../bootstrap/db.php';
try {
    $conn = mg_get_global_pdo();
    $owner = 'CONSINCO';
    $table = 'MEGAG_IMP_BI_METAS';
    
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM ALL_TAB_COLUMNS
        WHERE OWNER = :own
          AND TABLE_NAME = :tab
        ORDER BY COLUMN_ID
    ");
    $stmt->execute([':own' => $owner, ':tab' => $table]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Colunas da tabela {$owner}.{$table}:\n";
    print_r($cols);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
