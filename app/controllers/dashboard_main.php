<?php
// app/Controllers/dashboard_main.php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/autoloader.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /?page=login");
    exit();
}

// Inicializamos los modelos que usaremos
$novedadModel = new NovedadModel($conn);
$moduloModel = new ModuloModel($conn);

// Obtenemos las 3 novedades más recientes
$novedades = $novedadModel->getNovedadesRecientes(3);

// Limpieza de roles para asegurar que no haya caracteres extraños
$roles = $_SESSION['user_roles'] ?? [];
$roles = array_map(function($rol) {
    return preg_replace('/[^\p{L}]/u', '', $rol);
}, $roles);

$page_title = 'Menú Principal';
require_once __DIR__ . '/../views/layout/header.php';
?>

<h1>Menú Principal</h1>
<p>Bienvenido, <?= htmlspecialchars($_SESSION['user_name']) ?>. Selecciona un panel para comenzar.</p>

<div class="dashboard-nav">
    
    <?php if (in_array('agente', $roles)): ?>
        <a href="/?page=dashboard_agente" class="nav-card">
            <i class="fas fa-user"></i>
            <h3>Panel de Agente</h3>
        </a>
    <?php endif; ?>

    <?php if (in_array('jefe', $roles)): ?>
        <a href="/?page=dashboard_jefe" class="nav-card">
            <i class="fas fa-user-tie"></i>
            <h3>Panel de Jefe</h3>
        </a>
    <?php endif; ?>

    <?php if (in_array('supervisor', $roles)): ?>
        <a href="/?page=dashboard_supervisor" class="nav-card">
            <i class="fas fa-user-graduate"></i>
            <h3>Panel de Supervisor</h3>
        </a>
    <?php endif; ?>

    <?php if (in_array('rrhh', $roles)): ?>
        <a href="/?page=dashboard_rrhh" class="nav-card">
            <i class="fas fa-users-cog"></i>
            <h3>Panel de RRHH</h3>
        </a>
    <?php endif; ?>

    <?php if (in_array('admin', $roles)): ?>
        <a href="/?page=dashboard_admin" class="nav-card">
            <i class="fas fa-shield-alt"></i>
            <h3>Panel de Admin</h3>
        </a>
    <?php endif; ?>

</div>

<div class="container novedades-container">
    <h2><i class="fas fa-bullhorn"></i> Últimas Novedades</h2>
    <div class="novedades-list">
        <?php if ($novedades && $novedades->num_rows > 0): ?>
            <?php while($novedad = $novedades->fetch_assoc()): ?>
                <div class="novedad-item">
                    <h4><?= htmlspecialchars($novedad['nov_titulo']) ?></h4>
                    <p><?= htmlspecialchars($novedad['nov_contenido_corto']) ?></p>
                    <span>Publicado el: <?= date('d/m/Y', strtotime($novedad['nov_fecha_publicacion'])) ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay novedades publicadas recientemente.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/layout/footer.php';
?>