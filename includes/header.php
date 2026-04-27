<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP - MegaG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Novas Cores Premium */
            --brand-primary: #3b82f6;
            --brand-primary-soft: rgba(59, 130, 246, 0.1);
            --brand-primary-glow: rgba(59, 130, 246, 0.15);
            --bg-body: #f8fafc;
            --sidebar-bg: #ffffff;
            --sidebar-text: #64748b;
            --sidebar-active: #1e293b;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --card-radius: 24px;
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --sidebar-bg: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-active: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif !important;
            background-color: var(--bg-body) !important;
        }

        /* Ajuste do layout principal */
        .app-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .main-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background-color: var(--bg-body);
        }

        .page-container {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
        }
    </style>

    <link rel="stylesheet" href="assets/css/saas-theme.css?v=<?= time() ?>">
</head>

<body>

    <div class="d-flex h-100 w-100" style="width:100vw;">

        <script>
            /* ===== Tema global (auto + persistência) ===== */
            (function () {
                const root = document.documentElement;

                function applyTheme(theme) {
                    root.setAttribute('data-theme', theme);
                    try { localStorage.setItem('theme', theme); } catch (e) { }
                    // Atualiza botões do toggler se existirem
                    const lightBtn = document.getElementById('themeLight');
                    const darkBtn = document.getElementById('themeDark');
                    if (lightBtn && darkBtn) {
                        lightBtn.classList.toggle('active', theme === 'light');
                        darkBtn.classList.toggle('active', theme === 'dark');
                    }
                }

                const saved = (() => { try { return localStorage.getItem('theme'); } catch (e) { return null; } })();
                if (saved === 'dark' || saved === 'light') {
                    applyTheme(saved);
                } else {
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    applyTheme(prefersDark ? 'dark' : 'light');
                }

                // expõe pro sidebar poder chamar sem depender de bundler
                window.__applyTheme = applyTheme;
            })();

            /* ===== toggleMenu fallback (se já existir em outro lugar, não sobrescreve) ===== */
            if (typeof window.toggleMenu !== 'function') {
                window.toggleMenu = function () {
                    const sidebar = document.getElementById('sidebarMenu');
                    if (!sidebar) return;

                    let overlay = document.getElementById('sidebarOverlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'sidebarOverlay';
                        overlay.className = 'sidebar-overlay';
                        overlay.addEventListener('click', function () {
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