<?php
// models/FichadaModel.php

class FichadaModel {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Obtiene los registros de fichadas para una tarjeta en un período específico.
     * @param string $tarjeta_agente
     * @param int $anio
     * @param int|null $mes
     * @return mysqli_result
     */
    public function getFichadasPorPeriodo($tarjeta_agente, $anio, $mes) {
        $sql = "SELECT DATE(fich_fecha) AS fecha, TIME(fich_hora) AS hora
                FROM t_fichadas
                WHERE tarj_nro = ? AND YEAR(fich_fecha) = ?";
        
        $params = [$tarjeta_agente, $anio];
        $types = "si";

        if ($mes) {
            $sql .= " AND MONTH(fich_fecha) = ?";
            $types .= "i";
            $params[] = $mes;
        }
        
        $sql .= " ORDER BY fich_fecha ASC, fich_hora ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Procesa los resultados de la consulta para agruparlos por día y calcular las horas.
     * @param mysqli_result $result_fichadas
     * @return array Un array con dos elementos: [fichadas_por_dia, horas_trabajadas_por_dia]
     */
    public function procesarFichadasParaCalendario($result_fichadas) {
        $fichadas_por_dia = [];
        while ($row = $result_fichadas->fetch_assoc()) {
            $fecha = $row['fecha'];
            if (!isset($fichadas_por_dia[$fecha])) {
                $fichadas_por_dia[$fecha] = [];
            }
            $fichadas_por_dia[$fecha][] = $row['hora'];
        }

        $horas_trabajadas_por_dia = [];
        foreach ($fichadas_por_dia as $fecha => $horas) {
            $total_segundos = 0;
            $num_fichadas = count($horas);
            if ($num_fichadas >= 2) {
                for ($i = 0; $i < $num_fichadas - 1; $i += 2) {
                    $entrada = new DateTime($horas[$i]);
                    $salida = new DateTime($horas[$i+1]);
                    $diferencia = $salida->diff($entrada);
                    $total_segundos += ($diferencia->h * 3600) + ($diferencia->i * 60) + $diferencia->s;
                }
            }

            if ($total_segundos > 0) {
                $horas_calculadas = floor($total_segundos / 3600);
                $minutos_calculados = floor(($total_segundos % 3600) / 60);
                $horas_trabajadas_por_dia[$fecha] = sprintf('%02d:%02d', $horas_calculadas, $minutos_calculados);
            }
        }

        return [$fichadas_por_dia, $horas_trabajadas_por_dia];
    }
}