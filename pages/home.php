<?php
// pages/home_usuario.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$user = $_SESSION['usuario'] ?? 'Usuário';
$menuApps = $_SESSION['menu_apps'] ?? [];

// Helpers de normalização (caso não estejam no helpers)
if (!function_exists('normalizeLinkMenu')) {
    function normalizeLinkMenu($linkMenu) {
        $linkMenu = trim((string)$linkMenu);
        if ($linkMenu !== '' && stripos($linkMenu, '.php') !== false) {
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

// Placeholders (depois você pluga banco/rotinas)
// Você pode preencher isso no login_action.php, ou via consultas.
$recados = $_SESSION['recados'] ?? [
    ['titulo' => 'Bem-vindo!', 'texto' => 'Sua área pessoal está pronta. Em breve teremos recados e lembretes aqui.', 'tipo' => 'info', 'quando' => $dataFmt . ' ' . $horaFmt],
];

$tarefas = $_SESSION['tarefas'] ?? [
    ['titulo' => 'Revisar importações pendentes', 'status' => 'Pendente', 'prazo' => $dataFmt],
    ['titulo' => 'Validar comissões do mês', 'status' => 'Em andamento', 'prazo' => $dataFmt],
];

// Monta atalhos: pega do menu_apps (limita e ordena por ORDEM_APLICACAO)
$atalhos = [];
foreach ($menuApps as $app) {
    $linkRaw = (string)($app['LINKMENU'] ?? '');
    $slug    = normalizeLinkMenu($linkRaw);
    if ($slug === '' || $slug === 'home') continue;

    $atalhos[] = [
        'nome' => (string)($app['APLICACAO'] ?? $slug),
        'slug' => $slug,
        'mod'  => (string)($app['CODMODULO'] ?? 'OUTROS'),
        'ord'  => (int)($app['ORDEM_APLICACAO'] ?? 9999),
    ];
}

usort($atalhos, function($a, $b){
    if ($a['ord'] === $b['ord']) return strcmp($a['nome'], $b['nome']);
    return $a['ord'] <=> $b['ord'];
});

// limita atalhos para não poluir
$atalhos = array_slice($atalhos, 0, 10);

// Badges por tipo (recados)
function badgeRecado($tipo) {
    $tipo = strtolower((string)$tipo);
    if ($tipo === 'alerta' || $tipo === 'erro') return 'bg-danger bg-opacity-10 text-danger';
    if ($tipo === 'aviso') return 'bg-warning bg-opacity-10 text-warning';
    if ($tipo === 'sucesso') return 'bg-success bg-opacity-10 text-success';
    return 'bg-primary bg-opacity-10 text-primary';
}
?>

<style>
/* ===== Home do Usuário – SaaS/ERP ===== */
.userhub-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(99,102,241,.14), rgba(99,102,241,.05));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .userhub-head{
    background: linear-gradient(135deg, rgba(99,102,241,.16), rgba(255,255,255,.02));
}
.userhub-head:before{
    content:"";
    position:absolute;
    inset:-140px -200px auto auto;
    width: 380px;
    height: 380px;
    background: radial-gradient(circle at 30% 30%, rgba(99,102,241,.32), transparent 60%);
    filter: blur(6px);
    transform: rotate(8deg);
    pointer-events:none;
}
.userhub-title{ font-weight: 900; letter-spacing: -.02em; margin:0; color: var(--saas-text); }
.userhub-sub{ margin: 6px 0 0; color: var(--saas-muted); font-size: 14px; }

.saas-card{
    background: var(--saas-surface) !important;
    border: 1px solid var(--saas-border) !important;
    border-radius: 18px !important;
    box-shadow: var(--saas-shadow) !important;
    overflow:hidden;
}
.saas-card .card-header{
    background: transparent !important;
    border-bottom: 1px solid var(--saas-border) !important;
}
.saas-kicker{
    color: var(--saas-muted);
    font-size: 12px;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 900;
}
.item-row{
    padding: 12px 14px;
    border: 1px solid rgba(17,24,39,.08);
    border-radius: 14px;
    background: rgba(255,255,255,.55);
}
html[data-theme="dark"] .item-row{
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
}
.mini-muted{ color: var(--saas-muted); font-size: 12px; }

.quick-link{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(17,24,39,.08);
    background: rgba(255,255,255,.55);
    text-decoration:none;
    color: inherit;
}
.quick-link:hover{
    transform: translateY(-1px);
    box-shadow: 0 14px 26px rgba(0,0,0,.06);
}
html[data-theme="dark"] .quick-link{
    background: rgba(255,255,255,.04);
    border-color: rgba(255,255,255,.08);
}
</style>

<div class="container-fluid">

    <div class="userhub-head mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
            <div>
                <h3 class="userhub-title">Olá, <?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?> 👋</h3>
                <p class="userhub-sub">
                    <?php echo $dataFmt; ?> • <?php echo $horaFmt; ?> — sua central de recados, tarefas e atalhos.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">
                    <i class="bi bi-inboxes me-1"></i> Central do Usuário
                </span>
                <a class="btn btn-outline-secondary rounded-pill px-3" href="index.php?page=home">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Ir para Importação
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Coluna 1: Tarefas -->
        <div class="col-lg-4">
            <div class="card saas-card">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="saas-kicker">Minhas tarefas</div>
                        <div class="fw-bold" style="letter-spacing:-.01em;">Pendências e foco do dia</div>
                    </div>
                    <a href="index.php?page=tarefas" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-kanban me-1"></i> Kanban
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($tarefas)): ?>
                        <div class="text-muted">Sem tarefas por aqui. 🎉</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($tarefas as $t): ?>
                                <?php
                                    $st = strtolower((string)($t['status'] ?? 'pendente'));
                                    $badge = 'bg-secondary bg-opacity-10 text-secondary';
                                    if ($st === 'pendente') $badge = 'bg-warning bg-opacity-10 text-warning';
                                    if ($st === 'em andamento') $badge = 'bg-primary bg-opacity-10 text-primary';
                                    if ($st === 'concluida' || $st === 'concluída') $badge = 'bg-success bg-opacity-10 text-success';
                                ?>
                                <div class="item-row">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($t['titulo'] ?? 'Tarefa', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="mini-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                Prazo: <?php echo htmlspecialchars($t['prazo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                        <span class="badge rounded-pill <?php echo $badge; ?> fw-bold">
                                            <?php echo htmlspecialchars($t['status'] ?? 'Pendente', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="index.php?page=tarefas" class="text-decoration-none fw-bold">
                            Ver todas <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna 2: Recados -->
        <div class="col-lg-5">
            <div class="card saas-card">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="saas-kicker">Últimos recados</div>
                        <div class="fw-bold" style="letter-spacing:-.01em;">Avisos, novidades e comunicados</div>
                    </div>
                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">
                        <i class="bi bi-megaphone me-1"></i> Mural
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($recados)): ?>
                        <div class="text-muted">Nenhum recado no momento.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($recados as $r): ?>
                                <div class="item-row">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($r['titulo'] ?? 'Recado', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-muted" style="font-size:14px;">
                                                <?php echo htmlspecialchars($r['texto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div class="mini-muted mt-1">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo htmlspecialchars($r['quando'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                        <span class="badge rounded-pill <?php echo badgeRecado($r['tipo'] ?? 'info'); ?> fw-bold">
                                            <?php echo htmlspecialchars(strtoupper((string)($r['tipo'] ?? 'INFO')), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="index.php?page=recados" class="text-decoration-none fw-bold">
                            Ver mural completo <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna 3: Atalhos -->
        <div class="col-lg-3">
            <div class="card saas-card">
                <div class="card-header py-3">
                    <div class="saas-kicker">Atalhos</div>
                    <div class="fw-bold" style="letter-spacing:-.01em;">Acesso rápido</div>
                </div>
                <div class="card-body">
                    <?php if (empty($atalhos)): ?>
                        <div class="text-muted">Sem atalhos disponíveis.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($atalhos as $a): ?>
                                <a class="quick-link" href="index.php?page=<?php echo urlencode($a['slug']); ?>">
                                    <div class="d-flex flex-column">
                                        <div class="fw-bold"><?php echo htmlspecialchars($a['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="mini-muted"><?php echo htmlspecialchars($a['mod'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 text-muted" style="font-size:12px;">
                        *Atalhos baseados no seu menu/permissão.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
