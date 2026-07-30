<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de habitación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Tipos de habitación</h2>
                    <p class="text-muted mb-0">Gestione los tipos y tarifas base.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipo">Nuevo tipo</button>
            </div>

            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio base</th><th>Capacidad</th><th>Acciones</th></tr>
                        </thead>
                        <tbody id="tabla-tipos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTipo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tipo de habitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formTipo">
                        <input type="hidden" id="id_tipo_habitacion" name="id_tipo_habitacion">
                        <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" id="nombre" name="nombre" required></div>
                        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" required></textarea></div>
                        <div class="mb-3"><label class="form-label">Precio base</label><input type="number" step="0.01" min="0" class="form-control" id="precio_base" name="precio_base" required></div>
                        <div class="mb-3"><label class="form-label">Capacidad máxima</label><input type="number" min="1" class="form-control" id="capacidad_maxima" name="capacidad_maxima" required></div>
                        <div id="erroresTipo" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" form="formTipo" type="submit">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalTipo = new bootstrap.Modal(document.getElementById('modalTipo'));
        const form = document.getElementById('formTipo');
        const erroresTipo = document.getElementById('erroresTipo');
        const tabla = document.getElementById('tabla-tipos');

        function resetForm() {
            form.reset();
            document.getElementById('id_tipo_habitacion').value = '';
            erroresTipo.innerHTML = '';
        }

        async function cargarTipos() {
            const response = await fetch('backend/api_crud.php?modulo=tipos_habitacion');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id_tipo_habitacion}</td>
                    <td>${item.nombre}</td>
                    <td>${item.descripcion}</td>
                    <td>$${parseFloat(item.precio_base).toFixed(2)}</td>
                    <td>${item.capacidad_maxima}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="editarTipo(${item.id_tipo_habitacion})">Editar</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarTipo(${item.id_tipo_habitacion})">Eliminar</button>
                    </td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarTipo(event) {
            event.preventDefault();
            erroresTipo.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const errores = [
                ...validarTexto(payload.nombre, 'nombre', true, 50),
                ...validarTexto(payload.descripcion, 'descripción', true, 255),
                ...validarNumero(payload.precio_base, 'precio base', true, 0),
                ...validarNumero(payload.capacidad_maxima, 'capacidad máxima', true, 1, true)
            ];
            if (errores.length) {
                mostrarErrores(erroresTipo, errores);
                return;
            }
            const method = payload.id_tipo_habitacion ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=tipos_habitacion', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_tipo_habitacion: payload.id_tipo_habitacion || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                erroresTipo.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalTipo.hide();
            resetForm();
            cargarTipos();
        }

        async function eliminarTipo(id) {
            if (!confirm('¿Desea eliminar este tipo?')) return;
            const response = await fetch('backend/api_crud.php?modulo=tipos_habitacion', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_tipo_habitacion: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarTipos();
            }
        }

        function editarTipo(id) {
            fetch('backend/api_crud.php?modulo=tipos_habitacion')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_tipo_habitacion === id);
                    if (!item) return;
                    document.getElementById('id_tipo_habitacion').value = item.id_tipo_habitacion;
                    document.getElementById('nombre').value = item.nombre;
                    document.getElementById('descripcion').value = item.descripcion;
                    document.getElementById('precio_base').value = item.precio_base;
                    document.getElementById('capacidad_maxima').value = item.capacidad_maxima;
                    modalTipo.show();
                });
        }

        form.addEventListener('submit', guardarTipo);
        document.getElementById('modalTipo').addEventListener('hidden.bs.modal', resetForm);
        cargarTipos();
    </script>
</body>
</html>
