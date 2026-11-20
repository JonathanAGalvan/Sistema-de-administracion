<?php
require 'db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // En lugar de borrar, cambiamos el estado a 0
        $stmt = $pdo->prepare("UPDATE provedores SET estado = 0 WHERE id_provedores = ?");
        $stmt->execute([$id]);
        
        header("Location: ../proveedores.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        echo "Error al intentar desactivar el proveedor: " . $e->getMessage();
    }
}
