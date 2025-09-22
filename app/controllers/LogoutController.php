<?php

namespace App\Controllers;

class LogoutController extends BaseController
{
    public function logout()
    {
        // Asegurarse de que la sesión esté iniciada para poder destruirla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = array(); // Vaciar todas las variables de sesión
        session_destroy(); // Destruir la sesión

        header("Location: /login"); // Redirigir a la nueva ruta de login
        exit();
    }
}
