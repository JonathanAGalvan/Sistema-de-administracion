<html>

<link rel="stylesheet" href="css/menu.css">

<header>
    <nav class="nav">
        <div class="logo">
            <?php 
            include('datos.php');
            echo $nombre_sistema;
            ?>
        </div>

        <ul class="menu">
        <?php
        $rol = $_SESSION["id_cargo"];

        if ($rol == 1) {
            echo '
                <li><a href="inicio.php">INICIO</a></li>
                <li><a href="alumno.php">INVENTARIO</a></li>
                <li><a href="compras.php">COMPRAS</a></li>
                <li><a href="ventas.php">VENTAS</a></li>
                <li><a href="proveedores.php">PROVEEDORES</a></li>
                <li><a href="clientes.php">CLIENTES</a></li>
                <li><a href="om.php">O. MOVIMIENTOS</a></li>
            ';
        }

        if ($rol == 2) {
            echo '
                <li><a href="inicio.php">INICIO</a></li>
                <li><a href="alumno.php">INVENTARIO</a></li>
                <li><a href="ventas.php">VENTAS</a></li>
                <li><a href="#">O. MOVIMIENTOS</a></li>
            ';
        }
        ?>

        <li class="submenu" id="userMenu">
            <a href="#" id="userToggle" class="user-name">
                <?php echo $_SESSION["nombre"] . " " . $_SESSION["apellido"]; ?>
                <span class="arrow">▼</span>
            </a>

            <ul class="submenu-items" id="submenuItems">
                <li><a href="acerca_de.php">ACERCA DE</a></li>
                <li><a href="control/logout.php">Salir</a></li>
            </ul>
        </li>

        </ul>

    </nav>
</header>

<script src="js/menu.js"></script>

</html>