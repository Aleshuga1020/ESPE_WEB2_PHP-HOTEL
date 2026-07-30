<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Clientes</h2>
                    <p class="text-muted mb-0">Gestione la información de los huéspedes.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">Nuevo cliente</button>
            </div>
            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Cédula</th><th>Teléfono</th><th>Email</th><th>Dirección</th><th>Acciones</th></tr>
                        </thead>
                        <tbody id="tabla-clientes"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCliente">
                        <input type="hidden" id="id_cliente" name="id_cliente">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" id="nombre" name="nombre" required></div>
                            <div class="col-md-6"><label class="form-label">Apellido</label><input class="form-control" id="apellido" name="apellido" required></div>
                            <div class="col-md-6"><label class="form-label">Cédula</label><input class="form-control" id="cedula" name="cedula" required></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" id="telefono" name="telefono"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" id="email" name="email"></div>
                            <div class="col-md-6"><label class="form-label">Dirección</label><input class="form-control" id="direccion" name="direccion" required></div>
                        </div>
                        <div id="erroresCliente" class="text-danger mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" form="formCliente" type="submit">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/validaciones.js"></script>
    <script>
        const modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'));
        const form = document.getElementById('formCliente');
        const tabla = document.getElementById('tabla-clientes');
        const erroresCliente = document.getElementById('erroresCliente');

        function resetForm() {
            form.reset();
            document.getElementById('id_cliente').value = '';
            erroresCliente.innerHTML = '';
        }

        async function cargarClientes() {
            const response = await fetch('backend/api_crud.php?modulo=clientes');
            const data = await response.json();
            tabla.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id_cliente}</td>
                    <td>${item.nombre}</td>
                    <td>${item.apellido}</td>
                    <td>${item.cedula}</td>
                    <td>${item.telefono || ''}</td>
                    <td>${item.email || ''}</td>
                    <td>${item.direccion || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="editarCliente(${item.id_cliente})">Editar</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(${item.id_cliente})">Eliminar</button>
                    </td>`;
                tabla.appendChild(tr);
            });
        }

        async function guardarCliente(event) {
            event.preventDefault();
            erroresCliente.innerHTML = '';
            const payload = Object.fromEntries(new FormData(form).entries());
            const errores = [
                ...validarTexto(payload.nombre, 'nombre', true, 80),
                ...validarTexto(payload.apellido, 'apellido', true, 80),
                ...validarCedula(payload.cedula),
                ...validarTelefono(payload.telefono),
                ...validarEmail(payload.email),
                ...validarTexto(payload.direccion, 'dirección', true, 255)
            ];
            if (errores.length) {
                mostrarErrores(erroresCliente, errores);
                return;
            }
            const method = payload.id_cliente ? 'PUT' : 'POST';
            const response = await fetch('backend/api_crud.php?modulo=clientes', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ...payload, id_cliente: payload.id_cliente || undefined })
            });
            const result = await response.json();
            if (!response.ok || result.estado === 'error') {
                erroresCliente.innerHTML = (result.errores || [result.mensaje]).join('<br>');
                return;
            }
            modalCliente.hide();
            resetForm();
            cargarClientes();
        }

        async function eliminarCliente(id) {
            if (!confirm('¿Desea eliminar este cliente?')) return;
            const response = await fetch('backend/api_crud.php?modulo=clientes', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_cliente: id })
            });
            const result = await response.json();
            if (response.ok && result.estado === 'ok') {
                cargarClientes();
            }
        }

        function editarCliente(id) {
            fetch('backend/api_crud.php?modulo=clientes')
                .then(r => r.json())
                .then(data => {
                    const item = data.find(x => x.id_cliente === id);
                    if (!item) return;
                    document.getElementById('id_cliente').value = item.id_cliente;
                    document.getElementById('nombre').value = item.nombre;
                    document.getElementById('apellido').value = item.apellido;
                    document.getElementById('cedula').value = item.cedula;
                    document.getElementById('telefono').value = item.telefono || '';
                    document.getElementById('email').value = item.email || '';
                    document.getElementById('direccion').value = item.direccion || '';
                    modalCliente.show();
                });
        }

        form.addEventListener('submit', guardarCliente);
        document.getElementById('modalCliente').addEventListener('hidden.bs.modal', resetForm);
        cargarClientes();
    </script>
</body>
</html>
