<?php

namespace App\Controllers;

use App\Models\NovedadModel;

class NovedadController extends BaseController
{
    public function index()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver este panel.');
        }

        $novedadModel = new NovedadModel($this->conn);
        $novedades = $novedadModel->getNovedades();
        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        echo $this->twig->render('novedades/index.html.twig', [
            'page_title' => 'Gestión de Novedades',
            'novedades' => $novedades->fetch_all(MYSQLI_ASSOC),
            'mensaje' => $mensaje,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    public function create()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para crear novedades.');
        }

        $novedadModel = new NovedadModel($this->conn);
        $error_message = null;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('Error de validación de seguridad.');
            }
            
            $datos_novedad = [
                'titulo' => $_POST['titulo'],
                'contenido_corto' => $_POST['contenido_corto'],
                'contenido_largo' => $_POST['contenido_largo'],
                'autor_id' => $_SESSION['user_id']
            ];
            
            if ($novedadModel->crearNovedad($datos_novedad)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'La novedad ha sido creada correctamente.'];
                header("Location: /gestion_novedades");
                exit();
            } else {
                $error_message = "Error al crear la novedad.";
            }
        }

        echo $this->twig->render('novedades/create.html.twig', [
            'page_title' => 'Crear Nueva Novedad',
            'error_message' => $error_message,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function edit(array $vars)
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para editar novedades.');
        }

        $id_novedad = (int)$vars['id'];
        $novedadModel = new NovedadModel($this->conn);
        $error_message = null;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('Error de validación de seguridad.');
            }
            
            $datos_novedad = [
                'titulo' => $_POST['titulo'],
                'contenido_corto' => $_POST['contenido_corto'],
                'contenido_largo' => $_POST['contenido_largo']
            ];
            
            if ($novedadModel->actualizarNovedad($id_novedad, $datos_novedad)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Novedad actualizada correctamente.'];
                header("Location: /gestion_novedades");
                exit();
            } else {
                $error_message = "Error al actualizar la novedad.";
            }
        }

        $novedad = $novedadModel->getNovedadPorId($id_novedad);
        if (!$novedad) {
            die("Novedad no encontrada.");
        }

        echo $this->twig->render('novedades/edit.html.twig', [
            'page_title' => 'Editar Novedad',
            'novedad' => $novedad,
            'error_message' => $error_message,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function delete()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para eliminar novedades.');
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación de seguridad.');
        }
        
        $id_novedad = (int)$_POST['id'];
        $novedadModel = new NovedadModel($this->conn);

        if ($novedadModel->eliminarNovedad($id_novedad)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'La novedad ha sido eliminada correctamente.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error al eliminar la novedad.'];
        }

        header("Location: /gestion_novedades");
        exit();
    }
}