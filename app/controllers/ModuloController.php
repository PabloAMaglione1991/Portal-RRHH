<?php

namespace App\Controllers;

use App\Models\ModuloModel;
use Twig\Environment;
use PDO;

class ModuloController extends BaseController
{
    private $moduloModel;

    public function __construct(PDO $conn, Environment $twig)
    {
        parent::__construct($conn, $twig);
        $this->moduloModel = new ModuloModel($conn);
    }

    public function index()
    {
        // Verificación de permisos (solo admin)
        if (!isset($_SESSION['user_id']) || !in_array('admin', $_SESSION['user_roles'])) {
            header("Location: /login"); // Redirigir a login si no tiene permisos
            exit();
        }

        $modulos = $this->moduloModel->getTodosLosModulos();

        echo $this->twig->render('modulos/index.html.twig', [
            'page_title' => 'Gestión de Módulos',
            'modulos' => $modulos->fetchAll(PDO::FETCH_ASSOC) // Fetch all results for Twig
        ]);
    }

    public function updateStatus()
    {
        // Verificación de permisos (solo admin)
        if (!isset($_SESSION['user_id']) || !in_array('admin', $_SESSION['user_roles'])) {
            header("Location: /login"); // Redirigir a login si no tiene permisos
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modulo_id'])) {
            $estado_nuevo = isset($_POST['estado']) ? 1 : 0;
            $this->moduloModel->cambiarEstadoModulo((int)$_POST['modulo_id'], $estado_nuevo);
            header("Location: /gestion_modulos"); // Redirigir a la ruta FastRoute
            exit();
        }
        // Si no es POST o falta modulo_id, redirigir o mostrar error
        header("Location: /gestion_modulos");
        exit();
    }
}
