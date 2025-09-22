<?php
// public/index.php

define('ROOT_PATH', dirname(__DIR__));

session_start();

// 1. Cargar Autoloader de Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// 2. Inicializar Twig
$loader = new \Twig\Loader\FilesystemLoader(ROOT_PATH . '/app/views');
$twig = new \Twig\Environment($loader, [
    // 'cache' => ROOT_PATH . '/cache', // Descomentar en producción
]);
// Hacer la sesión global accesible en Twig
$twig->addGlobal('session', $_SESSION);


// 3. Inicializar la conexión a la BD (de momento, la mantenemos aquí)
require_once ROOT_PATH . '/config/db.php';


// 4. Configurar el Enrutador
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // --- RUTAS MIGRADAS ---
    $r->addRoute('GET', '/gestion_modulos', ['App\Controllers\ModuloController', 'index']);
    $r->addRoute('POST', '/gestion_modulos', ['App\Controllers\ModuloController', 'updateStatus']);
    $r->addRoute('GET', '/login', ['App\Controllers\LoginController', 'showLoginForm']);
    $r->addRoute('POST', '/login', ['App\Controllers\LoginController', 'authenticate']);
    $r->addRoute('GET', '/logout', ['App\Controllers\LogoutController', 'logout']);
    $r->addRoute('GET', '/dashboard_main', ['App\Controllers\DashboardController', 'main']);
    $r->addRoute('GET', '/dashboard_agente', ['App\Controllers\DashboardController', 'agente']);
    $r->addRoute('GET', '/dashboard_jefe', ['App\Controllers\DashboardController', 'jefe']);
    $r->addRoute('GET', '/dashboard_supervisor', ['App\Controllers\DashboardController', 'supervisor']);
    $r->addRoute('GET', '/dashboard_rrhh', ['App\Controllers\DashboardController', 'rrhh']);
    $r->addRoute('GET', '/dashboard_admin', ['App\Controllers\DashboardController', 'admin']);
    $r->addRoute('GET', '/cambiar_contrasena', ['App\Controllers\UsuarioController', 'changePasswordForm']);
    $r->addRoute('POST', '/cambiar_contrasena', ['App\Controllers\UsuarioController', 'updatePassword']);
    $r->addRoute('GET', '/fichadas', ['App\Controllers\FichadaController', 'index']);
    $r->addRoute('GET', '/gestion_novedades', ['App\Controllers\NovedadController', 'index']);
    $r->addRoute('GET', '/crear_novedad', ['App\Controllers\NovedadController', 'create']);
    $r->addRoute('POST', '/crear_novedad', ['App\Controllers\NovedadController', 'create']);
    $r->addRoute('GET', '/editar_novedad/{id:\d+}', ['App\Controllers\NovedadController', 'edit']);
    $r->addRoute('POST', '/editar_novedad/{id:\d+}', ['App\Controllers\NovedadController', 'edit']);
    $r->addRoute('POST', '/eliminar_novedad', ['App\Controllers\NovedadController', 'delete']);
    $r->addRoute('GET', '/documentacion_personal', ['App\Controllers\DocumentoController', 'index']);
    $r->addRoute('GET', '/ver_documentos/{id:\d+}', ['App\Controllers\DocumentoController', 'view']);
    $r->addRoute('POST', '/documentos/{id:\d+}/upload', ['App\Controllers\DocumentoController', 'upload']);
    $r->addRoute('POST', '/documentos/{id:\d+}/delete', ['App\Controllers\DocumentoController', 'deleteDocument']);
    $r->addRoute('GET', '/licencias_articulos', ['App\Controllers\LicenciaController', 'articulos']);
    $r->addRoute('GET', '/solicitar_licencia', ['App\Controllers\LicenciaController', 'solicitar']);
    $r->addRoute('POST', '/solicitar_licencia', ['App\Controllers\LicenciaController', 'solicitar']);
    $r->addRoute('GET', '/aprobar_licencias_jefe', ['App\Controllers\LicenciaController', 'aprobarJefe']);
    $r->addRoute('POST', '/aprobar_licencias_jefe', ['App\Controllers\LicenciaController', 'aprobarJefe']);
    $r->addRoute('GET', '/solicitudes_licencia', ['App\Controllers\LicenciaController', 'solicitudesRRHH']);
    $r->addRoute('POST', '/solicitudes_licencia', ['App\Controllers\LicenciaController', 'solicitudesRRHH']);
    $r->addRoute('GET', '/reportes_rrhh', ['App\Controllers\ReporteController', 'index']);

    // --- RUTAS EXISTENTES (UsuarioController) ---
    $r->addRoute('GET', '/usuarios', ['App\Controllers\UsuarioController', 'index']);
    $r->addRoute('GET', '/usuarios/ajax', ['App\Controllers\UsuarioController', 'ajaxGetUsuarios']);
    $r->addRoute('GET', '/usuarios/crear', ['App\Controllers\UsuarioController', 'create']);
    $r->addRoute('POST', '/usuarios/crear', ['App\Controllers\UsuarioController', 'store']);
    $r->addRoute('GET', '/usuarios/editar/{id:\d+}', ['App\Controllers\UsuarioController', 'edit']);
    $r->addRoute('POST', '/usuarios/editar/{id:\d+}', ['App\Controllers\UsuarioController', 'update']);
    $r->addRoute('POST', '/usuarios/eliminar/{id:\d+}', ['App\Controllers\UsuarioController', 'delete']);
    $r->addRoute('POST', '/usuarios/asignar-jefe', ['App\Controllers\UsuarioController', 'assignJefeMasivo']);

    // Aquí añadiremos más rutas a medida que migremos...
});

// 5. Despachar la Petición
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Limpiar la URI para el enrutador
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo $twig->render('404.html.twig', ['page_title' => 'Página no encontrada']);
        exit();
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo "Método no permitido. Permitidos: " . implode(', ', $allowedMethods);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        // Inyectar dependencias al controlador
        $controllerClass = $handler[0];
        $method = $handler[1];

        // Creamos una instancia del controlador, pasándole lo que necesite
        $controller = new $controllerClass($conn, $twig);

        // Llamamos al método
        $controller->$method($vars);
        break;
}