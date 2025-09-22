<?php
// app/views/layout/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Mi Aplicación' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css"> </head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="/assets/images/mihospital_logo.png" alt="Logo Aplicación"> </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="/?page=dashboard_main" class="<?= ($_GET['page'] ?? '') == 'dashboard_main' ? 'active' : '' ?>"><i class="fas fa-home"></i> Menú Principal</a></li>

                <?php 
                $roles = $_SESSION['user_roles'] ?? [];
                // ... (lógica de limpieza de roles que ya teníamos) ...
                if (!empty($roles)):
                ?>
                    <li><a href="/?page=fichadas&tarjeta=<?= htmlspecialchars($_SESSION['user_tarjeta'] ?? '') ?>" class="<?= ($_GET['page'] ?? '') == 'fichadas' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Mis Fichadas</a></li>
                    <li><a href="/?page=cambiar_contrasena" class="<?= ($_GET['page'] ?? '') == 'cambiar_contrasena' ? 'active' : '' ?>"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <?php endif; ?>

                <?php if (in_array('jefe', $roles)): ?>
                    <li><a href="/?page=dashboard_jefe" class="<?= ($_GET['page'] ?? '') == 'dashboard_jefe' ? 'active' : '' ?>"><i class="fas fa-user-tie"></i> Panel de Jefe</a></li>
                <?php endif; ?>
                <?php if (in_array('supervisor', $roles)): ?>
                    <li><a href="/?page=dashboard_supervisor" class="<?= ($_GET['page'] ?? '') == 'dashboard_supervisor' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Panel de Supervisor</a></li>
                <?php endif; ?>
                <?php if (in_array('rrhh', $roles)): ?>
                    <li><a href="/?page=dashboard_rrhh" class="<?= ($_GET['page'] ?? '') == 'dashboard_rrhh' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Panel de RRHH</a></li>
                <?php endif; ?>
                <?php if (in_array('admin', $roles)): ?>
                    <li><a href="/?page=dashboard_admin" class="<?= in_array(($_GET['page'] ?? ''), ['dashboard_admin', 'gestion_usuarios']) ? 'active' : '' ?>"><i class="fas fa-shield-alt"></i> Panel de Admin</a></li>
                <?php endif; ?>

                <li><a href="/?page=logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-wrapper">
        <div class="top-navbar">
        <div class="content-area">