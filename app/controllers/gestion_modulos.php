<?php
// app/Controllers/gestion_modulos.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/autoloader.php';

// Verificación de permisos (solo admin)
if (!isset($_SESSION['user_id']) || !in_array('admin', $_SESSION['user_roles'])) {
    header("Location: /?page=login");
    exit();
}

$moduloModel = new ModuloModel($conn);

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modulo_id'])) {
    $estado_nuevo = isset($_POST['estado']) ? 1 : 0;
    $moduloModel->cambiarEstadoModulo((int)$_POST['modulo_id'], $estado_nuevo);
    header("Location: /?page=gestion_modulos");
    exit();
}

$modulos = $moduloModel->getTodosLosModulos();

$page_title = 'Gestión de Módulos';
require_once __DIR__ . '/../views/layout/header.php';
?>

<h1>Gestión de Módulos</h1>
<p>Activa o desactiva funcionalidades de la aplicación en tiempo real.</p>

<div class="container">
    <table>
        <thead>
            <tr>
                <th>Módulo</th>
                <th>Descripción</th>
                <th style="text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($modulo = $modulos->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($modulo['modulo_nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($modulo['modulo_descripcion']) ?></td>
                    <td style="text-align: center;">
                        <form action="/?page=gestion_modulos" method="post">
                            <input type="hidden" name="modulo_id" value="<?= $modulo['modulo_id'] ?>">
                            <input type="checkbox" name="estado" value="1" <?= $modulo['modulo_activo'] ? 'checked' : '' ?> onchange="this.form.submit()">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>