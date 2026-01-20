</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleMenu(forceClose = false) {
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');

        if (!sidebar || !overlay) return;

        if (forceClose) {
            sidebar.classList.remove('show');
        } else {
            sidebar.classList.toggle('show');
        }

        const isOpen = sidebar.classList.contains('show');

        // Overlay
        overlay.style.display = isOpen ? 'block' : 'none';

        // UX mobile: evita scroll do conteúdo quando menu estiver aberto
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    // Fecha menu com ESC (Clean SaaS UX)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            toggleMenu(true);
        }
    });

    // Se o overlay já estiver visível por algum motivo, garante estado consistente
    window.addEventListener('load', function(){
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !overlay) return;

        const isOpen = sidebar.classList.contains('show');
        overlay.style.display = isOpen ? 'block' : 'none';
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });
</script>

<!-- Modal de Permissão (Global) -->
<div class="modal fade" id="modalPermissao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4" style="border:1px solid var(--saas-border); background: var(--saas-surface); color: var(--saas-text);">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold text-danger">
          <i class="bi bi-shield-lock-fill me-2"></i> Acesso negado
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body text-muted" id="modalPermissaoMsg">
        Você não possui permissão para realizar esta ação.
      </div>

      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
          Entendi
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  window.mostrarModalPermissao = function(msg){
    const elMsg = document.getElementById('modalPermissaoMsg');
    if (elMsg) elMsg.textContent = msg || 'Você não possui permissão para acessar esta página.';
    const modalEl = document.getElementById('modalPermissao');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  };

  // Intercepta cliques nos links protegidos da sidebar
  document.addEventListener('click', function(e){
    const a = e.target.closest('a[data-allowed]');
    if (!a) return;

    const allowed = a.dataset.allowed === '0';
    if (!allowed) {
      e.preventDefault();
      e.stopPropagation();
      window.mostrarModalPermissao(a.dataset.deniedMsg || 'Você não tem permissão para acessar este módulo.');
    }
  });
</script>

</body>
</html>
