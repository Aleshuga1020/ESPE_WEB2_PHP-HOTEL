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

function validarGasto(array $data): array {
    $errores = [];
    $idReserva = (int)($data['id_reserva'] ?? 0);
    $concepto = trim((string)($data['concepto'] ?? ''));
    $monto = (string)($data['monto'] ?? '');;

    if ($idReserva <= 0) $errores[] = 'Debe seleccionar una reserva.';
    if ($concepto === '') $errores[] = 'El concepto es obligatorio.';
    if ($monto === '' || !is_numeric($monto) || (float)$monto < 0) $errores[] = 'El monto debe ser un número no negativo.';

    return $errores;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT g.*, r.id_reserva, s.nombre AS servicio_nombre FROM GASTOS g LEFT JOIN SERVICIOS s ON g.id_servicio = s.id_servicio JOIN RESERVA r ON g.id_reserva = r.id_reserva ORDER BY g.id_gasto DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarGasto($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('INSERT INTO GASTOS (id_reserva, id_servicio, concepto, monto, fecha) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([
                (int)$input['id_reserva'],
                isset($input['id_servicio']) && $input['id_servicio'] !== '' ? (int)$input['id_servicio'] : null,
                trim((string)$input['concepto']),
                number_format((float)$input['monto'], 2, '.', '')
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Gasto creado.']);
            break;
        case 'PUT':
            $id = (int)($input['id_gasto'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarGasto($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('UPDATE GASTOS SET id_reserva = ?, id_servicio = ?, concepto = ?, monto = ? WHERE id_gasto = ?');
            $stmt->execute([
                (int)$input['id_reserva'],
                isset($input['id_servicio']) && $input['id_servicio'] !== '' ? (int)$input['id_servicio'] : null,
                trim((string)$input['concepto']),
                number_format((float)$input['monto'], 2, '.', ''),
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Gasto actualizado.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_gasto'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM GASTOS WHERE id_gasto = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Gasto eliminado.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
