<?php
    $server = 'srv737.hstgr.io'; 
$username = 'u102381755_sistema'; 
$password = 'Os5aVGSRL+'; 
$database = 'u102381755_sistemaInve';

    try {
        $con = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Consulta para llenar el select de clientes
        $stmt = $con->query("SELECT nombre FROM clientes ORDER BY nombre ASC");
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($clientes);

    } catch(Exception $e) { 
        echo json_encode([]); 
    }
?>