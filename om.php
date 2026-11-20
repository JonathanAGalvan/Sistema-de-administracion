<?php
session_start();
if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header("location: index.php");
    exit();
}

// RUTA VERIFICADA: db.php está en la subcarpeta
require 'otros_movimientos/db.php';

$filter = $_GET['tipo'] ?? 'Todos';
$edit_id = $_GET['edit'] ?? null;

// --- LÓGICA DE PAGINACIÓN ---
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros según el filtro para saber cuántas páginas hay
$sql_count = "SELECT COUNT(*) FROM otrosmovimientos";
if ($filter !== 'Todos') {
    $sql_count .= " WHERE tipo_mov = :filter";
}
$stmt_count = $pdo->prepare($sql_count);
if ($filter !== 'Todos') {
    $stmt_count->bindValue(':filter', $filter);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Traer movimientos con LIMIT y OFFSET
$query = "SELECT id_otrosMovimientos AS id, fecha, tipo_mov AS tipo, nombre_prod AS producto, cantidad, descripcion AS motivo, usuario, Productos_id_producto FROM otrosmovimientos";
$params = [];
if ($filter !== 'Todos') { 
    $query .= " WHERE tipo_mov=?"; 
    $params[] = $filter; 
}
$query .= " ORDER BY id DESC LIMIT $registros_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

// Totales por tipo (esto se mantiene igual para las cards)
$count = ["Ingreso"=>0,"Devolución"=>0,"Ajuste"=>0,"Merma"=>0];
$totales = $pdo->query("SELECT tipo_mov AS tipo, COUNT(*) AS total FROM otrosmovimientos GROUP BY tipo_mov")->fetchAll();
foreach($totales as $t) {
    if(isset($count[$t['tipo']])) $count[$t['tipo']] = $t['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Otros Movimientos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="otros_movimientos/style.css">
    <style>
        /* Estilos de botones de acción */
        .btn-accion {
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
            color: white;
            font-size: 14px;
        }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-edit:hover { background-color: #e0a800; }
        .btn-delete { background-color: #dc3545; }
        .btn-delete:hover { background-color: #c82333; }
        .d-flex-center { display: flex; gap: 8px; justify-content: center; align-items: center; }
        .table-container td { vertical-align: middle; }

        /* --- ESTILOS DEL PAGINADO --- */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .pagination {
            display: flex;
            gap: 5px;
        }
        .page-link {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #3f445e; /* Color oscuro como tu imagen */
            color: white;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
            font-size: 14px;
        }
        .page-link:hover {
            background-color: #2c3144;
        }
        .page-link.active {
            background-color: #2188ca; /* Azul brillante como tu imagen */
            box-shadow: 0 4px 10px rgba(33, 136, 202, 0.3);
        }
        .page-link.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include ("menu.php"); ?>

    <div class="scroll"></div>

    <div class="container">
        <h1>Otros Movimientos</h1>

        <div class="cards">
            <div class="card"><div class="card-title">Ingresos</div><div class="card-value"><?= $count['Ingreso'] ?></div></div>
            <div class="card"><div class="card-title">Devoluciones</div><div class="card-value"><?= $count['Devolución'] ?></div></div>
            <div class="card"><div class="card-title">Ajustes</div><div class="card-value"><?= $count['Ajuste'] ?></div></div>
            <div class="card"><div class="card-title">Mermas</div><div class="card-value"><?= $count['Merma'] ?></div></div>
        </div>

        <button class="btn-primary" onclick="openModal('nuevo')">+ Nuevo Movimiento</button>

        <div class="tabs">
            <?php foreach(["Todos","Ingreso","Devolución","Ajuste","Merma"] as $t): ?>
                <a href="?tipo=<?= $t ?>" class="tab <?= $filter === $t?'active':'' ?>"><?= $t ?></a>
            <?php endforeach; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($movimientos as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= $m['fecha'] ?></td>
                        <td><span class="badge <?= strtolower(str_replace(' ', '-', $m['tipo'])) ?>"><?= $m['tipo'] ?></span></td>
                        <td><?= htmlspecialchars($m['producto']) ?></td>
                        <td class="<?= $m['cantidad']>0?'cantidad-pos':'cantidad-neg' ?>"><?= $m['cantidad'] ?></td>
                        <td><?= htmlspecialchars($m['motivo']) ?></td>
                        <td><?= htmlspecialchars($m['usuario']) ?></td>
                        <td>
                            <div class="d-flex-center">
                                <button class="btn-accion btn-edit" onclick="openModal('editar', <?= $m['id'] ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-accion btn-delete" onclick="eliminarMovimiento(<?= $m['id'] ?>)" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if($pagina_actual > 1): ?>
                    <a href="?tipo=<?= $filter ?>&p=<?= $pagina_actual - 1 ?>" class="page-link">« Anterior</a>
                <?php endif; ?>

                <?php
                // Lógica para mostrar solo algunas páginas (rango)
                $rango = 2;
                $desde = max(1, $pagina_actual - $rango);
                $hasta = min($total_paginas, $pagina_actual + $rango);

                for ($i = $desde; $i <= $hasta; $i++): ?>
                    <a href="?tipo=<?= $filter ?>&p=<?= $i ?>" 
                       class="page-link <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                       <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($pagina_actual < $total_paginas): ?>
                    <a href="?tipo=<?= $filter ?>&p=<?= $pagina_actual + 1 ?>" class="page-link">Siguiente »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="modal-backdrop" id="modal">
            <div class="modal">
                <div class="modal-header">
                    <h2 id="modal-title">Nuevo Movimiento</h2>
                    <button class="close" onclick="closeModal()">×</button>
                </div>
                <div id="modal-content">
                    <form id="form-movimiento" method="post">
                        <input type="hidden" name="id" id="mov-id">
                        <input type="hidden" name="producto_id" id="mov-producto-id">
                        
                        <label>Fecha</label>
                        <input type="date" name="fecha" id="mov-fecha" required>
                        
                        <label>Tipo</label>
                        <select name="tipo" id="mov-tipo" required>
                            <?php foreach(['Ingreso','Devolución','Ajuste Entrada','Ajuste Salida','Merma'] as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Producto (Nombre)</label>
                        <input type="text" name="producto_nombre" id="mov-producto-nombre" autocomplete="off" required>
                        <div id="sugerencias" style="position:absolute; background:#fff; width:330px; max-height:180px; overflow-y:auto; border:1px solid #ccc; border-radius:10px; display:none; z-index:999;"></div>
                        
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" id="mov-cantidad" required>
                        
                        <label>Motivo</label>
                        <input type="text" name="motivo" id="mov-motivo" required>
                        
                        <input type="hidden" name="usuario" id="mov-usuario" value="<?= $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] ?>">

                        <div style="margin-top:20px;">
                            <button type="submit" class="btn-primary" id="btn-submit">Guardar</button>
                            <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openModal(tipo, id=null){
            const modal = document.getElementById("modal");
            const title = document.getElementById("modal-title");
            const form = document.getElementById("form-movimiento");
            modal.classList.add("show");
            if(tipo === 'nuevo'){
                title.innerText = "Nuevo Movimiento";
                form.action = "otros_movimientos/create_modal.php";
                form.reset();
                document.getElementById("mov-id").value='';
                document.getElementById("mov-producto-id").value='';
            } else if(tipo === 'editar' && id){
                title.innerText = "Editar Movimiento";
                form.action = "otros_movimientos/update_modal.php?id="+id;
                fetch('otros_movimientos/get_movimiento.php?id='+id)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById("mov-id").value = data.id;
                        document.getElementById("mov-fecha").value = data.fecha;
                        document.getElementById("mov-tipo").value = data.tipo;
                        document.getElementById("mov-producto-nombre").value = data.producto;
                        document.getElementById("mov-producto-id").value = data.Productos_id_producto;
                        document.getElementById("mov-cantidad").value = data.cantidad;
                        document.getElementById("mov-motivo").value = data.motivo;
                        document.getElementById("mov-usuario").value = data.usuario;
                    });
            }
        }
        function closeModal(){ document.getElementById("modal").classList.remove("show"); }
        function eliminarMovimiento(id) {
            if (confirm("¿Estás seguro de que deseas eliminar este movimiento?")) {
                window.location.href = "otros_movimientos/delate.php?id=" + id;
            }
        }

        // Autocompletado
        const input = document.getElementById("mov-producto-nombre");
        const box = document.getElementById("sugerencias");
        const inputId = document.getElementById("mov-producto-id");
        input.addEventListener("input", ()=>{
            const texto = input.value.trim();
            if(texto.length < 1){ box.style.display="none"; inputId.value=''; return; }
            fetch("otros_movimientos/buscar_producto.php?q="+texto)
            .then(r => r.json())
            .then(lista => {
                if(lista.length === 0){ box.style.display="none"; inputId.value=''; return; }
                box.innerHTML = "";
                lista.forEach(p => {
                    const div = document.createElement("div");
                    div.textContent = p.nombre;
                    div.style.padding = "10px";
                    div.style.cursor = "pointer";
                    div.addEventListener("click", ()=>{ 
                        input.value = p.nombre; 
                        inputId.value = p.id; 
                        box.style.display = "none"; 
                    });
                    box.appendChild(div);
                });
                box.style.top = (input.offsetTop + input.offsetHeight) + "px";
                box.style.left = input.offsetLeft + "px";
                box.style.display = "block";
            });
        });
        </script>
    </div>
</body>
</html>