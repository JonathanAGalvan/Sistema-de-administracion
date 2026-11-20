<?php
    $server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';

    header('Content-Type: application/json');

    try {
        $con = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verifica si tu columna se llama 'precio_compra' o solo 'precio'
        $sql = "SELECT id_productos, codigo_articulo, nombre, cantidad, precio_compra FROM productos ORDER BY nombre ASC";
        
        $stmt = $con->query($sql);
        $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($articulos); 

    } catch(Exception $e) { 
        echo json_encode([]); 
    }
?>