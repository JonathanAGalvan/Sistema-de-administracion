<?php
session_start();
if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header("location: ../index.php");
}

    include ("menu.php");
    $server = 'srv737.hstgr.io'; 
    $username = 'u102381755_sistema'; 
    $password = 'Os5aVGSRL+'; 
    $database ='u102381755_sistemaInve';
    try {
        $con = new PDO("mysql:host=$server;dbname=$database;", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) { die("Error: " . $e->getMessage()); }

    $busqueda = $_GET['buscar'] ?? ''; 
    $por_pagina = 10; 
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    $inicio = ($pagina - 1) * $por_pagina;

    // Filtro SQL (Solo se usará para la Tabla y la Paginación)
    $filtro = "";
    if($busqueda != ''){
        $filtro = " WHERE v.id_ventas LIKE :busqueda OR c.nombre LIKE :busqueda ";
    }

    //INFORMACION DE CUADROS
    $sql_global_count = "SELECT COUNT(*) FROM ventas";
    $stmt_gc = $con->query($sql_global_count);
    $total_global_registros = $stmt_gc->fetchColumn();

    // UNIDADES
    $sql_global_units = "SELECT SUM(cantidad) FROM ventas_productos";
    $stmt_gu = $con->query($sql_global_units);
    $total_global_unidades = $stmt_gu->fetchColumn() ?: 0;

    // TOTAL MONTO
    $sql_global_money = "SELECT SUM(total) FROM ventas";
    $stmt_gm = $con->query($sql_global_money);
    $total_global_ingresos = $stmt_gm->fetchColumn() ?: 0;

    // PROMEDIO
    $promedio_global = ($total_global_registros > 0) ? ($total_global_ingresos / $total_global_registros) : 0;


    // PAGINACIÓN
    // CONTAR TODOS LOS REGISTRADO FILTRADOS
    $sql_count_filter = "SELECT COUNT(*) FROM ventas v JOIN clientes c ON v.Clientes_id_clientes = c.id_clientes $filtro";
    $stmt_cf = $con->prepare($sql_count_filter);
    if($busqueda != '') $stmt_cf->bindValue(':busqueda', "%$busqueda%");
    $stmt_cf->execute();
    $total_registros_filtrados = $stmt_cf->fetchColumn();
    
    $total_paginas = ceil($total_registros_filtrados / $por_pagina) ?: 1;

    // OBTENER LOS DATOS Y MOSTRARLOS EN LA TABLA
    $sql_data = "SELECT v.id_ventas, v.Clientes_id_clientes as id_cliente, c.nombre as nombre_cliente, 
                        v.iva, v.total, v.fecha_venta, (v.total - v.iva) as subtotal
                 FROM ventas v 
                 JOIN clientes c ON v.Clientes_id_clientes = c.id_clientes 
                 $filtro
                 ORDER BY v.id_ventas DESC 
                 LIMIT :inicio, :por_pagina";
    
    $stmt = $con->prepare($sql_data);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':por_pagina', $por_pagina, PDO::PARAM_INT);
    if($busqueda != '') $stmt->bindValue(':busqueda', "%$busqueda%");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="img/venta.png">
    <title>Ventas</title>
    <link rel="icon" href="img/venta.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="ventas/venta.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script>
        const PAGINA_ACTUAL = <?php echo $pagina; ?>;
        const BUSQUEDA_ACTUAL = "<?php echo $busqueda; ?>";
    </script>
</head>
<body>
    <div class="scroll"></div>

    <div class="informacion" id="informacion">
        <div class="cuadro1">
            <span class="icono-producto"><i class="fas fa-box"></i></span>
            <h2>Total de Ventas</h2>
            <p id="kpi_total_ventas"><?php echo $total_global_registros; ?></p> 
            <p><?php echo $total_global_unidades; ?> unidades vendidas</p>
        </div>
        <div class="cuadro2">
            <span class="icono-dolar">$</span>
            <h2>Monto total</h2>
            <p id="kpi_monto_total">$<?php echo number_format($total_global_ingresos, 2); ?></p> 
            <p>Valor total estimado</p>
        </div>
        <div class="cuadro3">
            <span class="icono-dolar"><i class="fas fa-chart-line"></i></span>
            <h2>Promedio</h2>
            <p id="kpi_promedio">$<?php echo number_format($promedio_global, 2); ?></p>
            <p>Promedio por venta</p>
        </div>
    </div>

        <div><button id="btnAbrirFormulario">+ NUEVA VENTA</button></div>

        <form action="ventas.php" method="GET" class="buscar">
            <div class="input-box" >
                <i class="fa-solid fa-magnifying-glass" style="padding:0 10px; color:#555;"></i>
                <input type="text" name="buscar" placeholder="Buscar cliente o ID de venta..." value="<?php echo $busqueda; ?>">
                <button type="submit" class="btnBuscar" >Buscar</button>
                <?php if($busqueda != ''): ?>
                    <a href="ventas.php" class="eliminar">X</a>
                <?php endif; ?>
            </div>
        </form>

    
    <div id="modalOverlay" class="modal-overlay">
        <form id="formularioVenta">
            <span id="btnCerrarModal" class="cerrar-modal">&times;</span>
            <div class="campo"><label>CLIENTE:</label> 
            <select id="nombre_cliente" required></select></div>
            <div class="campo"><label>FECHA:</label> 
            <input type="date" id="fecha_venta" name="fecha_venta" required></div>
            <div class="campo"><label>IVA:</label> 
            <input type="number" id="iva" name="iva" step="0.01" value="0.16" readonly></div>
            <div class="campo"><label>ARTÍCULO:</label> 
            <select id="nom_articulo"></select></div>
            <div class="campo"><label>CANTIDAD:</label> 
            <input type="number" id="cantidad" name="cantidad" value="1"></div>
            <input type="hidden" id="pre_unitario">

            <button type="button" id="btnAnadirProducto" class="btn-anadir">Añadir Producto</button>
            <div class="table-container-modal">
                <table>
                    <thead>
                        <tr>
                            <th>ART</th>
                            <th>CANT</th>
                            <th>PRECIO</th>
                            <th>SUB</th>
                            <th>ELIMINAR</th>
                        </tr>
                    </thead>
                    <tbody id="carritoBody"></tbody>
                </table>
            </div>
            <input type="submit" value="REGISTRAR VENTA">
        </form>
    </div>

    <div class="table-container" id="tabla1">
        <table>
            <thead>
                <tr>
                    <th>ID VENTA</th>
                    <th>ID CLIENTE</th>
                    <th>NOMBRE CLIENTE</th>
                    <th>SUBTOTAL</th>
                    <th>IVA</th>
                    <th>TOTAL</th>
                    <th>FECHA VENTA</th>
                    <th class="acciones">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="tablaVentasBody">
                <?php if(count($ventas) > 0): ?>
                    <?php foreach($ventas as $v): ?>
                        <tr>
                            <td><?php echo $v['id_ventas']; ?></td>
                            <td><?php echo $v['id_cliente']; ?></td>
                            <td><?php echo $v['nombre_cliente']; ?></td>
                            <td>$<?php echo number_format($v['subtotal'], 2); ?></td>
                            <td>$<?php echo number_format($v['iva'], 2); ?></td>
                            <td>$<?php echo number_format($v['total'], 2); ?></td>
                            <td><?php echo $v['fecha_venta']; ?></td>
                            <td class="celda-acciones">
                                <?php if ($rol == 1): ?>
                                <button class="btn-modificar" data-id="<?php echo $v['id_ventas']; ?>"><i class="fas fa-pen"></i></button>
                                <button class="btn-eliminar" data-id="<?php echo $v['id_ventas']; ?>"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                                <a href="ventas/ticket_ventas.php?id=<?php echo $v['id_ventas']; ?>" target="_blank" class="btn-ticket">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:20px;">No se encontraron resultados para "<?php echo $busqueda; ?>"</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="paginacion">

    <?php $link = "?buscar=$busqueda&pagina="; ?>

    <!-- BOTÓN ANTERIOR -->
    <?php if ($pagina > 1): ?>
        <a href="<?php echo $link . ($pagina - 1); ?>">
            &laquo; Anterior
        </a>
    <?php endif; ?>

    <?php
        // Mostrar solo 10 botones por bloque
        $inicio_bloque = floor(($pagina - 1) / 10) * 10 + 1;
        $fin_bloque = min($inicio_bloque + 9, $total_paginas);
    ?>

    <!-- BOTÓN PARA BLOQUE ANTERIOR -->
    <?php if ($inicio_bloque > 1): ?>
        <a href="<?php echo $link . ($inicio_bloque - 1); ?>">
            ...
        </a>
    <?php endif; ?>

    <!-- NÚMEROS -->
    <?php for ($i = $inicio_bloque; $i <= $fin_bloque; $i++): ?>

        <a
            href="<?php echo $link . $i; ?>"
            class="<?php echo $i == $pagina ? 'activo' : ''; ?>"
        >
            <?php echo $i; ?>
        </a>

    <?php endfor; ?>

    <!-- BOTÓN PARA SIGUIENTE BLOQUE -->
    <?php if ($fin_bloque < $total_paginas): ?>
        <a href="<?php echo $link . ($fin_bloque + 1); ?>">
            ...
        </a>
    <?php endif; ?>

    <!-- BOTÓN SIGUIENTE -->
    <?php if ($pagina < $total_paginas): ?>
        <a href="<?php echo $link . ($pagina + 1); ?>">
            Siguiente &raquo;
        </a>
    <?php endif; ?>

</div>
    </div>

            <!--Tabla oculta para el PDF general-->
            <div id="contenedorPDF" style="display:none;">

    <table id="tablaPDF">

        <thead>
            <tr>
                <th>ID VENTA</th>
                <th>ID CLIENTE</th>
                <th>NOMBRE CLIENTE</th>
                <th>SUBTOTAL</th>
                <th>IVA</th>
                <th>TOTAL</th>
                <th>FECHA</th>
            </tr>
        </thead>

        <tbody>

            <?php

            $sqlPDF = "SELECT 
                        v.id_ventas,
                        v.Clientes_id_clientes as id_cliente,
                        c.nombre as nombre_cliente,
                        v.iva,
                        v.total,
                        v.fecha_venta,
                        (v.total - v.iva) as subtotal
                    FROM ventas v
                    JOIN clientes c
                    ON v.Clientes_id_clientes = c.id_clientes
                    $filtro
                    ORDER BY v.id_ventas DESC";

            $stmtPDF = $con->prepare($sqlPDF);

            if($busqueda != ''){
                $stmtPDF->bindValue(
                    ':busqueda',
                    "%$busqueda%"
                );
            }

            $stmtPDF->execute();

            $ventasPDF = $stmtPDF->fetchAll(PDO::FETCH_ASSOC);

            foreach($ventasPDF as $v):
            ?>

            <tr>

                <td><?= $v['id_ventas']; ?></td>

                <td><?= $v['id_cliente']; ?></td>

                <td><?= $v['nombre_cliente']; ?></td>

                <td>
                    $<?= number_format($v['subtotal'], 2); ?>
                </td>

                <td>
                    $<?= number_format($v['iva'], 2); ?>
                </td>

                <td>
                    $<?= number_format($v['total'], 2); ?>
                </td>

                <td><?= $v['fecha_venta']; ?></td>

            </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

    <?php if (isset($_GET['msg'])): ?>
    <?php
        $msg = $_GET['msg'];
        $texto = "";
        $clase = "";

        switch ($msg) {
            case "venta_ok":
                $texto = "¡Venta registrada con éxito!";
                $clase = "insertado"; 
                break;
            case "venta_error":
                $texto = "Hubo un error al registrar la venta.";
                $clase = "eliminado"; 
                break;
            case "modificar_ok":
                $texto = "Venta modificada correctamente.";
                $clase = "insertado";
                break;
            case "eliminar_ok":
                $texto = "Venta eliminada correctamente.";
                $clase = "eliminado"; 
                break;
            default:
                $texto = "Acción realizada.";
                $clase = "insertado";
                break;
        }
    ?>

    <div id="toast" class="toast <?= $clase ?>">
        <?= $texto ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toast = document.getElementById("toast");
            if (toast) {
                toast.classList.add("show");
                setTimeout(() => { toast.classList.remove("show"); }, 3000);
            }
        });
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url);
        }
    </script>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/toast.js"></script>

    <button id="descargarPDF">DESCARGAR PDF</button> 
    
    <div class="scroll"></div> 
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="ventas/ventaPDF.js"></script>
    <script src="ventas/vv.js"></script>
    
    <script>
        if(document.getElementById('descargarPDF')){
            document.getElementById('descargarPDF').addEventListener('click', generarPDF);
        }
    </script>
</body>
</html>