/**
 * Sistema de Nacionalidades Dinámico para Doctores con REST Countries v3.1
 * Carga TODAS las nacionalidades automáticamente sin "quemar" ninguna
 */

let nacionalidadesCacheDoctores = null;
let select2InicializadoDoctores = false;

// ===== CARGAR TODAS LAS NACIONALIDADES DESDE REST COUNTRIES v3.1 =====
async function cargarTodasLasNacionalidades() {
    if (nacionalidadesCacheDoctores) {
        return nacionalidadesCacheDoctores;
    }
    
    try {
        console.log('🌍 Cargando TODAS las nacionalidades desde REST Countries v3.1...');
        
        // Primero intentar v3.1 (más reciente)
        let response = await fetch("https://restcountries.com/v3.1/all?fields=name,cca2,flag,demonyms");
        
        if (!response.ok) {
            console.log('⚠️ v3.1 falló, intentando con v2...');
            // Fallback a v2 como tu código original
            response = await fetch("https://restcountries.com/v2/all?fields=name,alpha2Code,flag,demonym");
        }
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const paises = await response.json();
        
        // Detectar si es v3.1 o v2 y procesar accordingly
        if (paises[0]?.cca2) {
            // Es v3.1
            nacionalidadesCacheDoctores = procesarPaisesV3(paises);
        } else {
            // Es v2 (como tu código original)
            nacionalidadesCacheDoctores = procesarPaisesV2(paises);
        }
        
        console.log(`✅ ${nacionalidadesCacheDoctores.length} nacionalidades cargadas dinámicamente`);
        return nacionalidadesCacheDoctores;
        
    } catch (error) {
        console.error('❌ Error cargando nacionalidades:', error);
        
        // Fallback mínimo solo si todo falla
        nacionalidadesCacheDoctores = [
            { codigo: 'EC', nombre: 'Ecuadorian', pais: 'Ecuador', bandera: 'https://flagcdn.com/w20/ec.png' },
            { codigo: 'US', nombre: 'American', pais: 'United States', bandera: 'https://flagcdn.com/w20/us.png' },
            { codigo: 'ES', nombre: 'Spanish', pais: 'Spain', bandera: 'https://flagcdn.com/w20/es.png' }
        ];
        
        console.log('📝 Usando fallback mínimo de nacionalidades');
        return nacionalidadesCacheDoctores;
    }
}

// ===== PROCESAR PAÍSES DE API v3.1 =====
function procesarPaisesV3(paises) {
    return paises
        .filter(pais => {
            return pais.demonyms?.eng?.m && 
                   pais.name?.common && 
                   pais.cca2 &&
                   pais.flag;
        })
        .map(pais => ({
            codigo: pais.cca2.toUpperCase(),
            nombre: pais.demonyms.eng.m,
            pais: pais.name.common,
            bandera: `https://flagcdn.com/w20/${pais.cca2.toLowerCase()}.png`,
            banderaEmoji: pais.flag
        }))
        .sort((a, b) => a.nombre.localeCompare(b.nombre));
}

// ===== PROCESAR PAÍSES DE API v2 (como tu código original) =====
function procesarPaisesV2(paises) {
    return paises
        .filter(pais => pais.demonym && pais.name && pais.alpha2Code && pais.flag)
        .map(pais => ({
            codigo: pais.alpha2Code.toUpperCase(),
            nombre: pais.demonym,
            pais: pais.name,
            bandera: pais.flag,
            banderaEmoji: '🌍' // v2 no tiene emoji
        }))
        .sort((a, b) => a.nombre.localeCompare(b.nombre));
}

// ===== LLENAR SELECT CON SELECT2 Y BÚSQUEDA =====
// ===== LLENAR SELECT CON SELECT2 Y BÚSQUEDA (VERSIÓN CORREGIDA) =====
async function inicializarSelectNacionalidadesDoctores(selectores = ['#nacionalidad', '#editarNacionalidad']) {
    try {
        const nacionalidades = await cargarTodasLasNacionalidades();
        
        selectores.forEach(selector => {
            const $select = $(selector);
            
            if (!$select.length) return;
            
            // Limpiar opciones existentes excepto la primera
            $select.find('option:not(:first)').remove();
            
            // Agregar TODAS las nacionalidades dinámicamente
            nacionalidades.forEach(nacionalidad => {
                const option = new Option(
                    `${nacionalidad.nombre} (${nacionalidad.pais})`,
                    nacionalidad.nombre,
                    false,
                    false
                );
                option.setAttribute('data-codigo', nacionalidad.codigo);
                option.setAttribute('data-bandera', nacionalidad.bandera);
                option.setAttribute('data-pais', nacionalidad.pais);
                $select.append(option);
            });
            
            // 🔥 DESTRUIR Select2 anterior si existe
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            
            // 🔥 CONFIGURACIÓN ESPECÍFICA PARA MODALES
            const select2Config = {
                placeholder: 'Buscar nacionalidad...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron nacionalidades";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                templateResult: formatearOpcionNacionalidad,
                templateSelection: formatearSeleccionNacionalidad,
                escapeMarkup: function(markup) { return markup; },
                // 🔥 CONFIGURACIÓN CRÍTICA PARA MODALES
                dropdownParent: $select.closest('.modal').length ? $select.closest('.modal') : $('body'),
                dropdownAutoWidth: true
            };
            
            // Inicializar Select2
            $select.select2(select2Config);
            
            console.log(`✅ Select2 inicializado correctamente para: ${selector}`);
        });
        
        select2InicializadoDoctores = true;
        
    } catch (error) {
        console.error('❌ Error inicializando select de nacionalidades:', error);
    }
}
// ===== FORMATEAR OPCIONES EN EL DROPDOWN =====
function formatearOpcionNacionalidad(nacionalidad) {
    if (!nacionalidad.id) return nacionalidad.text;
    
    const $option = $(nacionalidad.element);
    const bandera = $option.data('bandera');
    const pais = $option.data('pais');
    const codigo = $option.data('codigo');
    
    if (!bandera) return nacionalidad.text;
    
    return $(`
        <div class="d-flex align-items-center">
            <img src="${bandera}" 
                 alt="${codigo}" 
                 style="width: 20px; height: 15px; margin-right: 8px; border-radius: 2px;"
                 onerror="this.style.display='none'">
            <span>${nacionalidad.text}</span>
        </div>
    `);
}

// ===== FORMATEAR SELECCIÓN ACTUAL =====
function formatearSeleccionNacionalidad(nacionalidad) {
    if (!nacionalidad.id) return nacionalidad.text;
    
    const $option = $(nacionalidad.element);
    const bandera = $option.data('bandera');
    const codigo = $option.data('codigo');
    
    if (!bandera) return nacionalidad.text;
    
    return $(`
        <div class="d-flex align-items-center">
            <img src="${bandera}" 
                 alt="${codigo}" 
                 style="width: 18px; height: 13px; margin-right: 6px; border-radius: 1px;"
                 onerror="this.style.display='none'">
            <span>${nacionalidad.text}</span>
        </div>
    `);
}

// ===== BUSCAR DATOS POR CÉDULA (como tu lógica original) =====
async function buscarPorCedulaDoctor() {
    const cedula = $('#cedula').val().trim();
    
    if (!cedula) {
        Swal.fire('Error', 'Por favor, ingresa una cédula', 'error');
        return;
    }
    
    if (cedula.length < 10) {
        Swal.fire('Error', 'La cédula debe tener al menos 10 dígitos', 'error');
        return;
    }
    
    // Mostrar loading en el botón
    const btnBuscar = $('#btnBuscarCedulaDoctor');
    const textoOriginal = btnBuscar.html();
    btnBuscar.html('<i class="bi bi-arrow-clockwise spin"></i>').prop('disabled', true);
    
    try {
        // Usar tu mismo endpoint
        const response = await fetch(`../../controladores/obtenerDatos.php?cedula=${cedula}`);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const json = await response.json();
        
        // Restaurar botón
        btnBuscar.html(textoOriginal).prop('disabled', false);
        
        if (config.debug) {
            console.log('Respuesta de búsqueda por cédula (doctor):', json);
        }
        
        if (json.estado !== 'OK' || !json.resultado?.length) {
            Swal.fire('Error', 'No se encontraron datos para la cédula ingresada.', 'error');
            return;
        }
        
        const datos = json.resultado[0];
        const palabras = datos.nombre.split(' ');
        
        // 🔥 LLENAR Y BLOQUEAR CAMPOS (como tu lógica)
        $('#apellidos').val(palabras.slice(0, 2).join(' ')).prop('readonly', true);
        $('#nombres').val(palabras.slice(2).join(' ')).prop('readonly', true);
        $('#cedula').prop('readonly', true);
        
        // 🔥 NACIONALIDAD: Si es ciudadano ecuatoriano
        if (datos.condicionCiudadano.toUpperCase() === 'CIUDADANO') {
            // Seleccionar "Ecuadorian" en el Select2
            $('#nacionalidad').val('Ecuadorian').trigger('change');
            $('#nacionalidad').prop('disabled', true);
            
            if (config.debug) {
                console.log('Nacionalidad seleccionada automáticamente: Ecuadorian');
            }
        }
        
        // 🔥 ESTILOS VISUALES
        $('#cedula, #nombres, #apellidos').addClass('bg-light text-muted');
        
        // 🔥 GENERAR USERNAME AUTOMÁTICO
        generarUsernameAutomaticoDoctor();
        
        // 🔥 MENSAJE DE ÉXITO
        Swal.fire({
            icon: 'success',
            title: 'Datos encontrados',
            text: 'Los datos han sido completados automáticamente desde el registro civil.',
            timer: 2500,
            showConfirmButton: false
        });
        
        // 🔥 AGREGAR BOTÓN DE RESETEO
        if (!$('#btnResetearDatosDoctor').length) {
            $('#btnBuscarCedulaDoctor').after(`
                <button type="button" class="btn btn-outline-warning btn-sm ms-1" id="btnResetearDatosDoctor" title="Limpiar datos">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            `);
            
            $('#btnResetearDatosDoctor').on('click', resetearCamposDoctores);
        }
        
    } catch (error) {
        btnBuscar.html(textoOriginal).prop('disabled', false);
        console.error('Error buscando cédula (doctor):', error);
        Swal.fire('Error', 'No se pudieron obtener los datos. Intente nuevamente.', 'error');
    }
}

// ===== GENERAR USERNAME AUTOMÁTICO =====
function generarUsernameAutomaticoDoctor() {
    const nombres = $('#nombres').val().trim();
    const apellidos = $('#apellidos').val().trim();
    
    if (nombres && apellidos) {
        const primerNombre = nombres.split(' ')[0].toLowerCase().replace(/[^a-z]/g, '');
        const primerApellido = apellidos.split(' ')[0].toLowerCase().replace(/[^a-z]/g, '');
        const username = `dr.${primerNombre}.${primerApellido}`;
        
        $('#username').val(username);
        
        // Verificar disponibilidad del username
        verificarUsernameDisponibleDoctor(username);
    }
}

// ===== VERIFICAR USERNAME DISPONIBLE =====
async function verificarUsernameDisponibleDoctor(username) {
    try {
        const response = await fetch(`../../controladores/DoctoresControlador/DoctoresController.php?action=verificarUsername&username=${username}&submenu_id=${config.submenuId}`);
        const result = await response.json();
        
        const usernameField = $('#username');
        const feedback = $('#usernameFeedback');
        
        if (result.disponible) {
            usernameField.removeClass('is-invalid').addClass('is-valid');
            feedback.removeClass('invalid-feedback').addClass('valid-feedback text-success').html('<i class="bi bi-check-circle"></i> Username disponible');
        } else {
            usernameField.removeClass('is-valid').addClass('is-invalid');
            feedback.removeClass('valid-feedback').addClass('invalid-feedback text-danger').html('<i class="bi bi-x-circle"></i> Username no disponible');
            
            // Sugerir alternativa
            const sugerencia = `${username}${Math.floor(Math.random() * 100)}`;
            setTimeout(() => {
                $('#username').val(sugerencia);
                verificarUsernameDisponibleDoctor(sugerencia);
            }, 1500);
        }
        
    } catch (error) {
        console.error('Error verificando username:', error);
    }
}

// ===== RESETEAR CAMPOS =====
function resetearCamposDoctores() {
    Swal.fire({
        title: '¿Limpiar datos?',
        text: 'Se borrarán todos los datos completados automáticamente',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Limpiar campos
            $('#cedula, #nombres, #apellidos, #username').val('').prop('readonly', false);
            
            // Resetear Select2 de nacionalidad
            $('#nacionalidad').val('').trigger('change').prop('disabled', false);
            
            // Remover estilos
            $('#cedula, #nombres, #apellidos, #username').removeClass('bg-light text-muted is-valid is-invalid');
            
            // Remover feedback
            $('#usernameFeedback').html('');
            
            // Remover botón de reseteo
            $('#btnResetearDatosDoctor').remove();
            
            Swal.fire('Limpiado', 'Los campos han sido limpiados', 'success');
        }
    });
}

// ===== CARGAR BANDERAS EN TABLA (como tu función original) =====
function cargarBanderasEnTablaDoctores() {
    cargarTodasLasNacionalidades().then(nacionalidades => {
        document.querySelectorAll('.nacionalidad-banderita-doctor').forEach(span => {
            try {
                const nacionalidadTexto = span.dataset.nacionalidad.toLowerCase();
                const nacionalidad = nacionalidades.find(n => 
                    n.nombre.toLowerCase() === nacionalidadTexto
                );

                if (nacionalidad) {
                    span.innerHTML = `
                        <img src="${nacionalidad.bandera}" 
                             alt="${nacionalidad.pais}" 
                             style="width: 20px; height: 15px; margin-right: 5px; border-radius: 2px;" 
                             onerror="this.style.display='none'"> 
                        ${nacionalidad.nombre}
                    `;
                } else {
                    span.innerHTML += ' <span title="No se encontró bandera">🌐</span>';
                }
            } catch (error) {
                console.error('Error procesando bandera en tabla:', error, span);
            }
        });
    }).catch(err => {
        console.error('Error cargando banderas para tabla:', err);
    });
}

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    console.log('🌍 Inicializando sistema de nacionalidades para doctores...');
    
    // Inicializar Select2 de nacionalidades
    inicializarSelectNacionalidadesDoctores();
    
    // Cargar banderas en tablas existentes
    setTimeout(() => {
        cargarBanderasEnTablaDoctores();
    }, 1000);
    
    // Eventos
    $(document).on('click', '#btnBuscarCedulaDoctor', buscarPorCedulaDoctor);
    
    // Event listeners para generar username automático
    $(document).on('input', '#nombres, #apellidos', function() {
        if (!$(this).prop('readonly')) {
            generarUsernameAutomaticoDoctor();
        }
    });
    
    // Event listener para validar username manual
    $(document).on('input', '#username', function() {
        const username = $(this).val().trim();
        if (username.length >= 3) {
            verificarUsernameDisponibleDoctor(username);
        }
    });
    
    console.log('✅ Sistema de nacionalidades para doctores inicializado');
});