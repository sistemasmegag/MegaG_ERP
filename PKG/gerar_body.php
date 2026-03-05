<?php
$files = ['AprovadoresCRUD.sql', 'DespesaCRUD.sql', 'TipoDespesaCRUD.sql', 'CentroCustoDespesaCRUD.sql'];
$body = "CREATE OR REPLACE PACKAGE BODY CONSINCO.PKG_MEGAG_DESP_CADASTRO IS\n\n";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        // Replace 'CREATE OR REPLACE PROCEDURE ' with 'PROCEDURE '
        $content = preg_replace('/CREATE OR REPLACE PROCEDURE /i', 'PROCEDURE ', $content);
        // Remove trailing slash lines used in SQL scripts
        $content = preg_replace('/^\/\s*$/m', '', $content);
        $body .= "/* ==================================================\n   FILE: $file\n================================================== */\n" . $content . "\n";
    }
}
$body .= "/* ==================================================\n   IMPLEMENTAR PROCEDURES FALTANTES DA ESPECIFICACAO:\n   (COLE AQUI O CODIGO DE: PRC_INS_MEGAG_DESP_ARQUIVO, \n    PRC_LIST_MEGAG_DESP_APROVACAO, ETC... )\n================================================== */\n\n";
$body .= "END PKG_MEGAG_DESP_CADASTRO;\n/\n";

file_put_contents(__DIR__ . '/PackageBody.sql', $body);
echo "File PackageBody.sql generated successfully.\n";
