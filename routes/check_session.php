<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Como este arquivo fica na raiz, redireciona para a tela de login em /pages
    header('Location: pages/login.html');
    exit;
}
?>