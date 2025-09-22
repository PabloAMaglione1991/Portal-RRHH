<?php
require_once 'includes/db.php';

// Los datos que quieres modificar
$dni_jefe = '28074091';
$nueva_contrasena = 'Galisteo2127!';

echo "Contraseña en el código: '{$nueva_contrasena}'<br>";
echo "Longitud de la contraseña: " . strlen($nueva_contrasena) . "<br>";
function validarContrasena($contrasena) {
    if (strlen($contrasena) < 12) {
        return false;
    }
    if (!preg_match('/[a-z]/', $contrasena)) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $contrasena)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $contrasena)) {
        return false;
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $contrasena)) {
        return false;
    }
    return true;
}

if (!validarContrasena($nueva_contrasena)) {
    die("Error: La contraseña no cumple con los requisitos de seguridad (mínimo 12 caracteres, mayúscula, minúscula, número y caracter especial).");
}

$hashed_password = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE t_agente SET age_password_hash = ? WHERE age_numdoc = ?");
$stmt->bind_param("ss", $hashed_password, $dni_jefe);

if ($stmt->execute()) {
    echo "Contraseña actualizada con éxito para el DNI: " . htmlspecialchars($dni_jefe) . "<br>";
    echo "Contraseña asignada: " . htmlspecialchars($nueva_contrasena);
} else {
    echo "Error al actualizar la contraseña: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>