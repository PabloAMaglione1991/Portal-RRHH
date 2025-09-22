<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array('jefe', $_SESSION['user_roles'])) {
    header("Location: login.php");
    exit();
}

$jefe_id = $_SESSION['user_id'];

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error de validación CSRF.');
    }

    if (isset($_POST['aprobar'])) {
        $solicitud_id = (int)$_POST['aprobar'];
        $stmt = $conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'aceptada_jefe', fecha_aprobacion_jefe = NOW() WHERE solicitud_id = ? AND jefe_age_id = ? AND estado = 'pendiente'");
        $stmt->bind_param("ii", $solicitud_id, $jefe_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['rechazar'])) {
        $solicitud_id = (int)$_POST['rechazar'];
        $stmt = $conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'rechazada', fecha_aprobacion_jefe = NOW() WHERE solicitud_id = ? AND jefe_age_id = ? AND estado = 'pendiente'");
        $stmt->bind_param("ii", $solicitud_id, $jefe_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard_jefe_licencias.php");
    exit();
}

// Obtener solicitudes pendientes
$stmt = $conn->prepare("
    SELECT ls.solicitud_id, ls.articulo, ls.fecha_inicio, ls.fecha_fin, ls.motivo,
           a.age_nombre, a.age_apell1
    FROM t_licencias_solicitadas AS ls
    JOIN t_agente AS a ON ls.age_id_agente = a.age_id
    WHERE ls.jefe_age_id = ? AND ls.estado = 'pendiente'
    ORDER BY ls.fecha_solicitud DESC
");
$stmt->bind_param("i", $jefe_id);
$stmt->execute();
$solicitudes = $stmt->get_result();
$stmt->close();
$conn->close();

$page_title = 'Solicitudes de Licencia - Jefe';
require_once 'templates/header.php';
?>

<a href="dashboard_jefe.php" class="back-link">Volver al Dashboard</a>
<h1>Solicitudes de Licencia Pendientes</h1>

<?php if ($solicitudes->num_rows > 0): ?>
    <p>Tienes <strong><?= $solicitudes->num_rows ?></strong> solicitudes pendientes de aprobación.</p>
    <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
        <div class="solicitud-item">
            <div class="solicitud-header">
                <h4>Agente: <?= htmlspecialchars($solicitud['age_apell1'] . ' ' . $solicitud['age_nombre']) ?></h4>
            </div>
            <div class="solicitud-info">
                <strong>Artículo:</strong> <?= htmlspecialchars($solicitud['articulo']) ?><br>
                <strong>Período:</strong> <?= htmlspecialchars($solicitud['fecha_inicio']) ?> a <?= htmlspecialchars($solicitud['fecha_fin']) ?><br>
                <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($solicitud['motivo'])) ?>
            </div>
            <div class="action-buttons">
                <form action="dashboard_jefe_licencias.php" method="post">
                    <input type="hidden" name="aprobar" value="<?= $solicitud['solicitud_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="aprobar-btn">Aprobar</button>
                </form>
                <form action="dashboard_jefe_licencias.php" method="post">
                    <input type="hidden" name="rechazar" value="<?= $solicitud['solicitud_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="rechazar-btn">Rechazar</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No tienes solicitudes de licencia pendientes.</p>
<?php endif; ?>

<?php
require_once 'templates/footer.php';
?>