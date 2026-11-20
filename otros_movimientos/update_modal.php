<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha           = $_POST['fecha'] ?? '';
    $tipo_nuevo      = $_POST['tipo'] ?? ''; 
    $producto_nombre = $_POST['producto_nombre'] ?? ''; 
    $producto_id     = $_POST['producto_id'] ?? ''; 
    $cantidad_nueva  = (int)($_POST['cantidad'] ?? 0);
    $motivo          = $_POST['motivo'] ?? ''; 
    $usuario         = $_POST['usuario'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. OBTENER EL MOVIMIENTO ANTES DE EDITARLO
        $stmtOld = $pdo->prepare("SELECT tipo_mov, cantidad FROM otrosmovimientos WHERE id_otrosMovimientos = ?");
        $stmtOld->execute([$id]);
        $mov_anterior = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if ($mov_anterior) {
            $tipo_viejo = $mov_anterior['tipo_mov'];
            $cantidad_vieja = (int)$mov_anterior['cantidad'];

            // 2. REVERTIR EL EFECTO DEL MOVIMIENTO VIEJO EN EL STOCK
            // Si antes entró, lo restamos; si antes salió (merma/ajuste salida), lo sumamos.
            if (in_array($tipo_viejo, ['Ingreso', 'Devolución', 'Ajuste Entrada'])) {
                $sqlRevertir = "UPDATE productos SET cantidad = cantidad - ? WHERE id_productos = ?";
            } else {
                $sqlRevertir = "UPDATE productos SET cantidad = cantidad + ? WHERE id_productos = ?";
            }
            $pdo->prepare($sqlRevertir)->execute([$cantidad_vieja, $producto_id]);
        }

        // 3. ACTUALIZAR EL REGISTRO EN LA TABLA OTROSMOVIMIENTOS
        $stmt = $pdo->prepare("UPDATE otrosmovimientos SET fecha=?, tipo_mov=?, nombre_prod=?, Productos_id_producto=?, cantidad=?, descripcion=?, usuario=? WHERE id_otrosMovimientos=?");
        $stmt->execute([$fecha, $tipo_nuevo, $producto_nombre, (int)$producto_id, $cantidad_nueva, $motivo, $usuario, $id]);

        // 4. APLICAR EL EFECTO DEL NUEVO MOVIMIENTO AL STOCK
        if (in_array($tipo_nuevo, ['Ingreso', 'Devolución', 'Ajuste Entrada'])) {
            $sqlAplicar = "UPDATE productos SET cantidad = cantidad + ? WHERE id_productos = ?";
        } else {
            $sqlAplicar = "UPDATE productos SET cantidad = cantidad - ? WHERE id_productos = ?";
        }
        $pdo->prepare($sqlAplicar)->execute([$cantidad_nueva, $producto_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al actualizar: " . $e->getMessage());
    }
}

header("Location: ../om.php?success=edit");
exit;