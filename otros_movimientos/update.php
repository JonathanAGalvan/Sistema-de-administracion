<?php
require 'db.php';

$id = $_GET['id'] ?? ($_POST['id'] ?? null);
if (!$id) { header("Location: om.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? '';
    $tipo = $_POST['tipo'] ?? ''; // tipo_mov
    $producto_nombre = trim($_POST['producto_nombre'] ?? ''); // nombre_prod
    $producto_id = $_POST['producto_id'] ?? ''; // Productos_id_producto (Nuevo)
    $cantidad = $_POST['cantidad'] ?? '';
    $motivo = trim($_POST['motivo'] ?? ''); // descripcion
    $usuario = trim($_POST['usuario'] ?? '');

    $errors = [];
    if (!$fecha) $errors[] = "La fecha es obligatoria.";
    if (!$tipo) $errors[] = "El tipo es obligatorio.";
    if ($producto_nombre === '') $errors[] = "El producto es obligatorio.";
    if (!$producto_id) $errors[] = "No se ha seleccionado un ID de producto válido."; // Validación de ID
    if ($cantidad === '' || !is_numeric($cantidad)) $errors[] = "La cantidad es obligatoria y numérica.";
    if ($motivo === '') $errors[] = "El motivo es obligatorio.";
    if ($usuario === '') $errors[] = "El usuario es obligatorio.";

    if (empty($errors)) {
        // Usar los nombres de columna y el ID del producto
        $update = $pdo->prepare("UPDATE otrosmovimientos SET fecha=?, tipo_mov=?, nombre_prod=?, Productos_id_producto=?, cantidad=?, descripcion=?, usuario=? WHERE id_otrosMovimientos=?");
        $update->execute([$fecha, $tipo, $producto_nombre, (int)$producto_id, (int)$cantidad, $motivo, $usuario, $id]);
        header("Location: ../om.php");
        exit;
    }
}

// Cargar datos actuales para el formulario (usando alias para simplificar)
$stmt = $pdo->prepare("SELECT id_otrosMovimientos AS id, fecha, tipo_mov AS tipo, nombre_prod AS producto_nombre, Productos_id_producto AS producto_id, cantidad, descripcion AS motivo, usuario FROM otrosmovimientos WHERE id_otrosMovimientos = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { header("Location: ../om.php"); exit; }
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Editar Movimiento</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <h1>Editar Movimiento #<?= htmlspecialchars($m['id']) ?></h1>
    <?php if (!empty($errors)): ?>
        <div style="background:#ffe1e1;padding:15px;border-radius:10px;margin-bottom:15px;">
            <?php foreach ($errors as $e): ?>
                <div style="color:#bb1a1a;font-weight:600;margin-bottom:4px;"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="update.php?id=<?= htmlspecialchars($m['id']) ?>">
        <input type="hidden" name="producto_id" id="producto_id_update" value="<?= htmlspecialchars($m['producto_id']) ?>">
        <label>Fecha</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($m['fecha']) ?>" required>
        <label>Tipo</label>
        <select name="tipo" required>
            <?php foreach (['Ingreso','Devolución','Ajuste','Merma'] as $t): ?>
                <option value="<?= $t ?>" <?= ($m['tipo'] === $t) ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <label>Producto</label>
        <input type="text" name="producto_nombre" id="producto_nombre_update" value="<?= htmlspecialchars($m['producto_nombre']) ?>" required autocomplete="off">
        <div id="sugerencias_update" style="position:absolute;background:#fff;width:330px;max-height:180px;overflow-y:auto;border:1px solid #ccc;border-radius:10px;display:none;z-index:999;"></div>

        <label>Cantidad</label>
        <input type="number" name="cantidad" value="<?= htmlspecialchars($m['cantidad']) ?>" required>
        <label>Motivo</label>
        <input type="text" name="motivo" value="<?= htmlspecialchars($m['motivo']) ?>" required>
        <label>Usuario</label>
        <input type="text" name="usuario" value="<?= htmlspecialchars($m['usuario']) ?>" required>
        <div style="margin-top:12px;">
            <button class="btn-primary">Guardar cambios</button>
            <a href="om.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
    <script>
    // JS para autocompletado en update.php (similar a om.php)
    const inputUpdate = document.getElementById("producto_nombre_update");
    const boxUpdate = document.getElementById("sugerencias_update");
    const inputIdUpdate = document.getElementById("producto_id_update");
    inputUpdate.addEventListener("input", ()=>{
        const texto = inputUpdate.value.trim();
        if(texto.length<1){boxUpdate.style.display="none"; inputIdUpdate.value=''; return;}
        fetch("buscar_producto.php?q="+texto)
        .then(r=>r.json())
        .then(lista=>{
            if(lista.length===0){boxUpdate.style.display="none"; inputIdUpdate.value=''; return;}
            boxUpdate.innerHTML="";
            lista.forEach(p=>{
                const div = document.createElement("div");
                div.textContent = p.nombre;
                div.style.padding="10px";
                div.style.cursor="pointer";
                div.addEventListener("click", ()=>{ 
                    inputUpdate.value=p.nombre; 
                    inputIdUpdate.value=p.id; 
                    boxUpdate.style.display="none"; 
                });
                boxUpdate.appendChild(div);
            });
            const rect = inputUpdate.getBoundingClientRect();
            boxUpdate.style.position="absolute";
            boxUpdate.style.top=(inputUpdate.offsetTop+inputUpdate.offsetHeight)+"px";
            boxUpdate.style.left=inputUpdate.offsetLeft+"px";
            boxUpdate.style.display="block";
        });
    });
    </script>
</div>
</body>
</html>