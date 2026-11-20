<?php
    header('Content-Type: text/plain');

    $server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';

    try {
        $con = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // LEER EL JSON
        $json_input = file_get_contents('php://input');
        $data = json_decode($json_input, true); 

        // OBTENER DATOS 
        $nombre_proveedor = $data['nombre_proveedor'] ?? ''; 
        $fecha_compra = $data['fecha_venta']; 
        $iva_porcentaje = filter_var($data['iva'], FILTER_VALIDATE_FLOAT);
        $productos_carrito = $data['productos'];

        if (empty($productos_carrito)) {
            die("Error: No se enviaron productos.");
        }

        // BUSCAR PROVEEDOR
        $sql_prov = "SELECT id_provedores FROM provedores WHERE nombre = :nombre_proveedor";
        $stmt_prov = $con->prepare($sql_prov);
        $stmt_prov->bindParam(':nombre_proveedor', $nombre_proveedor);
        $stmt_prov->execute();
        $proveedor = $stmt_prov->fetch(PDO::FETCH_ASSOC);
        
        if(!$proveedor){
             die("Error: El proveedor '$nombre_proveedor' no existe.");
        }
        $id_proveedor = $proveedor['id_provedores'];

        // --- 5. CALCULAR TOTALES ---
        $subtotal_final = 0;
        foreach ($productos_carrito as $producto) {
            $subtotal_final += $producto['precio'] * $producto['cantidad'];
        }
        $iva_calculado = $subtotal_final * $iva_porcentaje;
        $total_calculado = $subtotal_final + $iva_calculado;

        // iniciar transaccion
        $con->beginTransaction();

        try {
            // insertar compra
            $sql_compra = "INSERT INTO compras (Provedores_id_provedores, iva, total, fecha) 
             VALUES (:id_proveedor, :iva, :total, :fecha)";
            $stmt_compra = $con->prepare($sql_compra);
            $stmt_compra->bindParam(':id_proveedor', $id_proveedor);
            $stmt_compra->bindParam(':iva', $iva_calculado);
            $stmt_compra->bindParam(':total', $total_calculado);
            $stmt_compra->bindParam(':fecha', $fecha_compra);
            $stmt_compra->execute();
            $id_compra_creada = $con->lastInsertId();

            // insertar informacion en la tabla productos_compras
            $sql_cp = "INSERT INTO productos_compras (Compras_id_compras, Productos_id_productos, cantidad_pd_cp, precio_pd_cp, subtotal) 
                       VALUES (:id_compra, :id_articulo, :cantidad, :pre_unitario, :subtotal)";
            $stmt_cp = $con->prepare($sql_cp);

            // Actualizar Stock (AUMENTAR en tabla productos)
            $sql_stock = "UPDATE productos SET cantidad = cantidad + :cant WHERE id_productos = :id_prod";
            $stmt_stock = $con->prepare($sql_stock);

            // Bucle de balidación de compra
            foreach ($productos_carrito as $producto) {

                // NO numeros negativos
                if ($producto['cantidad'] <= 0) {
                    $con->rollBack();
                    die("Error: La cantidad debe ser mayor a 0 para el producto: " . $producto['nombre']);
                }
                $subtotal_producto = $producto['cantidad'] * $producto['precio'];
                
                // Guardar detalle de compra
                $stmt_cp->bindValue(':id_compra', $id_compra_creada);
                $stmt_cp->bindValue(':id_articulo', $producto['id_articulo']);
                $stmt_cp->bindValue(':cantidad', $producto['cantidad']);
                $stmt_cp->bindValue(':pre_unitario', $producto['precio']);
                $stmt_cp->bindValue(':subtotal', $subtotal_producto);
                $stmt_cp->execute();
                // Aumentar Stock en Inventario
                $stmt_stock->bindValue(':cant', $producto['cantidad']);
                $stmt_stock->bindValue(':id_prod', $producto['id_articulo']);
                $stmt_stock->execute();
            }
            // CONFIRMAR COMPRA EN LA BD
            $con->commit();
            echo "Compra registrada y stock actualizado correctamente.";

        } catch (PDOException $e_trans) {
            $con->rollBack();
            echo "Error al procesar la compra: " . $e_trans->getMessage();
        }

    } catch(PDOException $e) {
        die('Error de conexión: ' . $e->getMessage());
    }
?>