<?php
// models/NovedadModel.php

class NovedadModel {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Obtiene todas las novedades, ordenadas por fecha.
     * @return mysqli_result
     */
    public function getNovedades() {
        $stmt = $this->conn->prepare("SELECT nov_id, nov_titulo, nov_contenido_corto, nov_fecha_publicacion, nov_activo FROM t_novedades ORDER BY nov_fecha_publicacion DESC");
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtiene una única novedad por su ID.
     * @param int $id
     * @return array|null
     */
    public function getNovedadPorId($id) {
        $stmt = $this->conn->prepare("SELECT nov_titulo, nov_contenido_corto, nov_contenido_largo FROM t_novedades WHERE nov_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Crea una nueva novedad en la base de datos.
     * @param array $datos
     * @return bool
     */
    public function crearNovedad($datos) {
        $stmt = $this->conn->prepare("
            INSERT INTO t_novedades 
                (nov_titulo, nov_contenido_corto, nov_contenido_largo, nov_creado_por_age_id, nov_fecha_publicacion) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssi", $datos['titulo'], $datos['contenido_corto'], $datos['contenido_largo'], $datos['autor_id']);
        return $stmt->execute();
    }

    /**
     * Obtiene las N novedades más recientes que están activas.
     * @param int $limit El número de novedades a obtener.
     * @return mysqli_result
     */
    public function getNovedadesRecientes($limit = 3) {
        $stmt = $this->conn->prepare("
            SELECT nov_titulo, nov_contenido_corto, nov_fecha_publicacion
            FROM t_novedades
            WHERE nov_activo = 1
            ORDER BY nov_fecha_publicacion DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    /**
     * Actualiza una novedad existente.
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarNovedad($id, $datos) {
        $stmt = $this->conn->prepare("UPDATE t_novedades SET nov_titulo = ?, nov_contenido_corto = ?, nov_contenido_largo = ? WHERE nov_id = ?");
        $stmt->bind_param("sssi", $datos['titulo'], $datos['contenido_corto'], $datos['contenido_largo'], $id);
        return $stmt->execute();
    }

    /**
     * Elimina una novedad por su ID.
     * @param int $id
     * @return bool
     */
    public function eliminarNovedad($id) {
        $stmt = $this->conn->prepare("DELETE FROM t_novedades WHERE nov_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}