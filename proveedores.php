<?php
session_start();
if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header("location: index.php");
    exit();
}

require 'proveedores/db.php';

// --- LÓGICA DE PAGINACIÓN ---
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$search = $_GET['search'] ?? '';

// 1. Contar total de registros ACTIVOS
if ($search !== '') {
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM provedores WHERE estado = 1 AND (id_provedores = :id OR nombre LIKE :nombre)");
    $stmt_count->execute([':id' => $search, ':nombre' => "%$search%"]);
} else {
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM provedores WHERE estado = 1");
}
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 2. Obtener registros ACTIVOS
if ($search !== '') {
    $sql = "SELECT * FROM provedores WHERE estado = 1 AND (id_provedores = :id OR nombre LIKE :nombre) ORDER BY id_provedores DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $search, PDO::PARAM_INT);
    $stmt->bindValue(':nombre', "%$search%", PDO::PARAM_STR);
} else {
    $sql = "SELECT * FROM provedores WHERE estado = 1 ORDER BY id_provedores DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
}
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$proveedores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores - Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="proveedores/style.css?v=1.5">
</head>
<body>
    <?php include ("menu.php"); ?>

    <div class="scroll"></div>

    <div class="container">
        <h1>Proveedores</h1>

        <div class="top-bar">
            <a class="btn btn-primary" href="proveedores/create.php">+ NUEVO PROVEEDOR</a>
            
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Buscar por ID o nombre..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-search" type="submit">BUSCAR</button>
                <a class="btn btn-clear" href="proveedores.php">LIMPIAR</a>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOMBRE</th>
                        <th>RFC</th>
                        <th>EMAIL</th>
                        <th>TELÉFONO</th>
                        <th>DIRECCIÓN</th>
                        <th>PROMOTOR</th>
                        <th style="text-align: center;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr><td colspan="8" style="text-align: center;">No hay proveedores activos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><strong><?= $p['id_provedores'] ?></strong></td>
                            <td title="<?= htmlspecialchars($p['nombre']) ?>"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['rfc']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= htmlspecialchars($p['telefono']) ?></td>
                            <td title="<?= htmlspecialchars($p['direccion']) ?>"><?= htmlspecialchars($p['direccion']) ?></td>
                            <td><?= htmlspecialchars($p['promotor']) ?></td>
                            <td>
                                <div class="d-flex-center">
                                    <a class="btn-accion btn-edit" href="proveedores/edit.php?id=<?= $p['id_provedores'] ?>"><i class="fas fa-edit"></i></a>
                                    <a class="btn-accion btn-delete" href="proveedores/delete.php?id=<?= $p['id_provedores'] ?>" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if($pagina_actual > 1): ?>
                    <a href="?search=<?= urlencode($search) ?>&p=<?= $pagina_actual - 1 ?>" class="page-link">Anterior</a>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&p=<?= $i ?>" class="page-link <?= ($i == $pagina_actual) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if($pagina_actual < $total_paginas): ?>
                    <a href="?search=<?= urlencode($search) ?>&p=<?= $pagina_actual + 1 ?>" class="page-link">Siguiente</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>