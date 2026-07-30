<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Gastos</h2>
                    <p class="text-muted mb-0">Registre costos adicionales asociados a reservas.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGasto">Nuevo gasto</button>
            </div>
            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>ID</th><th>Reserva</th><th>Servicio</th><th>Concepto</th><th>Monto</th><th>Fecha</th><th>Acciones</th></tr></thead>
                        <tbody id="tabla-gastos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGasto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="formGasto">
                        <input type="hidden" id="id_gasto" name="id_gasto">
                        <div class="mb-3"><label class="form-label">Reserva</label><select class="form-select" id="id_reserva" name="id_reserva" required></select></div>
                        <div class="mb-3"><label class="form-label">Servicio</label><select class="form-select" id="id_servicio" name="id_servicio"><option value="">Sin servicio</option></select></div>
                        <div class="mb-3"><label class="form-label">Concepto</label><input class="form-control" id="concepto" name="concepto" required></div>
                        <div class="mb-3"><label class="form-label">Monto</label><input type="number" min="0" step="0.01" class="form-control" id="monto" name="monto" required></div>
                        <div id="erroresGasto" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" form="formGasto" type="submit">Guardar</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalGasto = new bootstrap.Modal(document.getElementById('modalGasto'));
        const form = document.getElementById('formGasto');
        const tabla = document.getElementById('tabla-gastos');
        const errores = document.getElementById('erroresGasto');
        const selectReservas = document.getElementById('id_reserva');
        const selectServicios = document.getElementById('id_servicio');

        function resetForm() {
            form.reset();
            document.getElementById('id_gasto').value = '';
            errores.innerHTML = '';
        }

        async function cargarCombos() {
            const [reservasRes, serviciosRes] = await Promise.all([
                fetch('backend/api_crud.php?modulo=reservas'),
                fetch('backend/api_crud.php?modulo=servicios')
            ]);
            const reservas = await reservasRes.json();
            const servicios = await serviciosRes.json();
            selectReservas.innerHTML = '<option value="">Seleccione</option>';
            reservas.forEach(item => selectReservas.innerHTML += `<option value="${item.id_reserva}">#${item.id_reserva} - ${item.cliente_nombre} ${item.cliente_apellido}</option>`);
            selectServicios.innerHTML = '<option value="">Sin servicio</option>';
            servicios.forEach(item => selectServicios.innerHTML += `<option value="${item.id_servicio}">${item.nombre}</option>`);
        }

        async function cargarGastos() {
            const response = await fetch('backend/api_crud.php?modulo=gastos');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.id_gasto}</td><td>${item.id_reserva}</td><td>${item.servicio_nombre || 'Sin servicio'}</td><td>${item.concepto}</td><td>$${parseFloat(item.monto).toFixed(2)}</td><td>${item.fecha}</td><td><button class="btn btn-sm btn-outline-secondary me-2" onclick="editarGasto(${item.id_gasto})">Editar</button><button class="btn btn-sm btn-outline-danger" onclick="eliminarGasto(${item.id_gasto})">Eliminar</button></td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarGasto(event) {
            event.preventDefault();
            errores.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const erroresForm = [
                ...validarNumero(payload.id_reserva, 'reserva', true, 1, true),
                ...validarTexto(payload.concepto, 'concepto', true, 150),
                ...validarNumero(payload.monto, 'monto', true, 0)
            ];
            if (erroresForm.length) {
                mostrarErrores(errores, erroresForm);
                return;
            }
            const method = payload.id_gasto ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=gastos', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_gasto: payload.id_gasto || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                errores.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalGasto.hide();
            resetForm();
            cargarGastos();
        }

        async function eliminarGasto(id) {
            if (!confirm('¿Desea eliminar este gasto?')) return;
            const response = await fetch('backend/api_crud.php?modulo=gastos', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_gasto: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarGastos();
            }
        }

        function editarGasto(id) {
            fetch('backend/api_crud.php?modulo=gastos')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_gasto === id);
                    if (!item) return;
                    document.getElementById('id_gasto').value = item.id_gasto;
                    document.getElementById('id_reserva').value = item.id_reserva;
                    document.getElementById('id_servicio').value = item.id_servicio || '';
                    document.getElementById('concepto').value = item.concepto;
                    document.getElementById('monto').value = item.monto;
                    modalGasto.show();
                });
        }

        form.addEventListener('submit', guardarGasto);
        document.getElementById('modalGasto').addEventListener('hidden.bs.modal', resetForm);
        cargarCombos().then(cargarGastos);
    </script>
</body>
</html>
