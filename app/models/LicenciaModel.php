<?php
// app/Models/LicenciaModel.php

class LicenciaModel {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // --- MÉTODOS PARA AGENTE ---

    /**
     * Crea una nueva solicitud de licencia.
     * @param array $datos
     * @return bool
     */
    public function crearSolicitud($datos) {
        $stmt = $this->conn->prepare("
            INSERT INTO t_licencias_solicitadas 
                (age_id_agente, jefe_age_id, articulo, fecha_inicio, fecha_fin, motivo) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissss", 
            $datos['agente_id'], 
            $datos['jefe_id'], 
            $datos['articulo'], 
            $datos['fecha_inicio'], 
            $datos['fecha_fin'], 
            $datos['motivo']
        );
        return $stmt->execute();
    }

    // --- MÉTODOS PARA JEFE ---

    /**
     * Obtiene las solicitudes pendientes para un jefe específico.
     * @param int $jefe_id
     * @return mysqli_result|false
     */
    public function getSolicitudesPendientesParaJefe($jefe_id) {
        $stmt = $this->conn->prepare("
            SELECT ls.*, CONCAT(a.age_apell1, ', ', a.age_nombre) AS agente_nombre
            FROM t_licencias_solicitadas AS ls
            JOIN t_agente AS a ON ls.age_id_agente = a.age_id
            WHERE ls.jefe_age_id = ? AND ls.estado IN ('pendiente', 'expirada')
            ORDER BY ls.fecha_solicitud DESC
        ");
        $stmt->bind_param("i", $jefe_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Revisa y actualiza el estado de las licencias pendientes que han expirado (más de 48hs).
     * @param int $jefe_id
     */
    public function actualizarLicenciasExpiradas($jefe_id) {
        $sql = "UPDATE t_licencias_solicitadas 
                SET estado = 'expirada' 
                WHERE jefe_age_id = ? 
                AND estado = 'pendiente' 
                AND fecha_solicitud < NOW() - INTERVAL 48 HOUR";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $jefe_id);
        $stmt->execute();
    }

    /**
     * Aprueba una solicitud por parte de un jefe.
     * @param int $solicitud_id
     * @param int $jefe_id
     * @return bool
     */
    public function aprobarSolicitudJefe($solicitud_id, $jefe_id) {
        // (Aquí va la lógica completa que también notifica a turnos)
        return true; // Placeholder
    }

    /**
     * Rechaza una solicitud por parte de un jefe.
     * @param int $solicitud_id
     * @param int $jefe_id
     * @return bool
     */
    public function rechazarSolicitudJefe($solicitud_id, $jefe_id) {
        $stmt = $this->conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'rechazada_jefe', fecha_decision_jefe = NOW() WHERE solicitud_id = ? AND jefe_age_id = ? AND estado = 'pendiente'");
        $stmt->bind_param("ii", $solicitud_id, $jefe_id);
        return $stmt->execute();
    }


    // --- MÉTODOS PARA RRHH ---

    /**
     * Obtiene todas las solicitudes para la vista de RRHH.
     * @return mysqli_result|false
     */
    public function getTodasLasSolicitudesParaRRHH() {
        $sql = "SELECT ls.*,
                       CONCAT(a.age_apell1, ', ', a.age_nombre) AS agente_nombre,
                       CONCAT(j.age_apell1, ', ', j.age_nombre) AS jefe_nombre
                FROM t_licencias_solicitadas ls
                JOIN t_agente a ON ls.age_id_agente = a.age_id
                LEFT JOIN t_agente j ON ls.jefe_age_id = j.age_id
                ORDER BY ls.fecha_solicitud DESC";
        return $this->conn->query($sql);
    }
    
    /**
     * Procesa una solicitud que fue aprobada por un jefe.
     * @param int $solicitud_id
     * @return bool
     */
    public function procesarSolicitudRRHH($solicitud_id) {
        $stmt = $this->conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'procesada_rrhh', fecha_procesado_rrhh = NOW() WHERE solicitud_id = ? AND estado = 'aceptada_jefe'");
        $stmt->bind_param("i", $solicitud_id);
        return $stmt->execute();
    }

    /**
     * Rechaza una solicitud que fue aprobada por un jefe.
     * @param int $solicitud_id
     * @return bool
     */
    public function rechazarSolicitudRRHH($solicitud_id) {
        $stmt = $this->conn->prepare("UPDATE t_licencias_solicitadas SET estado = 'rechazada_rrhh', fecha_procesado_rrhh = NOW() WHERE solicitud_id = ? AND estado = 'aceptada_jefe'");
        $stmt->bind_param("i", $solicitud_id);
        return $stmt->execute();
    }
}