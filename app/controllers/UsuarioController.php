<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use Twig\Environment;

// 1. Extender el BaseController
class UsuarioController extends BaseController
{
    private $usuarioModel;

    // 2. Actualizar el constructor
    public function __construct($conn, Environment $twig)
    {
        // Llamar al constructor del padre
        parent::__construct($conn, $twig);
        // Inicializar el modelo específico de este controlador
        $this->usuarioModel = new UsuarioModel($this->conn);
    }

    /**
     * Muestra la lista de usuarios y el formulario de filtros.
     */
    public function index()
    {
        // 3. Usar el método de autenticación centralizado
        $this->checkAuth('admin');

        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        $filtros = [
            'nombre' => $_GET['nombre'] ?? '',
            'rol_id' => $_GET['rol_id'] ?? '',
            'jefe_id' => $_GET['jefe_id'] ?? ''
        ];

        $registros_por_pagina = 25;
        $usuarios_result = $this->usuarioModel->getUsuarios($filtros, $registros_por_pagina, 0);
        $usuarios = [];
        while ($row = $usuarios_result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        $total_usuarios = $this->usuarioModel->contarUsuarios($filtros);
        $roles = $this->usuarioModel->getTodosLosRoles();
        $jefes = $this->usuarioModel->getJefesYSupervisores();

        echo $this->twig->render('usuarios/index.html.twig', [
            'page_title' => 'Gestión de Usuarios',
            'mensaje' => $mensaje,
            'filtros' => $filtros,
            'usuarios' => $usuarios,
            'total_usuarios' => $total_usuarios,
            'roles' => $roles,
            'jefes_filtro' => $jefes,
            'jefes_asignacion' => $jefes,
            'registros_por_pagina' => $registros_por_pagina,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'page' => 'gestion_usuarios'
        ]);
    }

    /**
     * Muestra el formulario de creación de usuario.
     */
    public function create()
    {
        $this->checkAuth('admin');

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $jefes = $this->usuarioModel->getJefesYSupervisores();
        
        echo $this->twig->render('usuarios/create.html.twig', [
            'page_title' => 'Crear Usuario',
            'csrf_token' => $_SESSION['csrf_token'],
            'jefes_supervisores' => $jefes,
            'page' => 'gestion_usuarios'
        ]);
    }

    /**
     * Almacena el nuevo usuario en la base de datos.
     */
    public function store()
    {
        $this->checkAuth('admin');

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación CSRF.');
        }

        $datos_usuario = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'dni' => $_POST['dni'],
            'password' => $_POST['password'],
            'roles' => $_POST['role'] ?? [],
            'jefe_id' => empty($_POST['jefe']) ? null : (int)$_POST['jefe'],
            'supervisor_id' => empty($_POST['supervisor']) ? null : (int)$_POST['supervisor']
        ];

        if ($this->usuarioModel->crearUsuario($datos_usuario)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Usuario creado correctamente.'];
            header("Location: /usuarios");
            exit();
        } else {
            $jefes = $this->usuarioModel->getJefesYSupervisores();

            echo $this->twig->render('usuarios/create.html.twig', [
                'page_title' => 'Crear Usuario',
                'csrf_token' => $_SESSION['csrf_token'],
                'jefes_supervisores' => $jefes,
                'error_message' => 'Error al crear el usuario.',
                'page' => 'gestion_usuarios'
            ]);
        }
    }

    /**
     * Maneja las peticiones AJAX para cargar más usuarios.
     */
    public function ajaxGetUsuarios()
    {
        $this->checkAuth('admin');

        $pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($pagina_actual < 1) { $pagina_actual = 1; }
        $registros_por_pagina = 25;
        $offset = ($pagina_actual - 1) * $registros_por_pagina;

        $filtros = [
            'nombre' => $_GET['nombre'] ?? '',
            'rol_id' => $_GET['rol_id'] ?? '',
            'jefe_id' => $_GET['jefe_id'] ?? ''
        ];

        $usuarios_result = $this->usuarioModel->getUsuarios($filtros, $registros_por_pagina, $offset);
        $usuarios = [];
        while ($row = $usuarios_result->fetch_assoc()) {
            $usuarios[] = $row;
        }

        if (!empty($usuarios)) {
            echo $this->twig->render('usuarios/_user_rows.html.twig', [
                'usuarios' => $usuarios
            ]);
        } else {
            http_response_code(204); // No Content
        }
    }

    /**
     * Muestra el formulario de edición de un usuario.
     */
    public function edit(array $vars)
    {
        $this->checkAuth('admin');

        $id_usuario = (int)$vars['id'];

        $usuario_a_editar = $this->usuarioModel->getUsuarioPorId($id_usuario);
        if (!$usuario_a_editar) {
            die("Usuario no encontrado.");
        }

        $roles_actuales = $this->usuarioModel->getRolesDeUsuario($id_usuario);
        $jefes = $this->usuarioModel->getJefesYSupervisores();
        
        echo $this->twig->render('usuarios/edit.html.twig', [
            'page_title' => 'Editar Usuario',
            'usuario' => $usuario_a_editar,
            'roles_actuales' => $roles_actuales,
            'jefes_supervisores' => $jefes,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'page' => 'gestion_usuarios'
        ]);
    }

    /**
     * Actualiza un usuario en la base de datos.
     */
    public function update(array $vars)
    {
        $this->checkAuth('admin');
        
        $id_usuario = (int)$vars['id'];

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación CSRF.');
        }

        $datos_usuario = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'dni' => $_POST['dni'],
            'password' => $_POST['password'] ?? '',
            'roles' => $_POST['role'] ?? [],
            'jefe_id' => empty($_POST['jefe']) ? null : (int)$_POST['jefe'],
            'supervisor_id' => empty($_POST['supervisor']) ? null : (int)$_POST['supervisor']
        ];

        if ($this->usuarioModel->actualizarUsuario($id_usuario, $datos_usuario)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Usuario actualizado correctamente.'];
            header("Location: /usuarios");
            exit();
        } else {
            $usuario_a_editar = $this->usuarioModel->getUsuarioPorId($id_usuario);
            $roles_actuales = $this->usuarioModel->getRolesDeUsuario($id_usuario);
            $jefes = $this->usuarioModel->getJefesYSupervisores();

            echo $this->twig->render('usuarios/edit.html.twig', [
                'page_title' => 'Editar Usuario',
                'usuario' => $usuario_a_editar,
                'roles_actuales' => $roles_actuales,
                'jefes_supervisores' => $jefes,
                'csrf_token' => $_SESSION['csrf_token'] ?? '',
                'error_message' => 'Error al actualizar el usuario.',
                'page' => 'gestion_usuarios'
            ]);
        }
    }

    /**
     * Elimina un usuario de la base de datos.
     */
    public function delete(array $vars)
    {
        $this->checkAuth('admin');

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación CSRF.');
        }
        
        $id_usuario = (int)$vars['id'];

        if ($this->usuarioModel->eliminarUsuario($id_usuario)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Usuario eliminado correctamente.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error al eliminar el usuario. Es posible que tenga otros registros asociados.'];
        }

        header("Location: /usuarios");
        exit();
    }

    /**
     * Asigna un jefe a múltiples agentes de forma masiva.
     */
    public function assignJefeMasivo()
    {
        $this->checkAuth('admin');

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error de validación de seguridad.'];
            header("Location: /usuarios");
            exit();
        }

        if (empty($_POST['agentes_ids']) || empty($_POST['jefe_id'])) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Debe seleccionar al menos un agente y un jefe.'];
            header("Location: /usuarios");
            exit();
        }

        $jefe_id = (int)$_POST['jefe_id'];
        $agentes_ids = array_map('intval', $_POST['agentes_ids']);

        if ($this->usuarioModel->asignarJefeMasivo($agentes_ids, $jefe_id)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => "Se ha asignado el nuevo jefe a " . count($agentes_ids) . " agente(s) correctamente."];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error al actualizar la base de datos.'];
        }

        header("Location: /usuarios?assign=success");
        exit();
    }

    public function changePasswordForm()
    {
        $this->checkAuth();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $mensaje = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);

        echo $this->twig->render('usuario/change_password.html.twig', [
            'page_title' => 'Cambiar Contraseña',
            'csrf_token' => $_SESSION['csrf_token'],
            'mensaje' => $mensaje,
            'page' => 'cambiar_contrasena'
        ]);
    }

    public function updatePassword()
    {
        $this->checkAuth();

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Error de validación de seguridad.');
        }

        $user_id = $_SESSION['user_id'];
        $contrasena_actual = $_POST['current_password'];
        $nueva_contrasena = $_POST['new_password'];
        $confirmar_contrasena = $_POST['confirm_password'];

        if ($nueva_contrasena !== $confirmar_contrasena) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Las nuevas contraseñas no coinciden.'];
        } elseif (strlen($nueva_contrasena) < 8) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
        } else {
            $stmt = $this->conn->prepare("SELECT age_password_hash FROM t_agente WHERE age_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();

            if ($usuario && password_verify($contrasena_actual, $usuario['age_password_hash'])) {
                $nuevo_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $stmt_update = $this->conn->prepare("UPDATE t_agente SET age_password_hash = ? WHERE age_id = ?");
                $stmt_update->bind_param("si", $nuevo_hash, $user_id);
                if ($stmt_update->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => '¡Contraseña actualizada correctamente!'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Hubo un error al actualizar la contraseña.'];
                }
                $stmt_update->close();
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'La contraseña actual es incorrecta.'];
            }
            $stmt->close();
        }
        header("Location: /cambiar_contrasena");
        exit();
    }
}