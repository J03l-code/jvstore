<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$title = 'Actualizar Estructura de Roles';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>
        <?= $title ?>
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body {
            padding: 2rem;
            font-family: sans-serif;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <h1>Actualizando Base de Datos...</h1>
    <?php
    $db = getDB();
    try {
        // 1. Modificar columna 'rol' para permitir 'staff'
        // Cambiamos a VARCHAR para mayor flexibilidad o actualizamos el ENUM
        $sql = "ALTER TABLE usuarios MODIFY COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'cliente'";
        $db->exec($sql);
        echo "<p class='success'>✅ Columna 'rol' actualizada a VARCHAR(20).</p>";

        // 2. Verificar si hay usuarios con rol vacío y corregirlos (opcional)
        // Por si acaso algún intento fallido quedó con rol vacío
        // $db->exec("UPDATE usuarios SET rol = 'staff' WHERE rol = ''");
    
        echo "<h2>¡Listo! Intenta crear/editar usuarios ahora.</h2>";

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    ?>
    <br>
    <a href="<?= BASE_URL ?>admin/usuarios.php" class="btn">Volver a Usuarios</a>
</body>

</html>