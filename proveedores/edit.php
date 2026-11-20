<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: ../proveedores.php"); // Cambiado a proveedores.php por consistencia
    exit;
}

// ERROR CORREGIDO: Usar id_proveedores en la selección
$stmt = $pdo->prepare("SELECT * FROM provedores WHERE id_provedores = ?");
$stmt->execute([$id]);
$proveedor = $stmt->fetch();

if (!$proveedor) {
    die("Proveedor no encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $rfc = $_POST['rfc'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion']; // Agregado
    $promotor = $_POST['promotor'];   // Cambiado de 'contacto' a 'promotor'

    // ERROR CORREGIDO: Usar id_proveedores en UPDATE, incluir direccion y promotor
    $stmt = $pdo->prepare("UPDATE provedores SET nombre=?, rfc=?, email=?, telefono=?, direccion=?, promotor=? WHERE id_provedores=?");
    $stmt->execute([$nombre, $rfc, $email, $telefono, $direccion, $promotor, $id]);

    header("Location: ../proveedores.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar proveedor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Editar proveedor</h1>
    <form method="post">
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required>
        <label>RFC:</label>
        <input type="text" name="rfc" value="<?= htmlspecialchars($proveedor['rfc']) ?>">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email']) ?>">
        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono']) ?>">
        
        <label>Dirección:</label>
        <input type="text" name="direccion" value="<?= htmlspecialchars($proveedor['direccion']) ?>">
        
        <label>Promotor:</label>
        <input type="text" name="promotor" value="<?= htmlspecialchars($proveedor['promotor']) ?>">
        
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <a class="btn" href="../proveedores.php">Cancelar</a>
    </form>
</div>
</body>
</html>