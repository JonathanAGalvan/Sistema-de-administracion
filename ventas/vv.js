document.addEventListener('DOMContentLoaded', function() {

    // ELEMENTOS PRINCIPALES
    const formulario = document.getElementById('formularioVenta');
    const modal = document.getElementById('modalOverlay');
    const btnAbrir = document.getElementById('btnAbrirFormulario');
    const tbody = document.getElementById('tablaVentasBody');

    // ELEMENTOS DEL MODAL
    const selectCliente = document.getElementById('nombre_cliente');
    const selectArticulo = document.getElementById('nom_articulo');
    const inputPrecioUnitario = document.getElementById('pre_unitario');
    const inputCantidad = document.getElementById('cantidad');
    const btnAnadirProducto = document.getElementById('btnAnadirProducto');
    const carritoBody = document.getElementById('carritoBody'); 

    let carrito = []; 
    let idVentaEdicion = null; 

    //CARGAR LISTAS Y SELECT2
    function cargarListas() {
        //CARGAR CLIENTES
    fetch('ventas/cargar_clientes.php')
        .then(r => r.json())
        .then(data => {
            if(selectCliente && !data.error) {
                selectCliente.innerHTML = '<option value="">Seleccione Cliente</option>';
                
                data.forEach(c => {
                    const op = document.createElement('option');
                    op.value = c.nombre; 
                    op.textContent = c.nombre;
                    if (c.nombre.toUpperCase() === "CLIENTE GENERAL") {
                        op.selected = true;
                    }
                    selectCliente.appendChild(op);
                });

                // Activar Select2
                $(selectCliente).select2({
                    dropdownParent: $('#modalOverlay'),
                    width: '100%',
                    placeholder: "Buscar cliente..."
                });
            }
        })
        .catch(e => console.error("Falta archivo cargar_clientes.php en carpeta ventas"));

        //CARGAR ARTICULOS
        fetch('ventas/cargar_articulos.php')
            .then(r => r.json())
            .then(data => {
                if(selectArticulo && !data.error) {
                    selectArticulo.innerHTML = '<option value="">Seleccione Artículo</option>';
                    data.forEach(a => {
                        const op = document.createElement('option');
                        op.value = a.nombre; 
                        op.textContent = `${a.id_productos} -  ${a.codigo_articulo}  - ${a.nombre} - (stock: ${a.cantidad})`;
                        // Guardamos datos clave
                        op.dataset.precio = a.precio_venta; 
                        op.dataset.id = a.id_productos;
                        selectArticulo.appendChild(op);
                    });

                    // Activar Select2 en Artículos
                    $(selectArticulo).select2({
                        dropdownParent: $('#modalOverlay'),
                        width: '100%',
                        placeholder: "Buscar artículo..."
                    });
                }
            })
            .catch(e => console.error("Falta archivo cargar_articulos.php en carpeta ventas"));
    }

    cargarListas();

    // Rellenar informacion automatiamente
    $(selectArticulo).on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.precio) {
            const precio = selectedOption.dataset.precio;
            if(inputPrecioUnitario) inputPrecioUnitario.value = precio;
        } else {
            if(inputPrecioUnitario) inputPrecioUnitario.value = 0;
        }
    });

    //PINTAR CARRITO
    function actualizarCarritoTabla() {
        if(!carritoBody) return;
        carritoBody.innerHTML = ''; 
        
        if (carrito.length === 0) {
            carritoBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Carrito vacío</td></tr>';
            return;
        }

        carrito.forEach((p, i) => {
            const subtotal = p.precio * p.cantidad;
            carritoBody.innerHTML += `
                <tr>
                    <td>${p.nombre}</td>
                    <td>${p.cantidad}</td>
                    <td>$${p.precio.toFixed(2)}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn-quitar" data-index="${i}" style="background:red; color:white; border:none; cursor:pointer; border-radius:3px;">X</button>
                    </td>
                </tr>
            `;
        });
    }

    //BOTÓN AÑADIR
    if (btnAnadirProducto) {
        btnAnadirProducto.addEventListener('click', function() {
            const selectedOption = selectArticulo.options[selectArticulo.selectedIndex];
            
            // Validación con Select2
            const id = selectedOption ? selectedOption.dataset.id : null;

            if (!id || selectArticulo.value === "") { 
                alert("Seleccione un artículo válido."); return; 
            }

            const nombre = selectedOption.text.split(' - ')[1] || selectedOption.text;
            const precio = parseFloat(inputPrecioUnitario.value) || 0;
            const cantidad = parseInt(inputCantidad.value);

            if (isNaN(cantidad) || cantidad <= 0) { 
                alert("Ingrese una cantidad válida (mayor a 0)."); return; 
            }

            // EXISTE O NO
            const indice = carrito.findIndex(p => String(p.id_articulo) === String(id));

            if (indice !== -1) {
                let cantActual = parseInt(carrito[indice].cantidad);
                carrito[indice].cantidad = cantActual + cantidad;
            } else {
                carrito.push({
                    id_articulo: id,
                    nombre: nombre,
                    cantidad: cantidad,
                    precio: precio
                });
            }
            actualizarCarritoTabla(); 

            // Resetear Select2 visualmente
            $(selectArticulo).val('').trigger('change');
            inputCantidad.value = 1;
            inputPrecioUnitario.value = ''; 
        });
    }

    // ELIMINAR COSAS DEL CARRO
    if (carritoBody) {
        carritoBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-quitar')) {
                carrito.splice(e.target.dataset.index, 1); 
                actualizarCarritoTabla(); 
            }
        });
    }

    //GUARDAR VENTA
    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            if (carrito.length === 0) { alert("El carrito está vacío."); return; }
            const datos = {
                nombre_cliente: selectCliente.value,
                fecha_venta: document.getElementById('fecha_venta').value,
                iva: document.getElementById('iva').value,
                productos: carrito,
                id_venta: idVentaEdicion 
            };
            let url = (idVentaEdicion !== null) ? 'ventas/modificar.php' : 'ventas/ventas_registro.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            })
            .then(r => r.text())
            .then(res => {
                let respuesta = res.trim();
                // Verificamos ÉXITO
                if (respuesta.includes("correctamente") || respuesta.includes("success") || respuesta.includes("registrada")) {
                    let mensaje = idVentaEdicion ? "modificar_ok" : "venta_ok";
                    window.location.href = "?msg=" + mensaje;
                } else {
                    // ERROR (No recarga)
                    alert("⚠️ " + respuesta);
                }
            })
            .catch(error => { 
                console.error(error); 
                alert("Error de conexión con el servidor."); 
            });
        });
    }

    //BOTONES DE ACTUALIZAR, ELIMINAR O PDF
    if(tbody) {
        tbody.addEventListener('click', function(e) {
            const btnDel = e.target.closest('.btn-eliminar');
            const btnEdit = e.target.closest('.btn-modificar');
            const btnPdf = e.target.closest('.btn-pdf');
            // ELIMINAR
            if (btnDel) {
                if (!confirm('¿Eliminar venta?')) return;
                const fd = new FormData();
                fd.append('id_venta', btnDel.dataset.id);
                
                fetch('ventas/ventas_bajas.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(msg => { 
                    if(!msg.includes("Error")) {
                        window.location.href = "?msg=eliminar_ok";
                    } else {
                        alert(msg);
                    }
                });
            }  
            // EDITAR
            if (btnEdit) {
                idVentaEdicion = btnEdit.dataset.id;
                fetch(`ventas/obtener_detalle_venta.php?id=${idVentaEdicion}`)
                    .then(r => r.json())
                    .then(data => {
                        if(data.venta) {
                            // Actualizar Select2 de Cliente
                            $(selectCliente).val(data.venta.nombre_cliente).trigger('change');
                            document.getElementById('fecha_venta').value = data.venta.fecha_venta;
                            // Llenar carrito
                            carrito = data.productos.map(p => ({
                                id_articulo: p.id, 
                                nombre: p.nombre,
                                cantidad: parseInt(p.cantidad),
                                precio: parseFloat(p.precio)
                            }));
                            actualizarCarritoTabla();
                            if (modal) modal.style.display = 'flex';
                        }
                    });
            }
            // PDF
            if (btnPdf) {
                window.open(`ventas/generar_ticket_venta.php?id=${btnPdf.dataset.id}`, '_blank'); 
            }
        });
    }
    // ABRIR MODAL (NUEVA VENTA)
    if (btnAbrir) {
        btnAbrir.addEventListener('click', () => {
            formulario.reset();
            carrito = []; 
            idVentaEdicion = null; 
            $(selectCliente).val('CLIENTE GENERAL').trigger('change');

            $(selectArticulo).val('').trigger('change');

            actualizarCarritoTabla(); 
            if(document.getElementById('fecha_venta')) {
                document.getElementById('fecha_venta').valueAsDate = new Date();
            }
            if (modal) modal.style.display = 'flex';
        });
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('.cerrar-modal')) {
                modal.style.display = 'none';
            }
        });
    }
});