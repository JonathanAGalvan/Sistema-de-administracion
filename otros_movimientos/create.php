<?php
require 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha = $_POST['fecha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $producto_nombre = trim($_POST['producto_nombre'] ?? '');
    $producto_id = $_POST['producto_id'] ?? '';
    $cantidad = $_POST['cantidad'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');

    // VALIDACIONES
    if (!$fecha) $errors[] = "La fecha es obligatoria.";
    if (!$tipo) $errors[] = "El tipo de movimiento es obligatorio.";
    if ($producto_nombre === '') $errors[] = "El producto es obligatorio.";
    if (!$producto_id) $errors[] = "No se ha seleccionado un producto válido.";
    if ($cantidad === '' || !is_numeric($cantidad)) $errors[] = "La cantidad es obligatoria y numérica.";
    if ((int)$cantidad <= 0) $errors[] = "La cantidad debe ser mayor a 0.";
    if ($motivo === '') $errors[] = "El motivo es obligatorio.";
    if ($usuario === '') $errors[] = "El usuario es obligatorio.";

    // VERIFICAR QUE EL PRODUCTO EXISTA
    $check = $pdo->prepare("SELECT * FROM productos WHERE id_productos = ?");
    $check->execute([$producto_id]);

    if ($check->rowCount() === 0) {
        $errors[] = "El producto seleccionado no existe.";
    }

    // SI NO HAY ERRORES
    if (empty($errors)) {

        // OBTENER CANTIDAD ACTUAL
        $stockQuery = $pdo->prepare("SELECT cantidad FROM productos WHERE id_productos = ?");
        $stockQuery->execute([$producto_id]);

        $producto = $stockQuery->fetch(PDO::FETCH_ASSOC);

        $stock_actual = (int)$producto['cantidad'];
        $nuevo_stock = $stock_actual;

        // CALCULAR NUEVO STOCK
        if ($tipo === 'Ingreso') {

            $nuevo_stock = $stock_actual + (int)$cantidad;

        } elseif ($tipo === 'Devolución') {

            $nuevo_stock = $stock_actual + (int)$cantidad;

        } elseif ($tipo === 'Merma') {

            $nuevo_stock = $stock_actual - (int)$cantidad;

        } elseif ($tipo === 'Ajuste') {

            // Ajusta directamente al valor ingresado
            $nuevo_stock = (int)$cantidad;
        }

        // VALIDAR STOCK NEGATIVO
        if ($nuevo_stock < 0) {
            $errors[] = "No hay suficiente inventario para realizar la operación.";
        }

        // SI TODO ESTÁ BIEN
        if (empty($errors)) {

            // GUARDAR MOVIMIENTO
            $stmt = $pdo->prepare("
                INSERT INTO otrosmovimientos 
                (fecha, tipo_mov, nombre_prod, Productos_id_producto, cantidad, descripcion, usuario)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $fecha,
                $tipo,
                $producto_nombre,
                (int)$producto_id,
                (int)$cantidad,
                $motivo,
                $usuario
            ]);

            // ACTUALIZAR INVENTARIO
            $updateStock = $pdo->prepare("
                UPDATE productos 
                SET cantidad = ? 
                WHERE id_productos = ?
            ");

            $updateStock->execute([
                $nuevo_stock,
                $producto_id
            ]);

            // REDIRECCIÓN
            header("Location: ../om.php");
            exit;
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Crear Movimiento</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Nuevo Movimiento</h1>

    <?php if (!empty($errors)): ?>
        <div style="background:#ffe1e1;padding:15px;border-radius:10px;margin-bottom:15px;">
            <?php foreach ($errors as $e): ?>
                <div style="color:#bb1a1a;font-weight:600;margin-bottom:4px;">
                    <?= htmlspecialchars($e) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <input 
            type="hidden" 
            name="producto_id" 
            id="producto_id_create"
            value="<?= htmlspecialchars($_POST['producto_id'] ?? '') ?>"
        >

        <label>Fecha</label>
        <input 
            type="date" 
            name="fecha" 
            required
            value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>"
        >

        <label>Tipo de movimiento</label>

        <select name="tipo" required>
            <option value="">-- Selecciona --</option>

            <?php foreach (['Ingreso','Devolución','Ajuste','Merma'] as $t): ?>

                <option 
                    value="<?= $t ?>"
                    <?= (($_POST['tipo'] ?? '') === $t) ? 'selected' : '' ?>
                >
                    <?= $t ?>
                </option>

            <?php endforeach; ?>
        </select>

        <label>Producto</label>

        <input 
            type="text"
            name="producto_nombre"
            id="producto_nombre_create"
            autocomplete="off"
            placeholder="Escribe el nombre del producto"
            required
            value="<?= htmlspecialchars($_POST['producto_nombre'] ?? '') ?>"
        >

        <div 
            id="sugerencias_create"
            style="
                position:absolute;
                background:#fff;
                width:330px;
                max-height:180px;
                overflow-y:auto;
                border:1px solid #ccc;
                border-radius:10px;
                display:none;
                z-index:999;
            "
        ></div>

        <label>Cantidad</label>

        <input 
            type="number"
            name="cantidad"
            min="1"
            required
            value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>"
        >

        <label>Motivo</label>

        <input 
            type="text"
            name="motivo"
            required
            value="<?= htmlspecialchars($_POST['motivo'] ?? '') ?>"
        >

        <label>Usuario</label>

        <input 
            type="text"
            name="usuario"
            required
            value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
        >

        <div style="margin-top:12px;">

            <button class="btn-primary">
                Guardar
            </button>

            <a href="../om.php" class="btn-secondary">
                Cancelar
            </a>

        </div>

    </form>

    <script>

    // AUTOCOMPLETADO DE PRODUCTOS

    const inputCreate = document.getElementById("producto_nombre_create");
    const boxCreate = document.getElementById("sugerencias_create");
    const inputIdCreate = document.getElementById("producto_id_create");

    inputCreate.addEventListener("input", () => {

        const texto = inputCreate.value.trim();

        if (texto.length < 1) {

            boxCreate.style.display = "none";
            inputIdCreate.value = '';
            return;
        }

        fetch("buscar_producto.php?q=" + texto)

            .then(r => r.json())

            .then(lista => {

                if (lista.length === 0) {

                    boxCreate.style.display = "none";
                    inputIdCreate.value = '';
                    return;
                }

                boxCreate.innerHTML = "";

                lista.forEach(p => {

                    const div = document.createElement("div");

                    div.textContent = p.nombre;
                    div.style.padding = "10px";
                    div.style.cursor = "pointer";

                    div.addEventListener("click", () => {

                        inputCreate.value = p.nombre;
                        inputIdCreate.value = p.id;

                        boxCreate.style.display = "none";
                    });

                    boxCreate.appendChild(div);
                });

                boxCreate.style.display = "block";
            });
    });

    </script>

</div>

</body>
</html>