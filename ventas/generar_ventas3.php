<?php
$server = 'srv737.hstgr.io';
$username = 'u102381755_sistema';
$password = 'Os5aVGSRL+';
$database = 'u102381755_sistemaInve';

try {
    $con = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // CLIENTE GENERAL
    $stmtCliente = $con->prepare("SELECT id_clientes FROM clientes WHERE nombre = 'CLIENTE GENERAL' LIMIT 1");
    $stmtCliente->execute();
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("No existe CLIENTE GENERAL");
    }

    $id_cliente = $cliente['id_clientes'];

    // PRODUCTOS
    $productos = $con->query("SELECT id_productos, precio_venta, cantidad FROM productos")->fetchAll(PDO::FETCH_ASSOC);

    if (count($productos) == 0) {
        die("No hay productos");
    }

    echo "Generando 100 ventas (1 producto, cantidad 2)... <br>";

    for ($i = 0; $i < 100; $i++) {

        $con->beginTransaction();

        try {

            // FECHA RANDOM
            $timestamp = rand(strtotime("2024-01-01"), strtotime("2025-12-31"));
            $fecha = date("Y-m-d", $timestamp);

            // 🔹 SOLO 1 PRODUCTO
            $p = $productos[array_rand($productos)];

            $cantidad = 2; // 👈 FIJO

            // VALIDAR STOCK REAL EN BD
            $stmtCheck = $con->prepare("SELECT cantidad FROM productos WHERE id_productos = :id FOR UPDATE");
            $stmtCheck->execute([':id' => $p['id_productos']]);
            $stockActual = $stmtCheck->fetchColumn();

            if ($stockActual < $cantidad) {
                $con->rollBack();
                continue;
            }

            $subtotal = $p['precio_venta'] * $cantidad;
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;

            // INSERT VENTA
            $stmt = $con->prepare("INSERT INTO ventas (Clientes_id_clientes, iva, total, fecha_venta)
                                   VALUES (:cliente, :iva, :total, :fecha)");
            $stmt->execute([
                ':cliente' => $id_cliente,
                ':iva' => $iva,
                ':total' => $total,
                ':fecha' => $fecha
            ]);

            $id_venta = $con->lastInsertId();

            // INSERT DETALLE
            $stmtDetalle = $con->prepare("INSERT INTO ventas_productos 
                (Ventas_id_ventas, Productos_id_productos, cantidad, precio, subtotal)
                VALUES (:venta, :producto, :cantidad, :precio, :subtotal)");

            $stmtDetalle->execute([
                ':venta' => $id_venta,
                ':producto' => $p['id_productos'],
                ':cantidad' => $cantidad,
                ':precio' => $p['precio_venta'],
                ':subtotal' => $subtotal
            ]);

            // UPDATE STOCK
            $stmtStock = $con->prepare("UPDATE productos 
                                       SET cantidad = cantidad - :cantidad 
                                       WHERE id_productos = :id");

            $stmtStock->execute([
                ':cantidad' => $cantidad,
                ':id' => $p['id_productos']
            ]);

            $con->commit();

            echo "Venta $i creada ✔ <br>";

        } catch (Exception $e) {
            $con->rollBack();
            echo "Error en venta $i: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br>LISTO: 100 ventas generadas.";

} catch (Exception $e) {
    die("Error conexión: " . $e->getMessage());
}
?>