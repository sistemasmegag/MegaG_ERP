<?php
return [
    'owner' => 'CONSINCO',
    'table' => 'MEGAG_IMP_LANCTOCOMISSAO',

    // Começa na linha 2 (linha 1 é cabeçalho)
    'start_row' => 2,

    // Não travar por coluna vazia fixa, porque o Excel pode ter linhas no meio.
    // Se você quiser travar por "CODEVENTO vazio", você pode definir uma coluna,
    // mas como o layout pode variar, vamos deixar null.
    'stop_when_empty_excel_col' => null,

    // Vamos validar por cabeçalho (igual seu processador antigo).
    // Aqui é o "canonical" esperado (sem acento).
    'required_headers' => [
        'CODEVENTO',
        'SEQPESSOA',
        'DTAHREMISSAO',
        'OBSERVACAO',
        'VLRTOTAL'
    ],

    // Mapeamento de colunas:
    // Usa excel_header (case-insensitive no universal) e type para converter
    'columns' => [

        // ===== obrigatórias =====
        'CODEVENTO' => [
            'source' => 'excel',
            'excel_header' => 'CODEVENTO',
            'type' => 'text',
            'required' => true
        ],

        'SEQPESSOA' => [
            'source' => 'excel',
            'excel_header' => 'SEQPESSOA',
            'type' => 'text',
            'required' => true
        ],

        // Excel -> datetime string "YYYY-MM-DD HH:MM:SS"
        'DTAHREMISSAO' => [
            'source' => 'excel',
            'excel_header' => 'DTAHREMISSAO',
            'type' => 'datetime',
            'required' => true
        ],

        'OBSERVACAO' => [
            'source' => 'excel',
            'excel_header' => 'OBSERVACAO',
            'type' => 'text',
            'required' => false
        ],

        'VLRTOTAL' => [
            'source' => 'excel',
            'excel_header' => 'VLRTOTAL',
            'type' => 'number',
            'required' => true
        ],

        // ===== obrigatórias no ORACLE (NOT NULL) =====
        // CORREÇÃO DO ORA-01400:
        // A tabela CONSINCO.MEGAG_IMP_LANCTOCOMISSAO exige USUINCLUSAO (NOT NULL),
        // então precisamos preencher com o usuário logado que fez a importação.
        'USUINCLUSAO' => [
            'source' => 'session',
            'key' => 'usuario',
            'type' => 'text',
            'required' => true
        ],

        // ===== opcionais (se existirem na tabela, ok inserir — se não existirem, dá erro)
        // ATENÇÃO: seu universal atual NÃO checa se a coluna existe no Oracle.
        // Então só deixe estes abaixo se você tem CERTEZA que as colunas existem na tabela.

        // Usuário logado que fez a importação
        'USULANCTO' => [
            'source' => 'session',
            'key' => 'usuario',
            'type' => 'text',
            'required' => false
        ],

        // Status default
        'STATUS' => [
            'source' => 'fixed',
            'value' => 'S',
            'type' => 'text',
            'required' => false
        ],

        // Log default (string vazia por enquanto, porque NULL real depende de ajuste no universal)
        'MSG_LOG' => [
            'source' => 'fixed',
            'value' => '',
            'type' => 'text',
            'required' => false
        ],

        // Data inclusão (string vazia por enquanto; SYSDATE real depende de ajuste no universal)
        // Se DTAINCLUSAO for DATE no Oracle, mandar string vazia pode dar erro.
        // Então recomendo comentar essa linha até eu ajustar o universal para SYSDATE raw.
        /*
        'DTAINCLUSAO' => [
            'source' => 'fixed',
            'value' => '',
            'type' => 'text',
            'required' => false
        ],
        */
    ],
];
