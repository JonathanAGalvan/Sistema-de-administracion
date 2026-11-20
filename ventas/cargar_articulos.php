<?php
    $server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';
    try {
        $con = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Consulta para llenar el select de artículos (incluyendo precio)
        $stmt = $con->query("SELECT id_productos, codigo_articulo, nombre, cantidad, precio_venta FROM productos ORDER BY nombre ASC");
        $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($articulos); // Envía los datos a JS

    } catch(Exception $e) { 
        echo json_encode([]); 
    }
?>