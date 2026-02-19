<?php
// pages/sem_permissao.php
?>

<style>
/* ===== Página Sem Permissão – Clean SaaS ===== */

.semperm-wrapper{
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.semperm-card{
    max-width: 720px;
    width: 100%;
    border-radius: 22px;
    border: 1px solid var(--saas-border, rgba(0,0,0,.08));
    background: var(--saas-surface, #ffffff);
    box-shadow: 0 30px 60px rgba(0,0,0,.08);
    padding: 42px 36px;
    position: relative;
    overflow: hidden;
}

html[data-theme="dark"] .semperm-card{
    background: #111827;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 30px 60px rgba(0,0,0,.5);
}

.semperm-card::before{
    content:"";
    position:absolute;
    top:-120px;
    right:-120px;
    width:300px;
    height:300px;
    background: radial-gradient(circle at center, rgba(220,53,69,.25), transparent 70%);
    pointer-events:none;
}

.semperm-icon{
    width:90px;
    height:90px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(220,53,69,.12);
    margin:0 auto 24px;
}

.semperm-icon i{
    font-size:42px;
    color:#dc3545;
}

.semperm-title{
    font-size:26px;
    font-weight:800;
    letter-spacing:-.02em;
    margin-bottom:8px;
}

.semperm-text{
    font-size:15px;
    color: var(--saas-muted, #6c757d);
    max-width: 520px;
    margin: 0 auto 26px;
}

.semperm-badge{
    display:inline-block;
    padding:6px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    background: rgba(220,53,69,.12);
    color:#dc3545;
    margin-bottom:20px;
}

.semperm-actions{
    margin-top: 18px;
}

.semperm-actions .btn{
    border-radius:999px;
    padding:10px 22px;
    font-weight:700;
    letter-spacing:.02em;
}

</style>

<div class="container-fluid">
    <div class="semperm-wrapper">

        <div class="semperm-card text-center">

            <div class="semperm-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <div class="semperm-badge">
                Acesso Restrito
            </div>

            <h3 class="semperm-title">
                Você não tem permissão para acessar esta página
            </h3>

            <p class="semperm-text">
                O módulo solicitado não está disponível para o seu perfil de acesso.
                Se você acredita que isso é um erro, entre em contato com o administrador do sistema.
            </p>

            <div class="semperm-actions d-flex justify-content-center gap-3 flex-wrap">

                <a href="index.php?page=home" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Voltar ao Dashboard
                </a>

                <button class="btn btn-danger" onclick="window.history.back();">
                    <i class="bi bi-clock-history me-1"></i> Página Anterior
                </button>

            </div>

        </div>

    </div>
</div>
