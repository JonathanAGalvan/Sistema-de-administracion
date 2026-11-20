<?php
session_start();
include("db.php"); // $conexion (mysqli)

if (!empty($_POST["btningresar"])) {

    if (!empty($_POST["usuario"]) && !empty($_POST["password"])) {

        $usuario = trim($_POST["usuario"]);
        $password = $_POST["password"];

        // CONSULTA SEGURA (solo por usuario)
        $stmt = $conexion->prepare("SELECT * FROM personal WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($datos = $resultado->fetch_object()) {

            // VERIFICAR CONTRASEÑA ENCRIPTADA
            if (password_verify($password, $datos->password)) {

                $_SESSION["id"] = $datos->id;
                $_SESSION["nombre"] = $datos->nombre;
                $_SESSION["apellido"] = $datos->apellido;
                $_SESSION["id_cargo"] = $datos->id_cargo;

                if ($datos->id_cargo == 1) {
                    header("Location: ./inicio.php");
                    exit();
                } elseif ($datos->id_cargo == 2) {
                    header("Location: ./inicio.php");
                    exit();
                } else {
                    echo "<div class='alert alert-warning'>Rol no asignado correctamente.</div>";
                }

            } else {
                echo "<div class='alert alert-danger'>Contraseña incorrecta</div>";
            }

        } else {
            echo "<div class='alert alert-danger'>Usuario no encontrado</div>";
        }

        $stmt->close();

    } else {
        echo "<div class='alert alert-warning'>Campos vacíos</div>";
    }
}
?>