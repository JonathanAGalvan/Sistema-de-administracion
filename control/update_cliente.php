<?php
include("conexion.php");
$conexion = conectar();

$id = $_POST['id_clientes'];
$nombre = $_POST['nombre'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$ciudad = $_POST['ciudad'];
$estado = $_POST['estado'];
$cp = $_POST['codigo_postal'];
$fecha = $_POST['fecha_registro'];
$estatus = $_POST['estatus'];

$sql = "UPDATE clientes SET 
        nombre='$nombre',
        email='$email',
        telefono='$telefono',
        direccion='$direccion',
        ciudad='$ciudad',
        estado='$estado',
        codigo_postal='$cp',
        fecha_registro='$fecha',
        estatus='$estatus'
        WHERE id_clientes=$id";

if (mysqli_query($conexion, $sql)) {
    header("Location: ../clientes.php?msg=update_ok");
} else {
    echo "Error: " . mysqli_error($conexion);
}
