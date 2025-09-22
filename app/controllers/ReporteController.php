<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class ReporteController extends BaseController
{
    public function index()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver esta sección.');
        }

        $usuarioModel = new UsuarioModel($this->conn);
        $agentes = $usuarioModel->getUsuarios([], 1000, 0); // Obtenemos hasta 1000 agentes para el reporte

        echo $this->twig->render('reportes/index.html.twig', [
            'page_title' => 'Reportes de RRHH',
            'agentes' => $agentes->fetch_all(MYSQLI_ASSOC),
        ]);
    }
}
