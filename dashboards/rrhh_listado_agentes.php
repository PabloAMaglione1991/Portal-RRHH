<?php
// Se elimina session_start()
require_once 'includes/db.php';
require_once 'utils/db_queries.php';


if (!isset($_SESSION['user_id']) || !in_array('rrhh', $_SESSION['user_roles'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT
        a.age_id,
        a.age_nombre,
        a.age_apell1,
        a.age_numdoc,
        a.age_role,
        j.age_apell1 AS jefe_apellido,
        s.age_apell1 AS supervisor_apellido,
        at.tarj_nro
    FROM t_agente AS a
    LEFT JOIN t_agente AS j ON a.jefe_age_id = j.age_id
    LEFT JOIN t_agente AS s ON a.supervisor_age_id = s.age_id
    LEFT JOIN t_age_tarj AS at ON a.age_id = at.age_id
    ORDER BY a.age_apell1
");
$stmt->execute();
$agentes = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Agentes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <a href="../dashboards/rrhh.php" class="back-link">Volver al Dashboard de RRHH</a>
        <h1>Listado Completo de Agentes</h1>
        <table>
            <thead>
                <tr>
                    <th>Nombre y Apellido</th>
                    <th>DNI</th>
                    <th>Rol(es)</th>
                    <th>Jefe</th>
                    <th>Supervisor</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agentes->num_rows > 0): ?>
                    <?php while ($agente = $agentes->fetch_assoc()): ?>
                        <tr>
                            <td><a href="../fichadas.php?tarjeta=<?= htmlspecialchars($agente['tarj_nro']) ?>"><?= htmlspecialchars($agente['age_apell1'] . ' ' . $agente['age_nombre']) ?></a></td>
                            <td><?= htmlspecialchars($agente['age_numdoc']) ?></td>
                            <td><?= htmlspecialchars($agente['age_role']) ?></td>
                            <td><?= htmlspecialchars($agente['jefe_apellido'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($agente['supervisor_apellido'] ?: 'N/A') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No se encontraron agentes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>