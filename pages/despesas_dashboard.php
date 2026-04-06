<?php
require_once __DIR__ . '/../routes/check_session.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="saas-main-content">
  <div class="saas-container">

    <div class="saas-head">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="saas-title m-0">Dashboard de Despesas</h2>
          <p class="saas-subtitle">Visão gerencial consolidada por categorias e alçadas.</p>
        </div>
        <div class="d-flex gap-2">
           <input type="month" class="saas-input py-1 px-3 d-inline-block" style="width:auto; height:38px;" id="fPeriodo">
           <button class="btn btn-primary-custom" onclick="loadDashboard()"><i class="bi bi-arrow-repeat"></i> Atualizar</button>
        </div>
      </div>

       <div class="metrics-row">
        <div class="metric-card">
          <div class="metric-title">Gasto Total (Histórico)</div>
          <div class="metric-value text-primary" id="mTotal">R$ 0,00</div>
          <div class="small text-muted mt-1"><i class="bi bi-graph-up text-success"></i> +12% vs mês anterior</div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Tiket Médio</div>
          <div class="metric-value" id="mAvg">R$ 0,00</div>
          <div class="small text-muted mt-1">Baseado em todas as despesas</div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Pendentes de Aprovação</div>
          <div class="metric-value text-warning" id="mPending">0</div>
          <div class="small text-muted mt-1">Aguardando fluxo de alçadas</div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
      <div class="col-xl-8">
        <div class="settings-card h-100">
           <h6 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-bar-chart-line text-primary me-2"></i> Evolução Mensal</h6>
           <div style="height: 350px;">
              <canvas id="chartEvolution"></canvas>
           </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="settings-card h-100">
           <h6 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-pie-chart text-primary me-2"></i> Por Categoria</h6>
           <div style="height: 350px;">
              <canvas id="chartCategory"></canvas>
           </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-xl-6">
         <div class="settings-card">
            <h6 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-building text-primary me-2"></i> TOP 5 Centros de Custo</h6>
            <div id="listTopCC">
               <div class="text-center py-5 text-muted">Carregando dados...</div>
            </div>
         </div>
      </div>
      <div class="col-xl-6">
         <div class="settings-card">
            <h6 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-info-circle text-primary me-2"></i> Resumo de Alçadas</h6>
            <p class="small text-muted">Distribuição de tempo médio por nível de aprovação.</p>
            <div class="text-center py-5 text-muted fst-italic">Funcionalidade em desenvolvimento...</div>
         </div>
      </div>
    </div>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let charts = {};

async function loadDashboard() {
    try {
        let res = await fetch('api/api_despesas.php', {
            method: 'POST',
            body: JSON.stringify({action: 'get_dashboard_data'})
        });
        let json = await res.json();
        
        if (json.sucesso) {
            const data = json.dados;
            
            // Atuallizar Métricas Mockadas/Somadas
            let total = data.byCategory.reduce((acc, curr) => acc + parseFloat(curr.TOTAL || 0), 0);
            document.getElementById('mTotal').innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            
            // Renderizar Evolução
            renderEvolution(data.evolution);
            
            // Renderizar Categorias
            renderByCat(data.byCategory);
            
            // Renderizar Top CC
            renderTopCC(data.topCC);
        }
    } catch(e) { console.error(e); }
}

function renderEvolution(rows) {
    const ctx = document.getElementById('chartEvolution').getContext('2d');
    if(charts.evolution) charts.evolution.destroy();
    
    charts.evolution = new Chart(ctx, {
        type: 'line',
        data: {
            labels: rows.map(r => r.MES),
            datasets: [{
                label: 'Gasto Consolidado',
                data: rows.map(r => r.TOTAL),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderByCat(rows) {
    const ctx = document.getElementById('chartCategory').getContext('2d');
    if(charts.cat) charts.cat.destroy();
    
    charts.cat = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: rows.map(r => r.DESCRICAO),
            datasets: [{
                data: rows.map(r => r.TOTAL),
                backgroundColor: ['#0d6efd', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6366f1']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } }
            },
            cutout: '70%'
        }
    });
}

function renderTopCC(rows) {
    let html = rows.map(r => `
        <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-light rounded-3">
           <div class="d-flex align-items-center gap-3">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:12px;font-weight:bold;">CC</div>
              <div>
                 <div class="fw-bold" style="font-size:14px;">${r.CENTROCUSTO}</div>
                 <div class="small text-muted">Centro de Custo</div>
              </div>
           </div>
           <div class="text-end fw-bold text-dark">
              ${parseFloat(r.TOTAL).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
           </div>
        </div>
    `).join('') || '<div class="text-muted">Sem dados.</div>';
    document.getElementById('listTopCC').innerHTML = html;
}

document.addEventListener("DOMContentLoaded", loadDashboard);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
