<?php
require_once __DIR__ . '/../routes/check_session.php';
require_once __DIR__ . '/mg_api_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function inv_json(bool $ok, $dados = null, ?string $erro = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'sucesso' => $ok,
        'dados' => $ok ? $dados : null,
        'erro' => $ok ? null : $erro,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function inv_lob_to_string($value): ?string
{
    if ($value === null) {
        return null;
    }
    if (is_resource($value)) {
        $content = stream_get_contents($value);
        if ($content === false) {
            return null;
        }
        return $content;
    }
    return (string)$value;
}

function inv_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function inv_action(array $body): string
{
    return strtolower(trim((string)($_GET['action'] ?? $body['action'] ?? 'list')));
}

function inv_user(): string
{
    $user = trim((string)($_SESSION['usuario'] ?? $_SESSION['login'] ?? 'SISTEMA'));
    return $user !== '' ? strtoupper($user) : 'SISTEMA';
}

function inv_throw_if_missing_schema(PDO $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $requiredTables = [
        'MEGAG_TI_EQUIPAMENTOS',
        'MEGAG_TI_TERMOS',
        'MEGAG_ALMOX_SOLICITACOES',
    ];

    foreach ($requiredTables as $tableName) {
        try {
            $conn->query("SELECT 1 FROM CONSINCO.{$tableName} WHERE 1 = 0");
        } catch (Throwable $e) {
            throw new Exception(
                'Não foi possível acessar CONSINCO.' . $tableName .
                '. Verifique se a tabela existe e se o usuário da aplicação possui permissão de SELECT.'
            );
        }
    }

    $checked = true;
}

function inv_options(): array
{
    return [
        'status' => [
            ['valor' => 'EM_ESTOQUE', 'rotulo' => 'Em estoque'],
            ['valor' => 'EM_USO', 'rotulo' => 'Em uso'],
            ['valor' => 'MANUTENCAO', 'rotulo' => 'Manutenção'],
            ['valor' => 'BAIXADO', 'rotulo' => 'Baixado'],
        ],
        'tipos' => [
            ['valor' => 'NOTEBOOK', 'rotulo' => 'Notebook'],
            ['valor' => 'DESKTOP', 'rotulo' => 'Desktop'],
            ['valor' => 'MONITOR', 'rotulo' => 'Monitor'],
            ['valor' => 'CELULAR', 'rotulo' => 'Celular'],
            ['valor' => 'TABLET', 'rotulo' => 'Tablet'],
            ['valor' => 'IMPRESSORA', 'rotulo' => 'Impressora'],
            ['valor' => 'MOBILIARIO', 'rotulo' => 'Mobiliario'],
            ['valor' => 'ELETRO', 'rotulo' => 'Eletroportatil'],
            ['valor' => 'FERRAMENTA', 'rotulo' => 'Ferramenta'],
            ['valor' => 'EPI', 'rotulo' => 'EPI'],
            ['valor' => 'LIMPEZA', 'rotulo' => 'Limpeza'],
            ['valor' => 'ESCRITORIO', 'rotulo' => 'Material de escritorio'],
            ['valor' => 'PECA_REPOSICAO', 'rotulo' => 'Peca de reposicao'],
            ['valor' => 'PERIFERICO', 'rotulo' => 'Periférico'],
            ['valor' => 'SERVIDOR', 'rotulo' => 'Servidor'],
            ['valor' => 'REDE', 'rotulo' => 'Rede'],
            ['valor' => 'OUTRO', 'rotulo' => 'Outro'],
        ],
        'condicoes' => [
            ['valor' => 'NOVO', 'rotulo' => 'Novo'],
            ['valor' => 'BOM', 'rotulo' => 'Bom'],
            ['valor' => 'REGULAR', 'rotulo' => 'Regular'],
            ['valor' => 'CRITICO', 'rotulo' => 'Crítico'],
        ],
        'report_types' => [
            ['valor' => 'EM_USO', 'rotulo' => 'Itens em uso'],
            ['valor' => 'EM_ESTOQUE', 'rotulo' => 'Itens em estoque'],
            ['valor' => 'POR_COLABORADOR', 'rotulo' => 'Por colaborador'],
            ['valor' => 'POR_TIPO', 'rotulo' => 'Por categoria'],
            ['valor' => 'POR_LOCALIZACAO', 'rotulo' => 'Por localizacao'],
            ['valor' => 'GARANTIAS_A_VENCER', 'rotulo' => 'Garantias a vencer'],
            ['valor' => 'BAIXADOS_MANUTENCAO', 'rotulo' => 'Baixados / manutencao'],
            ['valor' => 'TERMOS_ASSINADOS', 'rotulo' => 'Termos assinados por periodo'],
        ],
        'request_status' => [
            ['valor' => 'SOLICITADO', 'rotulo' => 'Solicitado'],
            ['valor' => 'EM_ANALISE', 'rotulo' => 'Em analise'],
            ['valor' => 'SEPARADO', 'rotulo' => 'Separado'],
            ['valor' => 'ATENDIDO', 'rotulo' => 'Atendido'],
            ['valor' => 'CANCELADO', 'rotulo' => 'Cancelado'],
        ],
        'request_priorities' => [
            ['valor' => 'BAIXA', 'rotulo' => 'Baixa'],
            ['valor' => 'MEDIA', 'rotulo' => 'Media'],
            ['valor' => 'ALTA', 'rotulo' => 'Alta'],
            ['valor' => 'URGENTE', 'rotulo' => 'Urgente'],
        ],
    ];
}

function inv_fetch_request_form_domains(PDO $conn): array
{
    $costCenters = [];
    $stmt = $conn->prepare("SELECT CENTRORESULTADO AS CENTROCUSTO,
                                   SEQCENTRORESULTADO,
                                   DESCRICAO AS NOME
                              FROM CONSINCO.ABA_CENTRORESULTADO
                             ORDER BY DESCRICAO");
    $stmt->execute();
    foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $costCenters[] = [
            'valor' => trim((string)($row['CENTROCUSTO'] ?? '')) . '|' . trim((string)($row['SEQCENTRORESULTADO'] ?? '')),
            'rotulo' => trim((string)($row['CENTROCUSTO'] ?? '')) . ' - ' . trim((string)($row['NOME'] ?? '')),
            'centro_custo' => trim((string)($row['CENTROCUSTO'] ?? '')),
            'seq_centro_resultado' => (int)($row['SEQCENTRORESULTADO'] ?? 0),
            'nome' => trim((string)($row['NOME'] ?? '')),
        ];
    }

    return [
        'cost_centers' => $costCenters,
        'filiais' => [
            ['valor' => '1 - Matriz', 'rotulo' => '1 - Matriz'],
            ['valor' => '2 - VGP', 'rotulo' => '2 - VGP'],
            ['valor' => '3 - Pouso Alegre', 'rotulo' => '3 - Pouso Alegre'],
            ['valor' => '5 - Araraquara', 'rotulo' => '5 - Araraquara'],
            ['valor' => '6 - Cacapava', 'rotulo' => '6 - Cacapava'],
        ],
    ];
}

function inv_resolve_cost_center(PDO $conn, string $value): ?array
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $parts = explode('|', $value);
    $code = trim((string)($parts[0] ?? ''));
    $seq = trim((string)($parts[1] ?? ''));

    $stmt = $conn->prepare("SELECT CENTRORESULTADO,
                                   SEQCENTRORESULTADO,
                                   DESCRICAO
                              FROM CONSINCO.ABA_CENTRORESULTADO
                             WHERE (:SEQ IS NOT NULL AND TO_CHAR(SEQCENTRORESULTADO) = :SEQ)
                                OR TO_CHAR(CENTRORESULTADO) = :COD
                                OR TO_CHAR(SEQCENTRORESULTADO) = :COD");
    if ($seq !== '') {
        $stmt->bindValue(':SEQ', $seq);
    } else {
        $stmt->bindValue(':SEQ', null, PDO::PARAM_NULL);
    }
    $stmt->bindValue(':COD', $code);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

function inv_fetch_responsibles_by_cost_center(PDO $conn, string $costCenterValue): array
{
    $costCenter = inv_resolve_cost_center($conn, $costCenterValue);
    $seq = (int)($costCenter['seqcentroresultado'] ?? 0);
    if ($seq <= 0) {
        return [];
    }

    $stmt = $conn->prepare("SELECT DISTINCT A.SEQUSUARIO,
                                   A.NOME,
                                   A.CODGRUPO
                              FROM CONSINCO.MEGAG_DESP_APROVADORES A
                             WHERE A.CENTROCUSTO = :CC
                             ORDER BY A.NOME");
    $stmt->bindValue(':CC', $seq, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $items = [];
    foreach ($rows as $row) {
        $name = trim((string)($row['NOME'] ?? ''));
        if ($name === '') {
            continue;
        }
        $items[] = [
            'valor' => $name,
            'rotulo' => $name,
            'sequsuario' => (int)($row['SEQUSUARIO'] ?? 0),
            'codgrupo' => isset($row['CODGRUPO']) ? (int)$row['CODGRUPO'] : null,
        ];
    }

    return $items;
}

function inv_sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    return strip_tags($html, '<div><p><span><strong><em><table><thead><tbody><tr><th><td><br><ul><ol><li>');
}

function inv_label(array $options, string $group, ?string $value): string
{
    $value = trim((string)$value);
    foreach (($options[$group] ?? []) as $item) {
        if (($item['valor'] ?? '') === $value) {
            return (string)$item['rotulo'];
        }
    }
    return $value;
}

function inv_nextval(PDO $conn, string $sequenceName): int
{
    try {
        $stmt = $conn->query("SELECT {$sequenceName}.NEXTVAL FROM DUAL");
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        throw new Exception(
            'Não foi possível consumir a sequence ' . $sequenceName .
            '. Verifique se o usuário da aplicação possui permissão de SELECT nessa sequence.'
        );
    }
}

function inv_format_dashboard(PDO $conn): array
{
    $sql = "SELECT COUNT(*) AS TOTAL,
                   SUM(CASE WHEN STATUS = 'EM_USO' THEN 1 ELSE 0 END) AS EM_USO,
                   SUM(CASE WHEN STATUS = 'EM_ESTOQUE' THEN 1 ELSE 0 END) AS EM_ESTOQUE,
                   SUM(CASE WHEN STATUS = 'MANUTENCAO' THEN 1 ELSE 0 END) AS MANUTENCAO,
                   SUM(CASE WHEN STATUS = 'BAIXADO' THEN 1 ELSE 0 END) AS BAIXADO,
                   SUM(CASE
                         WHEN GARANTIA_ATE IS NOT NULL
                          AND GARANTIA_ATE BETWEEN TRUNC(SYSDATE) AND TRUNC(SYSDATE) + 30
                         THEN 1 ELSE 0
                       END) AS GARANTIA_PROXIMA,
                   SUM(CASE
                         WHEN GARANTIA_ATE IS NOT NULL
                          AND GARANTIA_ATE < TRUNC(SYSDATE)
                         THEN 1 ELSE 0
                       END) AS GARANTIA_VENCIDA
              FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
             WHERE ATIVO = 'S'";
    $stmt = $conn->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return array_change_key_case($row, CASE_LOWER);
}

function inv_report_filters(): array
{
    return [
        'report_type' => strtoupper(trim((string)($_GET['report_type'] ?? 'EM_USO'))),
        'status' => strtoupper(trim((string)($_GET['status'] ?? ''))),
        'tipo' => strtoupper(trim((string)($_GET['tipo'] ?? ''))),
        'responsavel' => strtoupper(trim((string)($_GET['responsavel'] ?? ''))),
        'localizacao' => strtoupper(trim((string)($_GET['localizacao'] ?? ''))),
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to' => trim((string)($_GET['date_to'] ?? '')),
        'signed_only' => strtoupper(trim((string)($_GET['signed_only'] ?? 'S'))),
    ];
}

function inv_report_equipment_where(array $filters, array &$params): string
{
    $where = ["ATIVO = 'S'"];

    if ($filters['status'] !== '') {
        $where[] = 'STATUS = :STATUS_FILTER';
        $params[':STATUS_FILTER'] = $filters['status'];
    }

    if ($filters['tipo'] !== '') {
        $where[] = 'TIPO = :TIPO_FILTER';
        $params[':TIPO_FILTER'] = $filters['tipo'];
    }

    if ($filters['responsavel'] !== '') {
        $where[] = "UPPER(NVL(LOGIN_USUARIO, '')) LIKE :RESP_FILTER";
        $params[':RESP_FILTER'] = '%' . $filters['responsavel'] . '%';
    }

    if ($filters['localizacao'] !== '') {
        $where[] = "UPPER(NVL(LOCALIZACAO, '')) LIKE :LOCAL_FILTER";
        $params[':LOCAL_FILTER'] = '%' . $filters['localizacao'] . '%';
    }

    if ($filters['date_from'] !== '') {
        $where[] = "NVL(DATA_AQUISICAO, TRUNC(SYSDATE)) >= TO_DATE(:DATE_FROM, 'YYYY-MM-DD')";
        $params[':DATE_FROM'] = $filters['date_from'];
    }

    if ($filters['date_to'] !== '') {
        $where[] = "NVL(DATA_AQUISICAO, TRUNC(SYSDATE)) <= TO_DATE(:DATE_TO, 'YYYY-MM-DD')";
        $params[':DATE_TO'] = $filters['date_to'];
    }

    return implode(' AND ', $where);
}

function inv_report_rows(PDO $conn, string $sql, array $params): array
{
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function inv_report(PDO $conn): array
{
    $filters = inv_report_filters();
    $type = $filters['report_type'];
    $params = [];
    $options = inv_options();
    $title = 'Relatorio do inventario geral';
    $subtitle = 'Consulta operacional dos ativos da MEGA G.';
    $cards = [];
    $columns = [];
    $rows = [];
    $filename = 'relatorio_inventario_geral';

    if ($type === 'TERMOS_ASSINADOS') {
        $title = 'Termos assinados por periodo';
        $subtitle = 'Controle dos termos emitidos e assinados no inventario geral.';
        $filename = 'relatorio_termos_assinados';

        $where = ['1 = 1'];
        if ($filters['signed_only'] !== 'N') {
            $where[] = "t.STATUS_ASSINATURA = 'ASSINADO'";
        }
        if ($filters['date_from'] !== '') {
            $where[] = "TRUNC(t.DATA_TERMO) >= TO_DATE(:DATE_FROM, 'YYYY-MM-DD')";
            $params[':DATE_FROM'] = $filters['date_from'];
        }
        if ($filters['date_to'] !== '') {
            $where[] = "TRUNC(t.DATA_TERMO) <= TO_DATE(:DATE_TO, 'YYYY-MM-DD')";
            $params[':DATE_TO'] = $filters['date_to'];
        }
        if ($filters['responsavel'] !== '') {
            $where[] = "UPPER(NVL(e.LOGIN_USUARIO, '')) LIKE :RESP_FILTER";
            $params[':RESP_FILTER'] = '%' . $filters['responsavel'] . '%';
        }
        if ($filters['tipo'] !== '') {
            $where[] = 'e.TIPO = :TIPO_FILTER';
            $params[':TIPO_FILTER'] = $filters['tipo'];
        }
        if ($filters['localizacao'] !== '') {
            $where[] = "UPPER(NVL(e.LOCALIZACAO, '')) LIKE :LOCAL_FILTER";
            $params[':LOCAL_FILTER'] = '%' . $filters['localizacao'] . '%';
        }

        $rows = inv_report_rows(
            $conn,
            "SELECT t.ID_TERMO,
                    e.COD_PATRIMONIO,
                    e.NOME_EQUIPAMENTO,
                    e.TIPO,
                    NVL(t.NOME_COLABORADOR, e.NOME_USUARIO) AS COLABORADOR,
                    NVL(t.SETOR_COLABORADOR, e.DEPARTAMENTO) AS SETOR,
                    NVL(t.RESPONSAVEL_TI, '-') AS RESPONSAVEL_TI,
                    t.STATUS_ASSINATURA,
                    TO_CHAR(t.DATA_TERMO, 'DD/MM/YYYY') AS DATA_TERMO_FMT,
                    TO_CHAR(t.CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM_FMT,
                    NVL(t.TERMO_HASH, '-') AS TERMO_HASH
               FROM CONSINCO.MEGAG_TI_TERMOS t
               JOIN CONSINCO.MEGAG_TI_EQUIPAMENTOS e
                 ON e.ID_EQUIPAMENTO = t.ID_EQUIPAMENTO
              WHERE " . implode(' AND ', $where) . "
              ORDER BY t.DATA_TERMO DESC, t.ID_TERMO DESC",
            $params
        );

        $cards = [
            ['label' => 'Total de termos', 'value' => count($rows)],
            ['label' => 'Assinados', 'value' => count(array_filter($rows, fn($row) => ($row['STATUS_ASSINATURA'] ?? '') === 'ASSINADO'))],
            ['label' => 'Pendentes', 'value' => count(array_filter($rows, fn($row) => ($row['STATUS_ASSINATURA'] ?? '') === 'PENDENTE'))],
        ];
        $columns = [
            ['key' => 'DATA_TERMO_FMT', 'label' => 'Data do termo'],
            ['key' => 'COD_PATRIMONIO', 'label' => 'Patrimonio'],
            ['key' => 'NOME_EQUIPAMENTO', 'label' => 'Equipamento'],
            ['key' => 'TIPO', 'label' => 'Tipo'],
            ['key' => 'COLABORADOR', 'label' => 'Colaborador'],
            ['key' => 'SETOR', 'label' => 'Setor'],
            ['key' => 'RESPONSAVEL_TI', 'label' => 'Responsavel TI'],
            ['key' => 'STATUS_ASSINATURA', 'label' => 'Status'],
            ['key' => 'TERMO_HASH', 'label' => 'Hash'],
        ];
    } else {
        $equipmentWhere = inv_report_equipment_where($filters, $params);

        switch ($type) {
            case 'EM_ESTOQUE':
                $title = 'Itens em estoque';
                $subtitle = 'Ativos disponiveis para alocacao.';
                $filename = 'relatorio_equipamentos_estoque';
                $equipmentWhere .= " AND STATUS = 'EM_ESTOQUE'";
                break;

            case 'POR_COLABORADOR':
                $title = 'Itens por colaborador';
                $subtitle = 'Distribuicao de ativos por responsavel.';
                $filename = 'relatorio_por_colaborador';
                break;

            case 'POR_TIPO':
                $title = 'Itens por categoria';
                $subtitle = 'Resumo do parque por categoria.';
                $filename = 'relatorio_por_tipo';
                break;

            case 'POR_LOCALIZACAO':
                $title = 'Itens por localizacao';
                $subtitle = 'Mapa de ativos por local fisico ou setor.';
                $filename = 'relatorio_por_localizacao';
                break;

            case 'GARANTIAS_A_VENCER':
                $title = 'Garantias a vencer';
                $subtitle = 'Equipamentos com garantia proxima do vencimento.';
                $filename = 'relatorio_garantias_a_vencer';
                $equipmentWhere .= " AND GARANTIA_ATE IS NOT NULL AND GARANTIA_ATE BETWEEN TRUNC(SYSDATE) AND TRUNC(SYSDATE) + 30";
                break;

            case 'BAIXADOS_MANUTENCAO':
                $title = 'Baixados e manutencao';
                $subtitle = 'Ativos que exigem atencao operacional.';
                $filename = 'relatorio_baixados_manutencao';
                $equipmentWhere .= " AND STATUS IN ('BAIXADO', 'MANUTENCAO')";
                break;

            case 'EM_USO':
            default:
                $title = 'Itens em uso';
                $subtitle = 'Ativos atualmente vinculados ao uso operacional.';
                $filename = 'relatorio_equipamentos_em_uso';
                $equipmentWhere .= " AND STATUS = 'EM_USO'";
                $type = 'EM_USO';
                break;
        }

        if ($type === 'POR_COLABORADOR') {
            $rows = inv_report_rows(
                $conn,
                "SELECT NVL(NOME_USUARIO, 'Sem colaborador') AS NOME_GRUPO,
                        NVL(LOGIN_USUARIO, '-') AS LOGIN_USUARIO,
                        NVL(DEPARTAMENTO, '-') AS DEPARTAMENTO,
                        COUNT(*) AS TOTAL_EQUIPAMENTOS,
                        SUM(CASE WHEN STATUS = 'EM_USO' THEN 1 ELSE 0 END) AS EM_USO,
                        SUM(CASE WHEN STATUS = 'MANUTENCAO' THEN 1 ELSE 0 END) AS MANUTENCAO,
                        SUM(NVL(VALOR_AQUISICAO, 0)) AS VALOR_TOTAL
                   FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                  WHERE {$equipmentWhere}
                  GROUP BY NOME_USUARIO, LOGIN_USUARIO, DEPARTAMENTO
                  ORDER BY COUNT(*) DESC, NVL(NOME_USUARIO, 'Sem colaborador')",
                $params
            );
            $cards = [
                ['label' => 'Colaboradores no relatorio', 'value' => count($rows)],
                ['label' => 'Equipamentos vinculados', 'value' => array_sum(array_map(fn($row) => (int)($row['TOTAL_EQUIPAMENTOS'] ?? 0), $rows))],
                ['label' => 'Valor total', 'value' => array_sum(array_map(fn($row) => (float)($row['VALOR_TOTAL'] ?? 0), $rows)), 'format' => 'currency'],
            ];
            $columns = [
                ['key' => 'NOME_GRUPO', 'label' => 'Colaborador'],
                ['key' => 'LOGIN_USUARIO', 'label' => 'Login'],
                ['key' => 'DEPARTAMENTO', 'label' => 'Departamento'],
                ['key' => 'TOTAL_EQUIPAMENTOS', 'label' => 'Qtd. equipamentos'],
                ['key' => 'EM_USO', 'label' => 'Em uso'],
                ['key' => 'MANUTENCAO', 'label' => 'Em manutencao'],
                ['key' => 'VALOR_TOTAL', 'label' => 'Valor total', 'format' => 'currency'],
            ];
        } elseif ($type === 'POR_TIPO') {
            $rows = inv_report_rows(
                $conn,
                "SELECT TIPO,
                        COUNT(*) AS TOTAL_EQUIPAMENTOS,
                        SUM(CASE WHEN STATUS = 'EM_USO' THEN 1 ELSE 0 END) AS EM_USO,
                        SUM(CASE WHEN STATUS = 'EM_ESTOQUE' THEN 1 ELSE 0 END) AS EM_ESTOQUE,
                        SUM(CASE WHEN STATUS = 'MANUTENCAO' THEN 1 ELSE 0 END) AS MANUTENCAO,
                        SUM(NVL(VALOR_AQUISICAO, 0)) AS VALOR_TOTAL
                   FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                  WHERE {$equipmentWhere}
                  GROUP BY TIPO
                  ORDER BY COUNT(*) DESC, TIPO",
                $params
            );
            foreach ($rows as &$row) {
                $row['TIPO_LABEL'] = inv_label($options, 'tipos', $row['TIPO'] ?? '');
            }
            unset($row);
            $cards = [
                ['label' => 'Tipos no relatorio', 'value' => count($rows)],
                ['label' => 'Equipamentos catalogados', 'value' => array_sum(array_map(fn($row) => (int)($row['TOTAL_EQUIPAMENTOS'] ?? 0), $rows))],
                ['label' => 'Valor total', 'value' => array_sum(array_map(fn($row) => (float)($row['VALOR_TOTAL'] ?? 0), $rows)), 'format' => 'currency'],
            ];
            $columns = [
                ['key' => 'TIPO_LABEL', 'label' => 'Tipo'],
                ['key' => 'TOTAL_EQUIPAMENTOS', 'label' => 'Qtd. equipamentos'],
                ['key' => 'EM_USO', 'label' => 'Em uso'],
                ['key' => 'EM_ESTOQUE', 'label' => 'Em estoque'],
                ['key' => 'MANUTENCAO', 'label' => 'Em manutencao'],
                ['key' => 'VALOR_TOTAL', 'label' => 'Valor total', 'format' => 'currency'],
            ];
        } elseif ($type === 'POR_LOCALIZACAO') {
            $rows = inv_report_rows(
                $conn,
                "SELECT NVL(LOCALIZACAO, 'Sem localizacao') AS LOCALIZACAO_LABEL,
                        COUNT(*) AS TOTAL_EQUIPAMENTOS,
                        SUM(CASE WHEN STATUS = 'EM_USO' THEN 1 ELSE 0 END) AS EM_USO,
                        SUM(CASE WHEN STATUS = 'EM_ESTOQUE' THEN 1 ELSE 0 END) AS EM_ESTOQUE,
                        SUM(CASE WHEN STATUS = 'MANUTENCAO' THEN 1 ELSE 0 END) AS MANUTENCAO
                   FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                  WHERE {$equipmentWhere}
                  GROUP BY LOCALIZACAO
                  ORDER BY COUNT(*) DESC, NVL(LOCALIZACAO, 'Sem localizacao')",
                $params
            );
            $cards = [
                ['label' => 'Localizacoes no relatorio', 'value' => count($rows)],
                ['label' => 'Equipamentos mapeados', 'value' => array_sum(array_map(fn($row) => (int)($row['TOTAL_EQUIPAMENTOS'] ?? 0), $rows))],
            ];
            $columns = [
                ['key' => 'LOCALIZACAO_LABEL', 'label' => 'Localizacao'],
                ['key' => 'TOTAL_EQUIPAMENTOS', 'label' => 'Qtd. equipamentos'],
                ['key' => 'EM_USO', 'label' => 'Em uso'],
                ['key' => 'EM_ESTOQUE', 'label' => 'Em estoque'],
                ['key' => 'MANUTENCAO', 'label' => 'Em manutencao'],
            ];
        } else {
            $rows = inv_report_rows(
                $conn,
                "SELECT ID_EQUIPAMENTO,
                        COD_PATRIMONIO,
                        NOME_EQUIPAMENTO,
                        TIPO,
                        STATUS,
                        NVL(NOME_USUARIO, '-') AS NOME_USUARIO,
                        NVL(LOGIN_USUARIO, '-') AS LOGIN_USUARIO,
                        NVL(LOCALIZACAO, '-') AS LOCALIZACAO,
                        TO_CHAR(DATA_AQUISICAO, 'DD/MM/YYYY') AS DATA_AQUISICAO_FMT,
                        TO_CHAR(GARANTIA_ATE, 'DD/MM/YYYY') AS GARANTIA_ATE_FMT,
                        NVL(VALOR_AQUISICAO, 0) AS VALOR_AQUISICAO,
                        NVL(NUMERO_SERIE, '-') AS NUMERO_SERIE
                   FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                  WHERE {$equipmentWhere}
                  ORDER BY NVL(ALTERADO_EM, CRIADO_EM) DESC, NOME_EQUIPAMENTO",
                $params
            );
            foreach ($rows as &$row) {
                $row['STATUS_LABEL'] = inv_label($options, 'status', $row['STATUS'] ?? '');
                $row['TIPO_LABEL'] = inv_label($options, 'tipos', $row['TIPO'] ?? '');
            }
            unset($row);
            $cards = [
                ['label' => 'Total de equipamentos', 'value' => count($rows)],
                ['label' => 'Valor total', 'value' => array_sum(array_map(fn($row) => (float)($row['VALOR_AQUISICAO'] ?? 0), $rows)), 'format' => 'currency'],
                ['label' => 'Colaboradores vinculados', 'value' => count(array_unique(array_filter(array_map(fn($row) => trim((string)($row['LOGIN_USUARIO'] ?? '')), $rows))))],
            ];
            $columns = [
                ['key' => 'COD_PATRIMONIO', 'label' => 'Patrimonio'],
                ['key' => 'NUMERO_SERIE', 'label' => 'Numero de serie'],
                ['key' => 'NOME_EQUIPAMENTO', 'label' => 'Equipamento'],
                ['key' => 'TIPO_LABEL', 'label' => 'Tipo'],
                ['key' => 'STATUS_LABEL', 'label' => 'Status'],
                ['key' => 'NOME_USUARIO', 'label' => 'Colaborador'],
                ['key' => 'LOCALIZACAO', 'label' => 'Localizacao'],
                ['key' => 'GARANTIA_ATE_FMT', 'label' => 'Garantia ate'],
                ['key' => 'VALOR_AQUISICAO', 'label' => 'Valor', 'format' => 'currency'],
            ];
        }
    }

    return [
        'meta' => [
            'title' => $title,
            'subtitle' => $subtitle,
            'report_type' => $type,
            'generated_at' => date('d/m/Y H:i'),
            'filename' => $filename . '_' . date('Ymd_His'),
        ],
        'filters' => $filters,
        'cards' => $cards,
        'columns' => $columns,
        'rows' => $rows,
    ];
}

function inv_list(PDO $conn): array
{
    $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => strtoupper(trim((string)($_GET['status'] ?? ''))),
        'tipo' => strtoupper(trim((string)($_GET['tipo'] ?? ''))),
        'responsavel' => strtoupper(trim((string)($_GET['responsavel'] ?? ''))),
    ];

    $sql = "SELECT ID_EQUIPAMENTO,
                   COD_PATRIMONIO,
                   NOME_EQUIPAMENTO,
                   TIPO,
                   MARCA,
                   MODELO,
                   NUMERO_SERIE,
                   STATUS,
                   CONDICAO,
                   LOGIN_USUARIO,
                   NOME_USUARIO,
                   CPF_USUARIO,
                   DEPARTAMENTO,
                   LOCALIZACAO,
                   FORNECEDOR,
                   NOTA_FISCAL,
                   IP_EQUIPAMENTO,
                   ITENS_ENTREGUES,
                   OBSERVACAO,
                   TO_CHAR(DATA_AQUISICAO, 'YYYY-MM-DD') AS DATA_AQUISICAO,
                   TO_CHAR(GARANTIA_ATE, 'YYYY-MM-DD') AS GARANTIA_ATE,
                   NVL(VALOR_AQUISICAO, 0) AS VALOR_AQUISICAO,
                   TO_CHAR(ALTERADO_EM, 'DD/MM/YYYY HH24:MI') AS ALTERADO_EM_FMT,
                   CASE
                     WHEN GARANTIA_ATE IS NULL THEN 'SEM_GARANTIA'
                     WHEN GARANTIA_ATE < TRUNC(SYSDATE) THEN 'VENCIDA'
                     WHEN GARANTIA_ATE BETWEEN TRUNC(SYSDATE) AND TRUNC(SYSDATE) + 30 THEN 'PROXIMA'
                     ELSE 'OK'
                   END AS ALERTA_GARANTIA
              FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
             WHERE ATIVO = 'S'
               AND (:STATUS_NULL IS NULL OR STATUS = :STATUS_VALUE)
               AND (:TIPO_NULL IS NULL OR TIPO = :TIPO_VALUE)
               AND (:RESP_NULL IS NULL OR UPPER(NVL(LOGIN_USUARIO, '')) LIKE :RESP_LIKE)
               AND (
                    :Q_NULL IS NULL
                    OR UPPER(NVL(COD_PATRIMONIO, '')) LIKE :Q_LIKE_1
                    OR UPPER(NVL(NOME_EQUIPAMENTO, '')) LIKE :Q_LIKE_2
                    OR UPPER(NVL(MODELO, '')) LIKE :Q_LIKE_3
                    OR UPPER(NVL(NUMERO_SERIE, '')) LIKE :Q_LIKE_4
                    OR UPPER(NVL(NOME_USUARIO, '')) LIKE :Q_LIKE_5
                    OR UPPER(NVL(LOGIN_USUARIO, '')) LIKE :Q_LIKE_6
                    OR UPPER(NVL(LOCALIZACAO, '')) LIKE :Q_LIKE_7
                   )
             ORDER BY
                   CASE STATUS
                     WHEN 'MANUTENCAO' THEN 1
                     WHEN 'EM_USO' THEN 2
                     WHEN 'EM_ESTOQUE' THEN 3
                     ELSE 4
                   END,
                   NVL(ALTERADO_EM, CRIADO_EM) DESC,
                   NOME_EQUIPAMENTO";

    $stmt = $conn->prepare($sql);
    $status = $filters['status'] !== '' ? $filters['status'] : null;
    $tipo = $filters['tipo'] !== '' ? $filters['tipo'] : null;
    $resp = $filters['responsavel'] !== '' ? $filters['responsavel'] : null;
    $respLike = $resp !== null ? '%' . $resp . '%' : null;
    $q = $filters['q'] !== '' ? strtoupper($filters['q']) : null;
    $qLike = $q !== null ? '%' . $q . '%' : null;

    $stmt->bindValue(':STATUS_NULL', $status);
    $stmt->bindValue(':STATUS_VALUE', $status);
    $stmt->bindValue(':TIPO_NULL', $tipo);
    $stmt->bindValue(':TIPO_VALUE', $tipo);
    $stmt->bindValue(':RESP_NULL', $resp);
    $stmt->bindValue(':RESP_LIKE', $respLike);
    $stmt->bindValue(':Q_NULL', $q);
    $stmt->bindValue(':Q_LIKE_1', $qLike);
    $stmt->bindValue(':Q_LIKE_2', $qLike);
    $stmt->bindValue(':Q_LIKE_3', $qLike);
    $stmt->bindValue(':Q_LIKE_4', $qLike);
    $stmt->bindValue(':Q_LIKE_5', $qLike);
    $stmt->bindValue(':Q_LIKE_6', $qLike);
    $stmt->bindValue(':Q_LIKE_7', $qLike);
    $stmt->execute();

    $options = inv_options();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['ALERTA_GARANTIA_LABEL'] = match ($row['ALERTA_GARANTIA'] ?? '') {
            'VENCIDA' => 'Garantia vencida',
            'PROXIMA' => 'Vence em até 30 dias',
            'OK' => 'Dentro da garantia',
            default => 'Sem garantia',
        };
        $row['STATUS_LABEL'] = inv_label($options, 'status', $row['STATUS'] ?? '');
        $row['TIPO_LABEL'] = inv_label($options, 'tipos', $row['TIPO'] ?? '');
    }
    unset($row);

    return $rows;
}

function inv_get(PDO $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT ID_EQUIPAMENTO,
                                   COD_PATRIMONIO,
                                   NOME_EQUIPAMENTO,
                                   TIPO,
                                   MARCA,
                                   MODELO,
                                   NUMERO_SERIE,
                                   STATUS,
                                   CONDICAO,
                                   LOGIN_USUARIO,
                                   NOME_USUARIO,
                                   CPF_USUARIO,
                                   DEPARTAMENTO,
                                   LOCALIZACAO,
                                   FORNECEDOR,
                                   NOTA_FISCAL,
                                   IP_EQUIPAMENTO,
                                   ITENS_ENTREGUES,
                                   OBSERVACAO,
                                   TO_CHAR(DATA_AQUISICAO, 'YYYY-MM-DD') AS DATA_AQUISICAO,
                                   TO_CHAR(GARANTIA_ATE, 'YYYY-MM-DD') AS GARANTIA_ATE,
                                   NVL(VALOR_AQUISICAO, 0) AS VALOR_AQUISICAO
                              FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                             WHERE ID_EQUIPAMENTO = :ID
                               AND ATIVO = 'S'");
    $stmt->bindValue(':ID', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Equipamento não encontrado.');
    }

    return $row;
}

function inv_build_resume(array $before, array $after): string
{
    $changes = [];

    if (($before['STATUS'] ?? '') !== ($after['status'] ?? '')) {
        $changes[] = 'status';
    }
    if (($before['LOGIN_USUARIO'] ?? '') !== ($after['login_usuario'] ?? '')) {
        $changes[] = 'responsável';
    }
    if (($before['LOCALIZACAO'] ?? '') !== ($after['localizacao'] ?? '')) {
        $changes[] = 'localização';
    }
    if (($before['CONDICAO'] ?? '') !== ($after['condicao'] ?? '')) {
        $changes[] = 'condição';
    }

    if (!$changes) {
        return 'Atualização cadastral sem mudança de status, responsável ou localização.';
    }

    return 'Campos alterados: ' . implode(', ', $changes) . '.';
}

function inv_log_history(PDO $conn, int $idEquipamento, string $tipo, array $before, array $after, string $user): void
{
    $idHistorico = inv_nextval($conn, 'CONSINCO.SEQ_MEGAG_TI_EQUIP_HIST');
    $options = inv_options();
    $resumo = $tipo === 'CADASTRO'
        ? 'Equipamento cadastrado no inventário.'
        : inv_build_resume($before, $after);

    $stmt = $conn->prepare("INSERT INTO CONSINCO.MEGAG_TI_EQUIP_HIST (
                                ID_HISTORICO,
                                ID_EQUIPAMENTO,
                                TIPO_MOVIMENTACAO,
                                STATUS_ANTERIOR,
                                STATUS_NOVO,
                                LOGIN_USUARIO_ANTERIOR,
                                LOGIN_USUARIO_NOVO,
                                LOCALIZACAO_ANTERIOR,
                                LOCALIZACAO_NOVA,
                                OBSERVACAO,
                                DESCRICAO_RESUMO,
                                CRIADO_POR,
                                CRIADO_EM
                            ) VALUES (
                                :ID_HISTORICO,
                                :ID_EQUIPAMENTO,
                                :TIPO_MOVIMENTACAO,
                                :STATUS_ANTERIOR,
                                :STATUS_NOVO,
                                :LOGIN_USUARIO_ANTERIOR,
                                :LOGIN_USUARIO_NOVO,
                                :LOCALIZACAO_ANTERIOR,
                                :LOCALIZACAO_NOVA,
                                :OBSERVACAO,
                                :DESCRICAO_RESUMO,
                                :CRIADO_POR,
                                SYSDATE
                            )");
    $stmt->execute([
        ':ID_HISTORICO' => $idHistorico,
        ':ID_EQUIPAMENTO' => $idEquipamento,
        ':TIPO_MOVIMENTACAO' => $tipo,
        ':STATUS_ANTERIOR' => $before['STATUS'] ?? null,
        ':STATUS_NOVO' => $after['status'] ?? null,
        ':LOGIN_USUARIO_ANTERIOR' => $before['LOGIN_USUARIO'] ?? null,
        ':LOGIN_USUARIO_NOVO' => $after['login_usuario'] ?? null,
        ':LOCALIZACAO_ANTERIOR' => $before['LOCALIZACAO'] ?? null,
        ':LOCALIZACAO_NOVA' => $after['localizacao'] ?? null,
        ':OBSERVACAO' => trim((string)($after['observacao'] ?? '')) ?: null,
        ':DESCRICAO_RESUMO' => $resumo . ' Status: ' . inv_label($options, 'status', $after['status'] ?? ''),
        ':CRIADO_POR' => $user,
    ]);
}

function inv_validate_payload(array $payload): array
{
    $payload['cod_patrimonio'] = strtoupper(trim((string)($payload['cod_patrimonio'] ?? '')));
    $payload['nome_equipamento'] = trim((string)($payload['nome_equipamento'] ?? ''));
    $payload['tipo'] = strtoupper(trim((string)($payload['tipo'] ?? '')));
    $payload['status'] = strtoupper(trim((string)($payload['status'] ?? '')));
    $payload['marca'] = trim((string)($payload['marca'] ?? ''));
    $payload['modelo'] = trim((string)($payload['modelo'] ?? ''));
    $payload['numero_serie'] = trim((string)($payload['numero_serie'] ?? ''));
    $payload['condicao'] = strtoupper(trim((string)($payload['condicao'] ?? '')));
    $payload['login_usuario'] = strtoupper(trim((string)($payload['login_usuario'] ?? '')));
    $payload['nome_usuario'] = trim((string)($payload['nome_usuario'] ?? ''));
    $payload['cpf_usuario'] = preg_replace('/\D+/', '', (string)($payload['cpf_usuario'] ?? ''));
    $payload['departamento'] = trim((string)($payload['departamento'] ?? ''));
    $payload['localizacao'] = trim((string)($payload['localizacao'] ?? ''));
    $payload['fornecedor'] = trim((string)($payload['fornecedor'] ?? ''));
    $payload['nota_fiscal'] = trim((string)($payload['nota_fiscal'] ?? ''));
    $payload['ip_equipamento'] = trim((string)($payload['ip_equipamento'] ?? ''));
    $payload['itens_entregues'] = trim((string)($payload['itens_entregues'] ?? ''));
    $payload['observacao'] = trim((string)($payload['observacao'] ?? ''));
    $payload['valor_aquisicao'] = ($payload['valor_aquisicao'] ?? '') !== '' ? (float)$payload['valor_aquisicao'] : null;
    $payload['id_equipamento'] = !empty($payload['id_equipamento']) ? (int)$payload['id_equipamento'] : null;
    $payload['data_aquisicao'] = trim((string)($payload['data_aquisicao'] ?? '')) ?: null;
    $payload['garantia_ate'] = trim((string)($payload['garantia_ate'] ?? '')) ?: null;

    if ($payload['cod_patrimonio'] === '' || $payload['nome_equipamento'] === '' || $payload['tipo'] === '' || $payload['status'] === '') {
        throw new Exception('Patrimônio, nome do equipamento, tipo e status são obrigatórios.');
    }

    return $payload;
}

function inv_save(PDO $conn, array $payload, string $user): array
{
    $payload = inv_validate_payload($payload);

    $stmtDuplicidade = $conn->prepare("SELECT ID_EQUIPAMENTO
                                         FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                                        WHERE COD_PATRIMONIO = :COD
                                          AND ATIVO = 'S'
                                          AND (:ID_NULL IS NULL OR ID_EQUIPAMENTO <> :ID_VALUE)");
    $stmtDuplicidade->bindValue(':COD', $payload['cod_patrimonio']);
    $stmtDuplicidade->bindValue(':ID_NULL', $payload['id_equipamento']);
    $stmtDuplicidade->bindValue(':ID_VALUE', $payload['id_equipamento']);
    $stmtDuplicidade->execute();
    if ($stmtDuplicidade->fetchColumn()) {
        throw new Exception('Já existe um equipamento ativo com este código de patrimônio.');
    }

    $conn->beginTransaction();

    try {
        if ($payload['id_equipamento']) {
            $stmtAtual = $conn->prepare("SELECT *
                                           FROM CONSINCO.MEGAG_TI_EQUIPAMENTOS
                                          WHERE ID_EQUIPAMENTO = :ID
                                          FOR UPDATE");
            $stmtAtual->bindValue(':ID', $payload['id_equipamento'], PDO::PARAM_INT);
            $stmtAtual->execute();
            $before = $stmtAtual->fetch(PDO::FETCH_ASSOC);

            if (!$before) {
                throw new Exception('Equipamento não encontrado para atualização.');
            }

            $stmt = $conn->prepare("UPDATE CONSINCO.MEGAG_TI_EQUIPAMENTOS
                                       SET COD_PATRIMONIO = :COD_PATRIMONIO,
                                           NOME_EQUIPAMENTO = :NOME_EQUIPAMENTO,
                                           TIPO = :TIPO,
                                           MARCA = :MARCA,
                                           MODELO = :MODELO,
                                           NUMERO_SERIE = :NUMERO_SERIE,
                                           STATUS = :STATUS,
                                           CONDICAO = :CONDICAO,
                                           LOGIN_USUARIO = :LOGIN_USUARIO,
                                           NOME_USUARIO = :NOME_USUARIO,
                                           CPF_USUARIO = :CPF_USUARIO,
                                           DEPARTAMENTO = :DEPARTAMENTO,
                                           LOCALIZACAO = :LOCALIZACAO,
                                           DATA_AQUISICAO = CASE WHEN :DATA_AQUISICAO_NULL IS NULL THEN NULL ELSE TO_DATE(:DATA_AQUISICAO_VAL, 'YYYY-MM-DD') END,
                                           GARANTIA_ATE = CASE WHEN :GARANTIA_ATE_NULL IS NULL THEN NULL ELSE TO_DATE(:GARANTIA_ATE_VAL, 'YYYY-MM-DD') END,
                                           VALOR_AQUISICAO = :VALOR_AQUISICAO,
                                           FORNECEDOR = :FORNECEDOR,
                                           NOTA_FISCAL = :NOTA_FISCAL,
                                           IP_EQUIPAMENTO = :IP_EQUIPAMENTO,
                                           ITENS_ENTREGUES = :ITENS_ENTREGUES,
                                           OBSERVACAO = :OBSERVACAO,
                                           ALTERADO_POR = :ALTERADO_POR,
                                           ALTERADO_EM = SYSDATE
                                     WHERE ID_EQUIPAMENTO = :ID_EQUIPAMENTO");
            $stmt->execute([
                ':COD_PATRIMONIO' => $payload['cod_patrimonio'],
                ':NOME_EQUIPAMENTO' => $payload['nome_equipamento'],
                ':TIPO' => $payload['tipo'],
                ':MARCA' => $payload['marca'] ?: null,
                ':MODELO' => $payload['modelo'] ?: null,
                ':NUMERO_SERIE' => $payload['numero_serie'] ?: null,
                ':STATUS' => $payload['status'],
                ':CONDICAO' => $payload['condicao'] ?: null,
                ':LOGIN_USUARIO' => $payload['login_usuario'] ?: null,
                ':NOME_USUARIO' => $payload['nome_usuario'] ?: null,
                ':CPF_USUARIO' => $payload['cpf_usuario'] ?: null,
                ':DEPARTAMENTO' => $payload['departamento'] ?: null,
                ':LOCALIZACAO' => $payload['localizacao'] ?: null,
                ':DATA_AQUISICAO_NULL' => $payload['data_aquisicao'],
                ':DATA_AQUISICAO_VAL' => $payload['data_aquisicao'],
                ':GARANTIA_ATE_NULL' => $payload['garantia_ate'],
                ':GARANTIA_ATE_VAL' => $payload['garantia_ate'],
                ':VALOR_AQUISICAO' => $payload['valor_aquisicao'],
                ':FORNECEDOR' => $payload['fornecedor'] ?: null,
                ':NOTA_FISCAL' => $payload['nota_fiscal'] ?: null,
                ':IP_EQUIPAMENTO' => $payload['ip_equipamento'] ?: null,
                ':ITENS_ENTREGUES' => $payload['itens_entregues'] ?: null,
                ':OBSERVACAO' => $payload['observacao'] ?: null,
                ':ALTERADO_POR' => $user,
                ':ID_EQUIPAMENTO' => $payload['id_equipamento'],
            ]);

            inv_log_history($conn, $payload['id_equipamento'], 'ATUALIZACAO', $before, $payload, $user);
            $idEquipamento = $payload['id_equipamento'];
        } else {
            $idEquipamento = inv_nextval($conn, 'CONSINCO.SEQ_MEGAG_TI_EQUIPAMENTOS');

            $stmt = $conn->prepare("INSERT INTO CONSINCO.MEGAG_TI_EQUIPAMENTOS (
                                        ID_EQUIPAMENTO,
                                        COD_PATRIMONIO,
                                        NOME_EQUIPAMENTO,
                                        TIPO,
                                        MARCA,
                                        MODELO,
                                        NUMERO_SERIE,
                                        STATUS,
                                        CONDICAO,
                                        LOGIN_USUARIO,
                                        NOME_USUARIO,
                                        CPF_USUARIO,
                                        DEPARTAMENTO,
                                        LOCALIZACAO,
                                        DATA_AQUISICAO,
                                        GARANTIA_ATE,
                                        VALOR_AQUISICAO,
                                        FORNECEDOR,
                                        NOTA_FISCAL,
                                        IP_EQUIPAMENTO,
                                        ITENS_ENTREGUES,
                                        OBSERVACAO,
                                        ATIVO,
                                        CRIADO_POR,
                                        CRIADO_EM,
                                        ALTERADO_POR,
                                        ALTERADO_EM
                                    ) VALUES (
                                        :ID_EQUIPAMENTO,
                                        :COD_PATRIMONIO,
                                        :NOME_EQUIPAMENTO,
                                        :TIPO,
                                        :MARCA,
                                        :MODELO,
                                        :NUMERO_SERIE,
                                        :STATUS,
                                        :CONDICAO,
                                        :LOGIN_USUARIO,
                                        :NOME_USUARIO,
                                        :CPF_USUARIO,
                                        :DEPARTAMENTO,
                                        :LOCALIZACAO,
                                        CASE WHEN :DATA_AQUISICAO_NULL IS NULL THEN NULL ELSE TO_DATE(:DATA_AQUISICAO_VAL, 'YYYY-MM-DD') END,
                                        CASE WHEN :GARANTIA_ATE_NULL IS NULL THEN NULL ELSE TO_DATE(:GARANTIA_ATE_VAL, 'YYYY-MM-DD') END,
                                        :VALOR_AQUISICAO,
                                        :FORNECEDOR,
                                        :NOTA_FISCAL,
                                        :IP_EQUIPAMENTO,
                                        :ITENS_ENTREGUES,
                                        :OBSERVACAO,
                                        'S',
                                        :CRIADO_POR,
                                        SYSDATE,
                                        :ALTERADO_POR,
                                        SYSDATE
                                    )");
            $stmt->execute([
                ':ID_EQUIPAMENTO' => $idEquipamento,
                ':COD_PATRIMONIO' => $payload['cod_patrimonio'],
                ':NOME_EQUIPAMENTO' => $payload['nome_equipamento'],
                ':TIPO' => $payload['tipo'],
                ':MARCA' => $payload['marca'] ?: null,
                ':MODELO' => $payload['modelo'] ?: null,
                ':NUMERO_SERIE' => $payload['numero_serie'] ?: null,
                ':STATUS' => $payload['status'],
                ':CONDICAO' => $payload['condicao'] ?: null,
                ':LOGIN_USUARIO' => $payload['login_usuario'] ?: null,
                ':NOME_USUARIO' => $payload['nome_usuario'] ?: null,
                ':CPF_USUARIO' => $payload['cpf_usuario'] ?: null,
                ':DEPARTAMENTO' => $payload['departamento'] ?: null,
                ':LOCALIZACAO' => $payload['localizacao'] ?: null,
                ':DATA_AQUISICAO_NULL' => $payload['data_aquisicao'],
                ':DATA_AQUISICAO_VAL' => $payload['data_aquisicao'],
                ':GARANTIA_ATE_NULL' => $payload['garantia_ate'],
                ':GARANTIA_ATE_VAL' => $payload['garantia_ate'],
                ':VALOR_AQUISICAO' => $payload['valor_aquisicao'],
                ':FORNECEDOR' => $payload['fornecedor'] ?: null,
                ':NOTA_FISCAL' => $payload['nota_fiscal'] ?: null,
                ':IP_EQUIPAMENTO' => $payload['ip_equipamento'] ?: null,
                ':ITENS_ENTREGUES' => $payload['itens_entregues'] ?: null,
                ':OBSERVACAO' => $payload['observacao'] ?: null,
                ':CRIADO_POR' => $user,
                ':ALTERADO_POR' => $user,
            ]);

            inv_log_history($conn, $idEquipamento, 'CADASTRO', [], $payload, $user);
        }

        $conn->commit();
        return inv_get($conn, $idEquipamento);
    } catch (Throwable $e) {
        $conn->rollBack();
        throw $e;
    }
}

function inv_history(PDO $conn, int $id): array
{
    $options = inv_options();
    $stmt = $conn->prepare("SELECT ID_HISTORICO,
                                   TIPO_MOVIMENTACAO,
                                   STATUS_ANTERIOR,
                                   STATUS_NOVO,
                                   LOGIN_USUARIO_ANTERIOR,
                                   LOGIN_USUARIO_NOVO,
                                   LOCALIZACAO_ANTERIOR,
                                   LOCALIZACAO_NOVA,
                                   OBSERVACAO,
                                   DESCRICAO_RESUMO,
                                   CRIADO_POR,
                                   TO_CHAR(CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM_FMT
                              FROM CONSINCO.MEGAG_TI_EQUIP_HIST
                             WHERE ID_EQUIPAMENTO = :ID
                             ORDER BY CRIADO_EM DESC, ID_HISTORICO DESC");
    $stmt->bindValue(':ID', $id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['TIPO_MOVIMENTACAO_LABEL'] = match ($row['TIPO_MOVIMENTACAO'] ?? '') {
            'CADASTRO' => 'Cadastro inicial',
            'ATUALIZACAO' => 'Atualização cadastral',
            default => 'Movimentação',
        };

        $extra = [];
        if (!empty($row['STATUS_NOVO'])) {
            $extra[] = 'Status atual: ' . inv_label($options, 'status', $row['STATUS_NOVO']);
        }
        if (!empty($row['LOGIN_USUARIO_NOVO'])) {
            $extra[] = 'Responsável: ' . $row['LOGIN_USUARIO_NOVO'];
        }
        if (!empty($row['LOCALIZACAO_NOVA'])) {
            $extra[] = 'Localização: ' . $row['LOCALIZACAO_NOVA'];
        }
        if ($extra) {
            $row['DESCRICAO_RESUMO'] = trim((string)($row['DESCRICAO_RESUMO'] ?? '')) . ' ' . implode(' • ', $extra);
        }
    }
    unset($row);

    return $rows;
}

function inv_get_term(PDO $conn, int $idEquipamento): array
{
    $equipamento = inv_get($conn, $idEquipamento);

    $stmt = $conn->prepare("SELECT ID_TERMO,
                                   ID_EQUIPAMENTO,
                                   NOME_COLABORADOR,
                                   CPF_COLABORADOR,
                                   SETOR_COLABORADOR,
                                   CIDADE_EMISSAO,
                                   TO_CHAR(DATA_TERMO, 'YYYY-MM-DD') AS DATA_TERMO,
                                   RESPONSAVEL_TI,
                                   STATUS_ASSINATURA,
                                   ASSINATURA_COLABORADOR,
                                   ASSINATURA_TI,
                                   TERMO_HTML,
                                   TERMO_HASH,
                                   CRIADO_POR,
                                   TO_CHAR(CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM_FMT
                              FROM CONSINCO.MEGAG_TI_TERMOS
                             WHERE ID_EQUIPAMENTO = :ID
                             ORDER BY DATA_TERMO DESC, ID_TERMO DESC");
    $stmt->bindValue(':ID', $idEquipamento, PDO::PARAM_INT);
    $stmt->execute();
    $termo = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$termo) {
        $termo = [
            'ID_TERMO' => null,
            'ID_EQUIPAMENTO' => $idEquipamento,
            'NOME_COLABORADOR' => $equipamento['NOME_USUARIO'] ?? '',
            'CPF_COLABORADOR' => $equipamento['CPF_USUARIO'] ?? '',
            'SETOR_COLABORADOR' => $equipamento['DEPARTAMENTO'] ?? '',
            'CIDADE_EMISSAO' => 'São Paulo',
            'DATA_TERMO' => date('Y-m-d'),
            'RESPONSAVEL_TI' => inv_user(),
            'STATUS_ASSINATURA' => 'PENDENTE',
            'ASSINATURA_COLABORADOR' => null,
            'ASSINATURA_TI' => null,
            'TERMO_HTML' => null,
            'TERMO_HASH' => null,
            'CRIADO_POR' => inv_user(),
            'CRIADO_EM_FMT' => null,
        ];
    }

    return [
        'equipamento' => $equipamento,
        'termo' => $termo,
    ];
}

function inv_save_term(PDO $conn, array $payload, string $user): array
{
    $idEquipamento = (int)($payload['id_equipamento'] ?? 0);
    if ($idEquipamento <= 0) {
        throw new Exception('Equipamento inválido para gerar termo.');
    }

    $bundle = inv_get_term($conn, $idEquipamento);
    $equipamento = $bundle['equipamento'];

    $nomeColaborador = trim((string)($payload['nome_colaborador'] ?? $equipamento['NOME_USUARIO'] ?? ''));
    $cpfColaborador = preg_replace('/\D+/', '', (string)($payload['cpf_colaborador'] ?? $equipamento['CPF_USUARIO'] ?? ''));
    $setorColaborador = trim((string)($payload['setor_colaborador'] ?? $equipamento['DEPARTAMENTO'] ?? ''));
    $cidadeEmissao = trim((string)($payload['cidade_emissao'] ?? 'São Paulo'));
    $dataTermo = trim((string)($payload['data_termo'] ?? date('Y-m-d')));
    $responsavelTi = trim((string)($payload['responsavel_ti'] ?? $user));
    $assinaturaColaborador = trim((string)($payload['assinatura_colaborador'] ?? ''));
    $assinaturaTi = trim((string)($payload['assinatura_ti'] ?? ''));
    $termoHtml = inv_sanitize_html((string)($payload['termo_html'] ?? ''));

    if ($nomeColaborador === '') {
        throw new Exception('Informe o nome do colaborador para o termo.');
    }

    if ($responsavelTi === '') {
        throw new Exception('Informe o responsável de TI no termo.');
    }

    if ($assinaturaColaborador === '' || $assinaturaTi === '') {
        throw new Exception('Colete as duas assinaturas antes de salvar o termo.');
    }

    $status = 'ASSINADO';
    $hash = hash('sha256', implode('|', [
        $idEquipamento,
        $nomeColaborador,
        $cpfColaborador,
        $setorColaborador,
        $cidadeEmissao,
        $dataTermo,
        $responsavelTi,
        $assinaturaColaborador,
        $assinaturaTi,
        $termoHtml,
    ]));

    $idTermo = inv_nextval($conn, 'CONSINCO.SEQ_MEGAG_TI_TERMOS');

    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("INSERT INTO CONSINCO.MEGAG_TI_TERMOS (
                                    ID_TERMO,
                                    ID_EQUIPAMENTO,
                                    NOME_COLABORADOR,
                                    CPF_COLABORADOR,
                                    SETOR_COLABORADOR,
                                    CIDADE_EMISSAO,
                                    DATA_TERMO,
                                    RESPONSAVEL_TI,
                                    STATUS_ASSINATURA,
                                    ASSINATURA_COLABORADOR,
                                    ASSINATURA_TI,
                                    TERMO_HTML,
                                    TERMO_HASH,
                                    CRIADO_POR,
                                    CRIADO_EM
                                ) VALUES (
                                    :ID_TERMO,
                                    :ID_EQUIPAMENTO,
                                    :NOME_COLABORADOR,
                                    :CPF_COLABORADOR,
                                    :SETOR_COLABORADOR,
                                    :CIDADE_EMISSAO,
                                    TO_DATE(:DATA_TERMO, 'YYYY-MM-DD'),
                                    :RESPONSAVEL_TI,
                                    :STATUS_ASSINATURA,
                                    :ASSINATURA_COLABORADOR,
                                    :ASSINATURA_TI,
                                    :TERMO_HTML,
                                    :TERMO_HASH,
                                    :CRIADO_POR,
                                    SYSDATE
                                )");
        $stmt->execute([
            ':ID_TERMO' => $idTermo,
            ':ID_EQUIPAMENTO' => $idEquipamento,
            ':NOME_COLABORADOR' => $nomeColaborador,
            ':CPF_COLABORADOR' => $cpfColaborador ?: null,
            ':SETOR_COLABORADOR' => $setorColaborador ?: null,
            ':CIDADE_EMISSAO' => $cidadeEmissao ?: null,
            ':DATA_TERMO' => $dataTermo,
            ':RESPONSAVEL_TI' => $responsavelTi,
            ':STATUS_ASSINATURA' => $status,
            ':ASSINATURA_COLABORADOR' => $assinaturaColaborador,
            ':ASSINATURA_TI' => $assinaturaTi,
            ':TERMO_HTML' => $termoHtml ?: null,
            ':TERMO_HASH' => $hash,
            ':CRIADO_POR' => $user,
        ]);

        inv_log_history($conn, $idEquipamento, 'ATUALIZACAO', $equipamento, [
            'status' => $equipamento['STATUS'] ?? '',
            'login_usuario' => $equipamento['LOGIN_USUARIO'] ?? '',
            'localizacao' => $equipamento['LOCALIZACAO'] ?? '',
            'condicao' => $equipamento['CONDICAO'] ?? '',
            'observacao' => 'Termo de responsabilidade assinado digitalmente. Hash: ' . $hash,
        ], $user);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        throw $e;
    }

    return inv_get_term($conn, $idEquipamento);
}

function inv_request_validate_payload(array $payload): array
{
    $payload['id_solicitacao'] = !empty($payload['id_solicitacao']) ? (int)$payload['id_solicitacao'] : null;
    $payload['solicitante_nome'] = trim((string)($payload['solicitante_nome'] ?? ''));
    $payload['solicitante_login'] = strtoupper(trim((string)($payload['solicitante_login'] ?? inv_user())));
    $payload['solicitante_cpf'] = preg_replace('/\D+/', '', (string)($payload['solicitante_cpf'] ?? ''));
    $payload['setor_solicitante'] = trim((string)($payload['setor_solicitante'] ?? ''));
    $payload['centro_custo'] = trim((string)($payload['centro_custo'] ?? ''));
    $payload['seq_centro_resultado'] = !empty($payload['seq_centro_resultado']) ? (int)$payload['seq_centro_resultado'] : null;
    $payload['filial'] = trim((string)($payload['filial'] ?? ''));
    $payload['itens_solicitados'] = trim((string)($payload['itens_solicitados'] ?? ''));
    $payload['item_solicitado'] = trim((string)($payload['item_solicitado'] ?? ''));
    $payload['descricao_item'] = trim((string)($payload['descricao_item'] ?? ''));
    $payload['unidade_medida'] = trim((string)($payload['unidade_medida'] ?? ''));
    $payload['quantidade'] = (float)($payload['quantidade'] ?? 0);
    $payload['local_entrega'] = trim((string)($payload['local_entrega'] ?? ''));
    $payload['data_necessidade'] = trim((string)($payload['data_necessidade'] ?? '')) ?: null;
    $payload['prioridade'] = strtoupper(trim((string)($payload['prioridade'] ?? 'MEDIA')));
    $payload['status'] = strtoupper(trim((string)($payload['status'] ?? 'SOLICITADO')));
    $payload['justificativa'] = trim((string)($payload['justificativa'] ?? ''));
    $payload['observacao'] = trim((string)($payload['observacao'] ?? ''));
    $payload['responsavel_destino'] = trim((string)($payload['responsavel_destino'] ?? ''));
    $payload['responsavel_almox'] = trim((string)($payload['responsavel_almox'] ?? ''));
    $payload['assinatura_solicitante'] = trim((string)($payload['assinatura_solicitante'] ?? ''));
    $payload['assinatura_almox'] = trim((string)($payload['assinatura_almox'] ?? ''));

    if ($payload['item_solicitado'] === '' && $payload['itens_solicitados'] !== '') {
        $payload['item_solicitado'] = substr((string)strtok(str_replace(["\r\n", "\r"], "\n", $payload['itens_solicitados']), "\n"), 0, 200);
    }

    if ($payload['solicitante_nome'] === '') {
        throw new Exception('Informe o nome do solicitante.');
    }
    if ($payload['centro_custo'] === '') {
        throw new Exception('Informe o centro de custo da requisicao.');
    }
    if ($payload['filial'] === '') {
        throw new Exception('Informe a filial da requisicao.');
    }
    if ($payload['itens_solicitados'] === '') {
        throw new Exception('Descreva os itens solicitados ao almoxarifado.');
    }
    if ($payload['item_solicitado'] === '') {
        throw new Exception('Nao foi possivel identificar o item principal da solicitacao.');
    }
    if ($payload['quantidade'] <= 0) {
        throw new Exception('Informe uma quantidade valida para a solicitacao.');
    }
    if ($payload['justificativa'] === '') {
        throw new Exception('Descreva a justificativa da solicitacao.');
    }
    if ($payload['assinatura_solicitante'] === '') {
        throw new Exception('Colete a assinatura digital do solicitante antes de salvar.');
    }

    $allowedStatuses = array_column(inv_options()['request_status'], 'valor');
    if (!in_array($payload['status'], $allowedStatuses, true)) {
        $payload['status'] = 'SOLICITADO';
    }

    $allowedPriorities = array_column(inv_options()['request_priorities'], 'valor');
    if (!in_array($payload['prioridade'], $allowedPriorities, true)) {
        $payload['prioridade'] = 'MEDIA';
    }

    if ($payload['status'] === 'ATENDIDO') {
        if ($payload['responsavel_almox'] === '') {
            throw new Exception('Informe o responsavel do almoxarifado para concluir a entrega.');
        }
        if ($payload['assinatura_almox'] === '') {
            throw new Exception('Colete a assinatura do almoxarifado no momento da entrega.');
        }
    } elseif ($payload['assinatura_almox'] !== '') {
        throw new Exception('A assinatura do almoxarifado deve ser registrada apenas quando a solicitacao for marcada como ATENDIDO.');
    }

    return $payload;
}

function inv_request_protocol(array $row): string
{
    $id = (int)($row['ID_SOLICITACAO'] ?? 0);
    $date = trim((string)($row['CRIADO_EM_RAW'] ?? ''));
    $year = $date !== '' ? substr($date, 0, 4) : date('Y');
    return sprintf('ALM-%s-%05d', $year, $id);
}

function inv_list_requests(PDO $conn): array
{
    $stmt = $conn->query("SELECT ID_SOLICITACAO,
                                 SOLICITANTE_NOME,
                                 SOLICITANTE_LOGIN,
                                 SETOR_SOLICITANTE,
                                 CENTRO_CUSTO,
                                 SEQ_CENTRO_RESULTADO,
                                 FILIAL,
                                 ITEM_SOLICITADO,
                                 ITENS_SOLICITADOS,
                                 DESCRICAO_ITEM,
                                 UNIDADE_MEDIDA,
                                 QUANTIDADE,
                                 LOCAL_ENTREGA,
                                 TO_CHAR(DATA_NECESSIDADE, 'YYYY-MM-DD') AS DATA_NECESSIDADE,
                                 TO_CHAR(DATA_NECESSIDADE, 'DD/MM/YYYY') AS DATA_NECESSIDADE_FMT,
                                 PRIORIDADE,
                                 STATUS,
                                 RESPONSAVEL_DESTINO,
                                 RESPONSAVEL_ALMOX,
                                 CASE WHEN ASSINATURA_SOLICITANTE IS NOT NULL THEN 'S' ELSE 'N' END AS POSSUI_ASS_SOLICITANTE,
                                 CASE WHEN ASSINATURA_ALMOX IS NOT NULL THEN 'S' ELSE 'N' END AS POSSUI_ASS_ALMOX,
                                 TO_CHAR(CRIADO_EM, 'YYYY-MM-DD') AS CRIADO_EM_RAW,
                                 TO_CHAR(CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM_FMT
                            FROM CONSINCO.MEGAG_ALMOX_SOLICITACOES
                           ORDER BY ID_SOLICITACAO DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $options = inv_options();

    foreach ($rows as &$row) {
        $row['PROTOCOLO'] = inv_request_protocol($row);
        $row['STATUS_LABEL'] = inv_label($options, 'request_status', $row['STATUS'] ?? '');
        $row['PRIORIDADE_LABEL'] = inv_label($options, 'request_priorities', $row['PRIORIDADE'] ?? '');
        $row['ASSINATURAS_LABEL'] = (($row['POSSUI_ASS_SOLICITANTE'] ?? 'N') === 'S' ? 'Solicitante' : 'Sem assinatura');
        if (($row['POSSUI_ASS_ALMOX'] ?? 'N') === 'S') {
            $row['ASSINATURAS_LABEL'] .= ' + Almoxarifado';
        }
    }
    unset($row);

    return $rows;
}

function inv_get_request(PDO $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT ID_SOLICITACAO,
                                   SOLICITANTE_NOME,
                                   SOLICITANTE_LOGIN,
                                   SOLICITANTE_CPF,
                                   SETOR_SOLICITANTE,
                                   CENTRO_CUSTO,
                                   SEQ_CENTRO_RESULTADO,
                                   FILIAL,
                                   ITEM_SOLICITADO,
                                   ITENS_SOLICITADOS,
                                   DESCRICAO_ITEM,
                                   UNIDADE_MEDIDA,
                                   QUANTIDADE,
                                   LOCAL_ENTREGA,
                                   TO_CHAR(DATA_NECESSIDADE, 'YYYY-MM-DD') AS DATA_NECESSIDADE,
                                   PRIORIDADE,
                                   STATUS,
                                   JUSTIFICATIVA,
                                   OBSERVACAO,
                                   RESPONSAVEL_DESTINO,
                                   RESPONSAVEL_ALMOX,
                                   ASSINATURA_SOLICITANTE,
                                   ASSINATURA_ALMOX,
                                   TO_CHAR(CRIADO_EM, 'YYYY-MM-DD') AS CRIADO_EM_RAW,
                                   TO_CHAR(CRIADO_EM, 'DD/MM/YYYY HH24:MI') AS CRIADO_EM_FMT,
                                   TO_CHAR(ALTERADO_EM, 'DD/MM/YYYY HH24:MI') AS ALTERADO_EM_FMT
                              FROM CONSINCO.MEGAG_ALMOX_SOLICITACOES
                             WHERE ID_SOLICITACAO = :ID");
    $stmt->bindValue(':ID', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Solicitacao ao almoxarifado nao encontrada.');
    }

    $row['ASSINATURA_SOLICITANTE'] = inv_lob_to_string($row['ASSINATURA_SOLICITANTE'] ?? null);
    $row['ASSINATURA_ALMOX'] = inv_lob_to_string($row['ASSINATURA_ALMOX'] ?? null);
    $row['PROTOCOLO'] = inv_request_protocol($row);
    return $row;
}

function inv_save_request(PDO $conn, array $payload, string $user): array
{
    $payload = inv_request_validate_payload($payload);
    $costCenter = inv_resolve_cost_center($conn, $payload['centro_custo']);
    if (!$costCenter) {
        throw new Exception('O centro de custo informado nao foi encontrado no cadastro do ERP.');
    }
    $payload['centro_custo'] = trim((string)($costCenter['centroresultado'] ?? $payload['centro_custo']));
    $payload['seq_centro_resultado'] = (int)($costCenter['seqcentroresultado'] ?? $payload['seq_centro_resultado']);
    $conn->beginTransaction();

    try {
        if ($payload['id_solicitacao']) {
            $stmt = $conn->prepare("UPDATE CONSINCO.MEGAG_ALMOX_SOLICITACOES
                                       SET SOLICITANTE_NOME = :SOLICITANTE_NOME,
                                           SOLICITANTE_LOGIN = :SOLICITANTE_LOGIN,
                                           SOLICITANTE_CPF = :SOLICITANTE_CPF,
                                           SETOR_SOLICITANTE = :SETOR_SOLICITANTE,
                                           CENTRO_CUSTO = :CENTRO_CUSTO,
                                           SEQ_CENTRO_RESULTADO = :SEQ_CENTRO_RESULTADO,
                                           FILIAL = :FILIAL,
                                           ITEM_SOLICITADO = :ITEM_SOLICITADO,
                                           ITENS_SOLICITADOS = :ITENS_SOLICITADOS,
                                           DESCRICAO_ITEM = :DESCRICAO_ITEM,
                                           UNIDADE_MEDIDA = :UNIDADE_MEDIDA,
                                           QUANTIDADE = :QUANTIDADE,
                                           LOCAL_ENTREGA = :LOCAL_ENTREGA,
                                           DATA_NECESSIDADE = CASE WHEN :DATA_NECESSIDADE_NULL IS NULL THEN NULL ELSE TO_DATE(:DATA_NECESSIDADE_VAL, 'YYYY-MM-DD') END,
                                           PRIORIDADE = :PRIORIDADE,
                                           STATUS = :STATUS,
                                           JUSTIFICATIVA = :JUSTIFICATIVA,
                                           OBSERVACAO = :OBSERVACAO,
                                           RESPONSAVEL_DESTINO = :RESPONSAVEL_DESTINO,
                                           RESPONSAVEL_ALMOX = :RESPONSAVEL_ALMOX,
                                           ASSINATURA_SOLICITANTE = :ASSINATURA_SOLICITANTE,
                                           ASSINATURA_ALMOX = :ASSINATURA_ALMOX,
                                           ALTERADO_POR = :ALTERADO_POR,
                                           ALTERADO_EM = SYSDATE
                                     WHERE ID_SOLICITACAO = :ID_SOLICITACAO");
            $bindings = [
                ':SOLICITANTE_NOME' => $payload['solicitante_nome'],
                ':SOLICITANTE_LOGIN' => $payload['solicitante_login'] ?: $user,
                ':SOLICITANTE_CPF' => $payload['solicitante_cpf'] ?: null,
                ':SETOR_SOLICITANTE' => $payload['setor_solicitante'] ?: null,
                ':CENTRO_CUSTO' => $payload['centro_custo'],
                ':SEQ_CENTRO_RESULTADO' => $payload['seq_centro_resultado'] ?: null,
                ':FILIAL' => $payload['filial'],
                ':ITEM_SOLICITADO' => $payload['item_solicitado'],
                ':ITENS_SOLICITADOS' => $payload['itens_solicitados'],
                ':DESCRICAO_ITEM' => $payload['descricao_item'] ?: null,
                ':UNIDADE_MEDIDA' => $payload['unidade_medida'] ?: null,
                ':QUANTIDADE' => $payload['quantidade'],
                ':LOCAL_ENTREGA' => $payload['local_entrega'] ?: null,
                ':DATA_NECESSIDADE_NULL' => $payload['data_necessidade'],
                ':DATA_NECESSIDADE_VAL' => $payload['data_necessidade'],
                ':PRIORIDADE' => $payload['prioridade'],
                ':STATUS' => $payload['status'],
                ':JUSTIFICATIVA' => $payload['justificativa'],
                ':OBSERVACAO' => $payload['observacao'] ?: null,
                ':RESPONSAVEL_DESTINO' => $payload['responsavel_destino'] ?: null,
                ':RESPONSAVEL_ALMOX' => $payload['responsavel_almox'] ?: null,
                ':ALTERADO_POR' => $user,
                ':ID_SOLICITACAO' => $payload['id_solicitacao'],
            ];
            foreach ($bindings as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } elseif ($value === null) {
                    $stmt->bindValue($key, null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $assinaturaSolicitanteLob = fopen('php://temp', 'r+');
            fwrite($assinaturaSolicitanteLob, (string)$payload['assinatura_solicitante']);
            rewind($assinaturaSolicitanteLob);
            $stmt->bindParam(':ASSINATURA_SOLICITANTE', $assinaturaSolicitanteLob, PDO::PARAM_LOB);

            $assinaturaAlmoxLob = null;
            if (($payload['assinatura_almox'] ?? '') !== '') {
                $assinaturaAlmoxLob = fopen('php://temp', 'r+');
                fwrite($assinaturaAlmoxLob, (string)$payload['assinatura_almox']);
                rewind($assinaturaAlmoxLob);
                $stmt->bindParam(':ASSINATURA_ALMOX', $assinaturaAlmoxLob, PDO::PARAM_LOB);
            } else {
                $stmt->bindValue(':ASSINATURA_ALMOX', null, PDO::PARAM_NULL);
            }
            $stmt->execute();
            fclose($assinaturaSolicitanteLob);
            if (is_resource($assinaturaAlmoxLob)) {
                fclose($assinaturaAlmoxLob);
            }

            $idSolicitacao = $payload['id_solicitacao'];
        } else {
            $idSolicitacao = inv_nextval($conn, 'CONSINCO.SEQ_MEGAG_ALMOX_SOLICITACOES');
            $stmt = $conn->prepare("INSERT INTO CONSINCO.MEGAG_ALMOX_SOLICITACOES (
                                        ID_SOLICITACAO,
                                        SOLICITANTE_NOME,
                                        SOLICITANTE_LOGIN,
                                        SOLICITANTE_CPF,
                                        SETOR_SOLICITANTE,
                                        CENTRO_CUSTO,
                                        SEQ_CENTRO_RESULTADO,
                                        FILIAL,
                                        ITEM_SOLICITADO,
                                        ITENS_SOLICITADOS,
                                        DESCRICAO_ITEM,
                                        UNIDADE_MEDIDA,
                                        QUANTIDADE,
                                        LOCAL_ENTREGA,
                                        DATA_NECESSIDADE,
                                        PRIORIDADE,
                                        STATUS,
                                        JUSTIFICATIVA,
                                        OBSERVACAO,
                                        RESPONSAVEL_DESTINO,
                                        RESPONSAVEL_ALMOX,
                                        ASSINATURA_SOLICITANTE,
                                        ASSINATURA_ALMOX,
                                        CRIADO_POR,
                                        CRIADO_EM,
                                        ALTERADO_POR,
                                        ALTERADO_EM
                                    ) VALUES (
                                        :ID_SOLICITACAO,
                                        :SOLICITANTE_NOME,
                                        :SOLICITANTE_LOGIN,
                                        :SOLICITANTE_CPF,
                                        :SETOR_SOLICITANTE,
                                        :CENTRO_CUSTO,
                                        :SEQ_CENTRO_RESULTADO,
                                        :FILIAL,
                                        :ITEM_SOLICITADO,
                                        :ITENS_SOLICITADOS,
                                        :DESCRICAO_ITEM,
                                        :UNIDADE_MEDIDA,
                                        :QUANTIDADE,
                                        :LOCAL_ENTREGA,
                                        CASE WHEN :DATA_NECESSIDADE_NULL IS NULL THEN NULL ELSE TO_DATE(:DATA_NECESSIDADE_VAL, 'YYYY-MM-DD') END,
                                        :PRIORIDADE,
                                        :STATUS,
                                        :JUSTIFICATIVA,
                                        :OBSERVACAO,
                                        :RESPONSAVEL_DESTINO,
                                        :RESPONSAVEL_ALMOX,
                                        :ASSINATURA_SOLICITANTE,
                                        :ASSINATURA_ALMOX,
                                        :CRIADO_POR,
                                        SYSDATE,
                                        :ALTERADO_POR,
                                        SYSDATE
                                    )");
            $bindings = [
                ':ID_SOLICITACAO' => $idSolicitacao,
                ':SOLICITANTE_NOME' => $payload['solicitante_nome'],
                ':SOLICITANTE_LOGIN' => $payload['solicitante_login'] ?: $user,
                ':SOLICITANTE_CPF' => $payload['solicitante_cpf'] ?: null,
                ':SETOR_SOLICITANTE' => $payload['setor_solicitante'] ?: null,
                ':CENTRO_CUSTO' => $payload['centro_custo'],
                ':SEQ_CENTRO_RESULTADO' => $payload['seq_centro_resultado'] ?: null,
                ':FILIAL' => $payload['filial'],
                ':ITEM_SOLICITADO' => $payload['item_solicitado'],
                ':ITENS_SOLICITADOS' => $payload['itens_solicitados'],
                ':DESCRICAO_ITEM' => $payload['descricao_item'] ?: null,
                ':UNIDADE_MEDIDA' => $payload['unidade_medida'] ?: null,
                ':QUANTIDADE' => $payload['quantidade'],
                ':LOCAL_ENTREGA' => $payload['local_entrega'] ?: null,
                ':DATA_NECESSIDADE_NULL' => $payload['data_necessidade'],
                ':DATA_NECESSIDADE_VAL' => $payload['data_necessidade'],
                ':PRIORIDADE' => $payload['prioridade'],
                ':STATUS' => $payload['status'],
                ':JUSTIFICATIVA' => $payload['justificativa'],
                ':OBSERVACAO' => $payload['observacao'] ?: null,
                ':RESPONSAVEL_DESTINO' => $payload['responsavel_destino'] ?: null,
                ':RESPONSAVEL_ALMOX' => $payload['responsavel_almox'] ?: null,
                ':CRIADO_POR' => $user,
                ':ALTERADO_POR' => $user,
            ];
            foreach ($bindings as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } elseif ($value === null) {
                    $stmt->bindValue($key, null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $assinaturaSolicitanteLob = fopen('php://temp', 'r+');
            fwrite($assinaturaSolicitanteLob, (string)$payload['assinatura_solicitante']);
            rewind($assinaturaSolicitanteLob);
            $stmt->bindParam(':ASSINATURA_SOLICITANTE', $assinaturaSolicitanteLob, PDO::PARAM_LOB);

            $assinaturaAlmoxLob = null;
            if (($payload['assinatura_almox'] ?? '') !== '') {
                $assinaturaAlmoxLob = fopen('php://temp', 'r+');
                fwrite($assinaturaAlmoxLob, (string)$payload['assinatura_almox']);
                rewind($assinaturaAlmoxLob);
                $stmt->bindParam(':ASSINATURA_ALMOX', $assinaturaAlmoxLob, PDO::PARAM_LOB);
            } else {
                $stmt->bindValue(':ASSINATURA_ALMOX', null, PDO::PARAM_NULL);
            }
            $stmt->execute();
            fclose($assinaturaSolicitanteLob);
            if (is_resource($assinaturaAlmoxLob)) {
                fclose($assinaturaAlmoxLob);
            }
        }

        $conn->commit();
        return inv_get_request($conn, $idSolicitacao);
    } catch (Throwable $e) {
        $conn->rollBack();
        throw $e;
    }
}

try {
    $conn = getConexaoPDO();
    $body = inv_body();
    $action = inv_action($body);

    inv_throw_if_missing_schema($conn);

    switch ($action) {
        case 'domains':
            inv_json(true, inv_options());
            break;
        case 'request_form_domains':
            inv_json(true, inv_fetch_request_form_domains($conn));
            break;
        case 'request_responsibles':
            inv_json(true, inv_fetch_responsibles_by_cost_center($conn, trim((string)($_GET['centro_custo'] ?? ''))));
            break;

        case 'dashboard':
            inv_json(true, inv_format_dashboard($conn));
            break;

        case 'list':
            inv_json(true, inv_list($conn));
            break;
        case 'list_requests':
            inv_json(true, inv_list_requests($conn));
            break;

        case 'report':
            inv_json(true, inv_report($conn));
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Equipamento inválido.');
            }
            inv_json(true, inv_get($conn, $id));
            break;

        case 'history':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Equipamento inválido.');
            }
            inv_json(true, inv_history($conn, $id));
            break;

        case 'get_term':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Equipamento inválido.');
            }
            inv_json(true, inv_get_term($conn, $id));
            break;
        case 'get_request':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Solicitacao invalida.');
            }
            inv_json(true, inv_get_request($conn, $id));
            break;

        case 'save':
            inv_json(true, inv_save($conn, $body, inv_user()));
            break;

        case 'save_term':
            inv_json(true, inv_save_term($conn, $body, inv_user()));
            break;
        case 'save_request':
            inv_json(true, inv_save_request($conn, $body, inv_user()));
            break;

        default:
            throw new Exception('Ação inválida para o módulo de inventário.');
    }
} catch (Throwable $e) {
    $message = $e->getMessage();

    if ($e instanceof PDOException && str_contains(strtoupper($message), 'ORA-00942')) {
        $message = 'As tabelas do módulo de inventário não foram criadas no Oracle. Execute o script PKG/CREATE_TABLE_TI_INVENTARIO.sql.';
    }

    inv_json(false, null, $message, 500);
}
