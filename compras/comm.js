document.addEventListener('DOMContentLoaded', function() {

    // ELEMENTOS PRINCIPALES
    const formulario = document.getElementById('formularioCompras'); 
    const modal = document.getElementById('modalOverlay');
    const btnAbrir = document.getElementById('btnAbrirFormulario');
    const tbody = document.getElementById('tablaCompraBody');

    // ELEMENTOS DEL MODAL
    const selectProveedor = document.getElementById('nombre_proveedor');
    const selectArticulo = document.getElementById('nom_articulo');
    const inputPrecioUnitario = document.getElementById('pre_unitario');
    const inputCantidad = document.getElementById('cantidad');
    const btnAnadirProducto = document.getElementById('btnAnadirProducto');
    const carritoBody = document.getElementById('carritoBody'); 

    let carrito = []; 
    let idVentaEdicion = null; 

    //CARGAR LISTAS DESPLEGABLES
    function cargarListas() {
        //  CARGAR PROVEEDORES 
        fetch('compras/cargar_proveedores.php')
            .then(r => r.json())
            .then(data => {
                if(selectProveedor && !data.error) {
                    selectProveedor.innerHTML = '<option value="">Seleccione Proveedor</option>';
                    data.forEach(p => {
                        const op = document.createElement('option');
                        op.value = p.nombre; op.textContent = p.nombre;
                        selectProveedor.appendChild(op);
                    });
                    // Activar Select2 en Proveedores
                    $(selectProveedor).select2({
                        dropdownParent: $('#modalOverlay'),
                        width: '100%',
                        placeholder: "Buscar proveedor..."
                    });
                }
            })
            .catch(e => console.error("Error cargando proveedores:", e));

        //CARGAR ARTÍCULOs
        fetch('compras/cargar_articulos_compra.php')
            .then(r => r.json())
            .then(data => {
                if(selectArticulo && !data.error) {
                    selectArticulo.innerHTML = '<option value="">Seleccione Artículo</option>';
                    data.forEach(a => {
                        const op = document.createElement('option');
                        op.value = a.nombre; 
                        op.textContent = `${a.id_productos} -  ${a.codigo_articulo} - ${a.nombre} - (stock: ${a.cantidad})`;
                        // Guardar precio y ID en el dataset
                        op.dataset.precio = a.precio_compra || a.precio_venta || 0; 
                        op.dataset.id = a.id_productos;
                        selectArticulo.appendChild(op);
                    });
                    $(selectArticulo).select2({
                        dropdownParent: $('#modalOverlay'),
                        width: '100%',
                        placeholder: "Buscar artículo..."
                    });
                }
            })
            .catch(e => console.error("Error cargando artículos:", e));
    }
    cargarListas();

    // Rellenar campos con información y compatibilidad con select2
    $(selectArticulo).on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.precio) {
            const precio = selectedOption.dataset.precio;
            if(inputPrecioUnitario) inputPrecioUnitario.value = precio;
        } else {
            if(inputPrecioUnitario) inputPrecioUnitario.value = 0;
        }
    });

    // 2. FUNCIÓN PARA PINTAR EL CARRITO EN EL MODAL
    function pintarCarrito() {
        if(!carritoBody) return;
        carritoBody.innerHTML = '';
        if(carrito.length === 0) {
            carritoBody.innerHTML = '<tr><td colspan="5" style="text-align:center">Carrito vacío</td></tr>';
            return;
        }
        carrito.forEach((p, i) => {
            let sub = p.precio * p.cantidad;
            carritoBody.innerHTML += `
                <tr>
                    <td>${p.nombre}</td>
                    <td>${p.cantidad}</td>
                    <td>$${p.precio.toFixed(2)}</td>
                    <td>$${sub.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn-quitar" data-index="${i}" style="background:red; color:white; border:none; cursor:pointer; border-radius:3px;">X</button>
                    </td>
                </tr>`;
        });
    }

    // 3. BOTÓN AÑADIR PRODUCTO AL CARRITO
    if (btnAnadirProducto) {
        btnAnadirProducto.addEventListener('click', () => {
            const sel = selectArticulo.options[selectArticulo.selectedIndex];
            
            // Validación básica
            const id = sel ? sel.dataset.id : null;
            
            if(!id || selectArticulo.value === "") return alert("Seleccione un artículo válido");

            const cant = parseInt(inputCantidad.value) || 1;
            const pre = parseFloat(inputPrecioUnitario.value) || 0;
            const nombreLimpio = sel.value; 

            if (cant <= 0) return alert("La cantidad debe ser mayor a 0");

            // verificar si existe el producto en el carrito
            const idx = carrito.findIndex(p => String(p.id_articulo) === String(id));
            
            if(idx !== -1) {
                carrito[idx].cantidad += cant; 
            } else {
                carrito.push({ 
                    id_articulo: id, 
                    nombre: nombreLimpio, 
                    cantidad: cant, 
                    precio: pre 
                });
            }
            pintarCarrito();
            
            // RESETEAR CAMPOS Select2
            $(selectArticulo).val('').trigger('change');
            inputCantidad.value = 1;
            inputPrecioUnitario.value = '';
        });
    }

    // Limpiar carrito
    if(carritoBody) {
        carritoBody.addEventListener('click', (e) => {
            if(e.target.classList.contains('btn-quitar')) {
                carrito.splice(e.target.dataset.index, 1);
                pintarCarrito();
            }
        });
    }

    // 4. GUARDAR COMPRA 
    if (formulario) {
        formulario.addEventListener('submit', (e) => {
            e.preventDefault();
            if(carrito.length === 0) return alert("El carrito está vacío, agregue productos.");

            const datos = {
                nombre_proveedor: selectProveedor.value, 
                fecha_venta: document.getElementById('fecha_venta').value, 
                iva: document.getElementById('iva').value,
                productos: carrito,
                id_venta: idVentaEdicion 
            };

            let url = idVentaEdicion ? 'compras/modificar_compras.php' : 'compras/compras_registro.php';

            fetch(url, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify(datos) 
            })
            .then(r => r.text())
            .then(res => {
                let respuesta = res.trim();

                if (respuesta.includes("correctamente") || respuesta.includes("success") || respuesta.includes("registrada")) {
                    let mensaje = idVentaEdicion ? "modificar_ok" : "compra_ok"; 
                    window.location.href = "?msg=" + mensaje;
                } else {
                    alert("⚠️ " + respuesta);
                }
            })
            .catch(error => {
                console.error(error);
                alert("Error de conexión con el servidor.");
            });
        });
    }

    // 5. ACCIONES DE TABLA (EDITAR, ELIMINAR, PDF)
    if(tbody) {
        tbody.addEventListener('click', (e) => {
            const btnDel = e.target.closest('.btn-eliminar');
            const btnEdit = e.target.closest('.btn-modificar');
            const btnPdf = e.target.closest('.btn-pdf'); 

            // ELIMINAR
            if(btnDel) {
                if(!confirm("¿Desea eliminar esta compra? Se descontará del stock.")) return;
                
                let fd = new FormData(); 
                fd.append('id_compra', btnDel.dataset.id); 
                
                fetch('compras/compras_bajas.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(msg => { 
                    if(!msg.includes("Error")) {
                        window.location.href = "?msg=eliminar_ok";
                    } else {
                        alert("⚠️ " + msg);
                    }
                });
            }

            // EDITAR
            if(btnEdit) {
                idVentaEdicion = btnEdit.dataset.id;
                fetch(`compras/obtener_detalle_compra.php?id=${idVentaEdicion}`)
                .then(r => r.json())
                .then(d => {
                    if(d.venta) {
                        // Actualizar Select2 de Proveedor
                        $(selectProveedor).val(d.venta.nombre_proveedor || d.venta.nombre_cliente).trigger('change');
                        
                        document.getElementById('fecha_venta').value = d.venta.fecha_venta || d.venta.fecha; 
                        
                        carrito = d.productos.map(p => ({
                            id_articulo: p.id, 
                            nombre: p.nombre, 
                            cantidad: parseInt(p.cantidad), 
                            precio: parseFloat(p.precio)
                        }));
                        pintarCarrito();
                        if(modal) modal.style.display = 'flex';
                    }
                })
                .catch(e => console.error("Error al obtener detalles:", e));
            }

            // PDF
            if (btnPdf) {
                window.open(`compras/ticket.php?id=${btnPdf.dataset.id}`, '_blank'); 
            }
        });
    }

    // ABRIR MODAL (NUEVA COMPRA)
    if(btnAbrir) btnAbrir.addEventListener('click', () => {
        formulario.reset(); 
        carrito = []; 
        idVentaEdicion = null; 
        
        // Limpiar Select2 
        $(selectProveedor).val('').trigger('change');
        $(selectArticulo).val('').trigger('change');

        pintarCarrito();
        
        if(document.getElementById('fecha_venta')) {
            document.getElementById('fecha_venta').valueAsDate = new Date();
        }
        if(modal) modal.style.display = 'flex';
    });

    // CERRAR MODAL
    if(modal) modal.addEventListener('click', (e) => {
        if(e.target === modal || e.target.closest('.cerrar-modal')) modal.style.display = 'none';
    });
});