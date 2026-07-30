<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$usuarioInput = trim((string)($_POST['usuario'] ?? ''));
$passwordInput = (string)($_POST['password'] ?? '');

if ($usuarioInput === '' || $passwordInput === '') {
    header('Location: ../index.php?error=1');
    exit;
}

try {
    // Buscar al usuario activo en la tabla existente
    $check = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND estado = 1 LIMIT 1');
    $check->execute([$usuarioInput]);
    $usuarioDB = $check->fetch(PDO::FETCH_ASSOC);

    $passwordOk = false;
    if ($usuarioDB) {
        // Verificar si la contraseña coincide con el Hash o en texto plano
        if (password_verify($passwordInput, (string)$usuarioDB['password_hash'])) {
            $passwordOk = true;
        } elseif ($passwordInput === (string)$usuarioDB['password_hash']) {
            $passwordOk = true;
        }
    }

    if ($usuarioDB && $passwordOk) {
        $_SESSION['usuario_activo'] = [
            'id'      => (int)$usuarioDB['id'],
            'usuario' => (string)$usuarioDB['usuario'],
            'nombre'  => (string)$usuarioDB['usuario'],
            'rol'     => (string)$usuarioDB['rol']
        ];
        header('Location: ../dashboard.php');
        exit;
    }

    // Si no coincide la clave o el usuario
    header('Location: ../index.php?error=1');
    exit;

} catch (PDOException $e) {
    die('Error en la base de datos: ' . $e->getMessage());
}
