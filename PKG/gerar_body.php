<?php
$files = [
    'AprovadoresCRUD.sql', 
    'DespesaCRUD.sql', 
    'TipoDespesaCRUD.sql', 
    'CentroCustoDespesaCRUD.sql',
    'ArquivoCRUD.sql',
    'PoliticaCRUD.sql',
    'GrupoCRUD.sql',
    'RateioCRUD.sql'
];

$body = "CREATE OR REPLACE PACKAGE BODY CONSINCO.PKG_MEGAG_DESP_CADASTRO IS\n\n";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        // Replace 'CREATE OR REPLACE PROCEDURE ' with 'PROCEDURE '
        $content = preg_replace('/CREATE OR REPLACE PROCEDURE /i', 'PROCEDURE ', $content);
        // Remove trailing slash lines used in SQL scripts
        $content = preg_replace('/^\/\s*$/m', '', $content);
        $file_title = ($file == 'PoliticaCRUD.sql') ? 'PolíticaCRUD.sql' : $file;
        $body .= "/* ==================================================\n   FILE: $file_title\n================================================== */\n" . $content . "\n";
    }
}
$body .= "END PKG_MEGAG_DESP_CADASTRO;\n";

file_put_contents(__DIR__ . '/PackageBody.sql', $body);
echo "File PackageBody.sql generated successfully.\n";
