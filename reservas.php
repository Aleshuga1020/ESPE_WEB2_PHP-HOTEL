<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Reservas</h2>
                    <p class="text-muted mb-0">Controle las reservas y la ocupación del hotel.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalReserva">Nueva reserva</button>
            </div>
            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>ID</th><th>Cliente</th><th>Habitación</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Total</th><th>Acciones</th></tr></thead>
                        <tbody id="tabla-reservas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReserva" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Reserva</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="formReserva">
                        <input type="hidden" id="id_reserva" name="id_reserva">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Cliente</label><select class="form-select" id="id_cliente" name="id_cliente" required></select></div>
                            <div class="col-md-6"><label class="form-label">Habitación</label><select class="form-select" id="id_habitacion" name="id_habitacion" required></select></div>
                            <div class="col-md-6"><label class="form-label">Fecha entrada</label><input type="date" class="form-control" id="fecha_entrada" name="fecha_entrada" required></div>
                            <div class="col-md-6"><label class="form-label">Fecha salida</label><input type="date" class="form-control" id="fecha_salida" name="fecha_salida" required></div>
                            <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" id="estado" name="estado">
                                <option value="Confirmada">Confirmada</option>
                                <option value="En curso">En curso</option>
                                <option value="Finalizada">Finalizada</option>
                                <option value="Cancelada">Cancelada</option>
                            </select></div>
                            <div class="col-md-6"><label class="form-label">Total a pagar</label><input type="number" min="0" step="0.01" class="form-control" id="total_pagar" name="total_pagar" required></div>
                        </div>
                        <div id="erroresReserva" class="text-danger mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" form="formReserva" type="submit">Guardar</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalReserva = new bootstrap.Modal(document.getElementById('modalReserva'));
        const form = document.getElementById('formReserva');
        const tabla = document.getElementById('tabla-reservas');
        const errores = document.getElementById('erroresReserva');
        const selectClientes = document.getElementById('id_cliente');
        const selectHabitaciones = document.getElementById('id_habitacion');

        function resetForm() {
            form.reset();
            document.getElementById('id_reserva').value = '';
            errores.innerHTML = '';
        }

        async function cargarCombos() {
            const [clientesRes, habitacionesRes] = await Promise.all([
                fetch('backend/api_crud.php?modulo=clientes'),
                fetch('backend/api_crud.php?modulo=habitaciones')
            ]);
            const clientes = await clientesRes.json();
            const habitaciones = await habitacionesRes.json();
            selectClientes.innerHTML = '<option value="">Seleccione</option>';
            clientes.forEach(item => selectClientes.innerHTML += `<option value="${item.id_cliente}">${item.nombre} ${item.apellido}</option>`);
            selectHabitaciones.innerHTML = '<option value="">Seleccione</option>';
            habitaciones.forEach(item => selectHabitaciones.innerHTML += `<option value="${item.id_habitacion}">${item.numero_habitacion} - ${item.estado}</option>`);
        }

        async function cargarReservas() {
            const response = await fetch('backend/api_crud.php?modulo=reservas');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.id_reserva}</td><td>${item.cliente_nombre} ${item.cliente_apellido}</td><td>${item.numero_habitacion}</td><td>${item.fecha_entrada}</td><td>${item.fecha_salida}</td><td>${item.estado}</td><td>$${parseFloat(item.total_pagar).toFixed(2)}</td><td><button class="btn btn-sm btn-outline-secondary me-2" onclick="editarReserva(${item.id_reserva})">Editar</button><button class="btn btn-sm btn-outline-danger" onclick="eliminarReserva(${item.id_reserva})">Eliminar</button></td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarReserva(event) {
            event.preventDefault();
            errores.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const erroresForm = [
                ...validarNumero(payload.id_cliente, 'cliente', true, 1, true),
                ...validarNumero(payload.id_habitacion, 'habitación', true, 1, true),
                ...validarFecha(payload.fecha_entrada, 'de entrada'),
                ...validarFecha(payload.fecha_salida, 'de salida'),
                ...validarNumero(payload.total_pagar, 'total a pagar', true, 0)
            ];
            if (erroresForm.length) {
                mostrarErrores(errores, erroresForm);
                return;
            }
            const method = payload.id_reserva ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=reservas', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_reserva: payload.id_reserva || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                errores.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalReserva.hide();
            resetForm();
            cargarReservas();
        }

        async function eliminarReserva(id) {
            if (!confirm('¿Desea eliminar esta reserva?')) return;
            const response = await fetch('backend/api_crud.php?modulo=reservas', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_reserva: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarReservas();
            }
        }

        function editarReserva(id) {
            fetch('backend/api_crud.php?modulo=reservas')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_reserva === id);
                    if (!item) return;
                    document.getElementById('id_reserva').value = item.id_reserva;
                    document.getElementById('id_cliente').value = item.id_cliente;
                    document.getElementById('id_habitacion').value = item.id_habitacion;
                    document.getElementById('fecha_entrada').value = item.fecha_entrada;
                    document.getElementById('fecha_salida').value = item.fecha_salida;
                    document.getElementById('estado').value = item.estado;
                    document.getElementById('total_pagar').value = item.total_pagar;
                    modalReserva.show();
                });
        }

        form.addEventListener('submit', guardarReserva);
        document.getElementById('modalReserva').addEventListener('hidden.bs.modal', resetForm);
        cargarCombos().then(cargarReservas);
    </script>
</body>
</html>
