<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Habitaciones</h2>
                    <p class="text-muted mb-0">Administre el estado y ubicación de cada habitación.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalHabitacion">Nueva habitación</button>
            </div>
            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Número</th><th>Tipo</th><th>Piso</th><th>Estado</th><th>Precio</th><th>Acciones</th></tr>
                        </thead>
                        <tbody id="tabla-habitaciones"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalHabitacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Habitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formHabitacion">
                        <input type="hidden" id="id_habitacion" name="id_habitacion">
                        <div class="mb-3"><label class="form-label">Número</label><input class="form-control" id="numero_habitacion" name="numero_habitacion" required></div>
                        <div class="mb-3"><label class="form-label">Tipo</label><select class="form-select" id="id_tipo_habitacion" name="id_tipo_habitacion" required></select></div>
                        <div class="mb-3"><label class="form-label">Piso</label><input type="number" min="1" class="form-control" id="piso" name="piso" required></div>
                        <div class="mb-3"><label class="form-label">Estado</label><select class="form-select" id="estado" name="estado">
                            <option value="Disponible">Disponible</option>
                            <option value="Ocupada">Ocupada</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                            <option value="Limpieza">Limpieza</option>
                        </select></div>
                        <div id="erroresHabitacion" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" form="formHabitacion" type="submit">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalHabitacion = new bootstrap.Modal(document.getElementById('modalHabitacion'));
        const form = document.getElementById('formHabitacion');
        const tabla = document.getElementById('tabla-habitaciones');
        const selectTipos = document.getElementById('id_tipo_habitacion');
        const erroresHabitacion = document.getElementById('erroresHabitacion');

        function resetForm() {
            form.reset();
            document.getElementById('id_habitacion').value = '';
            erroresHabitacion.innerHTML = '';
        }

        async function cargarTipos() {
            const response = await fetch('backend/api_crud.php?modulo=tipos_habitacion');
            const data = await response.json();
            selectTipos.innerHTML = '<option value="">Seleccione</option>';
            data.forEach(item => {
                selectTipos.innerHTML += `<option value="${item.id_tipo_habitacion}">${item.nombre}</option>`;
            });
        }

        async function cargarHabitaciones() {
            const response = await fetch('backend/api_crud.php?modulo=habitaciones');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id_habitacion}</td>
                    <td>${item.numero_habitacion}</td>
                    <td>${item.tipo_nombre}</td>
                    <td>${item.piso}</td>
                    <td>${item.estado}</td>
                    <td>$${parseFloat(item.precio_base).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="editarHabitacion(${item.id_habitacion})">Editar</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarHabitacion(${item.id_habitacion})">Eliminar</button>
                    </td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarHabitacion(event) {
            event.preventDefault();
            erroresHabitacion.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const errores = [
                ...validarTexto(payload.numero_habitacion, 'número de habitación', true, 10),
                ...validarNumero(payload.id_tipo_habitacion, 'tipo de habitación', true, 1, true),
                ...validarNumero(payload.piso, 'piso', true, 1, true)
            ];
            if (errores.length) {
                mostrarErrores(erroresHabitacion, errores);
                return;
            }
            const method = payload.id_habitacion ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=habitaciones', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_habitacion: payload.id_habitacion || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                erroresHabitacion.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalHabitacion.hide();
            resetForm();
            cargarHabitaciones();
        }

        async function eliminarHabitacion(id) {
            if (!confirm('¿Desea eliminar esta habitación?')) return;
            const response = await fetch('backend/api_crud.php?modulo=habitaciones', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_habitacion: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarHabitaciones();
            }
        }

        function editarHabitacion(id) {
            fetch('backend/api_crud.php?modulo=habitaciones')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_habitacion === id);
                    if (!item) return;
                    document.getElementById('id_habitacion').value = item.id_habitacion;
                    document.getElementById('numero_habitacion').value = item.numero_habitacion;
                    document.getElementById('id_tipo_habitacion').value = item.id_tipo_habitacion;
                    document.getElementById('piso').value = item.piso;
                    document.getElementById('estado').value = item.estado;
                    modalHabitacion.show();
                });
        }

        form.addEventListener('submit', guardarHabitacion);
        document.getElementById('modalHabitacion').addEventListener('hidden.bs.modal', resetForm);
        cargarTipos().then(cargarHabitaciones);
    </script>
</body>
</html>
