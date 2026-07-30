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

function validarCliente(array $data): array {
    $errores = [];
    $nombre = trim((string)($data['nombre'] ?? ''));
    $apellido = trim((string)($data['apellido'] ?? ''));
    $cedula = trim((string)($data['cedula'] ?? ''));
    $telefono = trim((string)($data['telefono'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $direccion = trim((string)($data['direccion'] ?? ''));

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($apellido === '') $errores[] = 'El apellido es obligatorio.';
    
    // Validación Módulo 10 Cédula
    if ($cedula === '' || !preg_match('/^\d{10}$/', $cedula)) {
        $errores[] = 'La cédula debe contener exactamente 10 dígitos numéricos.';
    } else {
        $provincia = (int)substr($cedula, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            $errores[] = 'La cédula no pertenece a ninguna provincia válida de Ecuador.';
        } elseif ((int)$cedula[2] >= 6) {
            $errores[] = 'El número de cédula no corresponde a una persona natural.';
        } else {
            $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
            $suma = 0;
            for ($i = 0; $i < 9; $i++) {
                $v = (int)$cedula[$i] * $coeficientes[$i];
                $suma += ($v >= 10) ? ($v - 9) : $v;
            }
            $digitoVerificador = (10 - ($suma % 10)) % 10;
            if ($digitoVerificador !== (int)$cedula[9]) {
                $errores[] = 'La cédula ingresada es inválida (falló la verificación de Ecuador).';
            }
        }
    }

    if ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) $errores[] = 'El teléfono debe contener entre 7 y 15 números.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no tiene un formato válido.';
    if ($direccion === '') $errores[] = 'La dirección es obligatoria.';

    return $errores;
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query('SELECT * FROM CLIENTE ORDER BY id_cliente DESC');
            sendJson($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $errores = validarCliente($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('INSERT INTO CLIENTE (nombre, apellido, cedula, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['apellido']),
                trim((string)$input['cedula']),
                trim((string)$input['telefono']),
                trim((string)$input['email']),
                trim((string)$input['direccion'])
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Cliente creado.']);
            break;
        case 'PUT':
            $id = (int)($input['id_cliente'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $errores = validarCliente($input);
            if ($errores) {
                sendJson(['estado' => 'error', 'errores' => $errores], 422);
            }
            $stmt = $pdo->prepare('UPDATE CLIENTE SET nombre = ?, apellido = ?, cedula = ?, telefono = ?, email = ?, direccion = ? WHERE id_cliente = ?');
            $stmt->execute([
                trim((string)$input['nombre']),
                trim((string)$input['apellido']),
                trim((string)$input['cedula']),
                trim((string)$input['telefono']),
                trim((string)$input['email']),
                trim((string)$input['direccion']),
                $id
            ]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Cliente actualizado.']);
            break;
        case 'DELETE':
            $id = (int)($input['id_cliente'] ?? 0);
            if ($id <= 0) {
                sendJson(['estado' => 'error', 'errores' => ['El id es obligatorio.']], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM CLIENTE WHERE id_cliente = ?');
            $stmt->execute([$id]);
            sendJson(['estado' => 'ok', 'mensaje' => 'Cliente eliminado.']);
            break;
        default:
            sendJson(['estado' => 'error', 'mensaje' => 'Método no permitido.'], 405);
    }
} catch (PDOException $e) {
    sendJson(['estado' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
