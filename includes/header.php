<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP - MegaG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/v4-shims.min.css">

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