<?php
session_start();

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header("location: ../index.php");
}

$server = 'srv737.hstgr.io';
$username = 'u102381755_sistema';
$password = 'Os5aVGSRL+';
$database ='u102381755_sistemaInve';

try {

    $con = new PDO(
        "mysql:host=$server;dbname=$database;",
        $username,
        $password
    );

    $con->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch(PDOException $e) {

    die("Error: " . $e->getMessage());

}

$busqueda = $_GET['buscar'] ?? '';

$por_pagina = 10;

$pagina = isset($_GET['pagina'])
    ? (int)$_GET['pagina']
    : 1;

if ($pagina < 1) $pagina = 1;

$inicio = ($pagina - 1) * $por_pagina;

// ===== FILTRO =====

$filtro = "";

if($busqueda != ''){

    $filtro =
    " WHERE cp.id_compras LIKE :busqueda
      OR p.nombre LIKE :busqueda ";

}

// ===== INFORMACIÓN GENERAL =====

// TOTAL COMPRAS
$sql_global_count =
"SELECT COUNT(*) FROM compras";

$stmt_gc = $con->query($sql_global_count);

$total_global_registros =
$stmt_gc->fetchColumn();

// TOTAL UNIDADES
$sql_global_units =
"SELECT SUM(cantidad_pd_cp)
 FROM productos_compras";

$stmt_gu = $con->query($sql_global_units);

$total_global_unidades =
$stmt_gu->fetchColumn() ?: 0;

// TOTAL MONTO
$sql_global_money =
"SELECT SUM(total)
 FROM compras";

$stmt_gm = $con->query($sql_global_money);

$total_global_ingresos =
$stmt_gm->fetchColumn() ?: 0;

// PROMEDIO
$promedio_global =
($total_global_registros > 0)
? ($total_global_ingresos / $total_global_registros)
: 0;

// ===== PAGINACIÓN =====

// CONTAR FILTRADOS
$sql_count =
"SELECT COUNT(*)

 FROM compras cp

 JOIN provedores p
 ON cp.Provedores_id_provedores =
    p.id_provedores

 $filtro";

$stmt_cf = $con->prepare($sql_count);

if($busqueda != ''){

    $stmt_cf->bindValue(
        ':busqueda',
        "%$busqueda%"
    );

}

$stmt_cf->execute();

$total_registros_filtrados =
$stmt_cf->fetchColumn();

$total_paginas =
ceil($total_registros_filtrados / $por_pagina);

if($total_paginas < 1){
    $total_paginas = 1;
}

// ===== OBTENER DATOS =====

$sql_data =
"SELECT
    cp.id_compras,
    p.nombre as nombre_proveedor,
    cp.Provedores_id_provedores as id_proveedor,
    cp.iva,
    cp.total,
    cp.fecha,
    (cp.total - cp.iva) as subtotal

 FROM compras cp

 JOIN provedores p
 ON cp.Provedores_id_provedores =
    p.id_provedores

 $filtro

 ORDER BY cp.id_compras DESC

 LIMIT :inicio, :por_pagina";

$stmt = $con->prepare($sql_data);

$stmt->bindValue(
    ':inicio',
    $inicio,
    PDO::PARAM_INT
);

$stmt->bindValue(
    ':por_pagina',
    $por_pagina,
    PDO::PARAM_INT
);

if($busqueda != ''){

    $stmt->bindValue(
        ':busqueda',
        "%$busqueda%"
    );

}

$stmt->execute();

$compras =
$stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Compras</title>

    <link
        rel="icon"
        href="img/carrito.png"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="compras/compra.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"
    />

    <script>

        const PAGINA_ACTUAL =
        <?php echo $pagina; ?>;

        const BUSQUEDA_ACTUAL =
        "<?php echo $busqueda; ?>";

    </script>

</head>

<body>

<?php include ("menu.php"); ?>

<div class="scroll"></div>

<div class="informacion">

    <div class="cuadro1">

        <span class="icono-producto">
            <i class="fas fa-box"></i>
        </span>

        <h2>Total de Compras</h2>

        <p id="kpi_total_compras">
            <?php echo $total_global_registros; ?>
        </p>

        <p>
            <?php echo $total_global_unidades; ?>
            unidades compradas
        </p>

    </div>

    <div class="cuadro2">

        <span class="icono-dolar">$</span>

        <h2>Monto total</h2>

        <p id="kpi_monto_total">

            $<?php echo number_format(
                $total_global_ingresos,
                2
            ); ?>

        </p>

        <p>Valor total estimado</p>

    </div>

    <div class="cuadro3">

        <span class="icono-dolar">
            <i class="fas fa-chart-line"></i>
        </span>

        <h2>Promedio</h2>

        <p id="kpi_promedio">

            $<?php echo number_format(
                $promedio_global,
                2
            ); ?>

        </p>

        <p>Promedio por compra</p>

    </div>

</div>

<div>

    <button id="btnAbrirFormulario">
        + NUEVA COMPRA
    </button>

</div>

<form
    action="compras.php"
    method="GET"
    class="buscar"
>

    <div class="input-box">

        <i
            class="fa-solid fa-magnifying-glass"
            style="padding:0 10px; color:#555;"
        ></i>

        <input
            type="text"
            name="buscar"
            placeholder="Buscar proveedor o ID de compra..."
            value="<?php echo $busqueda; ?>"
        >

        <button
            type="submit"
            class="btnBuscar"
        >
            Buscar
        </button>

        <?php if($busqueda != ''): ?>

            <a
                href="compras.php"
                class="eliminar"
            >
                X
            </a>

        <?php endif; ?>

    </div>

</form>

<div id="modalOverlay" class="modal-overlay">

    <form id="formularioCompras">

        <span
            id="btnCerrarModal"
            class="cerrar-modal"
        >
            &times;
        </span>

        <div class="campo">
            <label>PROVEEDOR:</label>
            <select id="nombre_proveedor" required></select>
        </div>

        <div class="campo">
            <label>FECHA:</label>
            <input
                type="date"
                id="fecha_venta"
                name="fecha_venta"
                required
            >
        </div>

        <div class="campo">
            <label>IVA:</label>
            <input
                type="number"
                id="iva"
                name="iva"
                step="0.01"
                value="0.16"
                readonly
            >
        </div>

        <div class="campo">
            <label>ARTÍCULO:</label>
            <select id="nom_articulo"></select>
        </div>

        <div class="campo">
            <label>CANTIDAD:</label>
            <input
                type="number"
                id="cantidad"
                name="cantidad"
                value="1"
            >
        </div>

        <input type="hidden" id="pre_unitario">

        <button
            type="button"
            id="btnAnadirProducto"
            class="btn-anadir"
        >
            Añadir Producto
        </button>

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

        <input
            type="submit"
            value="REGISTRAR COMPRA"
        >

    </form>

</div>

<div
    class="table-container"
    id="tabla1"
>

    <table>

        <thead>

            <tr>

                <th>ID COMPRA</th>
                <th>ID PROVEEDOR</th>
                <th>NOMBRE PROVEEDOR</th>
                <th>SUBTOTAL</th>
                <th>IVA</th>
                <th>TOTAL</th>
                <th>FECHA COMPRA</th>
                <th class="acciones">ACCIONES</th>

            </tr>

        </thead>

        <tbody id="tablaCompraBody">

        <?php if(count($compras) > 0): ?>

            <?php foreach($compras as $c): ?>

                <tr>

                    <td><?php echo $c['id_compras']; ?></td>

                    <td><?php echo $c['id_proveedor']; ?></td>

                    <td><?php echo $c['nombre_proveedor']; ?></td>

                    <td>
                        $<?php echo number_format($c['subtotal'] ?? 0, 2); ?>
                    </td>

                    <td>
                        $<?php echo number_format($c['iva'], 2); ?>
                    </td>

                    <td>
                        $<?php echo number_format($c['total'], 2); ?>
                    </td>

                    <td>
                        <?php echo $c['fecha']; ?>
                    </td>

                    <td class="celda-acciones">

                        <button
                            class="btn-modificar"
                            data-id="<?php echo $c['id_compras']; ?>"
                        >
                            <i class="fas fa-pen"></i>
                        </button>

                        <button
                            class="btn-eliminar"
                            data-id="<?php echo $c['id_compras']; ?>"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>

                        <a
                            href="#"
                            class="btn-pdf"
                            data-id="<?php echo $c['id_compras']; ?>"
                        >
                            <i class="fas fa-file-pdf"></i>
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>

                <td
                    colspan="8"
                    style="text-align:center; padding:20px;"
                >
                    No se encontraron resultados
                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <!-- PAGINACIÓN -->

    <div class="paginacion">

    <?php
    $link = "?buscar=$busqueda&pagina=";

    // BLOQUE ACTUAL
    $bloque_actual = ceil($pagina / 10);

    // INICIO Y FIN DEL BLOQUE
    $inicio_bloque = (($bloque_actual - 1) * 10) + 1;
    $fin_bloque = $inicio_bloque + 9;

    if($fin_bloque > $total_paginas){
        $fin_bloque = $total_paginas;
    }
    ?>

    <!-- BOTÓN ANTERIOR -->
    <?php if ($pagina > 1): ?>

        <a href="<?php echo $link . ($pagina - 1); ?>">
            &laquo; Anterior
        </a>

    <?php endif; ?>

    <!-- BOTÓN PARA REGRESAR 10 PÁGINAS -->
    <?php if($bloque_actual > 1): ?>

        <a href="<?php echo $link . ($inicio_bloque - 10); ?>">
            ...
        </a>

    <?php endif; ?>

    <!-- PÁGINAS -->
    <?php for ($i = $inicio_bloque; $i <= $fin_bloque; $i++): ?>

        <a
            href="<?php echo $link . $i; ?>"
            class="<?php echo $i == $pagina ? 'activo' : ''; ?>"
        >
            <?php echo $i; ?>
        </a>

    <?php endfor; ?>

    <!-- BOTÓN PARA AVANZAR 10 PÁGINAS -->
    <?php if($fin_bloque < $total_paginas): ?>

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

<?php if (isset($_GET['msg'])): ?>

<?php

$msg = $_GET['msg'];

$texto = "";

$clase = "";

switch ($msg) {

    case "compra_ok":
        $texto = "¡Compra registrada con éxito!";
        $clase = "insertado";
        break;

    case "compra_error":
        $texto = "Hubo un error al registrar la Compra.";
        $clase = "eliminado";
        break;

    case "modificar_ok":
        $texto = "Compra modificada correctamente.";
        $clase = "insertado";
        break;

    case "eliminar_ok":
        $texto = "Compra eliminada correctamente.";
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

    const toast =
    document.getElementById("toast");

    if (toast) {

        toast.classList.add("show");

        setTimeout(() => {

            toast.classList.remove("show");

        }, 3000);

    }

});

if (window.history.replaceState) {

    const url =
    new URL(window.location);

    url.searchParams.delete('msg');

    window.history.replaceState(
        {},
        '',
        url
    );

}

</script>


<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="js/toast.js"></script>

<!-- ===== TABLA OCULTA PDF ===== -->

<div id="contenedorPDF" style="display:none;">

    <table id="tablaPDF">

        <thead>

            <tr>

                <th>ID COMPRA</th>
                <th>ID PROVEEDOR</th>
                <th>NOMBRE PROVEEDOR</th>
                <th>SUBTOTAL</th>
                <th>IVA</th>
                <th>TOTAL</th>
                <th>FECHA</th>

            </tr>

        </thead>

        <tbody>

        <?php

        $sqlPDF =
        "SELECT
            cp.id_compras,
            cp.Provedores_id_provedores as id_proveedor,
            p.nombre as nombre_proveedor,
            cp.iva,
            cp.total,
            cp.fecha,
            (cp.total - cp.iva) as subtotal

        FROM compras cp

        JOIN provedores p
        ON cp.Provedores_id_provedores =
           p.id_provedores

        $filtro

        ORDER BY cp.id_compras DESC";

        $stmtPDF = $con->prepare($sqlPDF);

        if($busqueda != ''){

            $stmtPDF->bindValue(
                ':busqueda',
                "%$busqueda%"
            );

        }

        $stmtPDF->execute();

        $comprasPDF =
        $stmtPDF->fetchAll(PDO::FETCH_ASSOC);

        foreach($comprasPDF as $c):

        ?>

            <tr>

                <td><?= $c['id_compras']; ?></td>

                <td><?= $c['id_proveedor']; ?></td>

                <td><?= $c['nombre_proveedor']; ?></td>

                <td>
                    $<?= number_format($c['subtotal'], 2); ?>
                </td>

                <td>
                    $<?= number_format($c['iva'], 2); ?>
                </td>

                <td>
                    $<?= number_format($c['total'], 2); ?>
                </td>

                <td><?= $c['fecha']; ?></td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<button id="descargarPDF">
    DESCARGAR PDF
</button>

<div class="scroll"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<script src="compras/comprasPDF.js"></script>

<script src="compras/comm.js"></script>

<script>

if(document.getElementById('descargarPDF')){

    document
    .getElementById('descargarPDF')
    .addEventListener(
        'click',
        generarPDF
    );

}

</script>

</body>
</html>