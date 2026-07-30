<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="d-flex flex-column p-3 text-white">
    <div class="text-center mb-4 mt-2">
        <h3 class="fw-bold m-0 text-light">🏨 HOTEL</h3>
        <small class="text-white-50">GESTIÓN HOTELERA</small>
    </div>
    <div class="card bg-light text-dark p-2 mb-3">
        <small class="text-muted">Usuario activo</small>
        <div class="fw-bold">Admin</div>
    </div>
    <hr class="border-light">
    <ul class="nav nav-pills flex-column mb-auto mt-2">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link <?php echo $pagina_actual === 'dashboard.php' ? 'active-menu' : ''; ?>">🏠 Dashboard</a>
        </li>
        <li class="nav-item mb-2">
            <a href="habitaciones.php" class="nav-link <?php echo $pagina_actual === 'habitaciones.php' ? 'active-menu' : ''; ?>">🛏️ Habitaciones</a>
        </li>
        <li class="nav-item mb-2">
            <a href="tipos_habitacion.php" class="nav-link <?php echo $pagina_actual === 'tipos_habitacion.php' ? 'active-menu' : ''; ?>">🧾 Tipos</a>
        </li>
        <li class="nav-item mb-2">
            <a href="clientes.php" class="nav-link <?php echo $pagina_actual === 'clientes.php' ? 'active-menu' : ''; ?>">👥 Clientes</a>
        </li>
        <li class="nav-item mb-2">
            <a href="reservas.php" class="nav-link <?php echo $pagina_actual === 'reservas.php' ? 'active-menu' : ''; ?>">📅 Reservas</a>
        </li>
        <li class="nav-item mb-2">
            <a href="servicios.php" class="nav-link <?php echo $pagina_actual === 'servicios.php' ? 'active-menu' : ''; ?>">🛎️ Servicios</a>
        </li>
        <li class="nav-item mb-2">
            <a href="gastos.php" class="nav-link <?php echo $pagina_actual === 'gastos.php' ? 'active-menu' : ''; ?>">💸 Gastos</a>
        </li>
    </ul>
    <div class="mt-3">
        <a href="backend/logout.php" class="btn btn-outline-light btn-sm w-100">Cerrar sesión</a>
    </div>
</nav>
