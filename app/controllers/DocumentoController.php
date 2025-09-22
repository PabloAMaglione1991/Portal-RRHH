<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\DocumentoModel;

class DocumentoController extends BaseController
{
    public function index()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver esta sección.');
        }

        $usuarioModel = new UsuarioModel($this->conn);
        $filtro_nombre = $_GET['nombre'] ?? '';
        $filtros = !empty($filtro_nombre) ? ['nombre' => $filtro_nombre] : [];
        $agentes = $usuarioModel->getUsuarios($filtros, 100, 0); // Usamos un límite alto por ahora

        echo $this->twig->render('documentos/index.html.twig', [
            'page_title' => 'Documentación del Personal',
            'filtro_nombre' => $filtro_nombre,
            'agentes' => $agentes->fetch_all(MYSQLI_ASSOC),
        ]);
    }

    public function view(array $vars)
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para ver esta sección.');
        }

        $agente_id = (int)$vars['id'];
        $usuarioModel = new UsuarioModel($this->conn);
        $documentoModel = new DocumentoModel($this->conn);

        $agente = $usuarioModel->getInfoBasicaAgente($agente_id);
        if (!$agente) {
            die("Agente no encontrado.");
        }
        $documentos = $documentoModel->getDocumentosPorAgente($agente_id);

        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        echo $this->twig->render('documentos/view.html.twig', [
            'page_title' => 'Documentos de ' . $agente['age_apell1'],
            'agente' => $agente,
            'documentos' => $documentos->fetch_all(MYSQLI_ASSOC),
            'mensaje' => $mensaje,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    public function upload(array $vars)
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para subir documentos.');
        }

        $agente_id = (int)$vars['id'];

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación de seguridad.');
        }

        $documentoModel = new DocumentoModel($this->conn);

        if ($documentoModel->subirDocumento($_FILES['documento'], $agente_id, $_POST['tipo_documento'])) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento subido correctamente.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error en la subida del archivo.'];
        }

        header("Location: /ver_documentos/" . $agente_id);
        exit();
    }

    public function deleteDocument(array $vars)
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        if (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) {
            http_response_code(403);
            die('Acceso Prohibido. No tienes los permisos necesarios para eliminar documentos.');
        }

        $agente_id = (int)$vars['id'];

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación de seguridad.');
        }
        
        $doc_id = (int)$_POST['doc_id'];
        $documentoModel = new DocumentoModel($this->conn);

        if ($documentoModel->eliminarDocumento($doc_id, $agente_id)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento eliminado correctamente.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error al eliminar el documento.'];
        }

        header("Location: /ver_documentos/" . $agente_id);
        exit();
    }
}