<?php
declare(strict_types=1);

// Leemos las variables de entorno configuradas en Render
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME')     ?: 'test';
$port     = getenv('DB_PORT')     ?: '4000';
$charset  = 'utf8mb4';

$dns = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // 👇 ESTA LÍNEA ES LA CLAVE PARA TIDB CLOUD: Habilita SSL/TLS obligatorio
    PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
];

try {
    $pdo = new PDO($dns, $user, $password, $opciones);
} catch (PDOException $e) {
    // Si la ruta del certificado por defecto en Linux no existe, intentamos activar SSL sin validar CA específico
    try {
        $opciones_alt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        $pdo = new PDO($dns, $user, $password, $opciones_alt);
    } catch (PDOException $ex) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'estado'  => 'error',
            'mensaje' => 'Error de conexión a la base de datos: ' . $ex->getMessage()
        ]);
        exit;
    }
}
