<?php
// pages/wiki.php
?>
<div class="wrap">
    <div class="saas-page-head"
        style="background: linear-gradient(135deg, rgba(25, 135, 84, .10), rgba(25, 135, 84, .04));">
        <div>
            <h2 class="saas-title"><i class="bi bi-journal-bookmark-fill text-success"></i> Base de Conhecimento</h2>
            <p class="saas-subtitle">Documentação, Manuais, Políticas e Artigos Corporativos</p>
        </div>
        <div class="actions">
            <div class="d-flex align-items-center me-3" style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; color: var(--saas-muted);"></i>
                <input type="text" id="wikiSearch" class="saas-input" placeholder="Buscar artigos..."
                    style="padding-left: 36px; min-width: 250px;" onkeyup="if(event.key === 'Enter') loadArticles()">
            </div>
            <button class="saas-btn primary" onclick="openWikiModal('create')"
                style="background: #198754; border-color: #198754; color: white;"><i class="bi bi-pencil-square"></i>
                Escrever Artigo</button>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Sidebar de Categorias -->
        <div class="col-lg-3">
            <div class="saas-card p-0" style="position: sticky; top: 20px;">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold m-0" style="font-size: 1.1rem;">Tópicos</h5>
                </div>
                <div class="p-2" id="wikiCategories">
                    <!-- Categorias renderizadas via JS -->
                    <div class="wiki-cat-item active" onclick="filterCategory('')" data-cat="">
                        <i class="bi bi-collection"></i> Todos os Artigos
                    </div>
                    <div class="text-center p-3 text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Artigos e Visualização -->
        <div class="col-lg-9">

            <!-- Estado 1: Lista -->
            <div id="wikiListView">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <h5 class="fw-bold m-0" id="currentCategoryTitle">Todos os Artigos</h5>
                    <span class="text-muted small" id="articleCount">0 artigos encontrados</span>
                </div>

                <div class="row g-3" id="wikiArticlesGrid">
                    <!-- Artigos renderizados via JS -->
                </div>
            </div>

            <!-- Estado 2: Visualizando um Artigo -->
            <div id="wikiReadView" style="display: none;">
                <div class="saas-card border-success" style="border-top: 4px solid #198754 !important;">
                    <div class="p-4 p-md-5">
                        <button class="saas-btn mb-4" onclick="voltarParaLista()"><i class="bi bi-arrow-left"></i>
                            Voltar para Lista</button>

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="saas-badge success" id="readCat">CATEGORIA</span>
                            <span class="text-muted small"><i class="bi bi-clock"></i> <span
                                    id="readDate">00/00/0000</span></span>
                            <span class="text-muted small ms-2"><i class="bi bi-eye"></i> <span id="readViews">0</span>
                                leituras</span>
                        </div>

                        <h1 class="fw-bolder mb-4" id="readTitle" style="font-size: 2.2rem; letter-spacing: -0.02em;">
                            Título do Artigo</h1>

                        <div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold"
                                style="width: 44px; height: 44px; font-size: 1.2rem;" id="readAuthorAvatar">A</div>
                            <div>
                                <h6 class="m-0 fw-bold" id="readAuthor">Autor do Artigo</h6>
                                <p class="text-muted m-0 small">Membro da Equipe</p>
                            </div>
                            <div class="ms-auto">
                                <button class="saas-btn" onclick="editarArtigoAtual()"><i
                                        class="bi bi-pencil"></i></button>
                                <button class="saas-btn" onclick="window.print()"><i class="bi bi-printer"></i></button>
                            </div>
                        </div>

                        <div id="readContent" style="font-size: 1.1rem; line-height: 1.8; color: var(--saas-text);">
                            <!-- Conteúdo do artigo renderizado aqui -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Criar / Editar Artigo -->
<div class="saas-modal-backdrop" id="wikiModal">
    <div class="saas-modal" style="max-width: 800px;">
        <div class="saas-modal-header"
            style="background: linear-gradient(135deg, rgba(25, 135, 84, .10), transparent);">
            <h3 id="wikiModalTitle"><i class="bi bi-journal-plus"></i> Escrever Artigo</h3>
            <button class="saas-btn" style="padding: 4px 8px;" onclick="closeWikiModal()"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="saas-modal-body">
            <input type="hidden" id="formWikiId">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="saas-kicker d-block mb-1">Título do Artigo</label>
                    <input type="text" id="formWikiTitle" class="saas-input"
                        placeholder="Ex: Manual de Integração da API">
                </div>
                <div class="col-md-4">
                    <label class="saas-kicker d-block mb-1">Categoria</label>
                    <select id="formWikiCat" class="saas-select">
                        <option value="SISTEMA">Sistema & Manuais</option>
                        <option value="RH">Recursos Humanos</option>
                        <option value="TI">Suporte TI</option>
                        <option value="COMERCIAL">Vendas & CRM</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="saas-kicker d-block mb-1">Conteúdo (Markdown Simples / Texto)</label>
                    <textarea id="formWikiContent" class="saas-textarea" rows="12"
                        placeholder="Escreva o conteúdo do artigo aqui... Pode usar linha em branco para separar parágrafos."
                        style="font-family: monospace;"></textarea>
                </div>
            </div>
        </div>
        <div class="saas-modal-footer">
            <button class="saas-btn danger d-none" id="btnDeleteWiki" onclick="deleteWiki()">Excluir Artigo</button>
            <button class="saas-btn" onclick="closeWikiModal()">Cancelar</button>
            <button class="saas-btn" style="background: #198754; border-color: #198754; color: white;"
                id="btnSalvarWiki" onclick="salvarWiki()"><i class="bi bi-check2"></i> Publicar Artigo</button>
        </div>
    </div>
</div>

<style>
    /* Estilos Especiais Wiki */
    .wiki-cat-item {
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 4px;
        cursor: pointer;
        color: var(--saas-muted);
        font-weight: 700;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .wiki-cat-item:hover {
        background: rgba(17, 24, 39, .04);
        color: var(--saas-text);
    }

    html[data-theme="dark"] .wiki-cat-item:hover {
        background: rgba(255, 255, 255, .04);
    }

    .wiki-cat-item.active {
        background: rgba(25, 135, 84, .10);
        color: #198754;
    }

    html[data-theme="dark"] .wiki-cat-item.active {
        color: #20c997;
    }

    .wiki-card {
        background: var(--saas-card);
        border: 1px solid var(--saas-border);
        border-radius: 16px;
        padding: 24px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }

    .wiki-card:hover {
        box-shadow: var(--saas-shadow-soft);
        transform: translateY(-3px);
        border-color: rgba(25, 135, 84, .30);
    }

    .wiki-card-title {
        font-weight: 800;
        font-size: 1.15rem;
        margin-bottom: 8px;
        color: var(--saas-text);
        line-height: 1.3;
    }

    .wiki-card-desc {
        color: var(--saas-muted);
        font-size: 0.95rem;
        line-height: 1.5;
        margin-bottom: 16px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script>
    const API_WIKI = 'api/wiki.php';
    let currentCategory = '';
    let memoryArticles = []; // Pra evitar fizes request na visualização (mock)
    let currentArticleObj = null;

    async function loadCategories() {
        try {
            const res = await fetch(API_WIKI + '?acao=CATEGORIAS');
            const json = await res.json();
            if (json.sucesso) {
                const el = document.getElementById('wikiCategories');
                el.innerHTML = `
                    <div class="wiki-cat-item active" onclick="filterCategory(this, '')">
                        <i class="bi bi-collection"></i> Todos os Artigos
                    </div>
                `;
                json.dados.forEach(cat => {
                    el.innerHTML += `
                        <div class="wiki-cat-item" onclick="filterCategory(this, '${cat.ID}')">
                            <i class="bi ${cat.ICONE}"></i> ${cat.NOME}
                        </div>
                    `;
                });
            }
        } catch (e) { console.error(e); }
    }

    async function loadArticles() {
        const busca = document.getElementById('wikiSearch').value;
        const url = `${API_WIKI}?acao=LISTAR&categoria=${currentCategory}&busca=${encodeURIComponent(busca)}`;

        try {
            const res = await fetch(url);
            const json = await res.json();

            if (json.sucesso) {
                memoryArticles = json.dados;
                document.getElementById('articleCount').textContent = json.dados.length + ' artigos encontrados';

                const grid = document.getElementById('wikiArticlesGrid');
                grid.innerHTML = '';

                if (json.dados.length === 0) {
                    grid.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 fw-bold text-muted">Nenhum artigo encontrado</h5>
                            <p class="text-muted">Tente ajustar seus termos de busca ou categoria.</p>
                        </div>
                    `;
                    return;
                }

                json.dados.forEach(art => {
                    const avatarStr = art.AUTOR ? art.AUTOR.substring(0, 1).toUpperCase() : 'A';

                    grid.innerHTML += `
                        <div class="col-md-6 mb-3">
                            <div class="wiki-card" onclick='abrirArtigo(${JSON.stringify(art).replace(/'/g, "&#39;")})'>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="saas-badge" style="background: rgba(25, 135, 84, .10); color: #198754; font-size: 0.70rem;">${art.CATEGORIA}</span>
                                    <span class="text-muted small"><i class="bi bi-eye"></i> ${art.VISUALIZACOES || 0}</span>
                                </div>
                                <h4 class="wiki-card-title">${art.TITULO}</h4>
                                <div class="wiki-card-desc">${art.RESUMO || art.CONTEUDO.substring(0, 100) + '...'}</div>
                                <div class="d-flex align-items-center mt-auto border-top pt-3">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width: 28px; height: 28px; font-size: 0.8rem;">${avatarStr}</div>
                                    <span class="small fw-bold text-muted">${art.AUTOR}</span>
                                    <span class="ms-auto small text-muted">${formatDateBr(art.CRIADO_EM)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        } catch (e) { console.error(e); }
    }

    function filterCategory(element, catId) {
        // Update UI
        document.querySelectorAll('.wiki-cat-item').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');

        // Update title
        document.getElementById('currentCategoryTitle').textContent = element ? element.textContent.trim() : 'Todos os Artigos';

        currentCategory = catId;
        voltarParaLista();
        loadArticles();
    }

    function formatDateBr(isoStr) {
        if (!isoStr) return '';
        const parts = isoStr.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return isoStr;
    }

    // --- Fluxo de Leitura (Read View) ---
    function abrirArtigo(artigo) {
        currentArticleObj = artigo;

        document.getElementById('wikiListView').style.display = 'none';
        document.getElementById('wikiReadView').style.display = 'block';

        document.getElementById('readTitle').textContent = artigo.TITULO;
        document.getElementById('readCat').textContent = artigo.CATEGORIA;
        document.getElementById('readDate').textContent = formatDateBr(artigo.CRIADO_EM);
        document.getElementById('readViews').textContent = (artigo.VISUALIZACOES || 0) + 1; // Fake view inc
        document.getElementById('readAuthor').textContent = artigo.AUTOR;
        document.getElementById('readAuthorAvatar').textContent = artigo.AUTOR ? artigo.AUTOR.substring(0, 1).toUpperCase() : 'A';

        // Simular um Markdown básico (Quebra de linha = parágrafo)
        let htmlContent = artigo.CONTEUDO.split('\n').filter(p => p.trim() !== '').map(p => `<p class="mb-4">${p}</p>`).join('');
        document.getElementById('readContent').innerHTML = htmlContent;

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function voltarParaLista() {
        document.getElementById('wikiReadView').style.display = 'none';
        document.getElementById('wikiListView').style.display = 'block';
        currentArticleObj = null;
    }

    function editarArtigoAtual() {
        if (currentArticleObj) {
            editWiki(currentArticleObj);
        }
    }

    // --- Modal e Criação ---
    function openWikiModal(mode) {
        document.getElementById('wikiModal').classList.add('show');
        if (mode === 'create') {
            document.getElementById('wikiModalTitle').innerHTML = '<i class="bi bi-journal-plus"></i> Escrever Artigo';
            document.getElementById('formWikiId').value = '';
            document.getElementById('formWikiTitle').value = '';
            document.getElementById('formWikiCat').value = 'SISTEMA';
            document.getElementById('formWikiContent').value = '';
            document.getElementById('btnDeleteWiki').classList.add('d-none');
            document.getElementById('btnSalvarWiki').innerHTML = '<i class="bi bi-check2"></i> Publicar Artigo';
        }
    }

    function closeWikiModal() {
        document.getElementById('wikiModal').classList.remove('show');
    }

    function editWiki(artObj) {
        openWikiModal('edit');
        document.getElementById('wikiModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Artigo';
        document.getElementById('formWikiId').value = artObj.ID;
        document.getElementById('formWikiTitle').value = artObj.TITULO;
        document.getElementById('formWikiCat').value = artObj.CATEGORIA;
        document.getElementById('formWikiContent').value = artObj.CONTEUDO;
        document.getElementById('btnDeleteWiki').classList.remove('d-none');
        document.getElementById('btnSalvarWiki').innerHTML = '<i class="bi bi-check2"></i> Salvar Alterações';
    }

    async function salvarWiki() {
        const title = document.getElementById('formWikiTitle').value;
        const cont = document.getElementById('formWikiContent').value;
        if (!title || !cont) { alert('Título e conteúdo são obrigatórios.'); return; }

        const btn = document.getElementById('btnSalvarWiki');
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Salvando...';

        try {
            const res = await fetch(API_WIKI, {
                method: document.getElementById('formWikiId').value ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: document.getElementById('formWikiId').value,
                    titulo: title,
                    categoria: document.getElementById('formWikiCat').value,
                    conteudo: cont
                })
            });
            const j = await res.json();
            if (j.sucesso) {
                closeWikiModal();
                if (currentArticleObj && currentArticleObj.ID == document.getElementById('formWikiId').value) {
                    // Update read view locally se foi edição
                    currentArticleObj.TITULO = title;
                    currentArticleObj.CATEGORIA = document.getElementById('formWikiCat').value;
                    currentArticleObj.CONTEUDO = cont;
                    abrirArtigo(currentArticleObj);
                    loadArticles();
                } else {
                    voltarParaLista();
                    loadArticles();
                }
            } else { alert(j.msg); }
        } catch (e) { alert(e.message); } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    }

    async function deleteWiki() {
        if (!confirm('Excluir este artigo permanentemente?')) return;
        try {
            const id = document.getElementById('formWikiId').value;
            const res = await fetch(API_WIKI + '?id=' + id, { method: 'DELETE' });
            const j = await res.json();
            if (j.sucesso) {
                closeWikiModal();
                voltarParaLista();
                loadArticles();
            }
        } catch (e) { alert(e.message); }
    }

    // Init
    document.addEventListener("DOMContentLoaded", () => {
        loadCategories();
        loadArticles();
    });
</script>