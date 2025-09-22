<?php
// models/ModuloModel.php

class ModuloModel {
    private $conn;
    private static $estados_modulos = null;

    public function __construct($db_connection) {
        $this->conn = $db_connection; // <<< CORRECCIÓN AQUÍ: Se cambió el punto (.) por una flecha (->)
    }

    /**
     * Carga todos los estados de los módulos en una caché estática.
     */
    private function cargarEstados() {
        if (self::$estados_modulos === null) {
            self::$estados_modulos = [];
            $result = $this->conn->query("SELECT modulo_clave, modulo_activo FROM t_modulos");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    self::$estados_modulos[$row['modulo_clave']] = (bool)$row['modulo_activo'];
                }
            }
        }
    }

    /**
     * Verifica si un módulo específico está activo.
     * @param string $clave
     * @return bool
     */
    public function isModuloActivo($clave) {
        $this->cargarEstados();
        return isset(self::$estados_modulos[$clave]) && self::$estados_modulos[$clave];
    }

    /**
     * Obtiene todos los módulos para la página de gestión.
     * @return mysqli_result|false
     */
    public function getTodosLosModulos() {
        return $this->conn->query("SELECT * FROM t_modulos ORDER BY modulo_nombre");
    }

    /**
     * Cambia el estado (activo/inactivo) de un módulo.
     * @param int $id
     * @param bool $estado
     * @return bool
     */
    public function cambiarEstadoModulo($id, $estado) {
        $stmt = $this->conn->prepare("UPDATE t_modulos SET modulo_activo = ? WHERE modulo_id = ?");
        $estado_int = (int)$estado;
        $stmt->bind_param("ii", $estado_int, $id);
        return $stmt->execute();
    }
}