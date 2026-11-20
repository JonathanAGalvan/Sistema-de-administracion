// ----- ABRIR MODAL EDITAR CLIENTE -----
function abrirEditarCliente(id, nombre, email, telefono, direccion, ciudad, estado, cp, fecha, estatus) {

    document.getElementById("edit_id_clientes").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_email").value = email;
    document.getElementById("edit_telefono").value = telefono;
    document.getElementById("edit_direccion").value = direccion;
    document.getElementById("edit_ciudad").value = ciudad;
    document.getElementById("edit_estado").value = estado;
    document.getElementById("edit_cp").value = cp;
    document.getElementById("edit_fecha").value = fecha;
    document.getElementById("edit_estatus").value = estatus;

    document.getElementById("modalEditarCliente").style.display = "flex";
}

// ----- CERRAR MODAL EDITAR -----
document.getElementById("btnCerrarEditarCliente").addEventListener("click", function () {
    document.getElementById("modalEditarCliente").style.display = "none";
});

// ----- CERRAR AL DAR CLICK FUERA -----
window.addEventListener("click", function (e) {
    const modal = document.getElementById("modalEditarCliente");
    if (e.target === modal) {
        modal.style.display = "none";
    }
});
