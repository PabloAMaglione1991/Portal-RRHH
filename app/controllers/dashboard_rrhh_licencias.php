<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array('rrhh', $_SESSION['user_roles'])) {
    header("Location: login.php");
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error de validación CSRF.');
    }

    if (isset($_POST['procesar'])) {
        $solicitud_id = (int)$_POST['procesar'];
        $stmt = $conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'procesada_rrhh' WHERE solicitud_id = ? AND estado = 'aceptada_jefe'");
        $stmt->bind_param("i", $solicitud_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['rechazar'])) {
        $solicitud_id = (int)$_POST['rechazar'];
        $stmt = $conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'rechazada_rrhh' WHERE solicitud_id = ? AND estado = 'aceptada_jefe'");
        $stmt->bind_param("i", $solicitud_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: dashboard_rrhh_licencias.php");
    exit();
}

// Obtener las solicitudes de licencia
$stmt = $conn->prepare("
    SELECT ls.solicitud_id, ls.articulo, ls.fecha_inicio, ls.fecha_fin, ls.motivo,
           a.age_nombre AS agente_nombre, a.age_apell1 AS agente_apellido,
           j.age_nombre AS jefe_nombre, j.age_apell1 AS jefe_apellido
    FROM t_licencias_solicitadas AS ls
    JOIN t_agente AS a ON ls.age_id_agente = a.age_id
    JOIN t_agente AS j ON ls.jefe_age_id = j.age_id
    WHERE ls.estado = 'aceptada_jefe'
    ORDER BY ls.fecha_solicitud ASC
");
$stmt->execute();
$solicitudes = $stmt->get_result();
$stmt->close();
$conn->close();

$page_title = 'Solicitudes de Licencia - RRHH';
require_once 'templates/header.php';
?>

<a href="dashboard_rrhh.php" class="back-link">Volver al Dashboard</a>
<h1>Portal de RRHH</h1>
<h2>Solicitudes de Licencia para Procesar</h2>

<?php if ($solicitudes->num_rows > 0): ?>
    <p>Hay <strong><?= $solicitudes->num_rows ?></strong> solicitudes aprobadas por jefes y pendientes de tu revisión.</p>
    <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
        <div class="solicitud-item">
            <div class="solicitud-header">
                <h4>Agente: <?= htmlspecialchars($solicitud['agente_apellido'] . ' ' . $solicitud['agente_nombre']) ?></h4>
                <span><strong>Aprobado por Jefe:</strong> <?= htmlspecialchars($solicitud['jefe_apellido'] . ' ' . $solicitud['jefe_nombre']) ?></span>
            </div>
            <div class="solicitud-info">
                <strong>Artículo:</strong> <?= htmlspecialchars($solicitud['articulo']) ?><br>
                <strong>Período:</strong> <?= htmlspecialchars($solicitud['fecha_inicio']) ?> a <?= htmlspecialchars($solicitud['fecha_fin']) ?><br>
                <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($solicitud['motivo'])) ?>
            </div>
            <div class="action-buttons">
                <form action="dashboard_rrhh_licencias.php" method="post">
                    <input type="hidden" name="procesar" value="<?= $solicitud['solicitud_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="procesar-btn">Procesar</button>
                </form>
                <form action="dashboard_rrhh_licencias.php" method="post">
                    <input type="hidden" name="rechazar" value="<?= $solicitud['solicitud_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="rechazar-btn">Rechazar</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No hay solicitudes pendientes de tu aprobación.</p>
<?php endif; ?>

<?php
require_once 'templates/footer.php';
?>