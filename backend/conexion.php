<?php
declare(strict_types=1);

// Leemos las variables de entorno configuradas en Render (con fallback por si acaso)
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME')     ?: 'test';
$port     = getenv('DB_PORT')     ?: '4000';
$charset  = 'utf8mb4';

// En la nube (Render) no usamos socket local, sino host y puerto TCP
$dns = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dns, $user, $password, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado'  => 'error',
        'mensaje' => 'Error de conexión a la base de datos: ' . $e->getMessage()
    ]);
    exit;
}
