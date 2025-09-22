<?php
// config/autoloader.php

spl_autoload_register(function ($className) {
    // Reemplaza las barras invertidas del namespace (si las hubiera) por barras de directorio.
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

    // Construye la ruta al archivo del modelo
    $file = ROOT_PATH . '/app/models/' . $className . '.php';

    // Si el archivo existe, lo incluye.
    if (file_exists($file)) {
        require_once $file;
    }
});