<?php

namespace App\Controllers;

use App\Models\FichadaModel;

class FichadaController extends BaseController
{
    public function index()
    {
        $this->checkAuth(); // Asegura que el usuario esté autenticado

        $tarjeta_agente = $_GET['tarjeta'] ?? null;

        if (!$tarjeta_agente) {
            // Si no hay tarjeta, redirigir o mostrar un error
            // Por ahora, redirigimos al dashboard principal o a una página de error
            header("Location: /dashboard_main");
            exit();
        }

        $fichadaModel = new FichadaModel($this->conn);

        $anio_actual = (int)date('Y');
        $mes = isset($_GET['mes']) && !empty($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : $anio_actual;

        $result_fichadas = $fichadaModel->getFichadasPorPeriodo($tarjeta_agente, $anio, $mes);
        list($fichadas_por_dia, $horas_trabajadas_por_dia) = $fichadaModel->procesarFichadasParaCalendario($result_fichadas);

        $meses_espanol = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $nombre_mes = $meses_espanol[$mes - 1];
        $titulo_pagina = "Fichadas de {$nombre_mes} {$anio}";
        $hoy = new \DateTime();

        // Lógica para el calendario (pasarla a Twig)
        $primer_dia_del_mes = new \DateTime("$anio-$mes-01");
        $numero_dias_mes = (int)$primer_dia_del_mes->format('t');
        $dia_semana_primer_dia = (int)$primer_dia_del_mes->format('w');

        $calendar_days = [];
        // Rellenar días vacíos al principio del mes
        for ($i = 0; $i < $dia_semana_primer_dia; $i++) {
            $calendar_days[] = ['day' => '', 'classes' => ['not-month']];
        }

        for ($dia_actual = 1; $dia_actual <= $numero_dias_mes; $dia_actual++) {
            $fecha_actual = new \DateTime("$anio-$mes-$dia_actual");
            $clases_td = [];
            $fecha_formato_db = $fecha_actual->format('Y-m-d');
            $dia_semana = (int)$fecha_actual->format('w');

            if ($dia_semana == 0 || $dia_semana == 6) {
                $clases_td[] = 'weekend';
            }
            if ($fecha_actual->format('Y-m-d') == $hoy->format('Y-m-d')) {
                $clases_td[] = 'today';
            }
            if (isset($fichadas_por_dia[$fecha_formato_db])) {
                $clases_td[] = 'has-fichadas';
            }

            $calendar_days[] = [
                'day' => $dia_actual,
                'classes' => $clases_td,
                'fichadas' => $fichadas_por_dia[$fecha_formato_db] ?? [],
                'horas_trabajadas' => $horas_trabajadas_por_dia[$fecha_formato_db] ?? null
            ];
        }

        // Rellenar días vacíos al final del mes
        $dia_semana_ultimo_dia = (int)$fecha_actual->format('w');
        if ($dia_semana_ultimo_dia != 6) {
            for ($i = 0; $i < (6 - $dia_semana_ultimo_dia); $i++) {
                $calendar_days[] = ['day' => '', 'classes' => ['not-month']];
            }
        }

        echo $this->twig->render('fichadas/index.html.twig', [
            'page_title' => $titulo_pagina,
            'tarjeta_agente' => $tarjeta_agente,
            'anio_actual' => $anio_actual,
            'mes_seleccionado' => $mes,
            'anio_seleccionado' => $anio,
            'meses_espanol' => $meses_espanol,
            'calendar_days' => $calendar_days
        ]);
    }
}
