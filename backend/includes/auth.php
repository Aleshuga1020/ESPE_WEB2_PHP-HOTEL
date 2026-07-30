<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit;
}

$usuario = $_SESSION['usuario_activo'];
