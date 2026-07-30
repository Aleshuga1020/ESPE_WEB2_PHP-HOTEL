<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode((string)file_get_contents('php://input'), true) ?? [];
$modulo = trim((string)($_GET['modulo'] ?? ($input['modulo'] ?? '')));

function sendJson($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function validarTexto(string $valor, string $nombre, bool $requerido = true, int $max = 255): array {
    $errores = [];
    $texto = trim($valor);
    if ($requerido && $texto === '') {
        $errores[] = 'El campo ' . $nombre . ' es obligatorio.';
        return $errores;
    }
    if ($texto !== '' && $max > 0 && mb_strlen($texto) > $max) {
        $errores[] = 'El campo ' . $nombre . ' no debe superar ' . $max . ' caracteres.';
    }
    return $errores;
}

function validarNumero($valor, string $nombre, bool $requerido = true, float $min = 0): array {
    $errores = [];
    $texto = trim((string)$valor);
    if ($requerido && $texto === '') {
        $errores[] = 'El campo ' . $nombre . ' es obligatorio.';
        return $errores;
    }
    if ($texto === '') {
        return $errores;
    }
    if (!is_numeric($texto) || (float)$texto < $min) {
        $errores[] = 'El campo ' . $nombre . ' debe ser un número válido no menor a ' . $min . '.';
    }
    return $errores;
}

function validarFecha(string $valor, string $nombre): array {
    $errores = [];
    $texto = trim($valor);
    if ($texto === '') {
        $errores[] = 'La fecha ' . $nombre . ' es obligatoria.';
        return $errores;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
        $errores[] = 'La fecha ' . $nombre . ' debe tener formato yyyy-mm-dd.';
    }
    return $errores;
}

function validarCedula(string $valor): array {
    $texto = trim($valor);
    if ($texto === '') {
        return ['La cédula es obligatoria.'];
    }
    return preg_match('/^\d{1,10}$/', $texto) ? [] : ['La cédula debe contener solo números.'];
}

function validarTelefono(string $valor): array {
    $texto = trim($valor);
    if ($texto === '') {
        return [];
    }
    return preg_match('/^\d{7,15}$/', $texto) ? [] : ['El teléfono debe contener solo números.'];
}

function validarEmail(string $valor): array {
    $texto = trim($valor);
    if ($texto === '') {
        return [];
    }
    return filter_var($texto, FILTER_VALIDATE_EMAIL) ? [] : ['El email no tiene un formato válido.'];
}

try {
    switch ($modulo) {
        case 'tipos_habitacion':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT * FROM TIPO_HABITACION ORDER BY id_tipo_habitacion DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 50),
                    validarTexto((string)($input['descripcion'] ?? ''), 'descripción'),
                    validarNumero((string)($input['precio_base'] ?? ''), 'precio base', true, 0),
                    validarNumero((string)($input['capacidad_maxima'] ?? ''), 'capacidad máxima', true, 1)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO TIPO_HABITACION (nombre, descripcion, precio_base, capacidad_maxima) VALUES (?, ?, ?, ?)');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['descripcion']), number_format((float)$input['precio_base'], 2, '.', ''), (int)$input['capacidad_maxima']]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación creado.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_tipo_habitacion'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 50),
                    validarTexto((string)($input['descripcion'] ?? ''), 'descripción'),
                    validarNumero((string)($input['precio_base'] ?? ''), 'precio base', true, 0),
                    validarNumero((string)($input['capacidad_maxima'] ?? ''), 'capacidad máxima', true, 1)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('UPDATE TIPO_HABITACION SET nombre = ?, descripcion = ?, precio_base = ?, capacidad_maxima = ? WHERE id_tipo_habitacion = ?');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['descripcion']), number_format((float)$input['precio_base'], 2, '.', ''), (int)$input['capacidad_maxima'], $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación actualizado.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_tipo_habitacion'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM TIPO_HABITACION WHERE id_tipo_habitacion = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Tipo de habitación eliminado.']);
            }
            break;

        case 'habitaciones':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT h.*, t.nombre AS tipo_nombre, t.precio_base FROM HABITACION h JOIN TIPO_HABITACION t ON h.id_tipo_habitacion = t.id_tipo_habitacion ORDER BY h.id_habitacion DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarTexto((string)($input['numero_habitacion'] ?? ''), 'número de habitación', true, 10),
                    validarNumero((string)($input['id_tipo_habitacion'] ?? ''), 'tipo de habitación', true, 1),
                    validarNumero((string)($input['piso'] ?? ''), 'piso', true, 1)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO HABITACION (numero_habitacion, id_tipo_habitacion, piso, estado) VALUES (?, ?, ?, ?)');
                $stmt->execute([trim((string)$input['numero_habitacion']), (int)$input['id_tipo_habitacion'], (int)$input['piso'], trim((string)$input['estado'])]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Habitación creada.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_habitacion'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarTexto((string)($input['numero_habitacion'] ?? ''), 'número de habitación', true, 10),
                    validarNumero((string)($input['id_tipo_habitacion'] ?? ''), 'tipo de habitación', true, 1),
                    validarNumero((string)($input['piso'] ?? ''), 'piso', true, 1)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('UPDATE HABITACION SET numero_habitacion = ?, id_tipo_habitacion = ?, piso = ?, estado = ? WHERE id_habitacion = ?');
                $stmt->execute([trim((string)$input['numero_habitacion']), (int)$input['id_tipo_habitacion'], (int)$input['piso'], trim((string)$input['estado']), $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Habitación actualizada.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_habitacion'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM HABITACION WHERE id_habitacion = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Habitación eliminada.']);
            }
            break;

        case 'clientes':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT * FROM CLIENTE ORDER BY id_cliente DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 80),
                    validarTexto((string)($input['apellido'] ?? ''), 'apellido', true, 80),
                    validarCedula((string)($input['cedula'] ?? '')),
                    validarTelefono((string)($input['telefono'] ?? '')),
                    validarEmail((string)($input['email'] ?? '')),
                    validarTexto((string)($input['direccion'] ?? ''), 'dirección', true, 255)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO CLIENTE (nombre, apellido, cedula, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['apellido']), trim((string)$input['cedula']), trim((string)$input['telefono']), trim((string)$input['email']), trim((string)$input['direccion'])]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Cliente creado.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_cliente'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 80),
                    validarTexto((string)($input['apellido'] ?? ''), 'apellido', true, 80),
                    validarCedula((string)($input['cedula'] ?? '')),
                    validarTelefono((string)($input['telefono'] ?? '')),
                    validarEmail((string)($input['email'] ?? '')),
                    validarTexto((string)($input['direccion'] ?? ''), 'dirección', true, 255)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('UPDATE CLIENTE SET nombre = ?, apellido = ?, cedula = ?, telefono = ?, email = ?, direccion = ? WHERE id_cliente = ?');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['apellido']), trim((string)$input['cedula']), trim((string)$input['telefono']), trim((string)$input['email']), trim((string)$input['direccion']), $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Cliente actualizado.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_cliente'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM CLIENTE WHERE id_cliente = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Cliente eliminado.']);
            }
            break;

        case 'reservas':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT r.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido, h.numero_habitacion FROM RESERVA r JOIN CLIENTE c ON r.id_cliente = c.id_cliente JOIN HABITACION h ON r.id_habitacion = h.id_habitacion ORDER BY r.id_reserva DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarNumero((string)($input['id_cliente'] ?? ''), 'cliente', true, 1),
                    validarNumero((string)($input['id_habitacion'] ?? ''), 'habitación', true, 1),
                    validarFecha((string)($input['fecha_entrada'] ?? ''), 'de entrada'),
                    validarFecha((string)($input['fecha_salida'] ?? ''), 'de salida'),
                    validarNumero((string)($input['total_pagar'] ?? ''), 'total a pagar', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                if (strtotime((string)$input['fecha_salida']) <= strtotime((string)$input['fecha_entrada'])) {
                    sendJson(['estado' => 'error', 'errores' => ['La fecha de salida debe ser posterior a la fecha de entrada.']], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO RESERVA (id_cliente, id_habitacion, fecha_reserva, fecha_entrada, fecha_salida, estado, total_pagar) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
                $stmt->execute([(int)$input['id_cliente'], (int)$input['id_habitacion'], trim((string)$input['fecha_entrada']), trim((string)$input['fecha_salida']), trim((string)$input['estado']), number_format((float)$input['total_pagar'], 2, '.', '')]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Reserva creada.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_reserva'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarNumero((string)($input['id_cliente'] ?? ''), 'cliente', true, 1),
                    validarNumero((string)($input['id_habitacion'] ?? ''), 'habitación', true, 1),
                    validarFecha((string)($input['fecha_entrada'] ?? ''), 'de entrada'),
                    validarFecha((string)($input['fecha_salida'] ?? ''), 'de salida'),
                    validarNumero((string)($input['total_pagar'] ?? ''), 'total a pagar', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                if (strtotime((string)$input['fecha_salida']) <= strtotime((string)$input['fecha_entrada'])) {
                    sendJson(['estado' => 'error', 'errores' => ['La fecha de salida debe ser posterior a la fecha de entrada.']], 422);
                }
                $stmt = $pdo->prepare('UPDATE RESERVA SET id_cliente = ?, id_habitacion = ?, fecha_entrada = ?, fecha_salida = ?, estado = ?, total_pagar = ? WHERE id_reserva = ?');
                $stmt->execute([(int)$input['id_cliente'], (int)$input['id_habitacion'], trim((string)$input['fecha_entrada']), trim((string)$input['fecha_salida']), trim((string)$input['estado']), number_format((float)$input['total_pagar'], 2, '.', ''), $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Reserva actualizada.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_reserva'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM RESERVA WHERE id_reserva = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Reserva eliminada.']);
            }
            break;

        case 'servicios':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT * FROM SERVICIOS ORDER BY id_servicio DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 80),
                    validarTexto((string)($input['descripcion'] ?? ''), 'descripción', true, 255),
                    validarNumero((string)($input['precio'] ?? ''), 'precio', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO SERVICIOS (nombre, descripcion, precio) VALUES (?, ?, ?)');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['descripcion']), number_format((float)$input['precio'], 2, '.', '')]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Servicio creado.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_servicio'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarTexto((string)($input['nombre'] ?? ''), 'nombre', true, 80),
                    validarTexto((string)($input['descripcion'] ?? ''), 'descripción', true, 255),
                    validarNumero((string)($input['precio'] ?? ''), 'precio', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('UPDATE SERVICIOS SET nombre = ?, descripcion = ?, precio = ? WHERE id_servicio = ?');
                $stmt->execute([trim((string)$input['nombre']), trim((string)$input['descripcion']), number_format((float)$input['precio'], 2, '.', ''), $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Servicio actualizado.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_servicio'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM SERVICIOS WHERE id_servicio = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Servicio eliminado.']);
            }
            break;

        case 'gastos':
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT g.*, r.id_reserva, s.nombre AS servicio_nombre FROM GASTOS g LEFT JOIN SERVICIOS s ON g.id_servicio = s.id_servicio JOIN RESERVA r ON g.id_reserva = r.id_reserva ORDER BY g.id_gasto DESC');
                sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            if ($method === 'POST') {
                $errores = array_merge(
                    validarNumero((string)($input['id_reserva'] ?? ''), 'reserva', true, 1),
                    validarTexto((string)($input['concepto'] ?? ''), 'concepto', true, 150),
                    validarNumero((string)($input['monto'] ?? ''), 'monto', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO GASTOS (id_reserva, id_servicio, concepto, monto, fecha) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([(int)$input['id_reserva'], isset($input['id_servicio']) && $input['id_servicio'] !== '' ? (int)$input['id_servicio'] : null, trim((string)$input['concepto']), number_format((float)$input['monto'], 2, '.', '')]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Gasto creado.']);
            }
            if ($method === 'PUT') {
                $id = (int)($input['id_gasto'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $errores = array_merge(
                    validarNumero((string)($input['id_reserva'] ?? ''), 'reserva', true, 1),
                    validarTexto((string)($input['concepto'] ?? ''), 'concepto', true, 150),
                    validarNumero((string)($input['monto'] ?? ''), 'monto', true, 0)
                );
                if ($errores) {
                    sendJson(['estado' => 'error', 'errores' => $errores], 422);
                }
                $stmt = $pdo->prepare('UPDATE GASTOS SET id_reserva = ?, id_servicio = ?, concepto = ?, monto = ? WHERE id_gasto = ?');
                $stmt->execute([(int)$input['id_reserva'], isset($input['id_servicio']) && $input['id_servicio'] !== '' ? (int)$input['id_servicio'] : null, trim((string)$input['concepto']), number_format((float)$input['monto'], 2, '.', ''), $id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Gasto actualizado.']);
            }
            if ($method === 'DELETE') {
                $id = (int)($input['id_gasto'] ?? 0);
                if ($id <= 0) {
                    sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
                }
                $stmt = $pdo->prepare('DELETE FROM GASTOS WHERE id_gasto = ?');
                $stmt->execute([$id]);
                sendJson(['estado' => 'ok', 'mensaje' => 'Gasto eliminado.']);
            }
            break;

        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Módulo no encontrado.'], 404);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
