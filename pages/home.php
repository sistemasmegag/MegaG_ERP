<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

require_once __DIR__ . '/../bootstrap/db.php';

$user = $_SESSION['usuario'] ?? 'Usuario';
$menuApps = $_SESSION['menu_apps'] ?? [];

if (!function_exists('normalizeLinkMenu')) {
    function normalizeLinkMenu($linkMenu)
    {
        $linkMenu = trim((string) $linkMenu);
        if ($linkMenu !== '' && preg_match('/\.php$/i', $linkMenu)) {
            $linkMenu = preg_replace('/\.php$/i', '', $linkMenu);
        }
        if ($linkMenu !== '' && stripos($linkMenu, 'upload_') === 0) {
            $linkMenu = 'imp_' . substr($linkMenu, strlen('upload_'));
        }
        return $linkMenu;
    }
}

date_default_timezone_set('America/Sao_Paulo');
$agora = new DateTime();
$dataFmt = $agora->format('d/m/Y');
$horaFmt = $agora->format('H:i');

$recados = [];
$tarefas = [];
$despesasRecentes = [];
$despesasMetricas = [
    'em_aprovacao' => 0,
    'reembolsado' => 0,
    'reprovado' => 0,
    'total' => 0.0,
];

$dbConnectPath = null;

try {
    $dbConnectPath = mg_load_db_config();
    $conn = mg_get_global_pdo();
    $schema = mg_db_schema_name();

        $sqlTarefas = "
            SELECT * FROM (
                SELECT
                    id,
                    titulo,
                    status,
                    prioridade,
                    responsavel,
                    data_entrega,
                    criado_por,
                    criado_em
                FROM megag_task_tasks
                WHERE UPPER(NVL(responsavel, criado_por)) = UPPER(:usuario)
                   OR UPPER(criado_por) = UPPER(:usuario)
                   OR UPPER(responsavel) = UPPER(:usuario)
                ORDER BY NVL(data_entrega, criado_em) ASC, criado_em DESC
            )
            WHERE ROWNUM <= 6
        ";

        $stmtTarefas = $conn->prepare($sqlTarefas);
        $stmtTarefas->bindValue(':usuario', $user);
        $stmtTarefas->execute();
        $tarefas = $stmtTarefas->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($tarefas)) {
            $sqlTarefasFallback = "
                SELECT * FROM (
                    SELECT
                        id,
                        titulo,
                        status,
                        prioridade,
                        responsavel,
                        data_entrega,
                        criado_por,
                        criado_em
                    FROM megag_task_tasks
                    ORDER BY criado_em DESC
                )
                WHERE ROWNUM <= 6
            ";
            $stmtTarefasFallback = $conn->prepare($sqlTarefasFallback);
            $stmtTarefasFallback->execute();
            $tarefas = $stmtTarefasFallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $sqlRecados = "
            SELECT * FROM (
                SELECT
                    id,
                    tipo,
                    titulo,
                    mensagem,
                    lida,
                    criado_em
                FROM megag_task_notificacoes
                WHERE UPPER(usuario) = UPPER(:usuario)
                ORDER BY NVL(lida, 'N') ASC, criado_em DESC, id DESC
            )
            WHERE ROWNUM <= 4
        ";

        $stmtRecados = $conn->prepare($sqlRecados);
        $stmtRecados->bindValue(':usuario', $user);
        $stmtRecados->execute();
        $recadosDb = $stmtRecados->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($recadosDb as $recadoDb) {
            $recados[] = [
                'titulo' => (string)($recadoDb['TITULO'] ?? 'Recado'),
                'texto' => (string)($recadoDb['MENSAGEM'] ?? ''),
                'tipo' => strtolower((string)($recadoDb['TIPO'] ?? 'info')),
                'quando' => !empty($recadoDb['CRIADO_EM'])
                    ? date('d/m/Y H:i', strtotime((string)$recadoDb['CRIADO_EM']))
                    : ($dataFmt . ' ' . $horaFmt),
            ];
        }

        $usuarioSolicitante = is_numeric($user) ? (int)$user : 1;
        $sqlDespesas = str_replace('CONSINCO.', $schema . '.', "
            SELECT * FROM (
                SELECT
                    D.CODDESPESA,
                    D.VLRRATDESPESA,
                    D.STATUS,
                    D.PAGO,
                    D.DTADESPESA,
                    D.DTAINCLUSAO,
                    D.FORNECEDOR,
                    (SELECT DESCRICAO
                       FROM CONSINCO.MEGAG_DESP_TIPO T
                      WHERE T.CODTIPODESPESA = D.CODTIPODESPESA
                        AND ROWNUM = 1) AS DESC_TIPO,
                    (SELECT COUNT(*)
                       FROM CONSINCO.MEGAG_DESP_APROVACAO A
                      WHERE A.CODDESPESA = D.CODDESPESA) AS QTD_APROVACOES
                FROM CONSINCO.MEGAG_DESP D
                WHERE D.USUARIOSOLICITANTE = :USU
                ORDER BY D.CODDESPESA DESC
            )
            WHERE ROWNUM <= 5
        ");

        $stmtDespesas = $conn->prepare($sqlDespesas);
        $stmtDespesas->bindValue(':USU', $usuarioSolicitante);
        $stmtDespesas->execute();
        $despesasDb = $stmtDespesas->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($despesasDb as $despesaDb) {
            $statusDespesa = strtoupper((string)($despesaDb['STATUS'] ?? ''));
            $qtdAprovacoes = (int)($despesaDb['QTD_APROVACOES'] ?? 0);
            $pagoDespesa = strtoupper((string)($despesaDb['PAGO'] ?? 'N'));

            if ($statusDespesa === 'LANCADO' && $qtdAprovacoes > 0) {
                $statusDespesa = 'EM_APROVACAO';
            }

            $valorDespesa = (float)($despesaDb['VLRRATDESPESA'] ?? 0);
            if (in_array($statusDespesa, ['LANCADO', 'EM_APROVACAO', 'APROVACAO'], true)) {
                $despesasMetricas['em_aprovacao']++;
            } elseif (in_array($statusDespesa, ['APROVADO', 'REEMBOLSADO'], true) || $pagoDespesa === 'S') {
                $despesasMetricas['reembolsado']++;
            } elseif ($statusDespesa === 'REJEITADO') {
                $despesasMetricas['reprovado']++;
            }
            $despesasMetricas['total'] += $valorDespesa;

            $dataDespesa = (string)($despesaDb['DTADESPESA'] ?? $despesaDb['DTAINCLUSAO'] ?? '');
            $despesasRecentes[] = [
                'id' => (int)($despesaDb['CODDESPESA'] ?? 0),
                'categoria' => (string)($despesaDb['DESC_TIPO'] ?? 'Despesa'),
                'fornecedor' => (string)($despesaDb['FORNECEDOR'] ?? 'Fornecedor nao informado'),
                'status' => $statusDespesa !== '' ? $statusDespesa : 'LANCADO',
                'valor' => $valorDespesa,
                'data' => $dataDespesa !== '' && strtotime($dataDespesa)
                    ? date('d/m/Y', strtotime($dataDespesa))
                    : $dataFmt,
            ];
        }
} catch (Throwable $e) {
    $recados = [];
    $tarefas = [];
    $despesasRecentes = [];
    $despesasMetricas = [
        'em_aprovacao' => 0,
        'reembolsado' => 0,
        'reprovado' => 0,
        'total' => 0.0,
    ];
}

if (empty($recados)) {
    $recados = $_SESSION['recados'] ?? [
    [
        'titulo' => 'Bem-vindo ao painel',
        'texto' => 'Sua central esta pronta para acompanhar tarefas, recados e atalhos do sistema.',
        'tipo' => 'info',
        'quando' => $dataFmt . ' ' . $horaFmt,
    ],
    ];
}

if (empty($tarefas)) {
    $tarefas = $_SESSION['tarefas'] ?? [
    ['titulo' => 'Revisar importacoes pendentes', 'status' => 'Pendente', 'prazo' => $dataFmt],
    ['titulo' => 'Validar comissoes do mes', 'status' => 'Em andamento', 'prazo' => $dataFmt],
    ];
}

$atalhos = [];
foreach ($menuApps as $app) {
    $slug = normalizeLinkMenu((string) ($app['LINKMENU'] ?? ''));
    if ($slug === '' || $slug === 'home') {
        continue;
    }

    $atalhos[] = [
        'nome' => (string) ($app['APLICACAO'] ?? $slug),
        'slug' => $slug,
        'mod' => strtoupper((string) ($app['CODMODULO'] ?? 'OUTROS')),
        'ord' => (int) ($app['ORDEM_APLICACAO'] ?? 9999),
    ];
}

usort($atalhos, function ($a, $b) {
    if ($a['ord'] === $b['ord']) {
        return strcmp($a['nome'], $b['nome']);
    }
    return $a['ord'] <=> $b['ord'];
});

$atalhos = array_slice($atalhos, 0, 12);

$pendentes = 0;
$emAndamento = 0;
$concluidas = 0;
$tarefasHoje = [];

foreach ($tarefas as $tarefa) {
    $statusRaw = (string)($tarefa['status'] ?? $tarefa['STATUS'] ?? 'pendente');
    $status = strtolower(trim($statusRaw));

    if ($status === 'pendente' || $status === 'todo') {
        $pendentes++;
    } elseif ($status === 'em andamento' || $status === 'doing') {
        $emAndamento++;
    } elseif ($status === 'concluida' || $status === 'concluida ' || $status === 'done') {
        $concluidas++;
    }

    $prazoRaw = (string)($tarefa['prazo'] ?? $tarefa['PRAZO'] ?? $tarefa['data_entrega'] ?? $tarefa['DATA_ENTREGA'] ?? '-');
    $prazoFmt = $prazoRaw;
    if ($prazoRaw !== '' && $prazoRaw !== '-') {
        $timestampPrazo = strtotime($prazoRaw);
        if ($timestampPrazo) {
            $prazoFmt = date('d/m/Y', $timestampPrazo);
        }
    }

    $statusLabel = 'Pendente';
    if ($status === 'doing' || $status === 'em andamento') {
        $statusLabel = 'Em andamento';
    } elseif ($status === 'done' || $status === 'concluida' || $status === 'concluida ') {
        $statusLabel = 'Concluida';
    } elseif ($status === 'todo' || $status === 'pendente') {
        $statusLabel = 'Pendente';
    } else {
        $statusLabel = ucfirst($statusRaw ?: 'Pendente');
    }

    $tarefasHoje[] = [
        'titulo' => (string)($tarefa['titulo'] ?? $tarefa['TITULO'] ?? 'Tarefa'),
        'status' => $statusLabel,
        'prazo' => $prazoFmt,
    ];
}

$totalRecados = count($recados);
$totalAtalhos = count($atalhos);
$totalTarefas = count($tarefasHoje);

$atalhosPorModulo = [];
foreach ($atalhos as $atalho) {
    $mod = $atalho['mod'] ?: 'OUTROS';
    if (!isset($atalhosPorModulo[$mod])) {
        $atalhosPorModulo[$mod] = [
            'mod' => $mod,
            'qtd' => 0,
            'primeiro_slug' => $atalho['slug'],
            'primeiro_nome' => $atalho['nome'],
        ];
    }
    $atalhosPorModulo[$mod]['qtd']++;
}

$modulosResumo = array_values($atalhosPorModulo);
usort($modulosResumo, function ($a, $b) {
    if ($a['qtd'] === $b['qtd']) {
        return strcmp($a['mod'], $b['mod']);
    }
    return $b['qtd'] <=> $a['qtd'];
});

$modulosResumo = array_slice($modulosResumo, 0, 6);
$tarefasHoje = array_slice($tarefasHoje, 0, 4);
$recadosDestaque = array_slice($recados, 0, 3);

$atividadeRecente = [];
foreach (array_slice($tarefas, 0, 4) as $taskActivity) {
    $tituloAtividade = (string)($taskActivity['titulo'] ?? $taskActivity['TITULO'] ?? 'Tarefa');
    $statusAtividade = strtolower(trim((string)($taskActivity['status'] ?? $taskActivity['STATUS'] ?? 'todo')));
    $criadoEmAtividade = (string)($taskActivity['criado_em'] ?? $taskActivity['CRIADO_EM'] ?? '');
    $quandoAtividade = $criadoEmAtividade !== '' && strtotime($criadoEmAtividade)
        ? date('d/m H:i', strtotime($criadoEmAtividade))
        : $dataFmt . ' ' . $horaFmt;

    $textoAtividade = 'Task registrada no fluxo.';
    if ($statusAtividade === 'doing' || $statusAtividade === 'em andamento') {
        $textoAtividade = 'Task em andamento e acompanhando o fluxo.';
    } elseif ($statusAtividade === 'done' || $statusAtividade === 'concluida' || $statusAtividade === 'concluida ') {
        $textoAtividade = 'Task marcada como concluida.';
    } elseif ($statusAtividade === 'todo' || $statusAtividade === 'pendente') {
        $textoAtividade = 'Task aguardando acao ou priorizacao.';
    }

    $atividadeRecente[] = [
        'tipo' => 'task',
        'tag' => 'Task',
        'titulo' => $tituloAtividade,
        'texto' => $textoAtividade,
        'quando' => $quandoAtividade,
    ];
}

foreach (array_slice($recados, 0, 3) as $recadoAtividade) {
    $atividadeRecente[] = [
        'tipo' => 'notif',
        'tag' => 'Recado',
        'titulo' => (string)($recadoAtividade['titulo'] ?? 'Atualizacao'),
        'texto' => (string)($recadoAtividade['texto'] ?? ''),
        'quando' => (string)($recadoAtividade['quando'] ?? ($dataFmt . ' ' . $horaFmt)),
    ];
}

$atividadeRecente = array_slice($atividadeRecente, 0, 6);

function homeStatusClass($status)
{
    $status = strtolower(trim((string) $status));
    if ($status === 'pendente' || $status === 'todo') {
        return 'status-warn';
    }
    if ($status === 'em andamento' || $status === 'doing') {
        return 'status-info';
    }
    if ($status === 'concluida' || $status === 'concluida ' || $status === 'done') {
        return 'status-ok';
    }
    return 'status-muted';
}

function homeRecadoClass($tipo)
{
    $tipo = strtolower(trim((string) $tipo));
    if ($tipo === 'erro' || $tipo === 'alerta') {
        return 'recado-alerta';
    }
    if ($tipo === 'aviso') {
        return 'recado-aviso';
    }
    if ($tipo === 'sucesso') {
        return 'recado-sucesso';
    }
    return 'recado-info';
}

function homeDespesaStatusClass($status)
{
    $status = strtoupper(trim((string)$status));
    if (in_array($status, ['LANCADO', 'EM_APROVACAO', 'APROVACAO'], true)) {
        return 'status-info';
    }
    if (in_array($status, ['APROVADO', 'REEMBOLSADO'], true)) {
        return 'status-ok';
    }
    if ($status === 'REJEITADO') {
        return 'status-warn';
    }
    return 'status-muted';
}
?>

<style>
  .home-shell {
    display: grid;
    gap: 1.5rem;
  }

  .home-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(13, 110, 253, .14);
    border-radius: 28px;
    padding: 1.6rem;
    background:
      radial-gradient(circle at top left, rgba(13, 110, 253, .18), transparent 36%),
      radial-gradient(circle at top right, rgba(25, 135, 84, .12), transparent 32%),
      linear-gradient(135deg, rgba(255,255,255,.96), rgba(235,243,255,.92));
    box-shadow: 0 24px 60px rgba(15, 23, 42, .08);
  }

  .home-hero::after {
    content: '';
    position: absolute;
    inset: auto -60px -80px auto;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(13, 110, 253, .12), transparent 70%);
    pointer-events: none;
  }

  .home-kicker,
  .home-card-kicker {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .18em;
    font-weight: 800;
    color: rgba(17, 24, 39, .46);
    margin-bottom: .45rem;
  }

  .home-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .home-title {
    margin: 0;
    font-size: clamp(2rem, 3vw, 2.85rem);
    line-height: 1;
    letter-spacing: -.04em;
    font-weight: 900;
    color: #182033;
  }

  .home-subtitle {
    margin: .55rem 0 0;
    max-width: 720px;
    color: rgba(17, 24, 39, .66);
    font-size: .98rem;
  }

  .home-actions {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
  }

  .home-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 .95rem;
    border-radius: 999px;
    border: 1px solid rgba(17, 24, 39, .12);
    text-decoration: none;
    color: #1d2740;
    background: rgba(255,255,255,.72);
    font-weight: 700;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }

  .home-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
    border-color: rgba(13, 110, 253, .22);
  }

  .home-btn-primary {
    color: #0d3fd1;
    background: linear-gradient(135deg, rgba(13, 110, 253, .16), rgba(13, 110, 253, .08));
    border-color: rgba(13, 110, 253, .22);
  }

  .home-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-top: 1.4rem;
  }

  .metric-tile {
    border-radius: 22px;
    padding: 1rem 1rem 1.05rem;
    border: 1px solid rgba(17, 24, 39, .08);
    background: rgba(255,255,255,.76);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.45);
  }

  .metric-value {
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 900;
    color: #101828;
    margin-bottom: .35rem;
  }

  .metric-note {
    font-size: .88rem;
    color: rgba(17, 24, 39, .64);
  }

  .home-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(320px, .95fr);
    gap: 1.5rem;
  }

  .home-stack {
    display: grid;
    gap: 1.5rem;
  }

  .home-card {
    border-radius: 24px;
    border: 1px solid rgba(17, 24, 39, .08);
    background: rgba(255,255,255,.92);
    box-shadow: 0 18px 40px rgba(15, 23, 42, .06);
    overflow: hidden;
  }

  .home-card-body {
    padding: 1.25rem;
  }

  .home-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .home-card-title {
    margin: 0;
    font-size: 1.2rem;
    line-height: 1.1;
    font-weight: 850;
    color: #182033;
    letter-spacing: -.03em;
  }

  .home-card-subtitle {
    margin: .3rem 0 0;
    color: rgba(17, 24, 39, .58);
    font-size: .92rem;
  }

  .home-pill {
    display: inline-flex;
    align-items: center;
    min-height: 36px;
    padding: 0 .75rem;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 800;
    color: #3554d1;
    background: rgba(13, 110, 253, .1);
    border: 1px solid rgba(13, 110, 253, .12);
  }

  .priority-list,
  .recado-list,
  .shortcut-list {
    display: grid;
    gap: .85rem;
  }

  .priority-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: .9rem;
    align-items: center;
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(17, 24, 39, .07);
    background:
      linear-gradient(135deg, rgba(248,250,252,.94), rgba(255,255,255,.98));
  }

  .priority-index {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #0d47bf;
    background: rgba(13, 110, 253, .12);
  }

  .priority-title {
    font-weight: 800;
    color: #182033;
    margin-bottom: .2rem;
  }

  .priority-meta {
    font-size: .84rem;
    color: rgba(17, 24, 39, .58);
  }

  .status-chip {
    display: inline-flex;
    align-items: center;
    padding: .42rem .7rem;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 800;
    border: 1px solid transparent;
  }

  .status-warn {
    color: #b25a00;
    background: rgba(255, 193, 7, .14);
    border-color: rgba(255, 193, 7, .18);
  }

  .status-info {
    color: #0c55d6;
    background: rgba(13, 110, 253, .1);
    border-color: rgba(13, 110, 253, .14);
  }

  .status-ok {
    color: #117a4c;
    background: rgba(25, 135, 84, .12);
    border-color: rgba(25, 135, 84, .16);
  }

  .status-muted {
    color: #596273;
    background: rgba(108, 117, 125, .12);
    border-color: rgba(108, 117, 125, .16);
  }

  .home-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
  }

  .mini-panel {
    border-radius: 20px;
    padding: 1rem;
    border: 1px solid rgba(17, 24, 39, .08);
    background: linear-gradient(135deg, rgba(255,255,255,.94), rgba(247,250,255,.96));
  }

  .mini-panel strong {
    display: block;
    font-size: 1.35rem;
    margin-top: .28rem;
    color: #141c2e;
  }

  .shortcut-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(15, 23, 42, .08);
    border-color: rgba(13, 110, 253, .16);
  }

  .activity-list {
    display: grid;
    gap: .9rem;
  }

  .activity-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: .95rem;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(17, 24, 39, .07);
    background:
      linear-gradient(135deg, rgba(255,255,255,.96), rgba(245,248,255,.92));
  }

  .activity-dot {
    width: 14px;
    height: 14px;
    margin-top: .35rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #5b7cff, #7ec8ff);
    box-shadow: 0 0 0 6px rgba(13, 110, 253, .08);
  }

  .activity-item[data-kind="notif"] .activity-dot {
    background: linear-gradient(135deg, #22c55e, #7dd3fc);
  }

  .activity-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
    margin-bottom: .3rem;
  }

  .activity-title {
    font-weight: 850;
    color: #182033;
  }

  .activity-tag {
    display: inline-flex;
    align-items: center;
    padding: .32rem .58rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    color: #2457e6;
    background: rgba(13, 110, 253, .08);
  }

  .activity-text {
    color: rgba(17, 24, 39, .62);
    font-size: .9rem;
    line-height: 1.45;
  }

  .activity-time {
    margin-top: .45rem;
    font-size: .78rem;
    color: rgba(17, 24, 39, .5);
  }

  .expense-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .85rem;
    margin-bottom: 1rem;
  }

  .expense-stat {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(17, 24, 39, .08);
    background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(245,248,255,.92));
  }

  .expense-stat strong {
    display: block;
    margin-top: .3rem;
    font-size: 1.45rem;
    line-height: 1;
    color: #182033;
  }

  .expense-list {
    display: grid;
    gap: .85rem;
  }

  .expense-item {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(17, 24, 39, .08);
    background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(248,250,255,.94));
  }

  .expense-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .8rem;
    margin-bottom: .45rem;
  }

  .expense-title {
    font-weight: 850;
    color: #182033;
    margin-bottom: .18rem;
  }

  .expense-text {
    color: rgba(17, 24, 39, .6);
    font-size: .9rem;
  }

  .expense-meta {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .55rem;
    font-size: .82rem;
    color: rgba(17, 24, 39, .56);
  }

  .recado-item {
    padding: 1rem;
    border-radius: 20px;
    border: 1px solid rgba(17, 24, 39, .07);
    background: rgba(255,255,255,.72);
  }

  .recado-top {
    display: flex;
    justify-content: space-between;
    gap: .85rem;
    align-items: flex-start;
    margin-bottom: .55rem;
  }

  .recado-title {
    font-weight: 850;
    color: #182033;
    margin-bottom: .2rem;
  }

  .recado-text {
    color: rgba(17, 24, 39, .66);
    font-size: .92rem;
  }

  .recado-time {
    margin-top: .55rem;
    font-size: .8rem;
    color: rgba(17, 24, 39, .5);
  }

  .recado-alerta {
    color: #c24716;
    background: rgba(255, 95, 31, .12);
  }

  .recado-aviso {
    color: #9a6b00;
    background: rgba(255, 193, 7, .14);
  }

  .recado-sucesso {
    color: #117a4c;
    background: rgba(25, 135, 84, .12);
  }

  .recado-info {
    color: #0c55d6;
    background: rgba(13, 110, 253, .1);
  }

  .shortcut-search {
    width: 100%;
    min-height: 46px;
    border-radius: 16px;
    padding: .85rem 1rem;
    border: 1px solid rgba(17, 24, 39, .09);
    background: rgba(248, 250, 252, .88);
    outline: none;
    transition: border-color .16s ease, box-shadow .16s ease;
  }

  .shortcut-search:focus {
    border-color: rgba(13, 110, 253, .25);
    box-shadow: 0 0 0 .24rem rgba(13, 110, 253, .1);
  }

  .shortcut-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
    padding: .95rem 1rem;
    border-radius: 18px;
    border: 1px solid rgba(17, 24, 39, .07);
    text-decoration: none;
    color: inherit;
    background: rgba(255,255,255,.76);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }

  .shortcut-title {
    font-weight: 800;
    color: #182033;
    margin-bottom: .15rem;
  }

  .shortcut-meta {
    font-size: .8rem;
    color: rgba(17, 24, 39, .52);
    text-transform: uppercase;
    letter-spacing: .08em;
  }

  .shortcut-arrow {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, .08);
    color: #0d47bf;
    font-weight: 900;
    flex: 0 0 auto;
  }

  .home-link {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    text-decoration: none;
    color: #2457e6;
    font-weight: 800;
  }

  .home-empty {
    padding: 1rem;
    border-radius: 18px;
    border: 1px dashed rgba(17, 24, 39, .14);
    color: rgba(17, 24, 39, .54);
    background: rgba(248, 250, 252, .7);
  }

  html[data-theme="dark"] .home-hero,
  html[data-theme="dark"] .home-card,
  html[data-theme="dark"] .metric-tile,
  html[data-theme="dark"] .priority-item,
  html[data-theme="dark"] .mini-panel,
  html[data-theme="dark"] .activity-item,
  html[data-theme="dark"] .expense-stat,
  html[data-theme="dark"] .expense-item,
  html[data-theme="dark"] .recado-item,
  html[data-theme="dark"] .shortcut-item,
  html[data-theme="dark"] .shortcut-search {
    background: rgba(18, 24, 38, .82);
    border-color: rgba(255,255,255,.09);
    color: rgba(255,255,255,.9);
  }

  html[data-theme="dark"] .home-title,
  html[data-theme="dark"] .home-card-title,
  html[data-theme="dark"] .priority-title,
  html[data-theme="dark"] .activity-title,
  html[data-theme="dark"] .expense-title,
  html[data-theme="dark"] .shortcut-title,
  html[data-theme="dark"] .recado-title,
  html[data-theme="dark"] .metric-value,
  html[data-theme="dark"] .mini-panel strong,
  html[data-theme="dark"] .expense-stat strong {
    color: rgba(255,255,255,.94);
  }

  html[data-theme="dark"] .home-subtitle,
  html[data-theme="dark"] .metric-note,
  html[data-theme="dark"] .home-card-subtitle,
  html[data-theme="dark"] .priority-meta,
  html[data-theme="dark"] .activity-text,
  html[data-theme="dark"] .activity-time,
  html[data-theme="dark"] .expense-text,
  html[data-theme="dark"] .expense-meta,
  html[data-theme="dark"] .recado-text,
  html[data-theme="dark"] .recado-time,
  html[data-theme="dark"] .shortcut-meta,
  html[data-theme="dark"] .home-kicker,
  html[data-theme="dark"] .home-card-kicker {
    color: rgba(255,255,255,.62);
  }

  @media (max-width: 1200px) {
    .home-grid,
    .home-metrics,
    .expense-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 991px) {
    .home-grid,
    .home-metrics,
    .home-mini-grid,
    .expense-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="container-fluid">
  <div class="home-shell">
    <section class="home-hero">
      <div class="home-hero-top">
        <div>
          <div class="home-kicker">Central Pessoal</div>
          <h1 class="home-title">Ola, <?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="home-subtitle">
            <?php echo $dataFmt; ?> as <?php echo $horaFmt; ?>. Esta home foi organizada para mostrar foco do dia,
            recados recentes e acesso rapido aos modulos que voce mais usa.
          </p>
        </div>

        <div class="home-actions">
          <a class="home-btn home-btn-primary" href="index.php?page=tarefas">Abrir Kanban</a>
          <a class="home-btn" href="index.php?page=home_importacao">Ir para Importacao</a>
        </div>
      </div>

      <div class="home-metrics">
        <div class="metric-tile">
          <div class="home-card-kicker">Pendencias</div>
          <div class="metric-value"><?php echo (int) $pendentes; ?></div>
          <div class="metric-note">Tarefas que pedem acao imediata.</div>
        </div>

        <div class="metric-tile">
          <div class="home-card-kicker">Em Andamento</div>
          <div class="metric-value"><?php echo (int) $emAndamento; ?></div>
          <div class="metric-note">Itens que ja estao em execucao.</div>
        </div>

        <div class="metric-tile">
          <div class="home-card-kicker">Recados</div>
          <div class="metric-value"><?php echo (int) $totalRecados; ?></div>
          <div class="metric-note">Avisos e comunicados recentes.</div>
        </div>

        <div class="metric-tile">
          <div class="home-card-kicker">Atalhos</div>
          <div class="metric-value"><?php echo (int) $totalAtalhos; ?></div>
          <div class="metric-note">Rotas liberadas pelo seu menu.</div>
        </div>
      </div>
    </section>

    <div class="home-grid">
      <div class="home-stack">
        <section class="home-card">
          <div class="home-card-body">
            <div class="home-card-head">
              <div>
                <div class="home-card-kicker">Prioridades Do Dia</div>
                <h2 class="home-card-title">O que merece atencao agora</h2>
                <p class="home-card-subtitle">Uma leitura rapida para voce decidir onde comecar.</p>
              </div>
              <span class="home-pill"><?php echo (int) $totalTarefas; ?> tarefas no radar</span>
            </div>

            <?php if (empty($tarefasHoje)): ?>
              <div class="home-empty">Nenhuma tarefa foi encontrada para montar a sua fila do dia.</div>
            <?php else: ?>
              <div class="priority-list">
                <?php foreach ($tarefasHoje as $index => $tarefa): ?>
                  <div class="priority-item">
                    <div class="priority-index"><?php echo $index + 1; ?></div>
                    <div>
                      <div class="priority-title"><?php echo htmlspecialchars($tarefa['titulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="priority-meta">Prazo previsto: <?php echo htmlspecialchars($tarefa['prazo'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="status-chip <?php echo homeStatusClass($tarefa['status']); ?>">
                      <?php echo htmlspecialchars($tarefa['status'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mt-3">
              <a class="home-link" href="index.php?page=tarefas">Ver quadro completo <span>&rarr;</span></a>
            </div>
          </div>
        </section>

        <section class="home-card">
          <div class="home-card-body">
            <div class="home-card-head">
              <div>
                <div class="home-card-kicker">Radar De Despesas</div>
                <h2 class="home-card-title">Resumo do seu fluxo financeiro</h2>
                <p class="home-card-subtitle">Um bloco mais util para o ERP: leitura rapida de status e ultimos lancamentos.</p>
              </div>
            </div>

            <div class="expense-grid">
              <div class="expense-stat">
                <div class="home-card-kicker">Em Aprovacao</div>
                <strong><?php echo (int)$despesasMetricas['em_aprovacao']; ?></strong>
                <div class="metric-note">Solicitacoes aguardando andamento financeiro.</div>
              </div>

              <div class="expense-stat">
                <div class="home-card-kicker">Reembolsadas</div>
                <strong><?php echo (int)$despesasMetricas['reembolsado']; ?></strong>
                <div class="metric-note">Itens aprovados ou pagos no seu historico recente.</div>
              </div>

              <div class="expense-stat">
                <div class="home-card-kicker">Reprovadas</div>
                <strong><?php echo (int)$despesasMetricas['reprovado']; ?></strong>
                <div class="metric-note">Despesas que pedem revisao ou ajuste.</div>
              </div>

              <div class="expense-stat">
                <div class="home-card-kicker">Volume</div>
                <strong>R$ <?php echo number_format((float)$despesasMetricas['total'], 2, ',', '.'); ?></strong>
                <div class="metric-note">Soma das despesas listadas nesta leitura inicial.</div>
              </div>
            </div>

            <?php if (empty($despesasRecentes)): ?>
              <div class="home-empty">Nenhuma despesa recente encontrada. Quando voce lancar despesas, este radar vai aparecer aqui.</div>
            <?php else: ?>
              <div class="expense-list">
                <?php foreach ($despesasRecentes as $despesa): ?>
                  <div class="expense-item">
                    <div class="expense-top">
                      <div>
                        <div class="expense-title"><?php echo htmlspecialchars($despesa['categoria'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="expense-text"><?php echo htmlspecialchars($despesa['fornecedor'], ENT_QUOTES, 'UTF-8'); ?></div>
                      </div>
                      <span class="status-chip <?php echo homeDespesaStatusClass($despesa['status']); ?>">
                        <?php echo htmlspecialchars($despesa['status'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </div>

                    <div class="expense-meta">
                      <span>Valor: R$ <?php echo number_format((float)$despesa['valor'], 2, ',', '.'); ?></span>
                      <span>Data: <?php echo htmlspecialchars($despesa['data'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <span>ID: #<?php echo (int)$despesa['id']; ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mt-3">
              <a class="home-link" href="index.php?page=despesas">Abrir modulo de despesas <span>&rarr;</span></a>
            </div>
          </div>
        </section>
      </div>

      <div class="home-stack">
        <section class="home-card">
          <div class="home-card-body">
            <div class="home-card-head">
              <div>
                <div class="home-card-kicker">Pulso Do Dia</div>
                <h2 class="home-card-title">Visao rapida da operacao</h2>
                <p class="home-card-subtitle">Leitura curta para entender volume, foco e proximos passos.</p>
              </div>
            </div>

            <div class="home-mini-grid">
              <div class="mini-panel">
                <div class="home-card-kicker">Foco Atual</div>
                <strong><?php echo $pendentes > 0 ? 'Atacar pendencias' : 'Fluxo sob controle'; ?></strong>
                <div class="metric-note">Pendentes contam mais no ritmo da sua fila.</div>
              </div>

              <div class="mini-panel">
                <div class="home-card-kicker">Ultima Leitura</div>
                <strong><?php echo (int) $totalRecados; ?> aviso(s)</strong>
                <div class="metric-note">Recados recentes do seu mural pessoal.</div>
              </div>

              <div class="mini-panel">
                <div class="home-card-kicker">Taxa De Conclusao</div>
                <strong><?php echo (int) $concluidas; ?></strong>
                <div class="metric-note">Itens concluidos dentro da amostra atual.</div>
              </div>

              <div class="mini-panel">
                <div class="home-card-kicker">Navegacao</div>
                <strong><?php echo (int) $totalAtalhos; ?> atalhos</strong>
                <div class="metric-note">Entradas disponiveis para acelerar seu fluxo.</div>
              </div>
            </div>
          </div>
        </section>

        <section class="home-card">
          <div class="home-card-body">
            <div class="home-card-head">
              <div>
                <div class="home-card-kicker">Mural</div>
                <h2 class="home-card-title">Comunicados e atualizacoes</h2>
                <p class="home-card-subtitle">Priorize o que muda sua rotina ou destrava alguma entrega.</p>
              </div>
            </div>

            <?php if (empty($recadosDestaque)): ?>
              <div class="home-empty">Nenhum recado disponivel para exibir agora.</div>
            <?php else: ?>
              <div class="recado-list">
                <?php foreach ($recadosDestaque as $recado): ?>
                  <div class="recado-item">
                    <div class="recado-top">
                      <div>
                        <div class="recado-title"><?php echo htmlspecialchars($recado['titulo'] ?? 'Recado', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="recado-text"><?php echo htmlspecialchars($recado['texto'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                      </div>
                      <span class="status-chip <?php echo homeRecadoClass($recado['tipo'] ?? 'info'); ?>">
                        <?php echo htmlspecialchars(strtoupper((string) ($recado['tipo'] ?? 'INFO')), ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </div>
                    <div class="recado-time"><?php echo htmlspecialchars($recado['quando'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <section class="home-card">
          <div class="home-card-body">
            <div class="home-card-head">
              <div>
                <div class="home-card-kicker">Acesso Rapido</div>
                <h2 class="home-card-title">Entradas liberadas no seu menu</h2>
                <p class="home-card-subtitle">Busque e acesse paginas sem depender so da navegacao lateral.</p>
              </div>
            </div>

            <input
              type="text"
              class="shortcut-search"
              id="homeShortcutSearch"
              placeholder="Buscar atalho por nome ou modulo..." />

            <div class="shortcut-list mt-3" id="homeShortcutList">
              <?php if (empty($atalhos)): ?>
                <div class="home-empty">Sem atalhos disponiveis para este usuario.</div>
              <?php else: ?>
                <?php foreach ($atalhos as $atalho): ?>
                  <a
                    class="shortcut-item"
                    href="index.php?page=<?php echo urlencode($atalho['slug']); ?>"
                    data-search="<?php echo htmlspecialchars(strtolower($atalho['nome'] . ' ' . $atalho['mod']), ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                      <div class="shortcut-title"><?php echo htmlspecialchars($atalho['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="shortcut-meta"><?php echo htmlspecialchars($atalho['mod'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="shortcut-arrow">&rarr;</span>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const input = document.getElementById('homeShortcutSearch');
    const list = document.getElementById('homeShortcutList');
    if (!input || !list) return;

    const normalize = (value) =>
      (value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    input.addEventListener('input', function () {
      const term = normalize(input.value);
      const items = list.querySelectorAll('.shortcut-item');
      let visible = 0;

      items.forEach(function (item) {
        const haystack = normalize(item.getAttribute('data-search'));
        const match = !term || haystack.includes(term);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
      });

      let empty = list.querySelector('.home-empty-search');
      if (!empty) {
        empty = document.createElement('div');
        empty.className = 'home-empty home-empty-search';
        empty.textContent = 'Nenhum atalho encontrado para esse filtro.';
      }

      if (visible === 0 && items.length > 0) {
        list.appendChild(empty);
      } else if (empty.parentNode) {
        empty.parentNode.removeChild(empty);
      }
    });
  })();
</script>
