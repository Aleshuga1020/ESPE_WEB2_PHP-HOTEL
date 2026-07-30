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

function validarReserva(array $data): array {
    $errores = [];
    $idCliente = (int)($data['id_cliente'] ?? 0);
    $idHabitacion = (int)($data['id_habitacion'] ?? 0);
    $entrada = trim((string)($data['fecha_entrada'] ?? ''));
    $salida = trim((string)($data['fecha_salida'] ?? ''));
    $estado = trim((string)($data['estado'] ?? ''));
    $total = (string)($data['total_pagar'] ?? '');

    if ($idCliente <= 0) $errores[] = 'Debe seleccionar un cliente.';
    if ($idHabitacion <= 0) $errores[] = 'Debe seleccionar una habitación.';
    if ($entrada === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $entrada)) $errores[] = 'La fecha de entrada es obligatoria y debe tener formato yyyy-mm-dd.';
    if ($salida === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $salida)) $errores[] = 'La fecha de salida es obligatoria y debe tener formato yyyy-mm-dd.';
    if ($entrada !== '' && $salida !== '' && strtotime($salida) <= strtotime($entrada)) $errores[] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
    if (!in_array($estado, ['Confirmada', 'En curso', 'Finalizada', 'Cancelada'], true)) $errores[] = 'Estado inválido.';
    if ($total === '' || !is_numeric($total) || (float)$total < 0) $errores[] = 'El total a pagar debe ser un número no negativo.';

    return $errores;
}

function verificarDisponibilidad(PDO $pdo, int $idHabitacion, string $entrada, string $salida, int $idReserva = 0): bool {
    $sql = 'SELECT COUNT(*) FROM RESERVA WHERE id_habitacion = ? AND id_reserva <> ? AND estado IN ("Confirmada", "En curso") AND fecha_entrada < ? AND fecha_salida > ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idHabitacion, $idReserva, $salida, $entrada]);
    return (int)$stmt->fetchColumn() === 0;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT r.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido, h.numero_habitacion FROM RESERVA r JOIN CLIENTE c ON r.id_cliente = c.id_cliente JOIN HABITACION h ON r.id_habitacion = h.id_habitacion ORDER BY r.id_reserva DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarReserva($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            if (!verificarDisponibilidad($pdo, (int)$input['id_habitacion'], trim((string)$input['fecha_entrada']), trim((string)$input['fecha_salida']))) {
                sendJson(['estado' => 'error', 'errores' => ['La habitación no está disponible para esas fechas.']], 422);
            }
            $stmt = $pdo->prepare('INSERT INTO RESERVA (id_cliente, id_habitacion, fecha_reserva, fecha_entrada, fecha_salida, estado, total_pagar) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
            $stmt->execute([
                (int)$input['id_cliente'],
                (int)$input['id_habitacion'],
                trim((string)$input['fecha_entrada']),
                trim((string)$input['fecha_salida']),
                trim((string)$input['estado']),
                number_format((float)$input['total_pagar'], 2, '.', '')
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Reserva creada.']);
            break;
        case 'PUT':
            $id = (int)($input['id_reserva'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarReserva($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            if (!verificarDisponibilidad($pdo, (int)$input['id_habitacion'], trim((string)$input['fecha_entrada']), trim((string)$input['fecha_salida']), $id)) {
                sendJson(['estado' => 'error', 'errores' => ['La habitación no está disponible para esas fechas.']], 422);
            }
            $stmt = $pdo->prepare('UPDATE RESERVA SET id_cliente = ?, id_habitacion = ?, fecha_entrada = ?, fecha_salida = ?, estado = ?, total_pagar = ? WHERE id_reserva = ?');
            $stmt->execute([
                (int)$input['id_cliente'],
                (int)$input['id_habitacion'],
                trim((string)$input['fecha_entrada']),
                trim((string)$input['fecha_salida']),
                trim((string)$input['estado']),
                number_format((float)$input['total_pagar'], 2, '.', ''),
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Reserva actualizada.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_reserva'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM RESERVA WHERE id_reserva = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Reserva eliminada.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
