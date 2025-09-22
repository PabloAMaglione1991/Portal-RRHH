<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni_jefe = $_POST['dni'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT age_id, age_nombre, age_apell1, age_password_hash FROM t_agente WHERE age_numdoc = ?");
    $stmt->bind_param("s", $dni_jefe);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $jefe = $result->fetch_assoc();
        
        if (password_verify($password, $jefe['age_password_hash'])) {
            $_SESSION['jefe_id'] = $jefe['age_id'];
            $_SESSION['jefe_nombre'] = $jefe['age_nombre'] . " " . $jefe['age_apell1'];
        } else {
            $_SESSION['error'] = "DNI o contraseña incorrectos.";
            header("Location: index.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "DNI o contraseña incorrectos.";
        header("Location: index.php");
        exit();
    }
}

if (!isset($_SESSION['jefe_id'])) {
    header("Location: index.php");
    exit();
}

$jefe_id = $_SESSION['jefe_id'];

$stmt = $conn->prepare("
    SELECT
      a.age_nombre,
      a.age_apell1,
      at.tarj_nro
    FROM t_agente AS a
    INNER JOIN t_age_tarj AS at ON a.age_id = at.age_id
    WHERE a.jefe_age_id = ?
    ORDER BY a.age_apell1
");
$stmt->bind_param("i", $jefe_id);
$stmt->execute();
$agentes = $stmt->get_result();
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Fichadas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f0f2f5;
            margin: 0;
            padding-top: 40px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        li a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .logout {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['jefe_nombre']) ?></h1>
        <h2>Agentes a tu cargo:</h2>
        <?php if ($agentes->num_rows > 0): ?>
            <ul>
                <?php while ($agente = $agentes->fetch_assoc()): ?>
                    <li>
                        <a href="fichadas.php?tarjeta=<?= htmlspecialchars($agente['tarj_nro']) ?>">
                            <?= htmlspecialchars($agente['age_apell1'] . ' ' . $agente['age_nombre']) ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No tienes agentes asignados.</p>
        <?php endif; ?>
        <a href="index.php" class="logout">Cerrar Sesión</a>
    </div>
</body>
</html>