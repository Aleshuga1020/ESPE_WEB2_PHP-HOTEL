<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Servicios</h2>
                    <p class="text-muted mb-0">Administre los servicios adicionales del hotel.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServicio">Nuevo servicio</button>
            </div>
            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Acciones</th></tr></thead>
                        <tbody id="tabla-servicios"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalServicio" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Servicio</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="formServicio">
                        <input type="hidden" id="id_servicio" name="id_servicio">
                        <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" id="nombre" name="nombre" required></div>
                        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" required></textarea></div>
                        <div class="mb-3"><label class="form-label">Precio</label><input type="number" min="0" step="0.01" class="form-control" id="precio" name="precio" required></div>
                        <div id="erroresServicio" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" form="formServicio" type="submit">Guardar</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalServicio = new bootstrap.Modal(document.getElementById('modalServicio'));
        const form = document.getElementById('formServicio');
        const tabla = document.getElementById('tabla-servicios');
        const errores = document.getElementById('erroresServicio');

        function resetForm() {
            form.reset();
            document.getElementById('id_servicio').value = '';
            errores.innerHTML = '';
        }

        async function cargarServicios() {
            const response = await fetch('backend/api_crud.php?modulo=servicios');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.id_servicio}</td><td>${item.nombre}</td><td>${item.descripcion}</td><td>$${parseFloat(item.precio).toFixed(2)}</td><td><button class="btn btn-sm btn-outline-secondary me-2" onclick="editarServicio(${item.id_servicio})">Editar</button><button class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(${item.id_servicio})">Eliminar</button></td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarServicio(event) {
            event.preventDefault();
            errores.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const erroresForm = [
                ...validarTexto(payload.nombre, 'nombre', true, 80),
                ...validarTexto(payload.descripcion, 'descripción', true, 255),
                ...validarNumero(payload.precio, 'precio', true, 0)
            ];
            if (erroresForm.length) {
                mostrarErrores(errores, erroresForm);
                return;
            }
            const method = payload.id_servicio ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=servicios', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_servicio: payload.id_servicio || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                errores.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalServicio.hide();
            resetForm();
            cargarServicios();
        }

        async function eliminarServicio(id) {
            if (!confirm('¿Desea eliminar este servicio?')) return;
            const response = await fetch('backend/api_crud.php?modulo=servicios', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_servicio: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarServicios();
            }
        }

        function editarServicio(id) {
            fetch('backend/api_crud.php?modulo=servicios')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_servicio === id);
                    if (!item) return;
                    document.getElementById('id_servicio').value = item.id_servicio;
                    document.getElementById('nombre').value = item.nombre;
                    document.getElementById('descripcion').value = item.descripcion;
                    document.getElementById('precio').value = item.precio;
                    modalServicio.show();
                });
        }

        form.addEventListener('submit', guardarServicio);
        document.getElementById('modalServicio').addEventListener('hidden.bs.modal', resetForm);
        cargarServicios();
    </script>
</body>
</html>
