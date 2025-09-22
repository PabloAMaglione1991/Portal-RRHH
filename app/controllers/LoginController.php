<?php

namespace App\Controllers;

use Twig\Environment;

class LoginController extends BaseController
{
    public function showLoginForm()
    {
        // Renderizar el formulario de login
        echo $this->twig->render('login/index.html.twig', [
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['error']); // Limpiar el error después de mostrarlo
    }

    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /login");
            exit();
        }

        $dni = trim($_POST['dni']);
        $password = $_POST['password'];

        $stmt = $this->conn->prepare("
            SELECT
                a.age_id, a.age_nombre, a.age_apell1, a.age_password_hash,
                GROUP_CONCAT(r.rol_nombre SEPARATOR ',') AS roles
            FROM t_agente AS a
            LEFT JOIN t_agente_roles AS ar ON a.age_id = ar.agente_id
            LEFT JOIN t_roles AS r ON ar.rol_id = r.rol_id
            WHERE a.age_numdoc = ?
            GROUP BY a.age_id
        ");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user && isset($user['age_password_hash']) && password_verify($password, $user['age_password_hash'])) {
                $stmt_tarj = $this->conn->prepare("SELECT tarj_nro FROM t_age_tarj WHERE age_id = ?");
                $stmt_tarj->bind_param("i", $user['age_id']);
                $stmt_tarj->execute();
                $tarjeta = $stmt_tarj->get_result()->fetch_assoc();
                $stmt_tarj->close();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['age_id'];
                $_SESSION['user_name'] = $user['age_nombre'] . " " . $user['age_apell1'];
                $_SESSION['user_roles'] = $user['roles'] ? array_map('trim', explode(',', $user['roles'])) : [];
                $_SESSION['user_tarjeta'] = $tarjeta['tarj_nro'] ?? 'NO_ASIGNADA';

                header("Location: /dashboard_main"); // Redirigir a la nueva ruta
                exit();
            }
        }
        
        $_SESSION['error'] = "DNI o contraseña incorrectos.";
        header("Location: /login"); // Redirigir a la nueva ruta
        exit();
    }
}
