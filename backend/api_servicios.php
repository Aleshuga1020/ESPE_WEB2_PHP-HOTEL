<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode((string)file_get_contents('php://input'), true) ?? [];

function sendJson($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function validarServicio(array $data): array {
    $errores = [];
    $nombre = trim((string)($data['nombre'] ?? ''));
    $descripcion = trim((string)($data['descripcion'] ?? ''));
    $precio = (string)($data['precio'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($descripcion === '') $errores[] = 'La descripción es obligatoria.';
    if ($precio === '' || !is_numeric($precio) || (float)$precio < 0) $errores[] = 'El precio debe ser un número no negativo.';

    return $errores;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT * FROM SERVICIOS ORDER BY id_servicio DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarServicio($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('INSERT INTO SERVICIOS (nombre, descripcion, precio) VALUES (?, ?, ?)');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['descripcion']),
                number_format((float)$input['precio'], 2, '.', '')
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Servicio creado.']);
            break;
        case 'PUT':
            $id = (int)($input['id_servicio'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarServicio($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('UPDATE SERVICIOS SET nombre = ?, descripcion = ?, precio = ? WHERE id_servicio = ?');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['descripcion']),
                number_format((float)$input['precio'], 2, '.', ''),
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Servicio actualizado.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_servicio'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM SERVICIOS WHERE id_servicio = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Servicio eliminado.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
