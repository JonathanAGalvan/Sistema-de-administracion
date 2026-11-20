<?php
require 'db.php';

$q = $_GET['q'] ?? '';

// Devolver el ID y el nombre para que el JS pueda guardar el ID del producto
$stmt = $pdo->prepare("SELECT id_productos AS id, nombre FROM productos WHERE nombre LIKE ? LIMIT 10");
$stmt->execute(["%$q%"]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));