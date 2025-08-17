<?php
namespace App\Validators;

use DateTime;
use Exception;

class DateValidator
{
    public static function validate($fecha): array
    {
        $errors = [];
        
        // Verificar que no esté vacía
        if (empty($fecha)) {
            $errors[] = 'La fecha es requerida';
            return $errors;
        }
        
        // Intentar crear DateTime para validar formato
        try {
            $fechaObj = new DateTime($fecha);
            
            // Verificar que la fecha no sea en el futuro lejano (más de 100 años)
            $fechaMaxima = new DateTime('+100 years');
            if ($fechaObj > $fechaMaxima) {
                $errors[] = 'La fecha no puede ser mayor a 100 años en el futuro';
            }
            
            // Verificar que la fecha no sea muy antigua (más de 150 años atrás)
            $fechaMinima = new DateTime('-150 years');
            if ($fechaObj < $fechaMinima) {
                $errors[] = 'La fecha no puede ser mayor a 150 años en el pasado';
            }
            
        } catch (Exception $e) {
            $errors[] = 'El formato de fecha no es válido. Use formatos como: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, DD/MM/YYYY';
        }
        
        return $errors;
    }
    
    public static function isValid($fecha): bool
    {
        return empty(self::validate($fecha));
    }
    
    // Validar rango de fechas
    public static function validateRange($fechaInicio, $fechaFin): array
    {
        $errors = [];
        
        // Validar fechas individuales
        $errorsInicio = self::validate($fechaInicio);
        $errorsFin = self::validate($fechaFin);
        
        if (!empty($errorsInicio)) {
            $errors['fecha_inicio'] = $errorsInicio;
        }
        
        if (!empty($errorsFin)) {
            $errors['fecha_fin'] = $errorsFin;
        }
        
        // Si hay errores individuales, no continuar
        if (!empty($errors)) {
            return $errors;
        }
        
        try {
            $inicio = new DateTime($fechaInicio);
            $fin = new DateTime($fechaFin);
            
            // Verificar que fecha inicio no sea mayor que fecha fin
            if ($inicio > $fin) {
                $errors['rango'] = ['La fecha de inicio no puede ser mayor que la fecha de fin'];
            }
            
            // Verificar que el rango no sea demasiado grande (por ejemplo, más de 5 años)
            $diferencia = $inicio->diff($fin);
            if ($diferencia->y > 5) {
                $errors['rango'] = ['El rango de fechas no puede ser mayor a 5 años'];
            }
            
        } catch (Exception $e) {
            $errors['rango'] = ['Error al procesar el rango de fechas'];
        }
        
        return $errors;
    }
    
    // Formatear fecha para MySQL
    public static function formatForDatabase($fecha): string
    {
        try {
            $fechaObj = new DateTime($fecha);
            return $fechaObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return '';
        }
    }
    
    // Formatear fecha para mostrar
    public static function formatForDisplay($fecha, $formato = 'd/m/Y H:i'): string
    {
        try {
            $fechaObj = new DateTime($fecha);
            return $fechaObj->format($formato);
        } catch (Exception $e) {
            return $fecha;
        }
    }
}
?>