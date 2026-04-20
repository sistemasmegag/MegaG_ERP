<?php
function fnLogErro($m){ return $m; }
require_once __DIR__ . '/../bootstrap/db.php';
$path = mg_db_config_path();
require_once $path;
$stmt = $conn->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM ALL_TAB_COLUMNS WHERE OWNER = :o AND TABLE_NAME = :t");
$stmt->execute([':o' => 'CONSINCO', ':t' => 'MEGAG_IMP_BI_METAS']);
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols) . "\n";
