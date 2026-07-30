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

function validarTipo(array $data): array {
    $errores = [];
    $nombre = trim((string)($data['nombre'] ?? ''));
    $descripcion = trim((string)($data['descripcion'] ?? ''));
    $precio = (string)($data['precio_base'] ?? '');
    $capacidad = (string)($data['capacidad_maxima'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (strlen($nombre) > 50) $errores[] = 'El nombre no debe superar 50 caracteres.';
    if ($descripcion === '') $errores[] = 'La descripción es obligatoria.';
    if ($precio === '' || !is_numeric($precio) || (float)$precio < 0) $errores[] = 'El precio base debe ser un número no negativo.';
    if ($capacidad === '' || !ctype_digit($capacidad) || (int)$capacidad <= 0) $errores[] = 'La capacidad máxima debe ser un entero mayor a 0.';

    return $errores;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT * FROM TIPO_HABITACION ORDER BY id_tipo_habitacion DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarTipo($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }

            $stmt = $pdo->prepare('INSERT INTO TIPO_HABITACION (nombre, descripcion, precio_base, capacidad_maxima) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['descripcion']),
                number_format((float)$input['precio_base'], 2, '.', ''),
                (int)$input['capacidad_maxima']
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación creado.']);
            break;
        case 'PUT':
            $id = (int)($input['id_tipo_habitacion'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarTipo($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }

            $stmt = $pdo->prepare('UPDATE TIPO_HABITACION SET nombre = ?, descripcion = ?, precio_base = ?, capacidad_maxima = ? WHERE id_tipo_habitacion = ?');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['descripcion']),
                number_format((float)$input['precio_base'], 2, '.', ''),
                (int)$input['capacidad_maxima'],
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación actualizado.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_tipo_habitacion'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM TIPO_HABITACION WHERE id_tipo_habitacion = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación eliminado.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
