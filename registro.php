<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('control/db.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/reg.css">
</head>
<body>

<form action="control/registro.php" method="POST">
    <h2>Registro</h2>

    <label>Nombre:</label>
    <input type="text" name="nombre" required>

    <label>Apellido:</label>
    <input type="text" name="apellido" required>

    <label>Usuario:</label>
    <input type="text" name="usuario" required>

    <label>Correo:</label>
    <input type="email" name="correo" required>

    <label>Contraseña:</label>
    <input type="password" name="password" required>

    <label>Cargo:</label>
    <select name="id_cargo" required>
        <option value="">Selecciona</option>

        <?php
        $result = $conexion->query("SELECT * FROM cargo");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='".$row['id']."'>".$row['nombre']."</option>";
        }
        ?>
    </select>

    <button type="submit">Registrarse</button>
</form>

</body>
</html>