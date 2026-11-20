<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('db.php');

// Datos
$nombre   = $_POST['nombre'];
$apellido = $_POST['apellido'];
$usuario  = $_POST['usuario'];
$correo   = $_POST['correo'];
$password = $_POST['password'];
$id_cargo = $_POST['id_cargo'];

// Validar duplicados
$check = $conexion->prepare("SELECT id FROM personal WHERE correo = ? OR usuario = ?");
$check->bind_param("ss", $correo, $usuario);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    die("El usuario o correo ya existe");
}

// Encriptar contraseña
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insertar
$sql = "INSERT INTO personal (nombre, apellido, usuario, correo, password, id_cargo) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssssi", $nombre, $apellido, $usuario, $correo, $passwordHash, $id_cargo);

if ($stmt->execute()) {
    echo "Registro exitoso 🔥";
} else {
    echo "Error: " . $stmt->error;
}

// Cerrar correctamente
$stmt->close();
$conexion->close();
?>