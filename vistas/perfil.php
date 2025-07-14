<?php
// vistas/perfil.php
require_once __DIR__ . "/../helpers/permisos.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MediSys</title>
    
    <!-- Bootstrap CSS -->
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css">

        <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    
</head>
<style>
        /* CSS específico para la página de perfil - REDISEÑADO */
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .profile-container {
            min-height: 100vh;
            padding: 30px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e1e8ed;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        .profile-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .profile-header p {
            margin: 8px 0 0;
            opacity: 0.85;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }
        
        .profile-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        .profile-tabs {
            background: white;
            padding: 25px 30px 0;
            margin-bottom: 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .profile-tabs .nav-link {
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            margin: 0 8px;
            font-weight: 500;
            color: #64748b;
            transition: all 0.3s ease;
            background: transparent;
            border: 1px solid transparent;
        }
        
        .profile-tabs .nav-link:hover {
            background: #f1f5f9;
            color: #475569;
            transform: translateY(-1px);
        }
        
        .profile-tabs .nav-link.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .tab-content {
            background: white;
            padding: 30px;
        }
        
        .profile-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e1e8ed;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .section-title {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .section-title i {
            color: #3b82f6;
            margin-right: 8px;
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .btn-primary {
            background: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }
        
        .btn-outline-secondary {
            border: 1px solid #d1d5db;
            color: #6b7280;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #374151;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 60px 20px;
            background: white;
        }
        
        .info-readonly {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Estados mejorados */
        .status-activo {
            background: #dcfce7 !important;
            color: #166534 !important;
            border-color: #bbf7d0 !important;
        }

        .status-pendiente {
            background: #fef3c7 !important;
            color: #92400e !important;
            border-color: #fde68a !important;
        }

        .status-bloqueado {
            background: #fecaca !important;
            color: #991b1b !important;
            border-color: #fca5a5 !important;
        }

        .status-inactivo {
            background: #f3f4f6 !important;
            color: #4b5563 !important;
            border-color: #d1d5db !important;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 4px;
            font-size: 13px;
            color: #ef4444;
        }

        /* Animaciones suaves */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-section {
            animation: slideUp 0.4s ease-out;
        }
        
        .profile-card {
            animation: slideUp 0.6s ease-out;
        }

        /* Responsive mejorado */
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px 0;
            }
            
            .profile-header {
                padding: 30px 20px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 36px;
            }
            
            .profile-tabs {
                padding: 20px 15px 0;
            }
            
            .profile-tabs .nav-link {
                padding: 10px 16px;
                margin: 0 4px;
                font-size: 13px;
            }
            
            .tab-content {
                padding: 20px 15px;
            }
            
            .profile-section {
                padding: 20px 16px;
            }
            
            .section-title {
                font-size: 15px;
            }
        }

        @media (max-width: 576px) {            
            .btn {
                width: 100%;
                margin-bottom: 12px;
            }
            
            .btn:last-child {
                margin-bottom: 0;
            }
        }
        
        /* Mejoras adicionales */
        .profile-card {
            transition: box-shadow 0.3s ease;
        }
        
        .profile-card:hover {
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
        }
        
        /* Scrollbar personalizado */
        .tab-content::-webkit-scrollbar {
            width: 6px;
        }

        .tab-content::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .tab-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .tab-content::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Tarjetas de estadísticas */
.stat-card {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #3b82f6;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
    </style>

<body>
    <!-- Incluir la barra de navegación -->
    <?php include "../navbars/header.php"; ?>
    
    <!-- Incluir el sidebar -->
    <?php include "../navbars/sidebar.php"; ?>


    
    <div class="main-content">
    <div class="profile-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="profile-card">
                        <!-- Loading Spinner -->
                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-3">Cargando información del perfil...</p>
                        </div>
                        
                        <!-- Contenido del Perfil -->
                        <div id="perfilContent" style="display: none;">
                            <!-- Header del Perfil -->
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <h2 id="nombreCompleto"></h2>
                                <p id="rolUsuario"></p>
                                <span class="profile-status" id="estadoUsuario"></span>
                            </div>
                            
                            <!-- Tabs de Navegación -->
                            <ul class="nav nav-pills profile-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#info-personal" type="button">
                                        <i class="bi bi-person-vcard me-2"></i>Información Personal
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#info-cuenta" type="button">
                                        <i class="bi bi-shield-check me-2"></i>Información de Cuenta
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Contenido de los Tabs -->
                            <div class="tab-content px-4 pb-4">
                                <!-- Tab Información Personal -->
                                <div class="tab-pane fade show active" id="info-personal">
                                    <!-- Datos Básicos -->
                                    <div class="profile-section">
                                        <h5 class="section-title">
                                            <i class="bi bi-person-badge me-2"></i>Datos Básicos
                                        </h5>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Cédula</label>
                                                <div class="info-readonly" id="cedulaUsuario"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Usuario</label>
                                                <div class="info-readonly" id="usernameUsuario"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nombres</label>
                                                <div class="info-readonly" id="nombresUsuario"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Apellidos</label>
                                                <div class="info-readonly" id="apellidosUsuario"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Sexo</label>
                                                <div class="info-readonly" id="sexoUsuario"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nacionalidad</label>
                                                <div class="info-readonly" id="nacionalidadUsuario"></div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Correo Electrónico</label>
                                                <div class="info-readonly" id="correoUsuario"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Información Específica -->
                                    <div class="profile-section" id="infoEspecifica" style="display: none;">
                                        <h5 class="section-title">
                                            <i class="bi bi-info-circle me-2"></i>Información Adicional
                                        </h5>
                                        
                                        <div class="row g-3" id="camposEspecificos">
                                            <!-- Se llenará dinámicamente según el tipo de usuario -->
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tab Información de Cuenta -->
                                <div class="tab-pane fade" id="info-cuenta">
                                    <!-- Información de Seguridad -->
                                    <div class="profile-section">
                                        <h5 class="section-title">
                                            <i class="bi bi-shield-check me-2"></i>Información de Cuenta
                                        </h5>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Fecha de Registro</label>
                                                <div class="info-readonly" id="fechaCreacion"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Estado de la Cuenta</label>
                                                <div class="info-readonly" id="estadoCuenta"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Último Acceso</label>
                                                <div class="info-readonly" id="ultimoAcceso">No disponible</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo de Usuario</label>
                                                <div class="info-readonly" id="tipoUsuario"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Estadísticas de Cuenta (opcional) -->
                                    <div class="profile-section" id="estadisticasCuenta" style="display: none;">
                                        <h5 class="section-title">
                                            <i class="bi bi-graph-up me-2"></i>Estadísticas
                                        </h5>
                                        
                                        <div class="row g-3" id="statsContainer">
                                            <!-- Se llenará dinámicamente según el tipo de usuario -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>
     <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
// Variables globales
let datosUsuario = null;

// Cargar perfil al iniciar
$(document).ready(function() {
    cargarPerfil();
});

// Función para cargar el perfil
function cargarPerfil() {
    $('#loadingSpinner').show();
    $('#perfilContent').hide();

    $.ajax({
        url: '../controladores/PerfilController.php',
        method: 'POST',
        data: { action: 'obtener' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                datosUsuario = response.data;
                mostrarDatosPerfil(datosUsuario);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al cargar el perfil'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo cargar la información del perfil'
            });
        },
        complete: function() {
            $('#loadingSpinner').hide();
            $('#perfilContent').show();
        }
    });
}

// Función para mostrar los datos del perfil
function mostrarDatosPerfil(usuario) {
    // Header
    $('#nombreCompleto').text(`${usuario.nombres} ${usuario.apellidos}`);
    $('#rolUsuario').text(usuario.nombre_rol);
    $('#estadoUsuario').text(usuario.nombre_estado);

    // Datos básicos
    $('#cedulaUsuario').text(usuario.cedula);
    $('#usernameUsuario').text(usuario.username);
    $('#nombresUsuario').text(usuario.nombres);
    $('#apellidosUsuario').text(usuario.apellidos);
    $('#sexoUsuario').text(obtenerTextoSexo(usuario.sexo));
    $('#nacionalidadUsuario').text(usuario.nacionalidad);
    $('#correoUsuario').text(usuario.correo);

    // Información de cuenta
    $('#fechaCreacion').text(formatearFecha(usuario.fecha_creacion));
    $('#estadoCuenta').text(usuario.nombre_estado);
    $('#tipoUsuario').text(capitalizarPrimeraLetra(usuario.tipo_usuario));

    // Información específica según el tipo de usuario
    mostrarInformacionEspecifica(usuario);
    mostrarEstadisticas(usuario);
}

// Función para mostrar información específica
function mostrarInformacionEspecifica(usuario) {
    const $infoEspecifica = $('#infoEspecifica');
    const $camposEspecificos = $('#camposEspecificos');
    
    $camposEspecificos.empty();

    if (usuario.tipo_usuario === 'paciente') {
        $infoEspecifica.show();
        $camposEspecificos.html(`
            <div class="col-md-6">
                <label class="form-label">Fecha de Nacimiento</label>
                <div class="info-readonly">${formatearFechaSolo(usuario.fecha_nacimiento) || 'No registrada'}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de Sangre</label>
                <div class="info-readonly">${usuario.tipo_sangre || 'No registrado'}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <div class="info-readonly">${usuario.telefono || 'No registrado'}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Edad</label>
                <div class="info-readonly">${calcularEdad(usuario.fecha_nacimiento) || 'No disponible'}</div>
            </div>
        `);
    } else if (usuario.tipo_usuario === 'doctor') {
        $infoEspecifica.show();
        $camposEspecificos.html(`
            <div class="col-md-6">
                <label class="form-label">Título Profesional</label>
                <div class="info-readonly">${usuario.titulo_profesional || 'No registrado'}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Especialidad</label>
                <div class="info-readonly">${usuario.nombre_especialidad || 'No registrada'}</div>
            </div>
        `);
    } else {
        $infoEspecifica.hide();
    }
}

// Función para mostrar estadísticas (opcional)
function mostrarEstadisticas(usuario) {
    const $estadisticas = $('#estadisticasCuenta');
    const $statsContainer = $('#statsContainer');
    
    // Solo mostrar estadísticas para doctores y pacientes
    if (usuario.tipo_usuario === 'doctor' || usuario.tipo_usuario === 'paciente') {
        $estadisticas.show();
        
        if (usuario.tipo_usuario === 'doctor') {
            $statsContainer.html(`
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Citas Atendidas</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Pacientes</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Consultas</div>
                    </div>
                </div>
            `);
        } else if (usuario.tipo_usuario === 'paciente') {
            $statsContainer.html(`
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Citas Realizadas</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Consultas</div>
                    </div>
                </div>
            `);
        }
    } else {
        $estadisticas.hide();
    }
}

// Funciones auxiliares
function obtenerTextoSexo(sexo) {
    const textos = {
        'M': 'Masculino',
        'F': 'Femenino',
        'O': 'Otro'
    };
    return textos[sexo] || 'No especificado';
}

function capitalizarPrimeraLetra(string) {
    if (!string) return 'No especificado';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function calcularEdad(fechaNacimiento) {
    if (!fechaNacimiento) return null;
    
    const hoy = new Date();
    const fecha = new Date(fechaNacimiento);
    let edad = hoy.getFullYear() - fecha.getFullYear();
    const mes = hoy.getMonth() - fecha.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
        edad--;
    }
    
    return edad + ' años';
}

function formatearFecha(fechaString) {
    if (!fechaString) return 'No disponible';
    
    const fecha = new Date(fechaString);
    const opciones = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return fecha.toLocaleDateString('es-ES', opciones);
}

function formatearFechaSolo(fechaString) {
    if (!fechaString) return null;
    
    const fecha = new Date(fechaString);
    const opciones = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    };
    
    return fecha.toLocaleDateString('es-ES', opciones);
}
</script>
</body>
</html>