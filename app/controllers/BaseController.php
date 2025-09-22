<?php

namespace App\Controllers;

use Twig\Environment;

class BaseController
{
    protected $conn;
    protected $twig;

    public function __construct($conn, Environment $twig)
    {
        $this->conn = $conn;
        $this->twig = $twig;
    }

    /**
     * Verifica si el usuario está autenticado y tiene el rol requerido.
     * Redirige al login si no cumple los requisitos.
     *
     * @param string|null $requiredRole El rol requerido para acceder. Si es null, solo verifica la autenticación.
     */
    protected function checkAuth(string $requiredRole = null)
    {
        // 1. Verificar si hay una sesión activa
        if (!isset($_SESSION['user_id'])) {
            header("Location: /?page=login"); // O una nueva ruta /login
            exit();
        }

        // 2. Si se requiere un rol, verificarlo
        if ($requiredRole !== null) {
            if (empty($_SESSION['user_roles']) || !in_array($requiredRole, $_SESSION['user_roles'])) {
                // El usuario no tiene el rol. Podríamos mostrar una página de "Acceso Prohibido" (403)
                // Por ahora, lo redirigimos al dashboard principal.
                http_response_code(403);
                die('Acceso Prohibido. No tienes los permisos necesarios.');
                // header("Location: /?page=dashboard_main");
                // exit();
            }
        }
    }
}
