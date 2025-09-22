<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\LicenciaModel;

class LicenciaController extends BaseController
{
    public function articulos()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        // Datos estáticos de los artículos de licencia
        $licencia_articulos = [
            'Licencia Anual' => [
                'Licencia Anual Ordinaria (Vacaciones)'
            ],
            'Licencias por Salud' => [
                'Enfermedad de Corto Tratamiento',
                'Enfermedad de Largo Tratamiento',
                'Atención de Familiar Enfermo',
                'Maternidad',
                'Paternidad'
            ],
            'Licencias Especiales' => [
                'Matrimonio del Agente o Hijos',
                'Fallecimiento de Familiar',
                'Donación de Sangre'
            ],
            'Licencias por Estudio o Actividad Gremial' => [
                'Para rendir Examen',
                'Actividad Gremial',
                'Otra (Justificar motivo)'
            ]
        ];

        echo $this->twig->render('licencias/articulos.html.twig', [
            'page_title' => 'Seleccionar Tipo de Licencia',
            'licencia_articulos' => $licencia_articulos,
        ]);
    }

    public function solicitar()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        $articulo = $_GET['articulo'] ?? null;
        if (!$articulo) {
            header("Location: /licencias_articulos"); // Redirigir si no se especifica el artículo
            exit();
        }

        $usuarioModel = new UsuarioModel($this->conn);
        $licenciaModel = new LicenciaModel($this->conn);
        $error_message = null;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener el jefe del agente actual para asignarlo a la solicitud
        $agente_info = $usuarioModel->getUsuarioPorId($_SESSION['user_id']);
        $jefe_id = $agente_info['jefe_age_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('Error de validación de seguridad.');
            }
            
            $datos_licencia = [
                'agente_id' => $_SESSION['user_id'],
                'jefe_id' => $jefe_id,
                'articulo' => $articulo,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $_POST['fecha_fin'],
                'motivo' => $_POST['motivo']
            ];

            if ($licenciaModel->crearSolicitud($datos_licencia)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Tu solicitud de licencia ha sido enviada correctamente.'];
                header("Location: /dashboard_agente");
                exit();
            } else {
                $error_message = "Error al enviar la solicitud.";
            }
        }

        echo $this->twig->render('licencias/solicitar.html.twig', [
            'page_title' => 'Solicitar Licencia',
            'articulo' => $articulo,
            'error_message' => $error_message,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function aprobarJefe()
    {
        $this->checkAuth('jefe'); // Asegura que el usuario esté autenticado y tenga el rol 'jefe'

        $licenciaModel = new LicenciaModel($this->conn);
        $jefe_id = $_SESSION['user_id'];
        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Primero, actualizamos el estado de las licencias que hayan expirado
        $licenciaModel->actualizarLicenciasExpiradas($jefe_id);

        // Procesar acciones POST (Aprobar o Rechazar)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('Error de validación de seguridad.');
            }

            if (isset($_POST['aprobar_id'])) {
                if ($licenciaModel->aprobarSolicitudJefe((int)$_POST['aprobar_id'], $jefe_id)) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Solicitud aprobada y enviada a RRHH.'];
                }
            }

            if (isset($_POST['rechazar_id'])) {
                if ($licenciaModel->rechazarSolicitudJefe((int)$_POST['rechazar_id'], $jefe_id)) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Solicitud rechazada.'];
                }
            }
            header("Location: /aprobar_licencias_jefe");
            exit();
        }

        // Obtener las solicitudes pendientes para la vista
        $solicitudes = $licenciaModel->getSolicitudesPendientesParaJefe($jefe_id);

        echo $this->twig->render('licencias/aprobar_jefe.html.twig', [
            'page_title' => 'Aprobar Licencias',
            'solicitudes' => $solicitudes->fetch_all(MYSQLI_ASSOC),
            'mensaje' => $mensaje,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function solicitudesRRHH()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver esta sección.');
        }

        $licenciaModel = new LicenciaModel($this->conn);
        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('Error de validación de seguridad.');
            }

            if (isset($_POST['procesar_id'])) {
                if ($licenciaModel->procesarSolicitudRRHH((int)$_POST['procesar_id'])) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Solicitud procesada correctamente.'];
                }
            }

            if (isset($_POST['rechazar_id'])) {
                if ($licenciaModel->rechazarSolicitudRRHH((int)$_POST['rechazar_id'])) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Solicitud rechazada.'];
                }
            }
            
            header("Location: /solicitudes_licencia");
            exit();
        }

        $solicitudes = $licenciaModel->getTodasLasSolicitudesParaRRHH();

        echo $this->twig->render('licencias/solicitudes_rrhh.html.twig', [
            'page_title' => 'Gestión de Solicitudes de Licencia',
            'solicitudes' => $solicitudes->fetch_all(MYSQLI_ASSOC),
            'mensaje' => $mensaje,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }
}