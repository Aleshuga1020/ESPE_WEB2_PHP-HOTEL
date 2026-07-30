<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Dashboard</h2>
                    <p class="text-muted mb-0">Resumen general del sistema de hotel.</p>
                </div>
                <div class="text-end">
                    <div class="fw-bold">Bienvenido, <?php echo htmlspecialchars((string)$usuario['nombre']); ?></div>
                    <a href="backend/logout.php" class="btn btn-outline-danger btn-sm mt-2">Cerrar sesión</a>
                </div>
            </div>

            <?php
            require_once __DIR__ . '/backend/conexion.php';
            $stats = [];
            $stats['habitaciones'] = $pdo->query('SELECT COUNT(*) AS total FROM HABITACION')->fetchColumn();
            $stats['disponibles'] = $pdo->query("SELECT COUNT(*) AS total FROM HABITACION WHERE estado = 'Disponible'")->fetchColumn();
            $stats['ocupadas'] = $pdo->query("SELECT COUNT(*) AS total FROM HABITACION WHERE estado = 'Ocupada'")->fetchColumn();
            $stats['reservas_activas'] = $pdo->query("SELECT COUNT(*) AS total FROM RESERVA WHERE estado IN ('Confirmada','En curso')")->fetchColumn();
            $stats['clientes'] = $pdo->query('SELECT COUNT(*) AS total FROM CLIENTE')->fetchColumn();
            ?>

            <div class="row g-4">
                <div class="col-md-3"><div class="card shadow-sm p-3"><h6 class="text-muted">Habitaciones</h6><h3><?php echo (int)$stats['habitaciones']; ?></h3></div></div>
                <div class="col-md-3"><div class="card shadow-sm p-3"><h6 class="text-muted">Disponibles</h6><h3><?php echo (int)$stats['disponibles']; ?></h3></div></div>
                <div class="col-md-3"><div class="card shadow-sm p-3"><h6 class="text-muted">Ocupadas</h6><h3><?php echo (int)$stats['ocupadas']; ?></h3></div></div>
                <div class="col-md-3"><div class="card shadow-sm p-3"><h6 class="text-muted">Reservas activas</h6><h3><?php echo (int)$stats['reservas_activas']; ?></h3></div></div>
            </div>
        </div>
    </div>
</body>
</html>
