function mostrarErrores(contenedor, errores) {
    if (!contenedor) return;
    contenedor.innerHTML = errores.length ? errores.join('<br>') : '';
}

function validarTexto(value, nombre, requerido = true, maxLength = null) {
    const errores = [];
    const texto = String(value || '').trim();
    if (requerido && texto === '') {
        errores.push(`El campo ${nombre} es obligatorio.`);
        return errores;
    }
    if (maxLength && texto.length > maxLength) {
        errores.push(`El campo ${nombre} no debe superar ${maxLength} caracteres.`);
    }
    return errores;
}

function validarNumero(value, nombre, requerido = true, min = 0, entero = false) {
    const errores = [];
    const texto = String(value || '').trim();
    if (requerido && texto === '') {
        errores.push(`El campo ${nombre} es obligatorio.`);
        return errores;
    }
    if (texto === '') return errores;
    const numero = Number(texto);
    if (!Number.isFinite(numero)) {
        errores.push(`El campo ${nombre} debe ser un número válido.`);
        return errores;
    }
    if (entero && !Number.isInteger(numero)) {
        errores.push(`El campo ${nombre} debe ser un entero.`);
    }
    if (numero < min) {
        errores.push(`El campo ${nombre} no puede ser menor a ${min}.`);
    }
    return errores;
}

function validarEmail(value) {
    const texto = String(value || '').trim();
    if (texto === '') return [];
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(texto) ? [] : ['El email no tiene un formato válido.'];
}

function validarCedula(value) {
    const texto = String(value || '').trim();
    if (texto === '') return ['La cédula es obligatoria.'];
    return /^\d{1,10}$/.test(texto) ? [] : ['La cédula debe contener solo números.'];
}

function validarTelefono(value) {
    const texto = String(value || '').trim();
    if (texto === '') return [];
    return /^\d{7,15}$/.test(texto) ? [] : ['El teléfono debe contener solo números.'];
}

function validarFecha(value, nombre) {
    const texto = String(value || '').trim();
    if (texto === '') return [`La fecha ${nombre} es obligatoria.`];
    return /^\d{4}-\d{2}-\d{2}$/.test(texto) ? [] : [`La fecha ${nombre} debe tener formato yyyy-mm-dd.`];
}
