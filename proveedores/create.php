<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $rfc = $_POST['rfc'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    // 1. CORRECCIÓN DE CONSISTENCIA: La variable POST ahora es 'promotor' 
    //    para coincidir con el nombre del campo en la base de datos y el formulario.
    $promotor = $_POST['promotor']; 

    // 2. CORRECCIÓN CRÍTICA 1: Nombre de la tabla. Cambiado de 'provedores' a 'proveedores'.
    // 3. CORRECCIÓN CRÍTICA 2: Nombre de la columna. Cambiado de 'contacto' a 'promotor'.
    $stmt = $pdo->prepare("INSERT INTO provedores (nombre, rfc, email, telefono, direccion, promotor) VALUES (?, ?, ?, ?, ?, ?)");
    
    // 4. CORRECCIÓN DE EJECUCIÓN: Se pasa la variable $promotor en lugar de la variable $contacto antigua.
    $stmt->execute([$nombre, $rfc, $email, $telefono, $direccion, $promotor]);

    header("Location: ../proveedores.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Proveedor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Agregar Proveedor</h1>
    <form method="post">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>

        <label>RFC:</label>
        <input type="text" name="rfc">

        <label>Email:</label>
        <input type="email" name="email">

        <label>Teléfono:</label>
        <input type="text" name="telefono">

        <label>Dirección:</label>
        <input type="text" name="direccion">
        
        <label>Promotor:</label>
        <input type="text" name="promotor">

        <button class="btn btn-primary" type="submit">Guardar</button>
        <a href="../proveedores.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>