<?php
// app/Controllers/dashboard_turnos.php
require_once __DIR__ . '/../../config/db.php';

// Verificación de permisos
if (!isset($_SESSION['user_id']) || !in_array('personal_de_turnos', $_SESSION['user_roles'])) {
    header("Location: /?page=login");
    exit();
}

$notificaciones = $conn->query("SELECT * FROM t_notificaciones_turnos WHERE vista = FALSE ORDER BY fecha_generacion DESC");

$page_title = 'Panel de Turnos';
require_once __DIR__ . '/../views/layout/header.php';
?>
<h1>Notificaciones para Gestión de Turnos</h1>
<div class="container">
    <h2>Licencias Aprobadas de Profesionales a Bloquear</h2>
    <table>
        <thead>
            <tr>
                <th>Mensaje</th>
                <th>Fecha de Notificación</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($notificaciones && $notificaciones->num_rows > 0): ?>
                <?php while ($notif = $notificaciones->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($notif['mensaje']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($notif['fecha_generacion'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2">No hay notificaciones nuevas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../views/layout/footer.php'; ?>