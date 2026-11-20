<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // DATOS DEL FORMULARIO
    $fecha           = $_POST['fecha'] ?? '';
    $tipo            = $_POST['tipo'] ?? '';
    $producto_nombre = $_POST['producto_nombre'] ?? '';
    $producto_id     = $_POST['producto_id'] ?? '';
    $cantidad        = $_POST['cantidad'] ?? 0;
    $motivo          = $_POST['motivo'] ?? '';
    $usuario         = $_POST['usuario'] ?? '';

    // VALIDAR PRODUCTO
    if (empty($producto_id) || !is_numeric($producto_id)) {
        die("Error: Debes seleccionar un producto válido.");
    }

    // VALIDAR CANTIDAD
    if ($cantidad <= 0) {
        die("Error: La cantidad debe ser mayor a 0.");
    }

    try {

        // =========================
        // OBTENER INVENTARIO ACTUAL
        // =========================

        $stockQuery = $pdo->prepare("
            SELECT cantidad 
            FROM productos 
            WHERE id_productos = ?
        ");

        $stockQuery->execute([$producto_id]);

        $producto = $stockQuery->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            die("Error: Producto no encontrado.");
        }

        $stock_actual = (int)$producto['cantidad'];

        // =========================
        // CALCULAR NUEVO STOCK
        // =========================

        $nuevo_stock = $stock_actual;

        // INGRESO
        if ($tipo === 'Ingreso') {

            $nuevo_stock = $stock_actual + (int)$cantidad;

        }

        // DEVOLUCIÓN
        elseif ($tipo === 'Devolución') {

            $nuevo_stock = $stock_actual + (int)$cantidad;

        }

        // AJUSTE DE ENTRADA
        elseif ($tipo === 'Ajuste Entrada') {

            $nuevo_stock = $stock_actual + (int)$cantidad;

        }

        // AJUSTE DE SALIDA
        elseif ($tipo === 'Ajuste Salida') {

            $nuevo_stock = $stock_actual - (int)$cantidad;

        }

        // MERMA
        elseif ($tipo === 'Merma') {

            $nuevo_stock = $stock_actual - (int)$cantidad;

        }

        // VALIDAR STOCK NEGATIVO
        if ($nuevo_stock < 0) {
            die("Error: Inventario insuficiente.");
        }

        // =========================
        // GUARDAR MOVIMIENTO
        // =========================

        $sql = "
            INSERT INTO otrosmovimientos
            (
                fecha,
                tipo_mov,
                nombre_prod,
                Productos_id_producto,
                cantidad,
                descripcion,
                usuario
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $fecha,
            $tipo,
            $producto_nombre,
            $producto_id,
            $cantidad,
            $motivo,
            $usuario
        ]);

        // =========================
        // ACTUALIZAR INVENTARIO
        // =========================

        $update = $pdo->prepare("
            UPDATE productos
            SET cantidad = ?
            WHERE id_productos = ?
        ");

        $update->execute([
            $nuevo_stock,
            $producto_id
        ]);

        // REDIRECCIÓN
        header("Location: ../om.php?success=1");
        exit;

    } catch (PDOException $e) {

        echo "Error: " . $e->getMessage();
        exit;
    }

} else {

    header("Location: ../om.php");
    exit;
}
?>