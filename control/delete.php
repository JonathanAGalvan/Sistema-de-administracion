<?php
include("conexion.php");
$con = conectar();

$id_productos = $_GET['id'];

try {

    mysqli_query($con, "DELETE FROM productos_compras 
    WHERE productos_id_productos = $id_productos");

    mysqli_query($con, "DELETE FROM ventas_productos 
    WHERE productos_id_productos = $id_productos");

    $sql = "DELETE FROM productos 
    WHERE id_productos = '$id_productos'";

    $query = mysqli_query($con, $sql);

    if ($query) {
        header("Location: ../alumno.php?msg=delete_ok");
    } else {
        header("Location: ../alumno.php?msg=delete_error");
    }

} catch (mysqli_sql_exception $e) {

    header("Location: ../alumno.php?msg=delete_error_fk");

}

exit();
?>