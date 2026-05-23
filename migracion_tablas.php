<?php
/**
 * Script de Migración - Ejecutar desde el navegador
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Migración de Base de Datos';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Migración de Base de Datos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            line-height: 1.6;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .log {
            background: #f4f4f4;
            padding: 1rem;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Migración de Tablas: Usuarios -> Clientes</h1>
    <div class="log">
        <?php
        try {
            $db = getDB();
            echo "<p>✅ Conexión a Base de Datos exitosa.</p>";

            // 1. Crear tabla clientes
            $sql = "CREATE TABLE IF NOT EXISTS `clientes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) DEFAULT NULL,
            `google_id` varchar(255) DEFAULT NULL,
            `avatar` varchar(255) DEFAULT NULL,
            `telefono` varchar(20) DEFAULT NULL,
            `direccion` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;";

            $db->exec($sql);
            echo "<p>✅ Tabla 'clientes' verificada/creada.</p>";

            // 2. Migrar datos
            $stmt = $db->query("SELECT * FROM usuarios WHERE rol = 'cliente'");
            $clientes = $stmt->fetchAll();

            $migrados = 0;
            $errores = 0;

            foreach ($clientes as $c) {
                // Verificar duplicados
                $check = $db->prepare("SELECT id FROM clientes WHERE id = ? OR email = ?");
                $check->execute([$c['id'], $c['email']]);

                if (!$check->fetch()) {
                    try {
                        $insert = $db->prepare("INSERT INTO clientes (id, nombre, email, password, google_id, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $insert->execute([
                            $c['id'],
                            $c['nombre'],
                            $c['email'],
                            $c['password'],
                            $c['google_id'],
                            $c['avatar'],
                            $c['created_at']
                        ]);
                        $migrados++;
                    } catch (PDOException $e) {
                        echo "<p class='error'>❌ Error migrando usuario {$c['email']}: " . $e->getMessage() . "</p>";
                        $errores++;
                    }
                } else {
                    // echo "<p>ℹ️ Usuario {$c['email']} ya existía en clientes.</p>";
                }
            }

            echo "<p>✅ Procesados. Migrados: <strong>$migrados</strong> nuevos clientes.</p>";

            // 3. Limpiar tabla usuarios
            if ($migrados > 0 || count($clientes) > 0) {
                // Solo borrar los que YA existen en clientes para seguridad
                $db->exec("DELETE FROM usuarios WHERE rol = 'cliente' AND id IN (SELECT id FROM clientes)");
                echo "<p>✅ Usuarios eliminados de la tabla antigua.</p>";
            }

            echo "<h2>🎉 ¡Migración Completada!</h2>";
            echo "<p>Ahora puedes eliminar este archivo (migracion_tablas.php) por seguridad.</p>";

        } catch (Exception $e) {
            echo "<h2 class='error'>❌ Error Fatal</h2>";
            echo "<p class='error'>" . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    <br>
    <a href="<?= BASE_URL ?>" class="btn">Volver al Inicio</a>
</body>

</html>