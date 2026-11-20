<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Usar la clave primaria correcta de la BD
    $stmt = $pdo->prepare("DELETE FROM otrosmovimientos WHERE id_otrosMovimientos=?");
    $stmt->execute([$id]);
}
header("Location: ../om.php");
exit;