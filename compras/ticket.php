<?php

include "../datos.php";

// VALIDAR ID
$id_compra = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_compra <= 0) {
    die("Error: ID de compra inválido.");
}

// CONEXIÓN
$server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';

try {
    $con = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ===============================
// CONSULTA ENCABEZADO (FIX JOIN)
// ===============================
$sqlInfo = "SELECT cp.id_compras, cp.fecha, cp.iva, cp.total, 
                   p.nombre as nombre_proveedor, p.rfc, p.direccion, p.telefono
            FROM compras cp 
            JOIN provedores p ON cp.Provedores_id_provedores = p.id_provedores
            WHERE cp.id_compras = :id";

$stmt = $con->prepare($sqlInfo);
$stmt->execute([':id' => $id_compra]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    die("Compra no encontrada. ID: " . $id_compra);
}

// ===============================
// CONSULTA DETALLE PRODUCTOS
// ===============================
$sqlProd = "SELECT pc.cantidad_pd_cp as cantidad, 
                   pc.precio_pd_cp as precio_compra, 
                   pr.nombre as nombre_producto
            FROM productos_compras pc
            JOIN productos pr 
            ON pc.Productos_id_productos = pr.id_productos
            WHERE pc.Compras_id_compras = :id";

$stmtP = $con->prepare($sqlProd);
$stmtP->execute([':id' => $id_compra]);
$productos = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// CÁLCULOS
// ===============================
$subtotal_final = $info['total'] - $info['iva'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Compra #<?php echo $info['id_compras']; ?></title>
    <link rel="stylesheet" href="ticket.css">
</head>
<body>

<div class="ticket-modal">
    <div class="ticket-body">

        <div class="company-info">
            <div class="company-name">MI EMPRESA</div>
            <div class="company-details">
                RFC: <?php echo $empresa_rfc; ?><br>
                <?php echo $empresa_direccion; ?><br>
                Tel: <?php echo $empresa_telefono; ?>
            </div>
        </div>

        <hr>

        <div><b>Ticket #:</b> <?php echo $info['id_compras']; ?></div>
        <div><b>Fecha:</b> <?php echo $info['fecha']; ?></div>
        <div><b>Proveedor:</b> <?php echo $info['nombre_proveedor'] ?? 'N/A'; ?></div>

        <hr>

        <h4>Detalle de Compra</h4>

        <?php if (count($productos) > 0): ?>
            <?php foreach($productos as $prod): 
                $subtotal_linea = $prod['cantidad'] * $prod['precio_compra'];
            ?>
                <div>
                    <?php echo $prod['nombre_producto']; ?><br>
                    <?php echo $prod['cantidad']; ?> x $<?php echo number_format($prod['precio_compra'], 2); ?> 
                    = $<?php echo number_format($subtotal_linea, 2); ?>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay productos en esta compra.</p>
        <?php endif; ?>

        <hr>

        <div><b>Subtotal:</b> $<?php echo number_format($subtotal_final, 2); ?></div>
        <div><b>IVA:</b> $<?php echo number_format($info['iva'], 2); ?></div>
        <div><b>TOTAL:</b> $<?php echo number_format($info['total'], 2); ?></div>

        <hr>

        <div style="text-align:center;">
            ¡Gracias por su compra!
        </div>

    </div>

    <div style="text-align:center; margin-top:20px;">
        <button onclick="window.print()">🖨️ IMPRIMIR</button>
    </div>
</div>

<script>
    window.onload = function() {
        window.print();
    };
</script>

</body>
</html>