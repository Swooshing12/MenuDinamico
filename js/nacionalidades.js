/**
 * Sistema de Nacionalidades Dinámico con API REST Countries
 * Carga automáticamente todas las nacionalidades con sus banderas
 */

let nacionalidadesCache = null;

// Cargar nacionalidades desde la API
async function cargarNacionalidadesDesdeAPI() {
    if (nacionalidadesCache) {
        return nacionalidadesCache;
    }
    
    try {
        console.log('🌍 Cargando nacionalidades desde API...');
        
        const response = await fetch("https://restcountries.com/v2/all?fields=name,alpha2Code,flag,demonym");
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const paises = await response.json();
        
        // Procesar y formatear nacionalidades
        nacionalidadesCache = paises
            .filter(pais => pais.demonym && pais.demonym.trim() !== '') // Solo países con gentilicio
            .map(pais => ({
                codigo: pais.alpha2Code,
                nombre: pais.demonym,
                pais: pais.name,
                bandera: pais.flag || '🌍'
            }))
            .sort((a, b) => a.nombre.localeCompare(b.nombre)); // Ordenar alfabéticamente
        
        console.log(`✅ ${nacionalidadesCache.length} nacionalidades cargadas`);
        return nacionalidadesCache;
        
    } catch (error) {
        console.error('❌ Error cargando nacionalidades:', error);
        
        // Fallback con algunas nacionalidades básicas
        nacionalidadesCache = [
            { codigo: 'EC', nombre: 'Ecuatoriana', pais: 'Ecuador', bandera: '🇪🇨' },
            { codigo: 'AR', nombre: 'Argentina', pais: 'Argentina', bandera: '🇦🇷' },
            { codigo: 'CO', nombre: 'Colombiana', pais: 'Colombia', bandera: '🇨🇴' },
            { codigo: 'PE', nombre: 'Peruana', pais: 'Perú', bandera: '🇵🇪' },
            { codigo: 'US', nombre: 'Estadounidense', pais: 'United States', bandera: '🇺🇸' },
            { codigo: 'ES', nombre: 'Española', pais: 'España', bandera: '🇪🇸' },
            { codigo: 'VE', nombre: 'Venezolana', pais: 'Venezuela', bandera: '🇻🇪' },
            { codigo: 'BR', nombre: 'Brasileña', pais: 'Brasil', bandera: '🇧🇷' },
            { codigo: 'MX', nombre: 'Mexicana', pais: 'México', bandera: '🇲🇽' },
            { codigo: 'CL', nombre: 'Chilena', pais: 'Chile', bandera: '🇨🇱' }
        ];
        
        console.log('📝 Usando nacionalidades básicas de fallback');
        return nacionalidadesCache;
    }
}

// Llenar select de nacionalidades
async function llenarSelectNacionalidades(selector = '#nacionalidad, #editarNacionalidad') {
    try {
        const nacionalidades = await cargarNacionalidadesDesdeAPI();
        const selects = $(selector);
        
        selects.each(function() {
            const select = $(this);
            const valorActual = select.val(); // Preservar valor actual
            
            // Limpiar y agregar opción por defecto
            select.empty().append('<option value="">🌍 Seleccionar nacionalidad</option>');
            
            // Agregar todas las nacionalidades
            nacionalidades.forEach(nac => {
                const option = $(`<option value="${nac.nombre}" data-codigo="${nac.codigo}" data-pais="${nac.pais}" data-bandera="${nac.bandera}">
                    ${nac.bandera} ${nac.nombre}
                </option>`);
                
                select.append(option);
            });
            
            // Restaurar valor si existía
            if (valorActual) {
                select.val(valorActual);
            }
        });
        
        console.log('✅ Selects de nacionalidad llenados correctamente');
        
    } catch (error) {
        console.error('❌ Error llenando selects de nacionalidad:', error);
    }
}

// Buscar nacionalidad por nombre
function buscarNacionalidad(nombre) {
    if (!nacionalidadesCache) return null;
    
    return nacionalidadesCache.find(nac => 
        nac.nombre.toLowerCase().includes(nombre.toLowerCase()) ||
        nac.pais.toLowerCase().includes(nombre.toLowerCase())
    );
}

// Auto-completar nacionalidad ecuatoriana
function establecerNacionalidadEcuatoriana(selector = '#nacionalidad') {
    $(selector).val('Ecuatoriana').trigger('change');
    console.log('🇪🇨 Nacionalidad establecida como Ecuatoriana');
}