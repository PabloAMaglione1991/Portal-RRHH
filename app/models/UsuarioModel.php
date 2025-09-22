<?php
namespace App\Models;
// app/Models/UsuarioModel.php

class UsuarioModel {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Cuenta todos los usuarios que coinciden con los filtros (ignorando el estado 'activo').
     */
    public function contarUsuarios($filtros = []) {
        $sql = "SELECT COUNT(DISTINCT a.age_id) as total FROM t_agente a
                LEFT JOIN t_agente_roles ar ON a.age_id = ar.agente_id
                WHERE 1=1";
        
        $params = [];
        $types = '';

        if (!empty($filtros['nombre'])) {
            $sql .= " AND (a.age_nombre LIKE ? OR a.age_apell1 LIKE ?)";
            $types .= "ss";
            $params[] = "%{$filtros['nombre']}";
            $params[] = "%{$filtros['nombre']}";
        }
        if (!empty($filtros['rol_id'])) {
            $sql .= " AND ar.rol_id = ?";
            $types .= "i";
            $params[] = $filtros['rol_id'];
        }
        if (!empty($filtros['jefe_id'])) {
            $sql .= " AND a.jefe_age_id = ?";
            $types .= "i";
            $params[] = $filtros['jefe_id'];
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)$result['total'];
    }

    /**
     * Obtiene una lista paginada de todos los usuarios (ignorando el estado 'activo').
     */
    public function getUsuarios($filtros = [], $limit = 25, $offset = 0) {
        $sql = "SELECT
                    a.age_id, a.age_nombre, a.age_apell1, a.age_numdoc,
                    j.age_apell1 AS jefe_apellido,
                    GROUP_CONCAT(r.rol_nombre SEPARATOR ', ') AS roles
                FROM t_agente AS a
                LEFT JOIN t_agente AS j ON a.jefe_age_id = j.age_id
                LEFT JOIN t_agente_roles AS ar ON a.age_id = ar.agente_id
                LEFT JOIN t_roles AS r ON ar.rol_id = r.rol_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($filtros['nombre'])) {
            $sql .= " AND (a.age_nombre LIKE ? OR a.age_apell1 LIKE ?)";
            $types .= "ss";
            $params[] = "%{$filtros['nombre']}";
            $params[] = "%{$filtros['nombre']}";
        }
        if (!empty($filtros['jefe_id'])) {
            $sql .= " AND a.jefe_age_id = ?";
            $types .= "i";
            $params[] = $filtros['jefe_id'];
        }
        if (!empty($filtros['rol_id'])) {
            $sql .= " AND a.age_id IN (SELECT agente_id FROM t_agente_roles WHERE rol_id = ?)";
            $types .= "i";
            $params[] = $filtros['rol_id'];
        }

        $sql .= " GROUP BY a.age_id ORDER BY a.age_apell1, a.age_nombre LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getInfoBasicaAgente($id) {
        $stmt = $this->conn->prepare("SELECT age_nombre, age_apell1 FROM t_agente WHERE age_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUsuarioPorId($id) {
        $stmt = $this->conn->prepare("SELECT age_nombre, age_apell1, age_numdoc, jefe_age_id, supervisor_age_id FROM t_agente WHERE age_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getTarjetaPorAgenteId($agente_id) {
        $stmt = $this->conn->prepare("SELECT tarj_nro FROM t_age_tarj WHERE age_id = ? LIMIT 1");
        $stmt->bind_param("i", $agente_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRolesDeUsuario($id) {
        $stmt = $this->conn->prepare("SELECT r.rol_nombre FROM t_agente_roles ar JOIN t_roles r ON ar.rol_id = r.rol_id WHERE ar.agente_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $roles = [];
        while($row = $result->fetch_assoc()) {
            $roles[] = $row['rol_nombre'];
        }
        return $roles;
    }

    public function getTodosLosRoles()
    {
        $stmt = $this->conn->prepare("SELECT rol_id, rol_nombre FROM t_roles ORDER BY rol_nombre");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getJefesYSupervisores() {
        $query = "SELECT age_id, age_nombre, age_apell1 FROM t_agente WHERE age_id IN (SELECT agente_id FROM t_agente_roles WHERE rol_id IN (SELECT rol_id FROM t_roles WHERE rol_nombre IN ('jefe', 'supervisor', 'admin'))) ORDER BY age_apell1";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getAgentesPorJefe($jefe_id) {
        $stmt = $this->conn->prepare("            SELECT a.age_nombre, a.age_apell1, at.tarj_nro
            FROM t_agente AS a
            INNER JOIN t_age_tarj AS at ON a.age_id = at.age_id
            WHERE a.jefe_age_id = ?
            ORDER BY a.age_apell1
        ");
        $stmt->bind_param("i", $jefe_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getJefesPorSupervisor($supervisor_id) {
        $stmt = $this->conn->prepare("            SELECT age_id, age_nombre, age_apell1
            FROM t_agente
            WHERE supervisor_age_id = ? 
            AND age_id IN (SELECT agente_id FROM t_agente_roles WHERE rol_id = (SELECT rol_id FROM t_roles WHERE rol_nombre = 'jefe'))
            ORDER BY age_apell1
        ");
        $stmt->bind_param("i", $supervisor_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function crearUsuario($datos) {
        $hashed_password = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("INSERT INTO t_agente (age_nombre, age_apell1, age_numdoc, age_password_hash, jefe_age_id, supervisor_age_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $datos['nombre'], $datos['apellido'], $datos['dni'], $hashed_password, $datos['jefe_id'], $datos['supervisor_id']);
        
        if ($stmt->execute()) {
            $nuevo_agente_id = $stmt->insert_id;
            $this->actualizarRolesDeUsuario($nuevo_agente_id, $datos['roles']);
            return true;
        }
        return false;
    }
    
    public function actualizarUsuario($id, $datos) {
        $sql = "UPDATE t_agente SET age_nombre = ?, age_apell1 = ?, age_numdoc = ?, jefe_age_id = ?, supervisor_age_id = ?";
        $params_types = "sssii";
        $params_values = [$datos['nombre'], $datos['apellido'], $datos['dni'], $datos['jefe_id'], $datos['supervisor_id']];

        if (!empty($datos['password'])) {
            $hashed_password = password_hash($datos['password'], PASSWORD_DEFAULT);
            $sql .= ", age_password_hash = ?";
            $params_types .= "s";
            $params_values[] = $hashed_password;
        }
        
        $sql .= " WHERE age_id = ?";
        $params_types .= "i";
        $params_values[] = $id;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($params_types, ...$params_values);
        $stmt->execute();
        
        $this->actualizarRolesDeUsuario($id, $datos['roles']);
        return true;
    }
    
    public function eliminarUsuario($id) {
        $stmt = $this->conn->prepare("DELETE FROM t_agente WHERE age_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Asigna un jefe a múltiples agentes de forma masiva.
     *
     * @param array $agentes_ids IDs de los agentes a actualizar.
     * @param int $jefe_id ID del nuevo jefe.
     * @return bool True si la operación fue exitosa, false en caso contrario.
     */
    public function asignarJefeMasivo(array $agentes_ids, int $jefe_id)
    {
        if (empty($agentes_ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($agentes_ids), '?'));
        $sql = "UPDATE t_agente SET jefe_age_id = ? WHERE age_id IN ($placeholders)";
        
        $types = 'i' . str_repeat('i', count($agentes_ids));
        $values = array_merge([$jefe_id], $agentes_ids);

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$values);
        
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    private function actualizarRolesDeUsuario($agente_id, $roles_seleccionados) {
        $stmt_delete = $this->conn->prepare("DELETE FROM t_agente_roles WHERE agente_id = ?");
        $stmt_delete->bind_param("i", $agente_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        if (!empty($roles_seleccionados)) {
            $stmt_insert = $this->conn->prepare("INSERT INTO t_agente_roles (agente_id, rol_id) VALUES (?, ?)");
            $roles_ids_query = $this->conn->query("SELECT rol_id, rol_nombre FROM t_roles");
            $roles_map = [];
            while($row = $roles_ids_query->fetch_assoc()) {
                $roles_map[$row['rol_nombre']] = $row['rol_id'];
            }
            foreach ($roles_seleccionados as $rol_nombre) {
                if (isset($roles_map[$rol_nombre])) {
                    $rol_id = $roles_map[$rol_nombre];
                    $stmt_insert->bind_param("ii", $agente_id, $rol_id);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }
    }
}
