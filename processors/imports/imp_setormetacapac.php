<?php
return [
    'owner' => 'CONSINCO',
    'table' => 'MEGAG_IMP_SETORMETACAPAC',

    // começa na linha 2
    'start_row' => 2,

    // se coluna A estiver vazia, para o loop
    'stop_when_empty_excel_col' => 'A',

    // cabeçalhos obrigatórios (se quiser validar por header)
    // se você usa por coluna (A,B,C...) pode deixar vazio
    'required_headers' => [],

    // MAPEAMENTO:
    // cada coluna do Oracle recebe uma regra:
    // source: excel|session|fixed
    // type: text|number|date|datetime
    // excel_col: 'A' (ou excel_header: 'SEQSETOR')
    'columns' => [
        'SEQSETOR'    => ['source' => 'excel',   'excel_col' => 'A', 'type' => 'number', 'required' => true],
        'TURNO'       => ['source' => 'excel',   'excel_col' => 'B', 'type' => 'text',   'required' => true],
        'DTA'         => ['source' => 'excel',   'excel_col' => 'C', 'type' => 'date',   'required' => true],
        'PESO_META'   => ['source' => 'excel',   'excel_col' => 'D', 'type' => 'number', 'required' => true],
        'PESO_CAPAC'  => ['source' => 'excel',   'excel_col' => 'E', 'type' => 'number', 'required' => true],

        // pega o nome do usuário logado
        'USUINCLUSAO' => ['source' => 'session', 'key' => 'usuario', 'type' => 'text',   'required' => true],
    ],
];
