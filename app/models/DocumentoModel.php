<?php
// models/DocumentoModel.php

class DocumentoModel {
    private $conn;
    private $upload_dir = 'uploads/';

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Obtiene todos los documentos de un agente específico.
     * @param int $agente_id
     * @return mysqli_result
     */
    public function getDocumentosPorAgente($agente_id) {
        $stmt = $this->conn->prepare("SELECT doc_id, tipo_documento, nombre_archivo, path_archivo, fecha_subida FROM t_documentos_agente WHERE age_id = ? ORDER BY fecha_subida DESC");
        $stmt->bind_param("i", $agente_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Procesa la subida de un nuevo documento.
     * @param array $file_info El array $_FILES['documento']
     * @param int $agente_id
     * @param string $tipo_documento
     * @return bool True si tuvo éxito, False si falló.
     */
    public function subirDocumento($file_info, $agente_id, $tipo_documento) {
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            return false; // Hubo un error en la subida
        }

        $nombre_original = basename($file_info['name']);
        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        $nombre_seguro = uniqid('doc_' . $agente_id . '_', true) . '.' . $extension;
        $path_archivo = $this->upload_dir . $nombre_seguro;

        if (move_uploaded_file($file_info['tmp_name'], $path_archivo)) {
            $stmt = $this->conn->prepare("INSERT INTO t_documentos_agente (age_id, tipo_documento, nombre_archivo, path_archivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $agente_id, $tipo_documento, $nombre_original, $path_archivo);
            return $stmt->execute();
        }
        
        return false;
    }

    /**
     * Elimina un documento de la base de datos y del servidor.
     * @param int $doc_id
     * @param int $agente_id
     * @return bool True si tuvo éxito, False si falló.
     */
    public function eliminarDocumento($doc_id, $agente_id) {
        // 1. Obtener la ruta del archivo para poder borrarlo
        $stmt_get = $this->conn->prepare("SELECT path_archivo FROM t_documentos_agente WHERE doc_id = ? AND age_id = ?");
        $stmt_get->bind_param("ii", $doc_id, $agente_id);
        $stmt_get->execute();
        $doc = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();
        
        if ($doc) {
            // 2. Borrar el archivo físico si existe
            if (file_exists($doc['path_archivo'])) {
                unlink($doc['path_archivo']);
            }
            
            // 3. Borrar el registro de la base de datos
            $stmt_del = $this->conn->prepare("DELETE FROM t_documentos_agente WHERE doc_id = ?");
            $stmt_del->bind_param("i", $doc_id);
            return $stmt_del->execute();
        }
        return false;
    }
}