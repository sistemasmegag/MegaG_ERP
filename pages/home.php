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

try {
    $dbConnectPath = mg_load_db_config();
    $conn = mg_get_global_pdo();
    
    // Busca Tarefas
    $sqlTarefas = "SELECT * FROM (SELECT id, titulo, status, data_entrega FROM megag_task_tasks WHERE UPPER(NVL(responsavel, criado_por)) = UPPER(:usuario) OR UPPER(criado_por) = UPPER(:usuario) ORDER BY criado_em DESC) WHERE ROWNUM <= 5";
    $stmtTarefas = $conn->prepare($sqlTarefas);
    $stmtTarefas->bindValue(':usuario', $user);
    $stmtTarefas->execute();
    $tarefas = $stmtTarefas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- MÉTRICAS REAIS DE ERP ---
    
    // 1. Aprovações Pendentes (Despesas aguardando este usuário)
    $sqlAprov = "SELECT COUNT(DISTINCT CODDESPESA) 
                 FROM CONSINCO.MEGAG_DESP_APROVACAO 
                 WHERE STATUS = 'LANCADO' 
                   AND USUARIOAPROVADOR = (SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(LOGINID) = UPPER(:usuario) AND ROWNUM = 1)";
    $stmtAprov = $conn->prepare($sqlAprov);
    $stmtAprov->bindValue(':usuario', $user);
    $stmtAprov->execute();
    $totalAprovacoes = $stmtAprov->fetchColumn() ?: 0;

    // 2. Minhas Despesas em Aberto (R$) - Dinheiro a receber
    $sqlDesp = "SELECT SUM(VLRRATDESPESA) FROM CONSINCO.MEGAG_DESP WHERE STATUS NOT IN ('REJEITADO', 'PAGO') AND USUARIOSOLICITANTE = (SELECT SEQUSUARIO FROM CONSINCO.GE_USUARIO WHERE UPPER(LOGINID) = UPPER(:usuario) AND ROWNUM = 1)";
    $stmtDesp = $conn->prepare($sqlDesp);
    $stmtDesp->bindValue(':usuario', $user);
    $stmtDesp->execute();
    $totalDespesas = $stmtDesp->fetchColumn() ?: 0;

    // 3. Notificações não lidas
    $sqlNotifCount = "SELECT COUNT(1) FROM MEGAG_TASK_NOTIFICACOES WHERE UPPER(USUARIO) = UPPER(:usuario) AND LIDA = 'N'";
    $stmtNotif = $conn->prepare($sqlNotifCount);
    $stmtNotif->bindValue(':usuario', $user);
    $stmtNotif->execute();
    $totalNotif = $stmtNotif->fetchColumn() ?: 0;
    
    // Busca Tarefas
    $sqlTarefas = "SELECT * FROM (SELECT id, titulo, status, data_entrega FROM megag_task_tasks WHERE UPPER(NVL(responsavel, criado_por)) = UPPER(:usuario) OR UPPER(criado_por) = UPPER(:usuario) ORDER BY criado_em DESC) WHERE ROWNUM <= 5";
    $stmtTarefas = $conn->prepare($sqlTarefas);
    $stmtTarefas->bindValue(':usuario', $user);
    $stmtTarefas->execute();
    $tarefas = $stmtTarefas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Busca Recados/Notificações
    $sqlRecados = "SELECT * FROM (SELECT titulo, mensagem, tipo, criado_em FROM megag_task_notificacoes WHERE UPPER(usuario) = UPPER(:usuario) ORDER BY criado_em DESC) WHERE ROWNUM <= 4";
    $stmtRecados = $conn->prepare($sqlRecados);
    $stmtRecados->bindValue(':usuario', $user);
    $stmtRecados->execute();
    $recadosDb = $stmtRecados->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($recadosDb as $r) {
        $recados[] = [
            'titulo' => $r['TITULO'] ?? 'Notificação',
            'texto' => $r['MENSAGEM'] ?? '',
            'tipo' => strtolower($r['TIPO'] ?? 'info'),
            'quando' => !empty($r['CRIADO_EM']) ? date('d/m/Y H:i', strtotime($r['CRIADO_EM'])) : $dataFmt
        ];
    }
} catch (Throwable $e) {}

$tarefasHoje = [];
foreach ($tarefas as $t) {
    $tarefasHoje[] = [
        'titulo' => $t['TITULO'] ?? $t['titulo'] ?? 'Tarefa',
        'status' => $t['STATUS'] ?? $t['status'] ?? 'Pendente',
        'prazo'  => !empty($t['DATA_ENTREGA']) ? date('d/m/Y', strtotime($t['DATA_ENTREGA'])) : '-'
    ];
}
$recadosDestaque = array_slice($recados, 0, 3);
?>

<style>
    .dashboard-wrapper { padding-bottom: 2rem; }
    .metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .metric-card { background: #fff; border-radius: 24px; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border: 1px solid rgba(226,232,240,0.5); position: relative; }
    .metric-icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #f0fdf4; color: #22c55e; }
    .icon-purple { background: #f5f3ff; color: #8b5cf6; }
    .icon-red { background: #fef2f2; color: #ef4444; }
    .metric-value { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin: 0; }
    .metric-label { color: #94a3b8; font-size: 0.85rem; font-weight: 600; }
    
    .dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
    .main-column { display: flex; flex-direction: column; gap: 2rem; }
    .content-card { background: #fff; border-radius: 24px; padding: 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); border: 1px solid rgba(226,232,240,0.5); }
    
    .action-button { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 112px; padding: 1.25rem .85rem; border-radius: 20px; color: #fff; font-weight: 800; font-size: 0.74rem; line-height: 1.18; text-align: center; text-transform: uppercase; text-decoration: none; transition: 0.2s; }
    .action-button i { font-size: 1.35rem; line-height: 1; }
    .action-button span { display: block; max-width: 92px; }
    .action-button:hover { transform: translateY(-3px); filter: brightness(1.1); color: #fff; }
    .btn-blue { background: #3b82f6; }
    .btn-green { background: #10b981; }
    .btn-orange { background: #f59e0b; }
    .btn-purple { background: #8b5cf6; }

    .notification-list { display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1.5rem; }
    .notif-item { display: flex; gap: 1rem; }
    .notif-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid #3b82f6; margin-top: 4px; flex-shrink: 0; }
    .notif-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0; }
    .notif-text { font-size: 0.8rem; color: #64748b; margin: 2px 0 0; }

    @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }
</style>

<div class="dashboard-wrapper">
    <div class="metrics-row">
        <div class="metric-card">
            <div class="metric-icon-box icon-blue"><i class="bi bi-shield-check"></i></div>
            <p class="metric-label">Aprovações Pendentes</p>
            <h3 class="metric-value"><?php echo $totalAprovacoes; ?></h3>
        </div>
        <div class="metric-card">
            <div class="metric-icon-box icon-green"><i class="bi bi-wallet2"></i></div>
            <p class="metric-label">Meus Reembolsos</p>
            <h3 class="metric-value">R$ <?php echo number_format($totalDespesas, 2, ',', '.'); ?></h3>
        </div>
        <div class="metric-card">
            <div class="metric-icon-box icon-purple"><i class="bi bi-kanban"></i></div>
            <p class="metric-label">Minhas Tarefas</p>
            <h3 class="metric-value"><?php echo count($tarefas); ?></h3>
        </div>
        <div class="metric-card">
            <div class="metric-icon-box icon-red"><i class="bi bi-chat-left-dots"></i></div>
            <p class="metric-label">Novas Notificações</p>
            <h3 class="metric-value"><?php echo $totalNotif; ?></h3>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="main-column">
            <div class="content-card">
                <h4 class="fw-bold mb-4">Fluxo de Importações</h4>
                <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
                    <span class="text-muted">Gráfico de desempenho (Atualizado hoje)</span>
                </div>
            </div>

            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Minhas Tarefas</h4>
                    <a href="index.php?page=tarefas" class="btn btn-sm btn-light rounded-pill px-3">Ver tudo</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="text-muted small">
                                <th>TAREFA</th>
                                <th>STATUS</th>
                                <th>PRAZO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarefasHoje as $t): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($t['titulo']); ?></td>
                                <td><span class="badge bg-primary-subtle text-primary rounded-pill"><?php echo $t['status']; ?></span></td>
                                <td class="text-muted"><?php echo $t['prazo']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="side-column">
            <div class="content-card mb-4">
                <h5 class="fw-bold mb-4">Ações Rápidas</h5>
                <div class="row g-3">
                    <div class="col-6">
                        <a href="index.php?page=home_importacao" class="action-button btn-blue">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            <span>NOVA IMPORTAÇÃO</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=despesas" class="action-button btn-green">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>LANÇAR DESPESA</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=inventario_ti" class="action-button btn-orange">
                            <i class="bi bi-boxes"></i>
                            <span>INVENTARIO</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=lancamento_campanhas" class="action-button btn-purple">
                            <i class="bi bi-megaphone-fill"></i>
                            <span>CAMPANHAS</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h5 class="fw-bold mb-3">Notificações</h5>
                <div class="notification-list">
                    <?php foreach ($recadosDestaque as $r): ?>
                    <div class="notif-item">
                        <div class="notif-dot"></div>
                        <div class="notif-body">
                            <p class="notif-title"><?php echo htmlspecialchars($r['titulo']); ?></p>
                            <p class="notif-text"><?php echo htmlspecialchars($r['texto']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
