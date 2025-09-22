<?php

namespace App\Controllers;

use App\Models\NovedadModel;
use App\Models\ModuloModel;
use App\Models\UsuarioModel;
use App\Models\LicenciaModel;

class DashboardController extends BaseController
{
    public function main()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        $novedadModel = new NovedadModel($this->conn);
        $moduloModel = new ModuloModel($this->conn);

        $novedades = $novedadModel->getNovedadesRecientes(3);

        $roles = $_SESSION['user_roles'] ?? [];
        $roles = array_map(function($rol) {
            return preg_replace('/[^\p{L}]/u', '', $rol);
        }, $roles);

        echo $this->twig->render('dashboard/main.html.twig', [
            'user_name' => $_SESSION['user_name'],
            'roles' => $roles,
            'novedades' => $novedades->fetch_all(MYSQLI_ASSOC) // Convertir el resultado a array asociativo
        ]);
    }

    public function agente()
    {
        $this->checkAuth('agente'); // Asegura que el usuario esté autenticado y tenga el rol 'agente'

        $usuarioModel = new UsuarioModel($this->conn);
        if (!isset($_SESSION['user_tarjeta'])) {
            $tarjetaInfo = $usuarioModel->getTarjetaPorAgenteId($_SESSION['user_id']);
            $_SESSION['user_tarjeta'] = $tarjetaInfo['tarj_nro'] ?? 'NO_ASIGNADA';
        }

        echo $this->twig->render('dashboard/agente.html.twig', [
            'user_name' => $_SESSION['user_name'],
            'user_tarjeta' => $_SESSION['user_tarjeta']
        ]);
    }

    public function jefe()
    {
        $this->checkAuth(); // La verificación de rol más específica se hará a continuación

        if (!in_array('jefe', $_SESSION['user_roles']) && !in_array('supervisor', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver este panel.');
        }

        $usuarioModel = new UsuarioModel($this->conn);
        $licenciaModel = new LicenciaModel($this->conn);

        $is_supervisor_viewing = isset($_GET['jefe_id']) && in_array('supervisor', $_SESSION['user_roles']);
        $jefe_id = $is_supervisor_viewing ? (int)$_GET['jefe_id'] : $_SESSION['user_id'];

        $jefe_info = $usuarioModel->getInfoBasicaAgente($jefe_id);
        $agentes = $usuarioModel->getAgentesPorJefe($jefe_id);
        $solicitudes_pendientes = $licenciaModel->contarSolicitudesPendientesParaJefe($jefe_id);

        echo $this->twig->render('dashboard/jefe.html.twig', [
            'jefe_info' => $jefe_info,
            'agentes' => $agentes->fetch_all(MYSQLI_ASSOC),
            'solicitudes_pendientes' => $solicitudes_pendientes,
            'is_supervisor_viewing' => $is_supervisor_viewing
        ]);
    }

    public function supervisor()
    {
        $this->checkAuth('supervisor'); // Asegura que el usuario esté autenticado y tenga el rol 'supervisor'

        $usuarioModel = new UsuarioModel($this->conn);
        $supervisor_id = $_SESSION['user_id'];

        $jefes = $usuarioModel->getJefesPorSupervisor($supervisor_id);

        echo $this->twig->render('dashboard/supervisor.html.twig', [
            'user_name' => $_SESSION['user_name'],
            'jefes' => $jefes->fetch_all(MYSQLI_ASSOC)
        ]);
    }
}