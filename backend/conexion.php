<?php
declare(strict_types=1);

$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'FINAL';
$charset = 'utf8mb4';
$unixSocket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';

$dns = "mysql:host=$host;dbname=$database;charset=$charset;unix_socket=$unixSocket";

$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dns, $user, $password, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error de conexión a la base de datos.'
    ]);
    exit;
}
