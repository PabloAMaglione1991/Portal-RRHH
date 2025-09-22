<?php
// config/db.php

// 1. Credenciales de la base de datos
define('DB_SERVER', '10.12.4.2');
define('DB_USERNAME', 'gestion_');
define('DB_PASSWORD', 'GESTION_77');
define('DB_NAME', 'factu30_prod_test');

// 2. Crear la conexión a la base de datos
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 3. Verificar si la conexión falló
if ($conn->connect_error) {
    // Detiene la ejecución y muestra el error.
    // En un entorno de producción, esto debería registrarse en un archivo de log.
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// 4. Establecer el charset a UTF-8 para evitar problemas con acentos y caracteres especiales
if (!$conn->set_charset("utf8")) {
    error_log("Error al cargar el conjunto de caracteres utf8: " . $conn->error);
}