<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Tuupola\Middleware\CorsMiddleware;
use App\Controllers\AuthController;
use App\Controllers\HistorialController;
use App\Controllers\CitasController;
use App\Controllers\DoctoresApiController;
use App\Utils\ResponseUtil;

require __DIR__ . '/../../vendor/autoload.php';

// Configuración de BD
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

// Autoloader para nuestras clases
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

$app = AppFactory::create();

// CONFIGURAR BASE PATH PARA SUBDIRECTORIOS
$app->setBasePath('/MenuDinamico/api');

// Middlewares
$app->addBodyParsingMiddleware();
$app->add(new CorsMiddleware([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    "headers.allow" => ["Content-Type", "Authorization", "X-Requested-With"],
    "credentials" => true,
]));
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// OPTIONS para CORS
$app->options('/{routes:.*}', function (Request $request, Response $response) {
    return $response;
});

// ===== RUTAS USANDO LOS CONTROLADORES QUE CREAMOS =====

// Ruta de prueba
$app->get('/test', function (Request $request, Response $response) {
    $data = [
        'success' => true,
        'message' => 'API MenuDinamico funcionando ✅',
        'timestamp' => date('Y-m-d H:i:s'),
        'estructura_funcionando' => [
            'controllers' => file_exists(__DIR__ . '/../src/Controllers/AuthController.php') ? '✅' : '❌',
            'validators' => file_exists(__DIR__ . '/../src/Validators/CedulaValidator.php') ? '✅' : '❌',
            'utils' => file_exists(__DIR__ . '/../src/Utils/ResponseUtil.php') ? '✅' : '❌'
        ]
    ];
    
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// PUNTOS 1-2: Autenticación (usando AuthController)
$app->post('/auth/login', [AuthController::class, 'login']);
$app->post('/auth/change-password', [AuthController::class, 'changePassword']);

// Agregar esta nueva ruta
$app->post('/auth/change-password-logged', [AuthController::class, 'changePasswordLoggedUser']);
// Enviar clave temporal por email
$app->post('/auth/enviar-clave-temporal', [AuthController::class, 'enviarClaveTemporalEmail']);

// PUNTOS 3-4: Historial clínico (usando HistorialController)
$app->get('/historial/paciente/{cedula}', [HistorialController::class, 'getHistorialByCedula']);


// PUNTOS 5,7,8: Citas (usando CitasController)
$app->get('/citas/especialidad/{id_especialidad}', [CitasController::class, 'getCitasByEspecialidad']);
$app->get('/citas/medico/{id_medico}', [CitasController::class, 'getCitasByMedico']);
$app->get('/citas/especialidad/{id_especialidad}/medico/{id_medico}', [CitasController::class, 'getCitasByEspecialidadYMedico']);
$app->post('/citas/rango-fechas', [CitasController::class, 'getCitasByRangoFechas']);
// NUEVO ENDPOINT: Consultar por especialidad (ID) y médico (cédula)
$app->post('/citas/especialidad-medico-cedula', [CitasController::class, 'getCitasByEspecialidadYMedicoCedula']);
// NUEVO ENDPOINT: Rango de fechas + cédula médico
$app->post('/citas/rango-fechas-medico-cedula', [CitasController::class, 'getCitasByRangoFechasYMedicoCedula']);

    // Búsqueda de pacientes
$app->get('/pacientes/buscar/{cedula}', HistorialController::class . ':buscarPacientePorCedula');

$app->get('/pacientes/buscar2/{cedula}', [CitasController::class, 'buscarPacienteRapido']);

// Historial clínico
$app->get('/historial/{cedula}', HistorialController::class . ':getHistorialByCedula');
$app->get('/historial/{cedula}/filtros', HistorialController::class . ':getHistorialCompleto');

// Detalle de citas
$app->get('/citas/{id_cita}/detalle', HistorialController::class . ':getDetalleCita');

// Datos para filtros
$app->get('/especialidades', HistorialController::class . ':getEspecialidades');
$app->get('/doctores/especialidad/{id_especialidad}', HistorialController::class . ':getDoctoresByEspecialidad');
$app->get('/sucursales', HistorialController::class . ':getSucursales');

// Consulta general de citas (nuevo endpoint)
$app->get('/citas/consulta-general', HistorialController::class . ':getConsultaGeneralCitas');
// Mis citas para médicos
$app->get('/citas/mis-citas', HistorialController::class . ':getMisCitasMedico');


$app->get('/doctores/estadisticas', [DoctoresApiController::class, 'obtenerEstadisticas']);


// ===== MÉDICOS - CRUD BÁSICO =====
$app->get('/doctores', [DoctoresApiController::class, 'listarDoctores']);
$app->post('/doctores', [DoctoresApiController::class, 'crearDoctor']);
$app->get('/doctores/{id}', [DoctoresApiController::class, 'obtenerDoctor']);
$app->put('/doctores/{id}', [DoctoresApiController::class, 'actualizarDoctor']);
$app->delete('/doctores/{id}', [DoctoresApiController::class, 'eliminarDoctor']);

// ===== MÉDICOS - GESTIÓN DE ESTADO =====
$app->post('/doctores/{id}/estado', [DoctoresApiController::class, 'cambiarEstadoDoctor']);

// ===== HORARIOS DE MÉDICOS =====
$app->get('/doctores/{id}/horarios', [DoctoresApiController::class, 'obtenerHorarios']);
$app->post('/doctores/{id}/horarios', [DoctoresApiController::class, 'guardarHorarios']);
$app->put('/doctores/{id}/horarios', [DoctoresApiController::class, 'guardarHorarios']);
$app->get('/doctores/{id}/disponibilidad', [DoctoresApiController::class, 'obtenerDisponibilidad']);

// ===== EXCEPCIONES DE MÉDICOS (Vacaciones, días especiales, etc.) =====
$app->get('/doctores/{id}/excepciones', [DoctoresApiController::class, 'obtenerExcepciones']);
$app->post('/doctores/{id}/excepciones', [DoctoresApiController::class, 'guardarExcepcion']);
$app->delete('/excepciones/{id}', [DoctoresApiController::class, 'eliminarExcepcion']);

// ===== RUTAS ALTERNATIVAS CON QUERY PARAMETERS (Para compatibilidad) =====
$app->get('/doctores-api', [DoctoresApiController::class, 'manejarSolicitud']);
$app->post('/doctores-api', [DoctoresApiController::class, 'manejarSolicitud']);
$app->put('/doctores-api', [DoctoresApiController::class, 'manejarSolicitud']);
$app->delete('/doctores-api', [DoctoresApiController::class, 'manejarSolicitud']);
$app->get('/doctores/cedula/{cedula}', [DoctoresApiController::class, 'buscarPorCedula']);
// ===== HORARIOS DE MÉDICOS =====




// Crear paciente
$app->post('/pacientes/crear', [CitasController::class, 'crearPaciente']);

// Tipos de cita
$app->get('/tipos-cita', [CitasController::class, 'getTiposCita']);

// Especialidades por sucursal
$app->get('/especialidades/sucursal/{id_sucursal}', [CitasController::class, 'getEspecialidadesPorSucursal']);

// Doctores por especialidad y sucursal
$app->get('/doctores/especialidad/{id_especialidad}/sucursal/{id_sucursal}', [CitasController::class, 'getDoctoresPorEspecialidadYSucursal']);

// Horarios disponibles
$app->get('/horarios/disponibles', [CitasController::class, 'getHorariosDisponibles']);

// Crear cita
$app->post('/citas/crear', [CitasController::class, 'crearCita']);

// En api/public/index.php agregar estas líneas:
$app->post('/doctores/horarios', [CitasController::class, 'guardarHorarios2']);
$app->delete('/horarios', [CitasController::class, 'eliminarHorario']);
$app->put('/horarios', [CitasController::class, 'editarHorario']);

// ✅ AGREGAR ESTA LÍNEA DESPUÉS DE LOS ENDPOINTS EXISTENTES
$app->post('/pacientes/crear2', [CitasController::class, 'crearPaciente2']);

$app->run();
?>