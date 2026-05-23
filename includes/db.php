<?php
/**
 * IMPORDISPAC - Conexión a Base de Datos (PDO Singleton)
 */
require_once __DIR__ . '/config.php';

function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción, loguear el error en vez de mostrarlo
            error_log("Error de conexión DB: " . $e->getMessage());

            if (defined('IS_API') && IS_API) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
                exit;
            }

            die("Error de conexión a la base de datos. Intente más tarde.");
        }
    }

    return $pdo;
}
