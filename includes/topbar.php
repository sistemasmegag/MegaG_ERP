<?php
// includes/topbar.php
$usuarioNome = $_SESSION['usuario'] ?? 'Usuário';
$primeiroNome = explode(' ', $usuarioNome)[0];
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle d-md-none" onclick="toggleMenu()">
            <i class="bi bi-list"></i>
        </button>
        <h2 class="welcome-text">Bem-vindo de volta, <span class="user-highlight"><?php echo htmlspecialchars($primeiroNome); ?></span></h2>
    </div>

    <div class="topbar-right">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Pesquisar...">
        </div>
        
        <button class="icon-btn" title="Notificações">
            <i class="bi bi-bell"></i>
            <span class="notification-badge"></span>
        </button>
    </div>
</header>

<style>
    .topbar {
        height: 80px;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        z-index: 100;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }

    .welcome-text {
        font-size: 1.15rem;
        font-weight: 500;
        color: #64748b;
        margin: 0;
    }

    .user-highlight {
        color: #4f46e5;
        font-weight: 700;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-box input {
        width: 100%;
        background: #e2e8f0;
        border: none;
        padding: 10px 10px 10px 38px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        outline: none;
    }

    .icon-btn {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 1.3rem;
        position: relative;
        cursor: pointer;
        transition: color 0.2s;
    }

    .icon-btn:hover {
        color: #1e293b;
    }

    .notification-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    [data-theme="dark"] .topbar {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }

    [data-theme="dark"] .search-box input {
        background: #1e293b;
        color: #f8fafc;
    }

    @media (max-width: 768px) {
        .topbar {
            height: 64px;
            padding: 0 12px;
            gap: 10px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 6px;
        }

        .welcome-text {
            font-size: 0.82rem;
            line-height: 1.15;
            max-width: 86px;
        }

        .topbar-right {
            gap: 8px;
            min-width: 0;
        }

        .search-box {
            width: clamp(118px, 42vw, 210px);
        }

        .search-box input {
            height: 36px;
            padding: 8px 8px 8px 34px;
            font-size: 0.78rem;
            border-radius: 10px;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
    }

    @media (max-width: 360px) {
        .topbar {
            padding: 0 8px;
        }

        .welcome-text {
            max-width: 68px;
            font-size: 0.76rem;
        }

        .search-box {
            width: 136px;
        }
    }
</style>
