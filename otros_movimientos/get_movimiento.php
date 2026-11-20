<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if(!$id) exit;
// Usar alias para que el JS sepa qué campos usar (id, tipo, producto, motivo)
$stmt = $pdo->prepare("SELECT id_otrosMovimientos AS id, fecha,
 tipo_mov AS tipo, nombre_prod AS producto, cantidad,
  descripcion AS motivo, usuario, Productos_id_producto FROM otrosmovimientos WHERE id_otrosMovimientos=?");
$stmt->execute([$id]);
$m = $stmt->fetch();
echo json_encode($m);