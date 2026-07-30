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
    $stmt = $pdo->prepare('CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(80) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, rol VARCHAR(30) NOT NULL DEFAULT "admin", estado TINYINT(1) NOT NULL DEFAULT 1)');
    $stmt->execute();

    $check = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND estado = 1 LIMIT 1');
    $check->execute([$usuarioInput]);
    $usuarioDB = $check->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioDB) {
        $count = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ((int)$count === 0) {
            $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO usuarios (usuario, password_hash, rol, estado) VALUES (?, ?, ?, 1)');
            $insert->execute(['admin', $defaultHash, 'admin']);
            $check = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND estado = 1 LIMIT 1');
            $check->execute(['admin']);
            $usuarioDB = $check->fetch(PDO::FETCH_ASSOC);
        }
    }

    $passwordOk = false;
    if ($usuarioDB) {
        if (password_verify($passwordInput, (string)$usuarioDB['password_hash'])) {
            $passwordOk = true;
        } elseif ($passwordInput === $usuarioDB['password_hash']) {
            $passwordOk = true;
        }
    }

    if ($usuarioDB && $passwordOk) {
        $_SESSION['usuario_activo'] = [
            'id' => (int)$usuarioDB['id'],
            'usuario' => (string)$usuarioDB['usuario'],
            'nombre' => (string)$usuarioDB['usuario'],
            'rol' => (string)$usuarioDB['rol']
        ];
        header('Location: ../dashboard.php');
        exit;
    }

    header('Location: ../index.php?error=1');
    exit;
} catch (PDOException $e) {
    die('Error en la base de datos: ' . $e->getMessage());
}
