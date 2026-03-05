<?php
$body = file_get_contents('c:/xampp/htdocs/importadorV2/PKG/PackageBody.sql');
// Find all procedure declarations in the body.
preg_match_all('/PROCEDURE\s+([a-zA-Z0-9_]+)\s*\((.*?)\)\s*(IS|AS)/si', $body, $matches);

$spec = "CREATE OR REPLACE PACKAGE CONSINCO.PKG_MEGAG_DESP_CADASTRO IS\n\n";

for ($i = 0; $i < count($matches[0]); $i++) {
    $proc_name = $matches[1][$i];
    $args = trim($matches[2][$i]);
    $spec .= "PROCEDURE $proc_name (\n    $args\n);\n\n";
}

$spec .= "END PKG_MEGAG_DESP_CADASTRO;\n/\n";

file_put_contents('c:/xampp/htdocs/importadorV2/PKG/Package.sql', $spec);
echo 'Package.sql sync successful!';
