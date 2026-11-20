<?php
$server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';

try {
    $con = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // OBTENER PROVEEDORES
    $proveedores = $con->query("SELECT id_provedores FROM provedores")->fetchAll(PDO::FETCH_ASSOC);
    if (count($proveedores) == 0) die("No hay proveedores");

    // OBTENER PRODUCTOS
    $productos = $con->query("SELECT id_productos, precio_compra FROM productos")->fetchAll(PDO::FETCH_ASSOC);
    if (count($productos) == 0) die("No hay productos");

    echo "Generando 100 compras...<br>";

    for ($i = 1; $i <= 100; $i++) {

        $con->beginTransaction();

        try {

            // PROVEEDOR RANDOM
            $prov = $proveedores[array_rand($proveedores)];
            $id_proveedor = $prov['id_provedores'];

            // FECHA RANDOM
            $timestamp = rand(strtotime("2024-01-01"), strtotime("2025-12-31"));
            $fecha = date("Y-m-d", $timestamp);

            // 1 a 5 productos
            $numProductos = rand(1, 5);

            $carrito = [];
            $subtotal_final = 0;

            for ($j = 0; $j < $numProductos; $j++) {

                $p = $productos[array_rand($productos)];

                $cantidad = rand(5, 50); // compras grandes
                $precio = $p['precio_compra'];

                $subtotal = $cantidad * $precio;

                $carrito[] = [
                    'id' => $p['id_productos'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal
                ];

                $subtotal_final += $subtotal;
            }

            $iva = $subtotal_final * 0.16;
            $total = $subtotal_final + $iva;

            // INSERT COMPRA
            $stmt_compra = $con->prepare("INSERT INTO compras 
                (Provedores_id_provedores, iva, total, fecha) 
                VALUES (:prov, :iva, :total, :fecha)");

            $stmt_compra->execute([
                ':prov' => $id_proveedor,
                ':iva' => $iva,
                ':total' => $total,
                ':fecha' => $fecha
            ]);

            $id_compra = $con->lastInsertId();

            // INSERT DETALLE
            $stmt_cp = $con->prepare("INSERT INTO productos_compras 
                (Compras_id_compras, Productos_id_productos, cantidad_pd_cp, precio_pd_cp, subtotal) 
                VALUES (:compra, :producto, :cantidad, :precio, :subtotal)");

            // UPDATE STOCK
            $stmt_stock = $con->prepare("UPDATE productos 
                SET cantidad = cantidad + :cantidad 
                WHERE id_productos = :id");

            foreach ($carrito as $c) {

                $stmt_cp->execute([
                    ':compra' => $id_compra,
                    ':producto' => $c['id'],
                    ':cantidad' => $c['cantidad'],
                    ':precio' => $c['precio'],
                    ':subtotal' => $c['subtotal']
                ]);

                $stmt_stock->execute([
                    ':cantidad' => $c['cantidad'],
                    ':id' => $c['id']
                ]);
            }

            $con->commit();

            echo "Compra $i creada ✔ <br>";

        } catch (Exception $e) {
            $con->rollBack();
            echo "Error en compra $i: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br>LISTO: 100 compras generadas.";

} catch (Exception $e) {
    die("Error conexión: " . $e->getMessage());
}
?>