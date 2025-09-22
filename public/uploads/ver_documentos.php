<?php
session_start();
require_once 'includes/db.php';

// Verificación de permisos y que se haya pasado un ID de agente
if (!isset($_SESSION['user_id']) || (!in_array('rrhh', $_SESSION['user_roles']) && !in_array('admin', $_SESSION['user_roles'])) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = (int)$_GET['id'];
$upload_dir = 'uploads/'; // Directorio de subida

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- LÓGICA PARA PROCESAR ACCIONES (SUBIR O ELIMINAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error de validación de seguridad.');
    }

    // --- ACCIÓN: SUBIR UN NUEVO DOCUMENTO ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'subir') {
        if (isset($_FILES['documento']) && $_FILES['documento']['error'] == 0) {
            $tipo_documento = $_POST['tipo_documento'];
            $nombre_original = basename($_FILES['documento']['name']);
            $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
            
            // Crear un nombre de archivo único para evitar sobreescrituras
            $nombre_seguro = uniqid('doc_' . $agente_id . '_', true) . '.' . $extension;
            $path_archivo = $upload_dir . $nombre_seguro;

            // Mover el archivo subido al directorio de uploads
            if (move_uploaded_file($_FILES['documento']['tmp_name'], $path_archivo)) {
                // Insertar el registro en la base de datos
                $stmt = $conn->prepare("INSERT INTO t_documentos_agente (age_id, tipo_documento, nombre_archivo, path_archivo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $agente_id, $tipo_documento, $nombre_original, $path_archivo);
                if ($stmt->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento subido correctamente.'];
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error al mover el archivo subido.'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error en la subida del archivo o ningún archivo seleccionado.'];
        }
        header("Location: ver_documentos.php?id=" . $agente_id);
        exit();
    }

    // --- ACCIÓN: ELIMINAR UN DOCUMENTO EXISTENTE ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        $doc_id = (int)$_POST['doc_id'];
        
        // 1. Obtener la ruta del archivo para poder borrarlo del servidor
        $stmt_get = $conn->prepare("SELECT path_archivo FROM t_documentos_agente WHERE doc_id = ? AND age_id = ?");
        $stmt_get->bind_param("ii", $doc_id, $agente_id);
        $stmt_get->execute();
        $doc = $stmt_get->get_result()->fetch_assoc();
        
        if ($doc) {
            // 2. Borrar el archivo físico si existe
            if (file_exists($doc['path_archivo'])) {
                unlink($doc['path_archivo']);
            }
            
            // 3. Borrar el registro de la base de datos
            $stmt_del = $conn->prepare("DELETE FROM t_documentos_agente WHERE doc_id = ?");
            $stmt_del->bind_param("i", $doc_id);
            if ($stmt_del->execute()) {
                 $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento eliminado correctamente.'];
            }
            $stmt_del->close();
        }
        $stmt_get->close();
        header("Location: ver_documentos.php?id=" . $agente_id);
        exit();
    }
}


// --- OBTENER DATOS PARA MOSTRAR EN LA PÁGINA ---
// Obtener nombre del agente
$stmt_agente = $conn->prepare("SELECT age_apell1, age_nombre FROM t_agente WHERE age_id = ?");
$stmt_agente->bind_param("i", $agente_id);
$stmt_agente->execute();
$agente = $stmt_agente->get_result()->fetch_assoc();
if (!$agente) { die("Agente no encontrado."); }

// Obtener lista de documentos del agente
$stmt_docs = $conn->prepare("SELECT doc_id, tipo_documento, nombre_archivo, path_archivo, fecha_subida FROM t_documentos_agente WHERE age_id = ? ORDER BY fecha_subida DESC");
$stmt_docs->bind_param("i", $agente_id);
$stmt_docs->execute();
$documentos = $stmt_docs->get_result();

$mensaje = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$page_title = 'Documentos de ' . htmlspecialchars($agente['age_apell1']);
require_once 'templates/header.php';
?>

<h1>Documentación de: <?= htmlspecialchars($agente['age_apell1'] . ', ' . $agente['age_nombre']) ?></h1>
<a href="documentacion_personal.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver al Listado de Agentes</a>

<?php if ($mensaje): ?>
    <div class="alert <?= htmlspecialchars($mensaje['type']) ?>"><?= htmlspecialchars($mensaje['text']) ?></div>
<?php endif; ?>

<div class="container">
    <h2>Subir Nuevo Documento</h2>
    <form action="ver_documentos.php?id=<?= $agente_id ?>" method="post" enctype="multipart/form-data" class="filters" style="justify-content: flex-start;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="accion" value="subir">
        
        <input type="text" name="tipo_documento" placeholder="Tipo de Documento (Ej: DNI, CV)" required style="max-width: 250px;">
        <input type="file" name="documento" required>
        
        <button type="submit" class="btn-view"><i class="fas fa-upload"></i> Subir Archivo</button>
    </form>
</div>

<div class="container" style="margin-top: 30px;">
    <h2>Documentos Existentes</h2>
    <table>
        <thead>
            <tr>
                <th>Tipo de Documento</th>
                <th>Nombre del Archivo</th>
                <th>Fecha de Subida</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($documentos->num_rows > 0): ?>
                <?php while ($doc = $documentos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($doc['path_archivo']) ?>" target="_blank">
                                <i class="fas fa-file-alt"></i> <?= htmlspecialchars($doc['nombre_archivo']) ?>
                            </a>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($doc['fecha_subida'])) ?></td>
                        <td class="action-buttons" style="justify-content: flex-end;">
                            <form action="ver_documentos.php?id=<?= $agente_id ?>" method="post" onsubmit="return confirm('¿Está seguro de que desea eliminar este documento?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="doc_id" value="<?= $doc['doc_id'] ?>">
                                <button type="submit" class="btn-delete btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Este agente no tiene documentos subidos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$stmt_agente->close();
$stmt_docs->close();
require_once 'templates/footer.php';
?>