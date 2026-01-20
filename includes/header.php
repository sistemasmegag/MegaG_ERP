<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Dados Mega G</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
    /* ==========================
       Clean SaaS Tokens + Theme
       ========================== */
    :root{
        --saas-bg: #f6f8fb;
        --saas-surface: #ffffff;
        --saas-text: #111827;
        --saas-muted: rgba(17,24,39,.62);
        --saas-border: rgba(17,24,39,.10);
        --saas-shadow: 0 12px 30px rgba(17,24,39,.08);
        --saas-shadow-soft: 0 10px 30px rgba(17,24,39,.06);

        /* Sidebar (Light SaaS) */
        --sidebar-bg: rgba(255,255,255,.72);
        --sidebar-text: rgba(17,24,39,.70);
        --sidebar-text-strong: rgba(17,24,39,.90);
        --sidebar-hover: rgba(13,110,253,.08);
        --sidebar-active-bg: rgba(13,110,253,.12);
        --accent: #0d6efd;
        --accent-red: #ff4757;

        --ring: rgba(13,110,253,.14);
    }

    html[data-theme="dark"]{
        --saas-bg: #0b1220;
        --saas-surface: rgba(255,255,255,.06);
        --saas-text: rgba(255,255,255,.92);
        --saas-muted: rgba(255,255,255,.66);
        --saas-border: rgba(255,255,255,.10);
        --saas-shadow: 0 16px 40px rgba(0,0,0,.35);
        --saas-shadow-soft: 0 14px 40px rgba(0,0,0,.25);

        /* Sidebar (Dark SaaS) */
        --sidebar-bg: rgba(255,255,255,.06);
        --sidebar-text: rgba(255,255,255,.72);
        --sidebar-text-strong: rgba(255,255,255,.92);
        --sidebar-hover: rgba(13,110,253,.14);
        --sidebar-active-bg: rgba(13,110,253,.18);
        --accent: #6ea8fe;

        --ring: rgba(13,110,253,.22);
    }

    body{
        height: 100vh;
        overflow: hidden; /* Scroll interno apenas */
        background:
            radial-gradient(1200px 600px at 15% 10%, rgba(13,110,253,.14), transparent 60%),
            radial-gradient(1000px 500px at 85% 25%, rgba(25,135,84,.10), transparent 55%),
            var(--saas-bg);
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: var(--saas-text);
    }

    /* ==========================
       Sidebar Clean SaaS
       ========================== */
    .modern-sidebar{
        width: 270px;
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        display:flex;
        flex-direction:column;
        height:100vh;
        transition: all .28s ease;
        padding: 1.25rem 1rem;
        border-right: 1px solid var(--saas-border);
        box-shadow: var(--saas-shadow-soft);
        backdrop-filter: blur(12px);
    }

    .modern-sidebar .brand{
        color: var(--sidebar-text-strong);
        font-weight: 900;
        font-size: 1.05rem;
        letter-spacing: -.02em;
        margin-bottom: 0;
        display:flex;
        align-items:center;
        text-decoration:none;
        gap:.65rem;
    }

    /* Barra de busca */
    .sidebar-search input{
        background: rgba(17,24,39,.04);
        border: 1px solid var(--saas-border);
        color: var(--saas-text);
        border-radius: 14px;
        padding-left: 2.5rem;
        padding-right: .75rem;
        height: 42px;
        box-shadow: none;
    }
    html[data-theme="dark"] .sidebar-search input{
        background: rgba(255,255,255,.06);
    }
    .sidebar-search input::placeholder{ color: rgba(17,24,39,.40); }
    html[data-theme="dark"] .sidebar-search input::placeholder{ color: rgba(255,255,255,.45); }

    .sidebar-search input:focus{
        border-color: rgba(13,110,253,.45);
        box-shadow: 0 0 0 .22rem var(--ring);
        background: var(--saas-surface);
    }

    .sidebar-search .bi-search{
        position:absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(17,24,39,.35);
    }
    html[data-theme="dark"] .sidebar-search .bi-search{ color: rgba(255,255,255,.40); }

    /* Seções */
    .menu-header{
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: rgba(17,24,39,.45);
        margin-top: 1.25rem;
        margin-bottom: .5rem;
        padding-left: .85rem;
        font-weight: 800;
    }
    html[data-theme="dark"] .menu-header{ color: rgba(255,255,255,.45); }
    
    /* Modal de permissão negada na sidebar */
    .nav-link.is-denied{
    opacity: .55;
    cursor: not-allowed;
    }
    .nav-link.is-denied:hover{
    transform: none !important;
    background: rgba(220,53,69,.08) !important;
    border-color: rgba(220,53,69,.18) !important;
    }

    /* Links */
    .modern-sidebar .nav-pills .nav-link{
        color: var(--sidebar-text);
        font-weight: 700;
        padding: .72rem .9rem;
        border-radius: 14px;
        margin-bottom: 6px;
        transition: all .18s ease;
        display:flex;
        align-items:center;
        justify-content: space-between;
        border: 1px solid transparent;
    }
    .modern-sidebar .nav-pills .nav-link i{ font-size: 1.05rem; }
    .modern-sidebar .nav-pills .nav-link:hover{
        background: var(--sidebar-hover);
        color: var(--sidebar-text-strong);
        border-color: rgba(13,110,253,.12);
        transform: translateY(-1px);
    }

    /* Ativo */
    .modern-sidebar .nav-pills .nav-link.active{
        background: var(--sidebar-active-bg);
        color: var(--sidebar-text-strong);
        border-color: rgba(13,110,253,.18);
        position:relative;
        box-shadow: 0 10px 18px rgba(13,110,253,.10);
    }
    .modern-sidebar .nav-pills .nav-link.active::before{
        content:'';
        position:absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        height: 18px;
        width: 6px;
        background: var(--accent);
        border-radius: 999px;
    }

    /* Badges */
    .badge-sidebar{
        background-color: var(--accent-red);
        color: white;
        font-size: .70rem;
        padding: 3px 8px;
        border-radius: 999px;
        font-weight: 900;
        letter-spacing: .02em;
    }

    /* Theme toggler (compact) */
    .theme-toggler{
        background: rgba(17,24,39,.04);
        border: 1px solid var(--saas-border);
        border-radius: 14px;
        padding: 4px;
        display:flex;
        margin-top:auto;
        margin-bottom: .9rem;
        gap: 4px;
    }
    html[data-theme="dark"] .theme-toggler{ background: rgba(255,255,255,.06); }
    .theme-toggler div{
        flex:1;
        text-align:center;
        padding: 8px 10px;
        font-size: .85rem;
        cursor:pointer;
        border-radius: 12px;
        color: var(--saas-muted);
        font-weight: 800;
        user-select: none;
    }
    .theme-toggler div.active{
        background: var(--sidebar-active-bg);
        color: var(--sidebar-text-strong);
    }

    /* Perfil usuário */
    .user-profile{
        display:flex;
        align-items:center;
        padding-top: 1rem;
        border-top: 1px solid var(--saas-border);
    }
    .user-avatar{
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight: 900;
        background: rgba(13,110,253,.12);
        color: var(--sidebar-text-strong);
        border: 1px solid rgba(13,110,253,.18);
        margin-right: 12px;
    }
    html[data-theme="dark"] .user-avatar{
        background: rgba(13,110,253,.16);
        border-color: rgba(13,110,253,.22);
    }

    .user-info{ flex-grow:1; overflow:hidden; }
    .user-info h6{
        margin:0;
        color: var(--sidebar-text-strong);
        font-weight: 900;
        font-size: .95rem;
        letter-spacing: -.01em;
    }
    .user-info small{
        color: var(--saas-muted);
        font-size: .80rem;
        white-space: nowrap; overflow:hidden; text-overflow: ellipsis; display:block;
    }
    .logout-btn{
        color: var(--saas-muted);
        transition: .2s;
    }
    .logout-btn:hover{
        color: var(--accent-red);
        transform: translateY(-1px);
    }

    /* Área principal */
    .main-content{
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;        /* importante no flex */
        overflow-y: auto;
        padding: 2rem;
    }

    /* garante que o shell ocupe a viewport toda */
    body > .d-flex{
        width: 100vw;
    }

    /* se alguma página usar .container do bootstrap, isso remove o "max-width" */
    .main-content .container{
        max-width: none !important;
        width: 100% !important;
    }

    /* Mantidos: console/table (só adaptando superfície e borda pro tema) */
    .console-container{ background-color: #1e1e1e; border-radius: 14px; border: 1px solid #444; box-shadow: 0 4px 6px rgba(0,0,0,0.3); overflow:hidden; }
    .console-header{ background-color:#343a40; color:#adb5bd; padding:8px 15px; font-size:.85rem; display:flex; align-items:center; gap:8px; border-bottom:1px solid #444; text-transform:uppercase; letter-spacing:1px; font-weight:bold; }
    .console-body{ height:400px; overflow-y:auto; padding:15px; font-family:'Consolas','Monaco','Courier New',monospace; font-size:.9rem; color:#d4d4d4; background-color:#1e1e1e; }
    .table-container{ background: var(--saas-surface); border-radius: 14px; box-shadow: var(--saas-shadow-soft); overflow:hidden; border: 1px solid var(--saas-border); }
    thead{ background-color: rgba(17,24,39,.03); color: var(--saas-text); }
    html[data-theme="dark"] thead{ background-color: rgba(255,255,255,.06); }
    th{ font-weight:800; text-transform:uppercase; font-size:.75rem; letter-spacing:.5px; white-space:nowrap; border-bottom:2px solid rgba(17,24,39,.08) !important; }
    html[data-theme="dark"] th{ border-bottom-color: rgba(255,255,255,.10) !important; }
    td{ font-size:.85rem; vertical-align:middle; white-space:nowrap; border-color: rgba(17,24,39,.06); }
    html[data-theme="dark"] td{ border-color: rgba(255,255,255,.08); }
    .col-msg{ max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor: help; }
    .col-hist{ max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor: help; }
    .log-item{ margin-bottom:4px; padding:2px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }
    .log-erro{ color:#ff6b6b; }
    .log-aviso{ color:#fcc419; }
    .log-sistema{ color:#74c0fc; font-weight:bold; margin-top:15px; border-top:1px dashed #444; padding-top:10px; }
    .log-sucesso{ color:#51cf66; font-weight:bold; }
    .console-body::-webkit-scrollbar{ width:10px; }
    .console-body::-webkit-scrollbar-track{ background:#1e1e1e; }
    .console-body::-webkit-scrollbar-thumb{ background:#555; border-radius:5px; border:2px solid #1e1e1e; }
    .console-body::-webkit-scrollbar-thumb:hover{ background:#777; }
    #loading{ display:none; }

    /* Mobile */
    .mobile-toggle{
        display:none;
        background:none;
        border:none;
        color: var(--saas-text);
        font-size: 1.5rem;
        padding: .5rem;
        cursor:pointer;
    }

    .sidebar-overlay{
        display:none;
        position:fixed;
        top:0; left:0; right:0; bottom:0;
        background: rgba(0,0,0,0.5);
        z-index:1040;
        backdrop-filter: blur(2px);
    }

    @media (max-width: 768px){
        .modern-sidebar{
            display:flex !important;
            position:fixed;
            left:-290px;
            top:0;
            bottom:0;
            z-index:1050;
            width:290px;
            box-shadow: 10px 0 30px rgba(0,0,0,0.22);
        }
        .modern-sidebar.show{ left: 0; }
        .mobile-toggle{ display:block; }
        .main-content{ padding: 1rem; }
    }
    </style>
</head>
<body>

<div class="d-flex h-100 w-100" style="width:100vw;">

<script>
/* ===== Tema global (auto + persistência) ===== */
(function(){
    const root = document.documentElement;

    function applyTheme(theme){
        root.setAttribute('data-theme', theme);
        try { localStorage.setItem('theme', theme); } catch(e) {}
        // Atualiza botões do toggler se existirem
        const lightBtn = document.getElementById('themeLight');
        const darkBtn  = document.getElementById('themeDark');
        if (lightBtn && darkBtn){
            lightBtn.classList.toggle('active', theme === 'light');
            darkBtn.classList.toggle('active', theme === 'dark');
        }
    }

    const saved = (() => { try { return localStorage.getItem('theme'); } catch(e){ return null; } })();
    if (saved === 'dark' || saved === 'light'){
        applyTheme(saved);
    } else {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    // expõe pro sidebar poder chamar sem depender de bundler
    window.__applyTheme = applyTheme;
})();

/* ===== toggleMenu fallback (se já existir em outro lugar, não sobrescreve) ===== */
if (typeof window.toggleMenu !== 'function'){
    window.toggleMenu = function(){
        const sidebar = document.getElementById('sidebarMenu');
        if (!sidebar) return;

        let overlay = document.getElementById('sidebarOverlay');
        if (!overlay){
            overlay = document.createElement('div');
            overlay.id = 'sidebarOverlay';
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', function(){
                sidebar.classList.remove('show');
                overlay.style.display = 'none';
            });
            document.body.appendChild(overlay);
        }

        const isOpen = sidebar.classList.toggle('show');
        overlay.style.display = isOpen ? 'block' : 'none';
    }
}
</script>
