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

function validarHabitacion(array $data): array {
    $errores = [];
    $numero = trim((string)($data['numero_habitacion'] ?? ''));
    $tipo = (int)($data['id_tipo_habitacion'] ?? 0);
    $piso = (string)($data['piso'] ?? '');
    $estado = trim((string)($data['estado'] ?? ''));

    if ($numero === '') $errores[] = 'El número de habitación es obligatorio.';
    if ($tipo <= 0) $errores[] = 'Debe seleccionar un tipo de habitación.';
    if ($piso === '' || !ctype_digit($piso) || (int)$piso <= 0) $errores[] = 'El piso debe ser un número entero mayor a 0.';
    if (!in_array($estado, ['Disponible', 'Ocupada', 'Mantenimiento', 'Limpieza'], true)) $errores[] = 'Estado inválido.';

    return $errores;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT h.*, t.nombre AS tipo_nombre, t.precio_base FROM HABITACION h JOIN TIPO_HABITACION t ON h.id_tipo_habitacion = t.id_tipo_habitacion ORDER BY h.id_habitacion DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarHabitacion($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('INSERT INTO HABITACION (numero_habitacion, id_tipo_habitacion, piso, estado) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim((string)$input['numero_habitacion']),
                (int)$input['id_tipo_habitacion'],
                (int)$input['piso'],
                trim((string)$input['estado'])
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Habitación creada.']);
            break;
        case 'PUT':
            $id = (int)($input['id_habitacion'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarHabitacion($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('UPDATE HABITACION SET numero_habitacion = ?, id_tipo_habitacion = ?, piso = ?, estado = ? WHERE id_habitacion = ?');
            $stmt->execute([
                trim((string)$input['numero_habitacion']),
                (int)$input['id_tipo_habitacion'],
                (int)$input['piso'],
                trim((string)$input['estado']),
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Habitación actualizada.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_habitacion'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM HABITACION WHERE id_habitacion = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Habitación eliminada.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
