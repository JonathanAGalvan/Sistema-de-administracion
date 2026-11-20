<?php
// conexion.php
    $server = 'srv737.hstgr.io'; 

    $username = 'u102381755_sistema'; 
    $password = 'Os5aVGSRL+'; 
    $database ='u102381755_sistemaInve';

try {
    $con = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>