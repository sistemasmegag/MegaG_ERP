<?php 
require '../check_session.php'; 
if ($_SESSION['nivel'] !== 'ADMIN') { header('Location: home.php'); exit; }
$paginaAtual = 'usuarios'; 
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<style>
/* ===== Clean SaaS (escopado pra Usuários) ===== */
.saas-head{
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(13,110,253,.04));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 18px 18px;
    overflow:hidden;
    position:relative;
}
html[data-theme="dark"] .saas-head{
    background: linear-gradient(135deg, rgba(13,110,253,.14), rgba(255,255,255,.02));
}
.saas-head:before{
    content:"";
    position:absolute;
    inset:-130px -190px auto auto;
    width: 360px;
    height: 360px;
    background: radial-gradient(circle at 30% 30%, rgba(13,110,253,.26), transparent 60%);
    filter: blur(6px);
    transform: rotate(10deg);
    pointer-events:none;
}
.saas-title{
    font-weight: 900;
    letter-spacing: -.02em;
    margin:0;
    color: var(--saas-text);
}
.saas-subtitle{
    margin: 6px 0 0;
    color: var(--saas-muted);
    font-size: 14px;
}

.saas-primary-btn{
    border-radius: 14px;
    font-weight: 900;
    letter-spacing: .01em;
    box-shadow: 0 10px 18px rgba(13,110,253,.18);
}

/* Card/tabela SaaS */
.saas-card{
    background: var(--saas-surface) !important;
    border: 1px solid var(--saas-border) !important;
    border-radius: 18px !important;
    box-shadow: var(--saas-shadow) !important;
    overflow:hidden;
    backdrop-filter: blur(10px);
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

/* Tabela sticky + clean */
.saas-table-wrap{
    overflow:hidden;
}
.saas-table-scroll{
    max-height: 68vh;
    overflow:auto;
    scrollbar-width: thin;
}
.saas-table-scroll::-webkit-scrollbar{ width: 10px; height: 10px; }
.saas-table-scroll::-webkit-scrollbar-track{ background: rgba(17,24,39,.04); }
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-track{ background: rgba(255,255,255,.06); }
.saas-table-scroll::-webkit-scrollbar-thumb{
    background: rgba(17,24,39,.18);
    border-radius: 999px;
    border: 2px solid rgba(0,0,0,0.06);
}
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-thumb{
    background: rgba(255,255,255,.18);
    border-color: rgba(0,0,0,0.25);
}
.saas-table-scroll::-webkit-scrollbar-thumb:hover{ background: rgba(17,24,39,.28); }
html[data-theme="dark"] .saas-table-scroll::-webkit-scrollbar-thumb:hover{ background: rgba(255,255,255,.26); }

.saas-table thead th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: rgba(17,24,39,.03) !important;
    color: var(--saas-text) !important;
    border-bottom: 1px solid var(--saas-border) !important;
}
html[data-theme="dark"] .saas-table thead th{
    background: rgba(255,255,255,.06) !important;
}
.saas-table tbody tr:hover{
    background: rgba(13,110,253,.06) !important;
}
html[data-theme="dark"] .saas-table tbody tr:hover{
    background: rgba(13,110,253,.12) !important;
}

/* Badge nível mais SaaS */
.badge-soft{
    border-radius: 999px;
    padding: 7px 10px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
    color: var(--saas-text);
}
html[data-theme="dark"] .badge-soft{
    background: rgba(255,255,255,.06);
}
.badge-admin{
    background: rgba(13,110,253,.12) !important;
    border-color: rgba(13,110,253,.22) !important;
    color: #0b5ed7 !important;
}
html[data-theme="dark"] .badge-admin{
    color: rgba(255,255,255,.92) !important;
}
.badge-user{
    background: rgba(108,117,125,.10) !important;
    border-color: rgba(108,117,125,.18) !important;
    color: rgba(17,24,39,.75) !important;
}
html[data-theme="dark"] .badge-user{
    color: rgba(255,255,255,.78) !important;
}

/* Botão revogar */
.btn-revoke{
    border-radius: 12px;
    font-weight: 900;
}

/* Modal SaaS */
#modalUsuario .modal-content{
    border-radius: 18px;
    border: 1px solid var(--saas-border);
    background: var(--saas-surface);
    color: var(--saas-text);
    box-shadow: var(--saas-shadow);
}
#modalUsuario .modal-header{
    border-bottom: 1px solid var(--saas-border);
}
#modalUsuario .form-label{
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .10em;
    text-transform: uppercase;
    color: var(--saas-muted);
}
#modalUsuario .form-control,
#modalUsuario .form-select{
    border-radius: 14px;
    border: 1px solid var(--saas-border);
    background: rgba(17,24,39,.03);
    color: var(--saas-text);
    height: 44px;
}
html[data-theme="dark"] #modalUsuario .form-control,
html[data-theme="dark"] #modalUsuario .form-select{
    background: rgba(255,255,255,.06);
}
#modalUsuario .form-control:focus,
#modalUsuario .form-select:focus{
    border-color: rgba(13,110,253,.45);
    box-shadow: 0 0 0 .22rem var(--ring);
    background: var(--saas-surface);
}
#modalUsuario .modal-footer{
    border-top: 1px solid var(--saas-border);
}
#modalUsuario .helpbox{
    border: 1px solid var(--saas-border);
    background: rgba(13,110,253,.06);
    border-radius: 14px;
    padding: 10px 12px;
    color: var(--saas-muted);
    font-size: 13px;
}
html[data-theme="dark"] #modalUsuario .helpbox{
    background: rgba(13,110,253,.12);
}

.text-muted{ color: var(--saas-muted) !important; }
.text-dark{ color: var(--saas-text) !important; }
</style>

<main class="main-content">
    <div class="container-fluid">

        <!-- Header SaaS -->
        <div class="saas-head mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
                <div>
                    <h3 class="saas-title">Gestão de Permissões Web</h3>
                    <p class="saas-subtitle">Controle acessos e níveis (USER/ADMIN) para os módulos do sistema.</p>
                </div>

                <button class="btn btn-primary saas-primary-btn" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                    <i class="bi bi-person-plus-fill me-2"></i> Adicionar Acesso
                </button>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card saas-card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-people-fill fs-5 text-primary"></i>
                    </div>
                    <div>
                        <div class="saas-kicker">Acessos</div>
                        <div class="fw-bold text-dark" style="letter-spacing:-.01em;">Usuários com Permissão Web</div>
                    </div>
                </div>
                <div class="text-muted small">Revogue acessos quando necessário</div>
            </div>

            <div class="card-body p-0 saas-table-wrap">
                <div class="saas-table-scroll">
                    <table class="table table-hover align-middle mb-0 saas-table">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Login</th>
                                <th class="py-3">Nome Completo</th>
                                <th class="py-3">Nível Web</th>
                                <th class="text-end pe-4 py-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaUsuarios"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal SaaS -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" style="letter-spacing:-.01em;">Conceder Acesso Web</h5>
                    <div class="text-muted small">Defina o usuário do ERP e o nível de acesso.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formUsuario">
                    <div class="mb-3">
                        <label class="form-label">Login do Usuário (ERP)</label>
                        <input type="text" id="inputUser" class="form-control" placeholder="EX: ABATAUTO" required style="text-transform: uppercase;">
                        <div class="helpbox mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            O usuário deve existir no cadastro do Consinco.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nível de Acesso</label>
                        <select id="inputNivel" class="form-select">
                            <option value="USER">Usuário (Operacional)</option>
                            <option value="ADMIN">Administrador (Total)</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary saas-primary-btn">Salvar Permissão</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    async function carregarUsuarios() {
        const tbody = document.getElementById('tabelaUsuarios');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-muted">Carregando...</td></tr>';

        try {
            const resp = await fetch('../api_usuarios.php');
            const json = await resp.json();

            if(!json.sucesso) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger p-4">${json.erro}</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            if(json.dados.length === 0) {
                 tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-muted">Nenhum usuário com acesso Web configurado.</td></tr>';
                 return;
            }

            json.dados.forEach(u => {
                const badge = u.NIVEL === 'ADMIN' 
                    ? '<span class="badge-soft badge-admin"><i class="bi bi-shield-lock me-1"></i>ADMIN</span>' 
                    : '<span class="badge-soft badge-user"><i class="bi bi-person me-1"></i>USER</span>';
                
                // NOME pode vir nulo se o join falhar, tratamos aqui
                const nome = u.NOME || '---';

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold">${u.USUARIO}</td>
                        <td>${nome}</td>
                        <td>${badge}</td>
                        <td class="text-end pe-4">
                            <button onclick="remover('${u.USUARIO}')" class="btn btn-sm btn-outline-danger btn-revoke">
                                <i class="bi bi-trash"></i> Revogar
                            </button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger p-4">Erro de conexão.</td></tr>';
        }
    }

    document.getElementById('formUsuario').addEventListener('submit', async (e) => {
        e.preventDefault();
        const usuario = document.getElementById('inputUser').value;
        const nivel = document.getElementById('inputNivel').value;

        try {
            const resp = await fetch('../api_usuarios.php', {
                method: 'POST',
                body: JSON.stringify({ usuario, nivel })
            });
            const json = await resp.json();
            
            if(json.sucesso) {
                bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                document.getElementById('formUsuario').reset();
                carregarUsuarios();
            } else {
                alert(json.erro);
            }
        } catch (e) { alert('Erro ao salvar'); }
    });

    async function remover(usuario) {
        if(!confirm(`Deseja revogar o acesso WEB de ${usuario}?`)) return;
        await fetch(`../api_usuarios.php?user=${usuario}`, { method: 'DELETE' });
        carregarUsuarios();
    }

    window.onload = carregarUsuarios;
</script>

<?php include '../includes/footer.php'; ?>
