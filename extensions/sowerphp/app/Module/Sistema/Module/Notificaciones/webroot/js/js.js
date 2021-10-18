function notificacion_leida(id) {
    $("#notification_"+id).remove();
    document.getElementById("n_notifications").textContent = document.getElementById("n_notifications").textContent - 1;
    $.get(_base + "/api/sistema/notificaciones/notificaciones/leida/" + id);
}

function notificacion_abrir(id) {
    window.location.href = _base + "/sistema/notificaciones/notificaciones/abrir/" + id;
}

function notificacion_leida_checkbox(checkbox) {
    checkbox.parentNode.textContent = "Si";
    $.get(_base + "/api/sistema/notificaciones/notificaciones/leida/" + checkbox.value);
}
